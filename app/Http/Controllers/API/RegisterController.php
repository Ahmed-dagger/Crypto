<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class RegisterController extends Controller
{

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'google_id' => 'nullable|string|unique:users,google_id', // Google Sign-In
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        // Check if the user already exists
        $user = User::where('email', $validated['email'])->first();

        if ($user) {
            if ($user->email_verified_at) {
                return response()->json(['message' => 'User already verified.'], 200);
            }

            if ($user->otp_expires_at && now()->lt($user->otp_expires_at)) {
                return response()->json(['message' => 'OTP already sent. Please wait or check your email.'], 200);
            }

            // If user exists but is not verified, allow OTP resend
            $otp = rand(100000, 999999);
            $user->update([
                'otp' => $otp,
                'otp_expires_at' => now()->addMinutes(3),
            ]);

            // Resend OTP email
            Mail::send([], [], function ($message) use ($validated, $otp) {
                $message->to($validated['email'])
                    ->from('ahdbo124@gmail.com', 'BitWest Support')
                    ->subject('Verify Your Email - OTP Code')
                    ->html("<p>Your new OTP code is: <strong>$otp</strong></p>");
            });

            return response()->json(['message' => 'New OTP sent.'], 200);
        }

        // Generate a new OTP for first-time users
        $otp = rand(100000, 999999);

        try {
            // Create new user with temporary password
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'google_id' => $validated['google_id'] ?? null,
                'otp' => $otp,
                'otp_expires_at' => now()->addMinutes(3),
                'password' => bcrypt('temporary_password'),
            ]);

            // Send OTP email
            Mail::send([], [], function ($message) use ($validated, $otp) {
                $message->to($validated['email'])
                    ->from('ahdbo124@gmail.com', 'BitWest Support')
                    ->subject('Verify Your Email - OTP Code')
                    ->html("<p>Your OTP code is: <strong>$otp</strong></p>");
            });

            return response()->json(['message' => 'OTP sent to email.', 'email' => $validated['email']], 200);
        } catch (\Exception $e) {
            Log::error('Error sending OTP: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to send OTP. Try again later.',
                'message' => config('app.debug') ? $e->getMessage() : 'Something went wrong.',
            ], 500);
        }
    }


    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|digits:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || $user->otp !== $request->otp) {
            return response()->json(['error' => 'Invalid OTP.'], 400);
        }

        // Mark email as verified
        $user->update([
            'email_verified_at' => Carbon::now(),
            'otp' => null // Clear OTP after verification
        ]);

        return response()->json(['message' => 'Email verified. Set your password now.'], 200);
    }

    public function setPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user->email_verified_at) {
            return response()->json(['error' => 'Email not verified.'], 400);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json(['message' => 'Password set successfully. You can now log in.'], 200);
    }

    
}

<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;

class ResetPasswordController extends Controller
{
    public function sendResetOtp(Request $request)
    {
        // Validate request
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        // Get the user
        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 422);
        }

        // Check if OTP was sent recently (within 5 minutes)
        if ($user->otp_expires_at && now()->lt($user->otp_expires_at)) {
            return response()->json(['message' => 'OTP already sent. Please wait 5 minutes before requesting a new one.'], 200);
        }

        // Generate new OTP
        $otp = rand(100000, 999999);

        // Update user with new OTP and expiration time
        $user->update([
            'otp' => $otp,
            'otp_expires_at' => now()->addMinutes(5),
        ]);

        // Send OTP email
        try {
            Mail::send([], [], function ($message) use ($validated, $otp) {
                $message->to($validated['email'])
                    ->from('support@bitwest.online', 'BitWest Support')
                    ->subject('Reset Password - OTP Code')
                    ->html("<p>Your OTP code to reset your password is: <strong>$otp</strong></p><p>This OTP will expire in 5 minutes.</p>");
            });

            return response()->json(['message' => 'OTP sent successfully. Check your email.'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to send OTP email. Please try again later.'], 500);
        }
    }

    
    public function verifyResetPasswordOtp(Request $request)
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
            'otp' => null // Clear OTP after verification
        ]);

        return response()->json(['message' => 'You can now reset your password now.'], 200);
    }


    public function reset(Request $request)
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

        return response()->json(['message' => 'Password reset successfully. You can now log in.'], 200);
    }
}

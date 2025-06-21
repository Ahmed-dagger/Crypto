<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Laravel\Socialite\Facades\Socialite;
use Google_Client;
use Illuminate\Support\Str;


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
                'otp_expires_at' => now()->addMinutes(5),
            ]);

            // Resend OTP email
            Mail::send([], [], function ($message) use ($validated, $otp) {
                $message->to($validated['email'])
                    ->from('support@bitwest.online', 'BitWest Support')
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
                'otp_expires_at' => now()->addMinutes(5),
                'password' => bcrypt('temporary_password'),
            ]);

            // Send OTP email
            Mail::send([], [], function ($message) use ($validated, $otp) {
                $message->to($validated['email'])
                    ->from('support@bitwest.online', 'BitWest Support')
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

        $userWallet = Wallet::create([
            'user_id' => $user->id,
            'currency' => 'USDT',
            'balance' => 0
        ]);

        $userWallet->save();

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




    public function googleTokenLogin(Request $request)
    {
        $request->validate([
            'id_token' => 'required|string',
        ]);

        $idToken = $request->input('id_token');

        // Verify token using Google Client
        $client = new \Google_Client(['client_id' => env('GOOGLE_CLIENT_ID')]);
        $payload = $client->verifyIdToken($idToken);

        if (!$payload) {
            return response()->json(['error' => 'Invalid ID token'], 401);
        }

        // Extract user info from token
        $googleId = $payload['sub'];
        $email = $payload['email'] ?? null;
        $name = $payload['name'] ?? 'Unknown';

        if (!$email) {
            return response()->json(['error' => 'Email not provided in token'], 422);
        }

        // Look for existing user
        $user = User::where('google_id', $googleId)->orWhere('email', $email)->first();

        if (!$user) {
            // First-time Google registration
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'google_id' => $googleId,
                'email_verified_at' => now(),
                'password' => bcrypt(Str::random(16)), // Random password
            ]);

            // Optionally create default wallet
            Wallet::create([
                'user_id' => $user->id,
                'currency' => 'USDT',
                'balance' => 0,
            ]);
        } else {
            // If user exists but doesn't have a google_id, assign it
            $updated = false;

            if (!$user->google_id) {
                $user->google_id = $googleId;
                $updated = true;
            }

            if (!$user->email_verified_at) {
                $user->email_verified_at = now();
                $updated = true;
            }

            if ($updated) {
                $user->save();
            }
        }

        // Create personal access token (e.g., Sanctum)
        $token = $user->createToken('google-login')->plainTextToken;

        return response()->json([
            'message' => 'Logged in with Google successfully.',
            'token' => $token,
            'user' => $user,
        ]);
    }
}

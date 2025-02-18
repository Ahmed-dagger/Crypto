<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Lang;

class ResetPasswordController extends Controller
{
    public function sendResetLinkEmail(Request $request)
    {
        // Validate the incoming request.
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        // Send the password reset link.
        $response = Password::sendResetLink(
            $request->only('email')
        );

        // Handle the response based on status
        if ($response == Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => 'We have emailed your password reset link!',
            ], 200);
        } else {
            return response()->json([
                'error' => 'Unable to send password reset link. Please check the email address and try again.',
            ], 500);
        }
    }


    public function reset(Request $request)
    {
        // Validate the incoming request.
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'password' => 'required|min:6|confirmed', // Confirm the password field
            'token' => 'required|string', // Reset token from the reset email link
        ]);

        // Reset the password
        $response = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                // Update the password
                $user->password = Hash::make($password);
                $user->save();
            }
        );

        // Handle the response
        if ($response == Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Password has been successfully reset.'], 200);
        } else {
            return response()->json(['error' => 'Failed to reset password. Please ensure the token is valid.'], 400);
        }
    }


}

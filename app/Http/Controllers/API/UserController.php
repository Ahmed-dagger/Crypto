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

class UserController extends Controller
{
    public function index()
    {
        return User::all();
    }

    public function show(User $user)
    {
        return response()->json($user); 

    }

    public function auth (Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            // Authentication successful
            $user = Auth::user();

            // Generate a personal access token
            $token = $user->createToken('auth_token')->plainTextToken;


            return response()->json([
                'message' => 'Authentication successful',
                'token' => $token,
                'user' => $user, // Optional: Include user details
            ], 200);
        }

        // Authentication failed
        return response()->json([
            'message' => 'Invalid credentials'
        ], 401);
    }



}
<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function index()
    {
        return User::all();
    }

    public function show(User $user)
    {
         $target = $user->findOrFail($user);
        
         return $target;

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

    public function store(Request $request)
    {
     
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $validated['password'] = bcrypt($validated['password']);

        if (User::where('email', $validated['email'])->exists()) {
            return response()->json(['error' => 'User already exists.'], 409);
        }

    try {
        $user = User::create($validated);
        return response()->json(['user' => $user], 201); // Return 201 Created
    } catch (\Exception $e) {
        // Log the error and return a 500 response
        Log::error('Error creating user: '.$e->getMessage());
        return response()->json([
    'error' => 'An error occurred while creating the user.',
    'message' => config('app.debug') ? $e->getMessage() : 'Please try again later.',
        ], 500);
    }

    }
}

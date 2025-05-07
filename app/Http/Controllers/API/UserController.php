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

    public function profile(User $user)
{
    // Get the first media item in the profile_pictures collection
    $profilePicture = $user->getFirstMedia('profile_pictures');

    return response()->json([
        'id' => $user->id,
        'email' => $user->email,
        'name' => $user->name,
        'created_at' => $user->created_at,
        'updated_at' => $user->updated_at,
        'profile_picture' => $profilePicture ? [
            'url' => $profilePicture->getUrl(),
            'file_name' => $profilePicture->file_name,
            'size' => $profilePicture->size,
            'mime_type' => $profilePicture->mime_type,
        ] : null, // Include detailed media information
    ]);
}


    public function auth(Request $request)
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

    public function update(Request $request, User $user)
    {
        try {
            // Log request data for debugging
            Log::info('Request Data:', [
                'name' => $request->input('name'),
                'photo' => $request->hasFile('photo') ? 'File Present' : 'No File',
            ]);
    
            // Validate request data
            $validatedData = $request->validate([
                'name' => 'sometimes|string|max:255',
                'photo' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048', // 2MB max
            ]);
    
            // Update name if provided
            if ($request->has('name')) {
                $user->name = $validatedData['name'];
            }
    
            // Handle photo upload if provided
            if ($request->hasFile('photo')) {
                // Remove old photo if exists
                $user->clearMediaCollection('profile_pictures');
    
                // Add new photo
                $user->addMediaFromRequest('photo')->toMediaCollection('profile_pictures');
            }
    
            // Save changes
            $user->save();
    
            // Reload user media
            $user->load('media');
    
            // Get the photo URL (fallback to null if no photo exists)
            $photoUrl = $user->getFirstMediaUrl('profile_pictures') ?: null;
    
            return response()->json([
                'message' => 'User updated successfully.',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'photo' => $photoUrl,
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('User Update Error:', ['error' => $e->getMessage()]);
    
            return response()->json([
                'message' => 'An error occurred while updating the user.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
}

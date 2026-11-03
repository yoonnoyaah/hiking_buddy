<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // Register a new member
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'city' => 'nullable|string|max:100',
            'fitness_level' => 'nullable|string|max:50',
            'preferred_pace' => 'nullable|string|max:50',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'city' => $validated['city'] ?? null,
            'fitness_level' => $validated['fitness_level'] ?? 'Intermediate',
            'preferred_pace' => $validated['preferred_pace'] ?? 'Steady',
        ]);

        // Set the default account permissions
        $user->role = 'member';
        $user->status = 'active';
        $user->save();

        $token = $user->createToken('hiking-buddy-token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful.',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    // Log in an existing member
    public function login(Request $request)
{
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required|string',
    ]);

    $user = User::where('email', $credentials['email'])->first();

    if (!$user || !Hash::check($credentials['password'], $user->password)) {
        throw ValidationException::withMessages([
            'email' => ['The email or password is incorrect.'],
        ]);
    }

    if ($user->status !== 'active') {
        return response()->json([
            'message' => 'This account is currently unavailable. Please contact the administrator.',
        ], 403);
    }

    $token = $user->createToken('hiking-buddy-token')->plainTextToken;

    return response()->json([
        'message' => 'Login successful.',
        'user' => $user,
        'token' => $token,
    ]);
}

    // Get the currently logged-in user's profile
    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }

    // Update the logged-in user's profile
    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'city' => 'nullable|string|max:100',
            'fitness_level' => 'required|string|max:50',
            'preferred_pace' => 'required|string|max:50',
        ]);

        $user = $request->user();

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => $user->fresh(),
        ]);
    }

    // Log out the current user
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout successful.',
        ]);
    }
}
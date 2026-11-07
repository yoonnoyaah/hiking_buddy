<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MountainController;
use App\Http\Controllers\Api\GroupTripController;
use App\Http\Controllers\Api\CommunityPostController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use App\Http\Controllers\Api\NotificationController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Forgot Password
Route::post('/forgot-password', function (Request $request) {

    $request->validate([
        'email' => 'required|email',
    ]);

    $status = Password::sendResetLink(
        $request->only('email')
    );

    if ($status === Password::RESET_LINK_SENT) {
        return response()->json([
            'message' => 'Password reset link has been sent to your email.'
        ]);
    }

    return response()->json([
        'message' => 'Unable to send password reset link. Please check your email.'
    ], 422);
});


// Reset Password
Route::post('/reset-password', function (Request $request) {

    $request->validate([
        'token' => 'required',
        'email' => 'required|email',
        'password' => 'required|min:8|confirmed',
    ]);

    $status = Password::reset(
        $request->only(
            'email',
            'password',
            'password_confirmation',
            'token'
        ),
        function ($user, $password) {

            $user->forceFill([
                'password' => $password,
                'remember_token' => Str::random(60),
            ])->save();
        }
    );

    if ($status === Password::PASSWORD_RESET) {
        return response()->json([
            'message' => 'Your password has been reset successfully.'
        ]);
    }

    return response()->json([
        'message' => 'Invalid or expired password reset link.'
    ], 422);
});

Route::get('/mountains', [MountainController::class, 'index']);
Route::get('/mountains/{id}', [MountainController::class, 'show']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'me']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::post('/logout', [AuthController::class, 'logout']);
    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
});

// Public group trip routes
Route::get('/group-trips', [GroupTripController::class, 'index']);
Route::get('/group-trips/{id}', [GroupTripController::class, 'show']);

// Protected group trip routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/group-trips', [GroupTripController::class, 'store']);
    Route::post('/group-trips/{id}/join', [GroupTripController::class, 'join']);
    Route::post('/group-trips/{id}/leave', [GroupTripController::class, 'leave']);
    Route::delete('/group-trips/{id}', [GroupTripController::class, 'destroy']);
});

// Public community posts
Route::get('/community-posts', [CommunityPostController::class, 'index']);

// Protected community actions
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/community-posts', [CommunityPostController::class, 'store']);
    Route::put('/community-posts/{id}', [CommunityPostController::class, 'update']);
    Route::delete('/community-posts/{id}', [CommunityPostController::class, 'destroy']);

    Route::post('/community-posts/{id}/like', [CommunityPostController::class, 'toggleLike']);
    Route::post('/community-posts/{id}/comments', [CommunityPostController::class, 'addComment']);
});
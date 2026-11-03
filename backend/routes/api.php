<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MountainController;
use App\Http\Controllers\Api\GroupTripController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/mountains', [MountainController::class, 'index']);
Route::get('/mountains/{id}', [MountainController::class, 'show']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'me']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::post('/logout', [AuthController::class, 'logout']);
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
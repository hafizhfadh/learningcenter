<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Auth endpoints
Route::post('/login', [\App\Http\Controllers\API\AuthController::class, 'login']);
Route::post('/refresh', [\App\Http\Controllers\API\AuthController::class, 'refresh']);
Route::post('/logout', [\App\Http\Controllers\API\AuthController::class, 'logout'])->middleware('auth:sanctum');

// Full API routes for students role
Route::middleware('auth:sanctum')->group(function () {
    // Learning Path endpoints
    Route::get('/learning-paths', [\App\Http\Controllers\API\LearningPathController::class, 'index']);
    Route::get('/learning-paths/{id}', [\App\Http\Controllers\API\LearningPathController::class, 'show']);
    Route::post('/learning-paths/{id}/enroll', [\App\Http\Controllers\API\LearningPathController::class, 'enroll']);
    Route::get('/learning-paths/progress/my', [\App\Http\Controllers\API\LearningPathController::class, 'progress']);
});


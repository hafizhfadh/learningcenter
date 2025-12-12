<?php

use Illuminate\Support\Facades\Route;

Route::post('/login', [\App\Http\Controllers\API\AuthController::class, 'login']);
Route::post('/refresh', [\App\Http\Controllers\API\AuthController::class, 'refresh']);
Route::post('/logout', [\App\Http\Controllers\API\AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [\App\Http\Controllers\API\AuthController::class, 'profile']);
    Route::get('/institution', [\App\Http\Controllers\API\AuthController::class, 'institution']);

    Route::get('/learning-paths', [\App\Http\Controllers\API\LearningPathController::class, 'index']);
    Route::get('/learning-paths/{id}', [\App\Http\Controllers\API\LearningPathController::class, 'show']);
    Route::post('/learning-paths/{id}/enroll', [\App\Http\Controllers\API\LearningPathController::class, 'enroll']);
    Route::get('/learning-paths/progress/my', [\App\Http\Controllers\API\LearningPathController::class, 'progress']);

    Route::get('/courses', [\App\Http\Controllers\API\CourseController::class, 'index']);
    Route::get('/courses/search', [\App\Http\Controllers\API\CourseController::class, 'search']);
    Route::get('/courses/{courseId}', [\App\Http\Controllers\API\CourseController::class, 'show']);
});


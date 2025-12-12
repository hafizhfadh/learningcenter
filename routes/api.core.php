<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;

Route::post('/login', [\App\Http\Controllers\API\AuthController::class, 'login'])
    ->middleware([
        EncryptCookies::class,
        AddQueuedCookiesToResponse::class,
        StartSession::class,
        VerifyCsrfToken::class,
        'app.token',
        'throttle:10,1',
    ]);

Route::middleware(['auth:sanctum', 'app.token'])->group(function () {
    Route::post('/refresh', [\App\Http\Controllers\API\AuthController::class, 'refresh']);
    Route::post('/logout', [\App\Http\Controllers\API\AuthController::class, 'logout']);

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

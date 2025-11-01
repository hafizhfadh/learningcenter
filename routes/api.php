<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Full API routes for students role
Route::middleware('auth:sanctum')->group(function () {
    // Courses endpoints
    Route::apiResource('courses', \App\Http\Controllers\Api\CourseController::class);
    // Enrollments endpoints
    Route::apiResource('enrollments', \App\Http\Controllers\Api\EnrollmentController::class);
    // Teachers endpoints
    Route::apiResource('teachers', \App\Http\Controllers\Api\TeacherController::class);
    // Profiles endpoints
    Route::apiResource('profiles', \App\Http\Controllers\Api\ProfileController::class);

});


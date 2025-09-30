<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()
    ]);
});


Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', [App\Http\Controllers\User\LoginController::class, 'login'])->name('login');
Route::post('/login', [App\Http\Controllers\User\LoginController::class, 'authenticate'])->name('login.post');
Route::post('/logout', [App\Http\Controllers\User\LoginController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    // Route Group Under /user including route naming prefix 'user.'
    // All routes in this group will have 'user.' prefix in their names
    Route::prefix('/user')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\User\DashboardController::class, 'index'])->name('user.dashboard');
        Route::get('/learningPath', [App\Http\Controllers\User\LearningPathController::class, 'index'])->name('user.learning-path.index');

        Route::get('/{learningPath}/course', [App\Http\Controllers\User\CourseController::class, 'index'])->name('user.course.index');

        // Post route to initiate course and track progress
        Route::post('/{learningPath}/{courseSlug}/initiate', [App\Http\Controllers\User\LessonController::class, 'initiateCourse'])->name('user.course.initiate');

        // List lessons in a course
        Route::get('/{learningPath}/{courseSlug}/lesson', [App\Http\Controllers\User\LessonController::class, 'index'])->name('user.lesson.index');

        // Show a specific lesson using slug for lesson binding
        Route::get('/{learningPath}/{courseSlug}/lesson/{lesson:slug}', [App\Http\Controllers\User\LessonController::class, 'show'])->name('user.lesson.show');

        // Post route to mark lesson as completed and go to next
        Route::post('/{learningPath}/{courseSlug}/lesson/{lesson:slug}/next', [App\Http\Controllers\User\LessonController::class, 'nextLesson'])->name('user.lesson.next'); 
    });
});

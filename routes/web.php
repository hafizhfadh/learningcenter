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
    Route::get('/dashboard', [App\Http\Controllers\User\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/category', [App\Http\Controllers\User\CategoryController::class, 'index'])->name('category.index');

    Route::get('/{exam}/course', [App\Http\Controllers\User\CourseController::class, 'index'])->name('course.index');

    // List lessons in a course
    Route::get('/{exam}/{courseSlug}/lesson', [App\Http\Controllers\User\LessonController::class, 'index'])->name('lesson.index');

    // Show a specific lesson using slug for lesson binding
    Route::get('/{exam}/{courseSlug}/lesson/{lesson:slug}', [App\Http\Controllers\User\LessonController::class, 'show'])->name('lesson.show');

    // Post route to mark lesson as completed and go to next
    Route::post('/{exam}/{courseSlug}/lesson/{lesson:slug}/next', [App\Http\Controllers\User\LessonController::class, 'nextLesson'])->name('lesson.next');
});
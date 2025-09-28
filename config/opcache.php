<?php

/**
 * OPcache Preload Configuration for Production
 * 
 * This file preloads frequently used classes and files into OPcache
 * for improved performance in production environments.
 */

// Ensure we're in production environment
if (php_sapi_name() !== 'cli' && app()->environment('production')) {
    
    // Preload Laravel framework core files
    $laravelFiles = [
        base_path('vendor/laravel/framework/src/Illuminate/Foundation/Application.php'),
        base_path('vendor/laravel/framework/src/Illuminate/Container/Container.php'),
        base_path('vendor/laravel/framework/src/Illuminate/Support/ServiceProvider.php'),
        base_path('vendor/laravel/framework/src/Illuminate/Http/Request.php'),
        base_path('vendor/laravel/framework/src/Illuminate/Http/Response.php'),
        base_path('vendor/laravel/framework/src/Illuminate/Routing/Router.php'),
        base_path('vendor/laravel/framework/src/Illuminate/Database/Eloquent/Model.php'),
        base_path('vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php'),
        base_path('vendor/laravel/framework/src/Illuminate/Database/Eloquent/Builder.php'),
    ];

    // Preload application models
    $modelFiles = [
        app_path('Models/User.php'),
        app_path('Models/Course.php'),
        app_path('Models/Lesson.php'),
        app_path('Models/LearningPath.php'),
        app_path('Models/Institution.php'),
        app_path('Models/Enrollment.php'),
        app_path('Models/Task.php'),
        app_path('Models/TaskQuestion.php'),
        app_path('Models/TaskSubmission.php'),
        app_path('Models/ProgressLog.php'),
        app_path('Models/LessonSection.php'),
        app_path('Models/LearningPathCourse.php'),
    ];

    // Preload service providers
    $providerFiles = [
        app_path('Providers/AppServiceProvider.php'),
        app_path('Providers/Filament/AdminPanelProvider.php'),
    ];

    // Preload configuration files
    $configFiles = [
        config_path('app.php'),
        config_path('database.php'),
        config_path('cache.php'),
        config_path('session.php'),
        config_path('queue.php'),
        config_path('octane.php'),
    ];

    // Preload Filament resources (if they exist)
    $filamentFiles = glob(app_path('Filament/Resources/*.php'));
    $filamentWidgets = glob(app_path('Filament/Widgets/*.php'));

    // Combine all files to preload
    $filesToPreload = array_merge(
        $laravelFiles,
        $modelFiles,
        $providerFiles,
        $configFiles,
        $filamentFiles ?: [],
        $filamentWidgets ?: []
    );

    // Preload each file
    foreach ($filesToPreload as $file) {
        if (file_exists($file)) {
            try {
                opcache_compile_file($file);
            } catch (Throwable $e) {
                // Log error but don't fail the application
                error_log("OPcache preload failed for {$file}: " . $e->getMessage());
            }
        }
    }

    // Preload Composer autoloader
    if (file_exists(base_path('vendor/autoload.php'))) {
        opcache_compile_file(base_path('vendor/autoload.php'));
    }
}
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Horizon\SupervisorCommandString;
use Laravel\Horizon\WorkerCommandString;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('local')) {
            if (class_exists(\Laravel\Boost\BoostServiceProvider::class)) {
                $this->app->register(\Laravel\Boost\BoostServiceProvider::class);
            }
        }

        SupervisorCommandString::$command = 'exec /usr/local/bin/frankenphp php-cli artisan horizon:supervisor';
        WorkerCommandString::$command = 'exec /usr/local/bin/frankenphp php-cli artisan horizon:work';
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

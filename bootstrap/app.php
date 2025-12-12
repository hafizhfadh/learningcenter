<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO,
        );

        $middleware->alias([
            'force.https' => \App\Http\Middleware\ForceHttps::class,
            'configure.api.url' => \App\Http\Middleware\ConfigureApiUrl::class,
        ]);

        $middleware->appendToGroup('web', \App\Http\Middleware\ForceHttps::class);
        $middleware->appendToGroup('api', \App\Http\Middleware\ForceHttps::class);
        $middleware->appendToGroup('api', \App\Http\Middleware\ConfigureApiUrl::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

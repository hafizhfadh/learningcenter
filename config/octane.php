<?php

use Laravel\Octane\Contracts\OperationTerminated;
use Laravel\Octane\Events\RequestHandled;
use Laravel\Octane\Events\RequestReceived;
use Laravel\Octane\Events\RequestTerminated;
use Laravel\Octane\Events\TaskReceived;
use Laravel\Octane\Events\TaskTerminated;
use Laravel\Octane\Events\TickReceived;
use Laravel\Octane\Events\TickTerminated;
use Laravel\Octane\Events\WorkerErrorOccurred;
use Laravel\Octane\Events\WorkerStarting;
use Laravel\Octane\Events\WorkerStopping;
use Laravel\Octane\Listeners\CloseMonologHandlers;
use Laravel\Octane\Listeners\CollectGarbage;
use Laravel\Octane\Listeners\DisconnectFromDatabases;
use Laravel\Octane\Listeners\EnsureUploadedFilesAreValid;
use Laravel\Octane\Listeners\EnsureUploadedFilesCanBeMoved;
use Laravel\Octane\Listeners\FlushOnce;
use Laravel\Octane\Listeners\FlushTemporaryContainerInstances;
use Laravel\Octane\Listeners\FlushUploadedFiles;
use Laravel\Octane\Listeners\ReportException;
use Laravel\Octane\Listeners\StopWorkerIfNecessary;
use Laravel\Octane\Octane;

return [

    /*
    |--------------------------------------------------------------------------
    | Octane Server
    |--------------------------------------------------------------------------
    |
    | This value determines the default "server" that will be used by Octane
    | when starting, restarting, or stopping your server via the CLI. You
    | are free to change this to the supported server of your choosing.
    |
    | Supported: "roadrunner", "swoole", "frankenphp"
    |
    */

    'server' => env('OCTANE_SERVER', 'frankenphp'),

    /*
    |--------------------------------------------------------------------------
    | Octane Worker Configuration
    |--------------------------------------------------------------------------
    |
    | These values control how many workers Octane boots as well as how many
    | requests each worker should process before it is recycled. The defaults
    | map to the production Docker compose environment but remain overridable
    | via the standard Octane environment variables.
    |
    */

    'workers' => env('OCTANE_WORKERS', 'auto'),

    'task_workers' => env('OCTANE_TASK_WORKERS', 'auto'),

    'max_requests' => env('OCTANE_MAX_REQUESTS', 500),

    'max_execution_time' => env('OCTANE_MAX_EXECUTION_TIME', 60),

    'host' => env('OCTANE_HOST', value(function () {
        $listen = env('OCTANE_LISTEN');

        if ($listen) {
            $parsed = parse_url($listen);

            if ($parsed !== false && isset($parsed['host'])) {
                return $parsed['host'];
            }

            return explode(':', $listen)[0] ?? '0.0.0.0';
        }

        return '0.0.0.0';
    })),

    'port' => env('OCTANE_PORT', value(function () {
        $listen = env('OCTANE_LISTEN');

        if ($listen) {
            $parsed = parse_url($listen);

            if ($parsed !== false && isset($parsed['port'])) {
                return $parsed['port'];
            }

            $segments = explode(':', $listen);

            return (int) ($segments[1] ?? 9000);
        }

        return 9000;
    })),

    /*
    |--------------------------------------------------------------------------
    | Force HTTPS
    |--------------------------------------------------------------------------
    |
    | When this configuration value is set to "true", Octane will inform the
    | framework that all absolute links must be generated using the HTTPS
    | protocol. Otherwise your links may be generated using plain HTTP.
    |
    */

    'https' => env('OCTANE_HTTPS', true),

    /*
    |--------------------------------------------------------------------------
    | Worker Configuration
    |--------------------------------------------------------------------------
    |
    | These options configure how many workers Octane should start for handling
    | requests and background tasks. They are exposed via environment variables
    | so that containerized deployments (such as the production Docker Compose
    | stack) and local development can share consistent defaults.
    |
    */

    'workers' => env('OCTANE_WORKERS', 'auto'),

    'task_workers' => env('OCTANE_TASK_WORKERS', 'auto'),

    'max_requests' => env('OCTANE_MAX_REQUESTS', 250),

    /*
    |--------------------------------------------------------------------------
    | FrankenPHP Defaults
    |--------------------------------------------------------------------------
    |
    | Centralized configuration for FrankenPHP allows both Artisan commands and
    | container entrypoints to reference a single source of truth for critical
    | options. Each value can be overridden via environment variables while
    | retaining sensible defaults for the production Docker deployment.
    |
    */

    'frankenphp' => [
        'workers' => env('OCTANE_FRANKENPHP_WORKERS', env('OCTANE_WORKERS', 'auto')),
        'admin_port' => env('OCTANE_FRANKENPHP_ADMIN_PORT', 2019),
        'https' => env('OCTANE_FRANKENPHP_HTTPS', env('OCTANE_HTTPS', true)),
        'http_redirect' => env('OCTANE_FRANKENPHP_HTTP_REDIRECT', true),
        'caddyfile' => env('OCTANE_FRANKENPHP_CADDYFILE', env('FRANKENPHP_CONFIG_PATH', '/etc/frankenphp/Caddyfile')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Octane Listeners
    |--------------------------------------------------------------------------
    |
    | All of the event listeners for Octane's events are defined below. These
    | listeners are responsible for resetting your application's state for
    | the next request. You may even add your own listeners to the list.
    |
    */

    'listeners' => [
        WorkerStarting::class => [
            EnsureUploadedFilesAreValid::class,
            EnsureUploadedFilesCanBeMoved::class,
        ],

        RequestReceived::class => [
            ...Octane::prepareApplicationForNextOperation(),
            ...Octane::prepareApplicationForNextRequest(),
            //
        ],

        RequestHandled::class => [
            //
        ],

        RequestTerminated::class => [
            // FlushUploadedFiles::class,
        ],

        TaskReceived::class => [
            ...Octane::prepareApplicationForNextOperation(),
            //
        ],

        TaskTerminated::class => [
            //
        ],

        TickReceived::class => [
            ...Octane::prepareApplicationForNextOperation(),
            //
        ],

        TickTerminated::class => [
            //
        ],

        OperationTerminated::class => [
            FlushOnce::class,
            FlushTemporaryContainerInstances::class,
            // DisconnectFromDatabases::class,
            // CollectGarbage::class,
        ],

        WorkerErrorOccurred::class => [
            ReportException::class,
            StopWorkerIfNecessary::class,
        ],

        WorkerStopping::class => [
            CloseMonologHandlers::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Warm / Flush Bindings
    |--------------------------------------------------------------------------
    |
    | The bindings listed below will either be pre-warmed when a worker boots
    | or they will be flushed before every new request. Flushing a binding
    | will force the container to resolve that binding again when asked.
    |
    */

    'warm' => [
        ...Octane::defaultServicesToWarm(),
    ],

    'flush' => [
        //
    ],

    /*
    |--------------------------------------------------------------------------
    | Octane Swoole Tables
    |--------------------------------------------------------------------------
    |
    | While using Swoole, you may define additional tables as required by the
    | application. These tables can be used to store data that needs to be
    | quickly accessed by other workers on the particular Swoole server.
    |
    */

    'tables' => [
        'example:1000' => [
            'name' => 'string:1000',
            'votes' => 'int',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Octane Swoole Cache Table
    |--------------------------------------------------------------------------
    |
    | While using Swoole, you may leverage the Octane cache, which is powered
    | by a Swoole table. You may set the maximum number of rows as well as
    | the number of bytes per row using the configuration options below.
    |
    */

    'cache' => [
        'rows' => 1000,
        'bytes' => 10000,
    ],

    /*
    |--------------------------------------------------------------------------
    | File Watching
    |--------------------------------------------------------------------------
    |
    | The following list of files and directories will be watched when using
    | the --watch option offered by Octane. If any of the directories and
    | files are changed, Octane will automatically reload your workers.
    |
    */

    'watch' => [
        'app',
        'bootstrap',
        'config/**/*.php',
        'database/**/*.php',
        'public/**/*.php',
        'resources/**/*.php',
        'routes',
        'composer.lock',
        '.env',
    ],

    /*
    |--------------------------------------------------------------------------
    | Garbage Collection Threshold
    |--------------------------------------------------------------------------
    |
    | When executing long-lived PHP scripts such as Octane, memory can build
    | up before being cleared by PHP. You can force Octane to run garbage
    | collection if your application consumes this amount of megabytes.
    |
    */

    'garbage' => 32, // Optimized for 4GB RAM - reduced from 50MB

    /*
    |--------------------------------------------------------------------------
    | FrankenPHP Server Defaults
    |--------------------------------------------------------------------------
    |
    | When serving the application with FrankenPHP we surface the most common
    | tuning levers through environment variables so that both local Artisan
    | commands and the container entrypoints behave consistently.
    |
    */

    'frankenphp' => [
        'config' => env('OCTANE_FRANKENPHP_CONFIG', env('FRANKENPHP_CONFIG', 'worker ./public/index.php')),
        'worker' => env('OCTANE_FRANKENPHP_WORKER', 'public/index.php'),
        'https' => env('OCTANE_HTTPS', false),
        'http_redirect' => env('OCTANE_FRANKENPHP_HTTP_REDIRECT', false),
        'caddyfile' => env('OCTANE_FRANKENPHP_CADDYFILE'),
        'admin_server' => env('OCTANE_FRANKENPHP_ADMIN_SERVER'),
        'admin_port' => env('OCTANE_FRANKENPHP_ADMIN_PORT'),
        'log_level' => env('OCTANE_FRANKENPHP_LOG_LEVEL'),
    ],

];

<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

$apiHost = config('app.api_host');

if ($apiHost) {
    Route::middleware('api')
        ->domain($apiHost)
        ->group(__DIR__.'/api.core.php');
}

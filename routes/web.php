<?php
 
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;
use Illuminate\Support\Facades\Route;
 
Route::get('/', function () {
    return view('welcome');
});

Route::get('/documentation-api', function () {
    return view('scramble::docs', [
        'spec' => url('api.json'),
        'config' => Scramble::getGeneratorConfig('default'),
    ]);
})
->middleware(Scramble::getGeneratorConfig('default')->get('middleware', [RestrictedDocsAccess::class]));

$apiHost = config('app.api_host');

if ($apiHost) {
    Route::middleware('api')
        ->domain($apiHost)
        ->group(__DIR__.'/api.core.php');
}

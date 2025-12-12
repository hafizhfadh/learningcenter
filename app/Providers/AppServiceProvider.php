<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;
use App\Models\PersonalAccessToken;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Dedoc\Scramble\Support\Generator\SecurityRequirement;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        Gate::define('viewApiDocs', function (User $user) {
            return in_array($user->email, ['admin@learningcenter.com']);
        });

        Scramble::configure()
            ->routes(function (Route $route) {
                return Str::startsWith($route->uri, 'api/');
            })
            ->withDocumentTransformers(function (OpenApi $openApi) {
                $openApi->components->securitySchemes['app_token'] = SecurityScheme::apiKey('header', 'APP_TOKEN');
                $openApi->components->securitySchemes['bearer'] = SecurityScheme::http('bearer');
                $openApi->security = [
                    new SecurityRequirement([
                        'app_token' => [],
                        'bearer' => [],
                    ]),
                ];
            });
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class ConfigureApiUrl
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiHost = config('app.api_host');

        if ($apiHost && $request->getHost() === $apiHost) {
            $baseUrl = config('app.api_url') ?: $request->getSchemeAndHttpHost();

            URL::forceRootUrl($baseUrl);

            if (config('app.force_https')) {
                URL::forceScheme('https');
            }
        }

        return $next($request);
    }
}


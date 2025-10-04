<?php

namespace App\Services;

use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CaddyService
{
    private string $caddyApiUrl;
    private string $origin;
    private int $timeout;

    public function __construct()
    {
        $this->caddyApiUrl = config('services.caddy.api_url', 'http://localhost:2019');
        $this->origin      = config('services.caddy.origin', 'myapp-admin-secret');
        $this->timeout     = config('services.caddy.timeout', 30);
    }

    /**
     * Add a new domain to Caddy configuration
     */
    public function addDomain(string $domain): bool
    {
        try {
            $config = $this->buildDomainConfig($domain);
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Origin'       => $this->origin, // enforce_origin requirement
                ])
                ->post($this->caddyApiUrl . '/load', $config);

            if ($response->successful()) {
                Log::info("Domain {$domain} added to Caddy successfully");
                return true;
            }

            Log::error("Failed to add domain {$domain} to Caddy", [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return false;
        } catch (Exception $e) {
            Log::error("Exception adding domain {$domain} to Caddy: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Remove a domain from Caddy configuration
     */
    public function removeDomain(string $domain): bool
    {
        try {
            // Get current routes
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Origin' => $this->origin,
                ])
                ->get($this->caddyApiUrl . '/config/apps/http/servers/srv0/routes');

            if (!$response->successful()) {
                Log::error("Failed to get current routes from Caddy", [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return false;
            }

            $routes = $response->json();
            $filteredRoutes = [];

            // Filter out routes that contain the domain
            foreach ($routes as $route) {
                if (
                    isset($route['match'][0]['host']) &&
                    !in_array($domain, $route['match'][0]['host'])
                ) {
                    $filteredRoutes[] = $route;
                }
            }

            // Update the routes
            $updateResponse = Http::timeout($this->timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Origin' => $this->origin,
                ])
                ->put($this->caddyApiUrl . '/config/apps/http/servers/srv0/routes', $filteredRoutes);

            if ($updateResponse->successful()) {
                Log::info("Domain {$domain} removed from Caddy successfully");
                return true;
            }

            Log::error("Failed to remove domain {$domain} from Caddy", [
                'status' => $updateResponse->status(),
                'body' => $updateResponse->body()
            ]);

            return false;
        } catch (Exception $e) {
            Log::error("Exception removing domain {$domain} from Caddy: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get current Caddy configuration
     */
    public function getCurrentConfig(): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get($this->caddyApiUrl . '/config/');

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Failed to get current Caddy configuration', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (Exception $e) {
            Log::error('Exception getting Caddy configuration: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if Caddy API is accessible
     */
    public function isHealthy(): bool
    {
        try {
            $response = Http::timeout(5)
                ->get($this->caddyApiUrl . '/health');

            return $response->successful();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Build domain configuration for Caddy
     */
    private function buildDomainConfig(string $domain): array
    {
        $upstreamHost = config('services.caddy.upstream_host', 'app');
        $upstreamPort = config('services.caddy.upstream_port', '8000');
        
        return [
            'apps' => [
                'http' => [
                    'servers' => [
                        'srv0' => [
                            'listen' => [':80', ':443'],
                            'routes' => [
                                [
                                    'match' => [['host' => [$domain]]],
                                    'handle' => [
                                        [
                                            'handler' => 'subroute',
                                            'routes' => [
                                                // Health check endpoint
                                                [
                                                    'match' => [['path' => ['/health']]],
                                                    'handle' => [
                                                        [
                                                            'handler' => 'static_response',
                                                            'status_code' => 200,
                                                            'body' => 'OK'
                                                        ]
                                                    ]
                                                ],
                                                // Static assets with caching
                                                [
                                                    'match' => [['path' => ['/build/*', '/storage/*', '/favicon.ico', '/robots.txt']]],
                                                    'handle' => [
                                                        [
                                                            'handler' => 'headers',
                                                            'response' => [
                                                                'set' => [
                                                                    'Cache-Control' => ['public, max-age=31536000'],
                                                                    'Expires' => ['{http.time_now.add_duration.31536000s}']
                                                                ]
                                                            ]
                                                        ],
                                                        [
                                                            'handler' => 'reverse_proxy',
                                                            'upstreams' => [
                                                                ['dial' => $upstreamHost . ':' . $upstreamPort]
                                                            ],
                                                            'headers' => [
                                                                'request' => [
                                                                    'set' => [
                                                                        'X-Forwarded-Host' => ['{http.request.host}'],
                                                                        'X-Forwarded-Proto' => ['{http.request.scheme}'],
                                                                        'X-Real-IP' => ['{http.request.remote_host}']
                                                                    ]
                                                                ]
                                                            ]
                                                        ]
                                                    ]
                                                ],
                                                // All other requests to Laravel Octane
                                                [
                                                    'handle' => [
                                                        [
                                                            'handler' => 'reverse_proxy',
                                                            'upstreams' => [
                                                                ['dial' => $upstreamHost . ':' . $upstreamPort]
                                                            ],
                                                            'headers' => [
                                                                'request' => [
                                                                    'set' => [
                                                                        'X-Forwarded-Host' => ['{http.request.host}'],
                                                                        'X-Forwarded-Proto' => ['{http.request.scheme}'],
                                                                        'X-Real-IP' => ['{http.request.remote_host}']
                                                                    ]
                                                                ]
                                                            ],
                                                            'health_checks' => [
                                                                'active' => [
                                                                    'path' => '/health',
                                                                    'interval' => '30s',
                                                                    'timeout' => '5s'
                                                                ]
                                                            ]
                                                        ]
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ],
                                    'terminal' => true
                                ]
                            ]
                        ]
                    ]
                ],
                'tls' => [
                    'automation' => [
                        'policies' => [
                            [
                                'subjects' => [$domain],
                                'issuers' => [
                                    [
                                        'module' => 'acme',
                                        'email' => config('services.caddy.acme_email', 'admin@example.com')
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Remove domain from existing configuration
     */
    private function removeDomainFromConfig(array $config, string $domain): array
    {
        if (!isset($config['apps']['http']['servers']['srv0']['routes'])) {
            return $config;
        }

        $routes = $config['apps']['http']['servers']['srv0']['routes'];
        $filteredRoutes = [];

        foreach ($routes as $route) {
            if (
                isset($route['match'][0]['host']) &&
                !in_array($domain, $route['match'][0]['host'])
            ) {
                $filteredRoutes[] = $route;
            }
        }

        $config['apps']['http']['servers']['srv0']['routes'] = $filteredRoutes;

        return $config;
    }

    /**
     * Validate domain format
     */
    public function isValidDomain(string $domain): bool
    {
        // Check for empty string
        if (empty($domain)) {
            return false;
        }

        // Check for leading or trailing dots
        if (str_starts_with($domain, '.') || str_ends_with($domain, '.')) {
            return false;
        }

        // Check for consecutive dots
        if (str_contains($domain, '..')) {
            return false;
        }

        // Check for URL schemes
        if (str_contains($domain, '://')) {
            return false;
        }

        // Use PHP's built-in domain validation
        return filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false;
    }

    /**
     * Get all configured domains
     */
    public function getConfiguredDomains(): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get($this->caddyApiUrl . '/config/apps/http/servers/srv0/routes');

            if (!$response->successful()) {
                Log::error('Failed to get configured domains from Caddy', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return [];
            }

            $routes = $response->json();
            $domains = [];

            foreach ($routes as $route) {
                if (isset($route['match'][0]['host'])) {
                    $domains = array_merge($domains, $route['match'][0]['host']);
                }
            }

            return array_unique($domains);
        } catch (Exception $e) {
            Log::error('Exception getting configured domains: ' . $e->getMessage());
            return [];
        }
    }
}

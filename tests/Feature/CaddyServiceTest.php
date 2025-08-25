<?php

namespace Tests\Feature;

use App\Services\CaddyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CaddyServiceTest extends TestCase
{
    use RefreshDatabase;

    private CaddyService $caddyService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->caddyService = app(CaddyService::class);
    }

    public function test_can_check_caddy_health(): void
    {
        Http::fake([
            '*/health' => Http::response(['status' => 'ok'], 200),
        ]);

        $isHealthy = $this->caddyService->isHealthy();

        $this->assertTrue($isHealthy);
        Http::assertSent(function (Request $request) {
            return $request->url() === config('services.caddy.api_url') . '/health';
        });
    }

    public function test_health_check_returns_false_on_error(): void
    {
        Http::fake([
            '*/health' => Http::response([], 500),
        ]);

        $isHealthy = $this->caddyService->isHealthy();

        $this->assertFalse($isHealthy);
    }

    public function test_can_add_domain_to_caddy(): void
    {
        Http::fake([
            '*/load' => Http::response(['success' => true], 200),
        ]);

        $result = $this->caddyService->addDomain('example.com');

        $this->assertTrue($result);
        Http::assertSent(function (Request $request) {
            $data = $request->data();
            return $request->url() === config('services.caddy.api_url') . '/load' &&
                   $request->method() === 'POST' &&
                   isset($data['apps']['http']['servers']['srv0']['routes']) &&
                   $data['apps']['http']['servers']['srv0']['routes'][0]['match'][0]['host'][0] === 'example.com';
        });
    }

    public function test_add_domain_returns_false_on_error(): void
    {
        Http::fake([
            '*/load' => Http::response([], 500),
        ]);

        $result = $this->caddyService->addDomain('example.com');

        $this->assertFalse($result);
    }

    public function test_can_remove_domain_from_caddy(): void
    {
        Http::fake([
            '*/config/apps/http/servers/srv0/routes' => Http::response([
                [
                    'match' => [['host' => ['example.com']]],
                    'handle' => []
                ],
                [
                    'match' => [['host' => ['other.com']]],
                    'handle' => []
                ]
            ], 200),
            '*/load' => Http::response(['success' => true], 200),
        ]);

        $result = $this->caddyService->removeDomain('example.com');

        $this->assertTrue($result);
    }

    public function test_remove_domain_returns_false_on_error(): void
    {
        Http::fake([
            '*/config/apps/http/servers/srv0/routes' => Http::response([], 500),
        ]);

        $result = $this->caddyService->removeDomain('example.com');

        $this->assertFalse($result);
    }

    public function test_can_get_configured_domains(): void
    {
        Http::fake([
            '*/config/apps/http/servers/srv0/routes' => Http::response([
                [
                    'match' => [['host' => ['example.com']]],
                    'handle' => []
                ],
                [
                    'match' => [['host' => ['test.com']]],
                    'handle' => []
                ]
            ], 200),
        ]);

        $domains = $this->caddyService->getConfiguredDomains();

        $this->assertCount(2, $domains);
        $this->assertContains('example.com', $domains);
        $this->assertContains('test.com', $domains);
    }

    public function test_get_configured_domains_returns_empty_on_error(): void
    {
        Http::fake([
            '*/config/apps/http/servers/srv0/routes' => Http::response([], 500),
        ]);

        $domains = $this->caddyService->getConfiguredDomains();

        $this->assertEmpty($domains);
    }

    public function test_validates_domain_format(): void
    {
        $this->assertTrue($this->caddyService->isValidDomain('example.com'));
        $this->assertTrue($this->caddyService->isValidDomain('sub.example.com'));
        $this->assertTrue($this->caddyService->isValidDomain('test-site.co.uk'));
        
        $this->assertFalse($this->caddyService->isValidDomain(''));
        $this->assertFalse($this->caddyService->isValidDomain('invalid..domain'));
        $this->assertFalse($this->caddyService->isValidDomain('.example.com'));
        $this->assertFalse($this->caddyService->isValidDomain('example.com.'));
        $this->assertFalse($this->caddyService->isValidDomain('http://example.com'));
    }

    public function test_handles_multiple_hosts_in_route(): void
    {
        Http::fake([
            '*/config/apps/http/servers/srv0/routes' => Http::response([
                [
                    'match' => [['host' => ['example.com', 'www.example.com']]],
                    'handle' => []
                ]
            ], 200),
        ]);

        $domains = $this->caddyService->getConfiguredDomains();

        $this->assertCount(2, $domains);
        $this->assertContains('example.com', $domains);
        $this->assertContains('www.example.com', $domains);
    }

    public function test_handles_routes_without_host_match(): void
    {
        Http::fake([
            '*/config/apps/http/servers/srv0/routes' => Http::response([
                [
                    'match' => [['path' => ['/api/*']]],
                    'handle' => []
                ],
                [
                    'match' => [['host' => ['example.com']]],
                    'handle' => []
                ]
            ], 200),
        ]);

        $domains = $this->caddyService->getConfiguredDomains();

        $this->assertCount(1, $domains);
        $this->assertContains('example.com', $domains);
    }

    public function test_handles_empty_routes_response(): void
    {
        Http::fake([
            '*/config/apps/http/servers/srv0/routes' => Http::response([], 200),
        ]);

        $domains = $this->caddyService->getConfiguredDomains();

        $this->assertEmpty($domains);
    }

    public function test_handles_malformed_routes_response(): void
    {
        Http::fake([
            '*/config/apps/http/servers/srv0/routes' => Http::response([
                [
                    'invalid' => 'structure'
                ]
            ], 200),
        ]);

        $domains = $this->caddyService->getConfiguredDomains();

        $this->assertEmpty($domains);
    }
}
<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_login_allows_valid_app_token_without_bearer_token(): void
    {
        config(['app.client_tokens' => ['test-app-token']]);

        $password = 'password123';

        $user = User::factory()->create([
            'email' => 'login-user@example.com',
            'password' => bcrypt($password),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => $password,
        ], [
            'APP_TOKEN' => 'test-app-token',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'code' => 200,
                'message' => 'Login successful',
            ]);

        $this->assertIsString($response->json('data.token'));
        $this->assertIsString($response->json('data.app_token'));
        $this->assertNotEmpty($response->json('data.app_token'));
    }

    public function test_login_rejects_missing_app_token(): void
    {
        config(['app.client_tokens' => ['test-app-token']]);

        $password = 'password123';

        $user = User::factory()->create([
            'email' => 'login-missing-token@example.com',
            'password' => bcrypt($password),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => $password,
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'code' => 401,
                'message' => 'Unauthorized',
            ]);
    }

    public function test_login_rejects_invalid_app_token(): void
    {
        config(['app.client_tokens' => ['test-app-token']]);

        $password = 'password123';

        $user = User::factory()->create([
            'email' => 'login-invalid-token@example.com',
            'password' => bcrypt($password),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => $password,
        ], [
            'APP_TOKEN' => 'invalid-token',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'code' => 403,
            'message' => 'Forbidden',
        ]);
    }

    public function test_protected_route_requires_app_token(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/profile');

        $response->assertStatus(401)
            ->assertJson([
                'code' => 401,
                'message' => 'Unauthorized',
            ]);
    }

    public function test_protected_route_accepts_valid_enhanced_app_token(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $appToken = $this->makeEnhancedAppToken($user);

        $response = $this->withHeaders([
            'APP_TOKEN' => $appToken,
        ])->getJson('/api/profile');

        $response->assertStatus(200);
    }

    public function test_protected_route_rejects_expired_app_token(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $appToken = $this->makeEnhancedAppToken($user, [
            'exp' => now()->subMinute()->timestamp,
        ]);

        $response = $this->withHeaders([
            'APP_TOKEN' => $appToken,
        ])->getJson('/api/profile');

        $response->assertStatus(403)
            ->assertJson([
                'code' => 403,
                'message' => 'Forbidden',
            ]);
    }

    public function test_protected_route_rejects_token_for_different_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Sanctum::actingAs($user);

        $appToken = $this->makeEnhancedAppToken($otherUser);

        $response = $this->withHeaders([
            'APP_TOKEN' => $appToken,
        ])->getJson('/api/profile');

        $response->assertStatus(401)
            ->assertJson([
                'code' => 401,
                'message' => 'Unauthorized',
            ]);
    }

    private function makeEnhancedAppToken(User $user, array $overrides = []): string
    {
        $claims = array_merge([
            'sub' => $user->id,
            'app' => 'test-app',
            'org_id' => $user->institution_id,
            'org_name' => null,
            'iat' => now()->timestamp,
            'exp' => now()->addMinutes(5)->timestamp,
        ], $overrides);

        $payload = base64_encode(json_encode($claims));
        $signature = base64_encode(
            hash_hmac('sha256', $payload, (string) config('app.key'), true)
        );

        return $payload.'.'.$signature;
    }
}

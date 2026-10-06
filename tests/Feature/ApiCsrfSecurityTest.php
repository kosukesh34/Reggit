<?php

namespace Tests\Feature;

use App\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ApiCsrfSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withMiddleware(ValidateCsrfToken::class);
        // Laravel skips CSRF in tests by default; exercise the deployed behavior.
        $this->app->bind(ValidateCsrfToken::class, fn ($app) => new class($app, $app['encrypter']) extends ValidateCsrfToken
        {
            protected function runningUnitTests()
            {
                return false;
            }
        });
        Route::middleware('web')->post('/api/csrf-security-fixture', fn () => response()->json(['ok' => true]));
        Route::middleware('web')->post('/csrf-admin-fixture', fn () => response()->json(['ok' => true]));
    }

    public function test_cookie_api_rejects_missing_and_wrong_csrf_tokens(): void
    {
        $this->withSession(['_token' => 'fixture-session-token'])
            ->postJson('/api/csrf-security-fixture')->assertStatus(419);
        $this->withSession(['_token' => 'fixture-session-token'])
            ->postJson('/api/csrf-security-fixture', [], ['X-CSRF-TOKEN' => 'wrong-token'])->assertStatus(419);
    }

    public function test_member_withdrawal_rejects_cookie_only_request(): void
    {
        $this->withSession(['_token' => 'fixture-session-token'])
            ->postJson('/api/members/withdraw')->assertStatus(419);
    }

    public function test_stateless_bearer_api_does_not_require_csrf(): void
    {
        $this->postJson('/api/v1/csrf-fixture-project/orders')->assertUnauthorized();
    }

    public function test_cookie_api_accepts_matching_csrf_token(): void
    {
        $this->withSession(['_token' => 'fixture-session-token'])
            ->postJson('/api/csrf-security-fixture', [], ['X-CSRF-TOKEN' => 'fixture-session-token'])->assertOk();
    }

    public function test_admin_csrf_protection_remains_enabled(): void
    {
        $this->postJson('/csrf-admin-fixture')->assertStatus(419);
    }

    public function test_external_webhook_remains_exempt(): void
    {
        Route::middleware('web')->post('/api/payment/webhook', fn () => response()->json(['ok' => true]));
        $this->postJson('/api/payment/webhook')->assertOk();
    }
}

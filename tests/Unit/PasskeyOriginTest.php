<?php

namespace Tests\Unit;

use App\Support\PasskeyOrigin;
use Illuminate\Http\Request;
use Tests\TestCase;

class PasskeyOriginTest extends TestCase
{
    public function test_uses_current_request_host_as_relying_party_id(): void
    {
        config([
            'app.url' => 'https://unadministrable-idiocratically-annalee.ngrok-free.dev',
            'passkeys.relying_party_id' => 'unadministrable-idiocratically-annalee.ngrok-free.dev',
            'passkeys.relying_party_id_force' => null,
            'passkeys.allowed_origins' => ['https://unadministrable-idiocratically-annalee.ngrok-free.dev'],
        ]);

        $request = Request::create('http://localhost:8080/admin/passkey/enroll', 'GET');
        PasskeyOrigin::apply($request);

        $this->assertSame('localhost', config('passkeys.relying_party_id'));
        $this->assertContains('http://localhost:8080', config('passkeys.allowed_origins'));
        $this->assertContains('https://unadministrable-idiocratically-annalee.ngrok-free.dev', config('passkeys.allowed_origins'));
    }

    public function test_forced_relying_party_id_wins_when_set(): void
    {
        config([
            'app.url' => 'http://localhost:8080',
            'passkeys.relying_party_id_force' => 'example.test',
            'passkeys.allowed_origins' => [],
        ]);

        PasskeyOrigin::apply(Request::create('https://shop.example.test/admin', 'GET'));

        $this->assertSame('example.test', config('passkeys.relying_party_id'));
    }
}

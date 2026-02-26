<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateApiToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationApiV1Test extends TestCase
{
    use RefreshDatabase;

    public function test_index_会員登録が必須であること(): void
    {
        $user = User::factory()->create(['line_user_id' => 'line-resv-v1', 'project_id' => 1]);

        $response = $this->withoutMiddleware(AuthenticateApiToken::class)
            ->withHeaders(['X-Line-User-Id' => 'line-resv-v1'])
            ->get('/api/v1/reservations');

        $response->assertStatus(403)
            ->assertJsonPath('message', '会員登録が必要です');
    }
}

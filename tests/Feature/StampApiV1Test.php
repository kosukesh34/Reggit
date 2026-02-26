<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateApiToken;
use App\Models\Member;
use App\Models\StampCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StampApiV1Test extends TestCase
{
    use RefreshDatabase;

    private const LINE_USER_ID = 'line-stamp-v1';

    public function test_index_スタンプカード一覧を返すこと(): void
    {
        $user = User::factory()->create(['line_user_id' => self::LINE_USER_ID, 'project_id' => 1]);
        Member::factory()->create(['user_id' => $user->id, 'project_id' => 1]);
        StampCard::factory()->create(['project_id' => 1]);

        $response = $this->withoutMiddleware(AuthenticateApiToken::class)
            ->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->get('/api/v1/stamps');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['stamp_cards']]);
    }
}

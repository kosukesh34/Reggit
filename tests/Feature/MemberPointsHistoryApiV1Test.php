<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateApiToken;
use App\Models\Member;
use App\Models\PointTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberPointsHistoryApiV1Test extends TestCase
{
    use RefreshDatabase;

    private const LINE_USER_ID = 'line-member-history-v1';

    public function test_pointHistory_ポイント履歴が返ること(): void
    {
        $user = User::factory()->create(['line_user_id' => self::LINE_USER_ID, 'project_id' => 1]);
        $member = Member::factory()->create(['user_id' => $user->id, 'project_id' => 1]);
        PointTransaction::create([
            'project_id' => 1,
            'member_id' => $member->id,
            'type' => 'earned',
            'points' => 10,
            'description' => 'Test',
        ]);

        $response = $this->withoutMiddleware(AuthenticateApiToken::class)
            ->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->get('/api/v1/members/points/history');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['transactions', 'pagination']]);
    }

    public function test_pointCard_ポイントカード要約が返ること(): void
    {
        $user = User::factory()->create(['line_user_id' => self::LINE_USER_ID, 'project_id' => 1]);
        Member::factory()->create(['user_id' => $user->id, 'project_id' => 1, 'points' => 30]);

        $response = $this->withoutMiddleware(AuthenticateApiToken::class)
            ->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->get('/api/v1/members/points/card');

        $response->assertStatus(200)
            ->assertJsonPath('data.card.points', 30);
    }
}

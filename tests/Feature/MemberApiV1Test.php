<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateApiToken;
use App\Models\Member;
use App\Models\PointTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberApiV1Test extends TestCase
{
    use RefreshDatabase;

    private const LINE_USER_ID = 'line-member-v1';

    public function test_me_会員データを返すこと(): void
    {
        $user = User::factory()->create(['line_user_id' => self::LINE_USER_ID, 'project_id' => 1]);
        Member::factory()->create(['user_id' => $user->id, 'project_id' => 1]);

        $response = $this->withoutMiddleware(AuthenticateApiToken::class)
            ->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->get('/api/v1/members/me');

        $response->assertStatus(200)
            ->assertJsonPath('data.member.is_member', true);
    }

    public function test_getPoints_ポイントを返すこと(): void
    {
        $user = User::factory()->create(['line_user_id' => self::LINE_USER_ID, 'project_id' => 1]);
        $member = Member::factory()->create(['user_id' => $user->id, 'project_id' => 1, 'points' => 30]);
        PointTransaction::create([
            'project_id' => 1,
            'member_id' => $member->id,
            'type' => 'earned',
            'points' => 30,
            'description' => 'test',
        ]);

        $response = $this->withoutMiddleware(AuthenticateApiToken::class)
            ->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->get('/api/v1/members/points');

        $response->assertStatus(200)
            ->assertJsonPath('data.points', 30);
    }
}

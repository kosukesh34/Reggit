<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateApiToken;
use App\Models\Member;
use App\Models\PointTransaction;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberPointsHistoryApiV1Test extends TestCase
{
    use RefreshDatabase;

    private const LINE_USER_ID = 'U00000000000000000000000000000004';

    public function test_point_history_ポイント履歴が返ること(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default', 'description' => '', 'is_active' => true]
        );
        $user = User::factory()->create(['line_user_id' => self::LINE_USER_ID, 'project_id' => $project->id]);
        $member = Member::factory()->create(['user_id' => $user->id, 'project_id' => $project->id]);
        PointTransaction::create([
            'project_id' => $project->id,
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

    public function test_point_card_ポイントカード要約が返ること(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default', 'description' => '', 'is_active' => true]
        );
        $user = User::factory()->create(['line_user_id' => self::LINE_USER_ID, 'project_id' => $project->id]);
        Member::factory()->create(['user_id' => $user->id, 'project_id' => $project->id, 'points' => 30]);

        $response = $this->withoutMiddleware(AuthenticateApiToken::class)
            ->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->get('/api/v1/members/points/card');

        $response->assertStatus(200)
            ->assertJsonPath('data.card.points', 30);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\PointTransaction;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberPointsHistoryTest extends TestCase
{
    use RefreshDatabase;

    private const LINE_USER_ID = 'line-member-history';

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }

    public function test_pointHistory_ポイント履歴が返ること(): void
    {
        $this->createProject();
        $user = User::factory()->create(['line_user_id' => self::LINE_USER_ID, 'project_id' => 1]);
        $member = Member::factory()->create(['user_id' => $user->id, 'project_id' => 1]);
        PointTransaction::create([
            'project_id' => 1,
            'member_id' => $member->id,
            'type' => 'earned',
            'points' => 10,
            'description' => 'Test',
        ]);

        $response = $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->get('/api/members/points/history?project_key=default');

        $response->assertStatus(200)
            ->assertJsonStructure(['transactions', 'pagination']);
    }

    public function test_pointCard_ポイントカード要約が返ること(): void
    {
        $this->createProject();
        $user = User::factory()->create(['line_user_id' => self::LINE_USER_ID, 'project_id' => 1]);
        Member::factory()->create(['user_id' => $user->id, 'project_id' => 1, 'points' => 50]);

        $response = $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->get('/api/members/points/card?project_key=default');

        $response->assertStatus(200)
            ->assertJsonPath('points', 50);
    }
}

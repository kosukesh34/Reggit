<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\PointTransaction;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberControllerTest extends TestCase
{
    use RefreshDatabase;

    /** 有効な形式（U + 32桁 hex）でないと UserService がユーザーを特定できない */
    private const LINE_USER_ID = 'U00000000000000000000000000000002';

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }

    public function test_register_会員登録が作成されること(): void
    {
        $this->createProject();

        $response = $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->post('/api/members/register?project_key=default', [
                'name' => 'Test User',
                'phone' => '090-1234-5678',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('is_member', true);
    }

    public function test_me_会員データを返すこと(): void
    {
        $project = $this->createProject();
        $user = User::factory()->create(['line_user_id' => self::LINE_USER_ID, 'project_id' => $project->id]);
        Member::factory()->create(['user_id' => $user->id, 'project_id' => $project->id]);

        $response = $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->get('/api/members/me?project_key=default');

        $response->assertStatus(200)
            ->assertJsonPath('is_member', true);
    }

    public function test_get_points_ポイント情報を返すこと(): void
    {
        $project = $this->createProject();
        $user = User::factory()->create(['line_user_id' => self::LINE_USER_ID, 'project_id' => $project->id]);
        $member = Member::factory()->create(['user_id' => $user->id, 'project_id' => $project->id, 'points' => 50]);
        PointTransaction::create([
            'project_id' => $project->id,
            'member_id' => $member->id,
            'type' => 'earned',
            'points' => 50,
            'description' => 'test',
        ]);

        $response = $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->get('/api/members/points?project_key=default');

        $response->assertStatus(200)
            ->assertJsonPath('points', 50);
    }
}

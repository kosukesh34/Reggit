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

    private const LINE_USER_ID = 'line-member-1';

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
            ->post('/api/members/register?project_key=default', ['name' => 'Test User']);

        $response->assertStatus(201)
            ->assertJsonPath('is_member', true);
    }

    public function test_me_会員データを返すこと(): void
    {
        $this->createProject();
        $user = User::factory()->create(['line_user_id' => self::LINE_USER_ID, 'project_id' => 1]);
        Member::factory()->create(['user_id' => $user->id, 'project_id' => 1]);

        $response = $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->get('/api/members/me?project_key=default');

        $response->assertStatus(200)
            ->assertJsonPath('is_member', true);
    }

    public function test_getPoints_ポイント情報を返すこと(): void
    {
        $this->createProject();
        $user = User::factory()->create(['line_user_id' => self::LINE_USER_ID, 'project_id' => 1]);
        $member = Member::factory()->create(['user_id' => $user->id, 'project_id' => 1, 'points' => 50]);
        PointTransaction::create([
            'project_id' => 1,
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

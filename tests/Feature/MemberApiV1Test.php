<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateApiToken;
use App\Models\Member;
use App\Models\PointTransaction;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberApiV1Test extends TestCase
{
    use RefreshDatabase;

    /** 有効な形式（U + 32桁 hex）でないと UserService がユーザーを特定できない */
    private const LINE_USER_ID = 'U00000000000000000000000000000001';

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default', 'description' => '', 'is_active' => true]
        );
    }

    public function test_register_モバイルアプリから会員登録できること(): void
    {
        $project = $this->createProject();
        config(['smaregi.contract_id' => '']);

        $response = $this->withoutMiddleware(AuthenticateApiToken::class)
            ->withHeader('X-Line-User-Id', self::LINE_USER_ID)
            ->post('/api/v1/members/register', [
                'name' => 'API会員',
                'phone' => '090-1234-5678',
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.member.is_member', true);

        $this->assertDatabaseHas('users', [
            'project_id' => $project->id,
            'line_user_id' => self::LINE_USER_ID,
            'name' => 'API会員',
        ]);
        $this->assertDatabaseHas('members', ['project_id' => $project->id]);
    }

    public function test_update_モバイルアプリと管理画面が同じ会員を更新すること(): void
    {
        $project = $this->createProject();
        $user = User::factory()->create(['line_user_id' => self::LINE_USER_ID, 'project_id' => $project->id]);
        $member = Member::factory()->create(['user_id' => $user->id, 'project_id' => $project->id]);

        $response = $this->withoutMiddleware(AuthenticateApiToken::class)
            ->withHeader('X-Line-User-Id', self::LINE_USER_ID)
            ->put('/api/v1/members/me', [
                'name' => '更新会員',
                'phone' => '080-1111-2222',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.member.is_member', true);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => '更新会員', 'phone' => '080-1111-2222']);
        $this->assertDatabaseHas('members', ['id' => $member->id]);
    }

    public function test_me_会員データを返すこと(): void
    {
        $project = $this->createProject();
        $user = User::factory()->create(['line_user_id' => self::LINE_USER_ID, 'project_id' => $project->id]);
        Member::factory()->create(['user_id' => $user->id, 'project_id' => $project->id]);

        $response = $this->withoutMiddleware(AuthenticateApiToken::class)
            ->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->get('/api/v1/members/me');

        $response->assertStatus(200)
            ->assertJsonPath('data.member.is_member', true);
    }

    public function test_get_points_ポイントを返すこと(): void
    {
        $project = $this->createProject();
        $user = User::factory()->create(['line_user_id' => self::LINE_USER_ID, 'project_id' => $project->id]);
        $member = Member::factory()->create(['user_id' => $user->id, 'project_id' => $project->id, 'points' => 30]);
        PointTransaction::create([
            'project_id' => $project->id,
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

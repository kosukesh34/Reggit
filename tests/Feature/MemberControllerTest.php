<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
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
        // デフォルト契約IDがあると実スマレジへ飛ぶため、Featureでは契約なしにする
        config(['smaregi.contract_id' => '']);

        $response = $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->post('/api/members/register?project_key=default', [
                'name' => 'Test User',
                'phone' => '090-1234-5678',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('is_member', true);
    }

    public function test_register_ライン名だけ送ってもプロフィール名は空のままであること(): void
    {
        $this->createProject();
        config(['smaregi.contract_id' => '']);

        $response = $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->post('/api/members/register?project_key=default', [
                'line_name' => 'LINE花子',
                'phone' => '090-1234-5678',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('is_member', true)
            ->assertJsonMissingPath('user.line_name');

        $user = User::query()->where('line_user_id', self::LINE_USER_ID)->first();
        $this->assertNotNull($user);
        $this->assertNull($user->name);
        $this->assertSame('LINE花子', $user->line_name);
    }

    public function test_register_二重送信でも会員は1件のみ作成されること(): void
    {
        $this->createProject();
        config(['smaregi.contract_id' => '']);

        $headers = ['X-Line-User-Id' => self::LINE_USER_ID];
        $payload = [
            'name' => 'Test User',
            'phone' => '090-1234-5678',
        ];

        $first = $this->withHeaders($headers)
            ->post('/api/members/register?project_key=default', $payload);
        $second = $this->withHeaders($headers)
            ->post('/api/members/register?project_key=default', $payload);

        $first->assertStatus(201)->assertJsonPath('is_member', true);
        $second->assertStatus(200)->assertJsonPath('is_member', true);

        $user = User::query()->where('line_user_id', self::LINE_USER_ID)->first();
        $this->assertNotNull($user);
        $this->assertSame(1, Member::query()->where('user_id', $user->id)->count());
        $this->assertSame(1, User::query()->where('line_user_id', self::LINE_USER_ID)->count());
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

        ShopSettingStore::setMemberRegistrationMode('prompt', (int) $project->id);
        $response = $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->get('/api/members/me?project_key=default');
        $response->assertStatus(200)
            ->assertJsonPath('member_registration_mode', 'prompt');
    }

    public function test_me_ゲスト可能ならログイン時に会員を自動作成すること(): void
    {
        $project = $this->createProject();
        config(['smaregi.contract_id' => '']);
        ShopSettingStore::setMemberRegistrationMode('optional', (int) $project->id);

        $response = $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->get('/api/members/me?project_key=default');

        $response->assertStatus(200)
            ->assertJsonPath('is_member', true);
        $this->assertTrue(
            Member::query()->where('project_id', $project->id)->exists()
        );
    }

    public function test_me_会員必須では未会員のまま返すこと(): void
    {
        $project = $this->createProject();
        config(['smaregi.contract_id' => '']);
        ShopSettingStore::setMemberRegistrationMode('force', (int) $project->id);

        $response = $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->get('/api/members/me?project_key=default');

        $response->assertStatus(200)
            ->assertJsonPath('is_member', false);
        $this->assertFalse(
            Member::query()->where('project_id', $project->id)->exists()
        );
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

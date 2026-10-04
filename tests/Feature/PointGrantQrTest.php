<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\AdminUser;
use App\Models\Member;
use App\Models\PointGrantCode;
use App\Models\Project;
use App\Models\Settings\ProjectLineLiffSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PointGrantQrTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{Project, User, Member, PointGrantCode} */
    private function fixture(): array
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id, 'points' => 0]);
        ShopSettingStore::setValue('use_points', true, 'boolean', $project->id);
        $code = PointGrantCode::create(['project_id' => $project->id, 'token' => bin2hex(random_bytes(24)), 'points' => 100, 'usage_quota' => 1, 'expires_at' => now()->addHour()]);

        return [$project, $user, $member, $code];
    }

    private function endpoint(Project $project, PointGrantCode $code): string
    {
        return route('api.points.grant-code', ['token' => $code->token, 'project_key' => $project->project_key]);
    }

    public function test_会員がポイントを受け取り重複や上限超過は付与されない(): void
    {
        [$project, $user, $member, $code] = $this->fixture();
        $url = $this->endpoint($project, $code);
        $this->actingAs($user)->postJson($url)->assertOk()->assertJsonPath('granted_points', 100)->assertJsonPath('points', 100);
        $this->postJson($url)->assertUnprocessable();
        $otherUser = User::factory()->create(['project_id' => $project->id]);
        Member::factory()->create(['project_id' => $project->id, 'user_id' => $otherUser->id, 'points' => 0]);
        $this->actingAs($otherUser)->postJson($url)->assertUnprocessable();
        $this->assertDatabaseCount('point_grant_redemptions', 1);
        $this->assertDatabaseHas('point_transactions', ['project_id' => $project->id, 'member_id' => $member->id, 'points' => 100]);
    }

    public function test_別店舗の_q_rからはポイントを受け取れない(): void
    {
        [$project, $user, $member, $code] = $this->fixture();
        $other = Project::factory()->create();
        $this->actingAs($user)->postJson($this->endpoint($other, $code))->assertUnauthorized();
        $otherUser = User::factory()->create(['project_id' => $other->id]);
        Member::factory()->create(['project_id' => $other->id, 'user_id' => $otherUser->id]);
        $this->actingAs($otherUser)->postJson($this->endpoint($other, $code))->assertNotFound();
        $this->assertDatabaseCount('point_grant_redemptions', 0);
        $this->assertDatabaseMissing('point_transactions', ['project_id' => $project->id, 'member_id' => $member->id]);
    }

    public function test_期限切れやポイント機能無効時は付与されない(): void
    {
        [$project, $user, $member, $code] = $this->fixture();
        $code->update(['expires_at' => now()->subMinute()]);
        $this->actingAs($user)->postJson($this->endpoint($project, $code))->assertStatus(410);
        $code->update(['expires_at' => now()->addHour()]);
        ShopSettingStore::setValue('use_points', false, 'boolean', $project->id);
        $this->postJson($this->endpoint($project, $code))->assertForbidden();
        $this->assertDatabaseCount('point_grant_redemptions', 0);
    }

    public function test_q_rを開くだけでは付与されず受け取り画面へ進む(): void
    {
        [$project, $user, $member, $code] = $this->fixture();
        $this->get(route('points.grant.open', ['project_key' => $project->project_key, 'token' => $code->token]))->assertRedirect();
        $this->assertStringContainsString($code->token, (string) $this->get(route('points.grant.open', ['project_key' => $project->project_key, 'token' => $code->token]))->headers->get('Location'));
        $this->assertDatabaseCount('point_grant_redemptions', 0);
    }

    public function test_ポイント_q_rは共通設定に関係なく自店舗の画面を開く(): void
    {
        [$project, , , $code] = $this->fixture();
        config(['line.liff_url' => 'https://liff.line.me/another-project']);
        $url = route('points.grant.open', ['project_key' => $project->project_key, 'token' => $code->token]);
        $location = $this->get($url)->assertRedirect()->headers->get('Location');
        $this->assertStringStartsWith(route('liff.project', ['project_key' => $project->project_key]), $location);
        $this->assertStringNotContainsString('another-project', $location);
        ProjectLineLiffSetting::create(['project_id' => $project->id, 'line_liff_id' => 'own-app']);
        $location = $this->get($url)->assertRedirect()->headers->get('Location');
        $this->assertStringStartsWith('https://liff.line.me/own-app?', $location);
        $this->assertStringContainsString($code->token, $location);
        $this->assertDatabaseCount('point_grant_redemptions', 0);
    }

    public function test_店舗管理者が利用条件を指定して_q_rを発行できる(): void
    {
        [$project] = $this->fixture();
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN, 'project_id' => $project->id]);
        $url = route('admin.qr-codes.points.store', ['project_key' => $project->project_key]);
        $this->actingAs($admin, 'admin')->post($url, ['points' => 50, 'expires_minutes' => 30, 'usage_quota' => 10])->assertRedirect()->assertSessionHas('issued_point_code_id');
        $this->get(route('admin.qr-codes', ['project_key' => $project->project_key]))->assertOk()->assertSee('50ポイント')->assertSee('利用上限 10回');
        $this->assertDatabaseHas('point_grant_codes', ['project_id' => $project->id, 'points' => 50, 'usage_quota' => 10]);
        $this->post($url, ['points' => 0, 'expires_minutes' => 0, 'usage_quota' => 0])->assertSessionHasErrors(['points', 'expires_minutes', 'usage_quota']);
    }
}

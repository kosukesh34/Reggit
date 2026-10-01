<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Project;
use App\Models\SystemNotice;
use App\Services\Smaregi\SmaregiProductSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemNoticeAndServiceStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_validation_restores_only_the_notice_being_edited(): void
    {
        $system = AdminUser::factory()->create(['role' => AdminUser::ROLE_SYSTEM_ADMIN]);
        $notice = SystemNotice::create(['title' => '元のタイトル', 'body' => '保存済み本文', 'is_published' => true]);
        $other = SystemNotice::create(['title' => '別のお知らせ', 'body' => '別の本文', 'is_published' => false]);
        $this->actingAs($system, 'admin')->from(route('system-admin.notices.index'))
            ->put(route('system-admin.notices.update', $notice->id), [
                '_notice_id' => (string) $notice->id,
                'title' => '編集中のタイトル', 'body' => '', 'is_published' => '0',
                'starts_at' => '2026-10-01T12:00',
            ])->assertSessionHasErrors('body');
        $response = $this->get(route('system-admin.notices.index'))->assertOk()
            ->assertSee('value="2026-10-01T12:00"', false)
            ->assertSee('value="別のお知らせ"', false);
        $this->assertSame(1, substr_count($response->getContent(), 'value="編集中のタイトル"'));
        $this->assertDatabaseHas('system_notices', ['id' => $notice->id, 'title' => '元のタイトル', 'is_published' => true]);
        $this->assertDatabaseHas('system_notices', ['id' => $other->id, 'body' => '別の本文']);
    }

    public function test_only_current_published_notices_are_shown(): void
    {
        foreach ([['title' => '公開お知らせ', 'is_published' => true], ['title' => '下書きお知らせ', 'is_published' => false], ['title' => '未来のお知らせ', 'is_published' => true, 'starts_at' => now()->addDay()], ['title' => '終了したお知らせ', 'is_published' => true, 'ends_at' => now()->subDay()]] as $data) {
            SystemNotice::create(['body' => '本文', ...$data]);
        }
        $this->get('/admin/login')->assertOk()->assertDontSee('公開お知らせ');
        $project = Project::factory()->create();
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN, 'project_id' => $project->id]);
        $admin->projects()->sync([$project->id]);
        $response = $this->actingAs($admin, 'admin')->get(route('admin.dashboard', ['project_key' => $project->project_key]));
        $response->assertOk()->assertSee('お知らせ')->assertSee('公開お知らせ')->assertDontSee('下書きお知らせ')->assertDontSee('未来のお知らせ')->assertDontSee('終了したお知らせ');
        $this->assertSame(1, substr_count($response->getContent(), '公開お知らせ'));
        $response->assertSee('dashboard-notices-scroll')->assertSee('dashboard-notice-item')->assertSee('dashboard-notice-list-date')->assertDontSee('本文');
        $visible = SystemNotice::visible()->firstOrFail();
        $this->get(route('admin.dashboard.notice', ['project_key' => $project->project_key, 'id' => $visible->id]))->assertOk()->assertSee('公開お知らせ')->assertSee('作成日時：')->assertSee('本文');
        $hidden = SystemNotice::where('title', '下書きお知らせ')->firstOrFail();
        $this->get(route('admin.dashboard.notice', ['project_key' => $project->project_key, 'id' => $hidden->id]))->assertNotFound();
        $this->get(route('admin.products', ['project_key' => $project->project_key]))->assertOk()->assertDontSee('公開お知らせ');

    }

    public function test_system_admin_can_create_notice_but_store_admin_cannot(): void
    {
        $system = AdminUser::factory()->create(['role' => AdminUser::ROLE_SYSTEM_ADMIN]);
        $this->actingAs($system, 'admin')->post(route('system-admin.notices.store'), ['title' => 'アップデート', 'body' => '本文', 'is_published' => '1'])->assertRedirect();
        $this->assertDatabaseHas('system_notices', ['title' => 'アップデート', 'is_published' => true]);
        $this->get(route('system-admin.notices.index'))->assertOk()->assertSee('本文（Markdown）')->assertSee('選択した文字に色を付ける')->assertSee('保存済みの本文プレビュー');
        $store = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN]);
        $this->actingAs($store, 'admin')->post(route('system-admin.notices.store'), ['title' => '不正', 'body' => '本文'])->assertForbidden();
    }

    public function test_suspension_blocks_login_and_existing_sessions(): void
    {
        $project = Project::factory()->create();
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN, 'project_id' => $project->id, 'password' => bcrypt('password')]);
        $admin->projects()->sync([$project->id]);
        foreach (['unpaid', 'cancelled'] as $status) {
            $project->update(['service_status' => $status]);
            auth('admin')->logout();
            $this->post('/admin/login', ['login_id' => $admin->login_id, 'password' => 'password'])->assertSessionHasErrors('login_id');
            $this->assertGuest('admin');
            $this->actingAs($admin, 'admin')->get(route('admin.products', ['project_key' => $project->project_key]))->assertNotFound();
            $this->get('/liff/'.$project->project_key)->assertNotFound();
        }
        $project->update(['service_status' => 'active']);
        $this->actingAs($admin, 'admin')->get(route('admin.products', ['project_key' => $project->project_key]))->assertOk()->assertSee('スマレジから取り込む');
    }

    public function test_preview_renders_unsaved_markdown_without_creating_notice(): void
    {
        $system = AdminUser::factory()->create(['role' => AdminUser::ROLE_SYSTEM_ADMIN]);
        $this->actingAs($system, 'admin')->postJson(route('system-admin.notices.preview'), ['body' => '## 未保存の本文'])->assertOk()->assertJsonPath('html', "<h2>未保存の本文</h2>\n");
        $this->assertDatabaseCount('system_notices', 0);
        $this->postJson(route('system-admin.notices.preview'), ['body' => str_repeat('a', 20001)])->assertUnprocessable();
        $store = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN]);
        $this->actingAs($store, 'admin')->postJson(route('system-admin.notices.preview'), ['body' => '本文'])->assertForbidden();
    }

    public function test_saved_published_notice_is_visible_on_dashboard(): void
    {
        $project = Project::factory()->create();
        $system = AdminUser::factory()->create(['role' => AdminUser::ROLE_SYSTEM_ADMIN]);
        $this->actingAs($system, 'admin')->post(route('system-admin.notices.store'), ['title' => '保存から公開', 'body' => '**更新内容**', 'is_published' => '1'])->assertRedirect();
        $this->get(route('system-admin.notices.index'))->assertOk()->assertSee('公開中')->assertSee('公開期間：')->assertSee('編集中の内容をプレビュー');
        $store = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN, 'project_id' => $project->id]);
        $store->projects()->sync([$project->id]);
        $this->actingAs($store, 'admin')->get(route('admin.dashboard', ['project_key' => $project->project_key]))->assertOk()->assertSee('保存から公開')->assertDontSee('<strong>更新内容</strong>', false);
        $notice = SystemNotice::where('title', '保存から公開')->firstOrFail();
        $this->get(route('admin.dashboard.notice', ['project_key' => $project->project_key, 'id' => $notice->id]))->assertOk()->assertSee('<strong>更新内容</strong>', false);
    }

    public function test_new_badge_disappears_after_each_admin_reads_notice_and_returns_after_edit(): void
    {
        $project = Project::factory()->create();
        $notice = SystemNotice::create(['title' => '更新のお知らせ', 'body' => '本文', 'is_published' => true]);
        $first = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN, 'project_id' => $project->id]);
        $second = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN, 'project_id' => $project->id]);
        $first->projects()->sync([$project->id]);
        $second->projects()->sync([$project->id]);
        $dashboard = route('admin.dashboard', ['project_key' => $project->project_key]);
        $detail = route('admin.dashboard.notice', ['project_key' => $project->project_key, 'id' => $notice->id]);

        $this->actingAs($first, 'admin')->get($dashboard)->assertOk()->assertSee('NEW');
        $this->get($detail)->assertOk();
        $this->get($dashboard)->assertOk()->assertDontSee('dashboard-notice-new');
        $this->actingAs($second, 'admin')->get($dashboard)->assertOk()->assertSee('NEW');

        $system = AdminUser::factory()->create(['role' => AdminUser::ROLE_SYSTEM_ADMIN]);
        $this->actingAs($system, 'admin')->put(route('system-admin.notices.update', $notice->id), ['title' => '更新のお知らせ', 'body' => '更新本文', 'is_published' => '1'])->assertRedirect();
        $this->actingAs($first, 'admin')->get($dashboard)->assertOk()->assertSee('NEW');
    }

    public function test_import_uses_current_project(): void
    {
        $project = Project::factory()->create();
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN, 'project_id' => $project->id]);
        $admin->projects()->sync([$project->id]);
        $this->mock(SmaregiProductSyncService::class, function ($mock) use ($project) {
            $mock->shouldReceive('sync')->once()->with($project->id)->andReturn(['created' => 2, 'updated' => 1, 'failed' => 0, 'errors' => []]);
        });
        $this->actingAs($admin, 'admin')->post(route('admin.products.import-smaregi', ['project_key' => $project->project_key]))->assertRedirect()->assertSessionHas('success');
    }
}

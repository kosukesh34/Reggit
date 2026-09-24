<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\HelpArticle;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemHelpArticleTest extends TestCase
{
    use RefreshDatabase;

    public function test_統合管理で追加した質問が店舗ヘルプに出ること(): void
    {
        $project = Project::factory()->create(['project_key' => 'help-store', 'is_active' => true]);
        $system = AdminUser::factory()->create(['role' => AdminUser::ROLE_SYSTEM_ADMIN]);
        $store = AdminUser::factory()->create([
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);
        $store->projects()->sync([$project->id]);

        $this->actingAs($system, 'admin')
            ->post(route('system-admin.help-articles.store'), [
                'slug' => 'delivery-error',
                'title' => '配信が失敗したとき',
                'summary' => '失敗しても発行されない',
                'category' => 'faq',
                'body' => "## 失敗\n\n発行されません。",
                'sort_order' => 1,
                'is_published' => '1',
            ])
            ->assertRedirect();

        $this->actingAs($store, 'admin')
            ->get(route('admin.help', ['project_key' => $project->project_key, 'q' => '失敗']))
            ->assertOk()
            ->assertSee('配信が失敗したとき');

        $this->actingAs($store, 'admin')
            ->get(route('admin.help.show', ['project_key' => $project->project_key, 'slug' => 'delivery-error']))
            ->assertOk()
            ->assertSee('<h2>失敗</h2>', false)
            ->assertSee('発行されません。');
    }

    public function test_固定記事と同じスラッグは追加できないこと(): void
    {
        $system = AdminUser::factory()->create(['role' => AdminUser::ROLE_SYSTEM_ADMIN]);

        $this->actingAs($system, 'admin')
            ->from(route('system-admin.help-articles.create'))
            ->post(route('system-admin.help-articles.store'), [
                'slug' => 'delivery-cancel',
                'title' => '重複',
                'summary' => '重複',
                'category' => 'faq',
                'body' => '本文',
                'is_published' => '1',
            ])
            ->assertRedirect(route('system-admin.help-articles.create'))
            ->assertSessionHasErrors('slug');

        $this->assertSame(0, HelpArticle::query()->count());
    }

    public function test_非公開の記事は店舗ヘルプに出ないこと(): void
    {
        $project = Project::factory()->create(['project_key' => 'help-hidden', 'is_active' => true]);
        $store = AdminUser::factory()->create([
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);
        $store->projects()->sync([$project->id]);
        HelpArticle::query()->create([
            'slug' => 'hidden-note',
            'title' => '非公開メモ',
            'summary' => '出さない',
            'category' => 'faq',
            'body' => '秘密',
            'sort_order' => 0,
            'is_published' => false,
        ]);

        $this->actingAs($store, 'admin')
            ->get(route('admin.help.show', ['project_key' => $project->project_key, 'slug' => 'hidden-note']))
            ->assertNotFound();
    }
}

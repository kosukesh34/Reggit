<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\HelpArticle;
use App\Models\Project;
use App\Support\HelpMarkdown;
use App\UseCases\Admin\Help\AdminHelpShowUseCase;
use Database\Seeders\HelpArticleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemHelpArticleTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_uses_store_help_renderer_without_saving(): void
    {
        $system = AdminUser::factory()->create(['role' => AdminUser::ROLE_SYSTEM_ADMIN]);
        $body = "## 見出し\n**太字**\n<script>alert(1)</script>";
        $this->actingAs($system, 'admin')
            ->postJson(route('system-admin.help-articles.preview'), ['body' => $body])
            ->assertOk()
            ->assertJson(['html' => app(HelpMarkdown::class)->toHtml($body)]);
        $this->assertDatabaseCount('help_articles', count((new HelpArticleSeeder)->articles()));
        $this->get(route('system-admin.help-articles.index'))->assertOk()->assertSee('ヘルプ記事を書く')
            ->assertDontSee('href="'.route('admin.password.edit').'"', false);
        $this->get(route('system-admin.help-articles.create'))->assertOk()->assertSee('プレビュー');
        $store = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN]);
        $this->actingAs($store, 'admin')->postJson(route('system-admin.help-articles.preview'), ['body' => $body])->assertForbidden();
    }

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

    public function test_既存記事と同じスラッグは追加できないこと(): void
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

        $this->assertSame(count((new HelpArticleSeeder)->articles()), HelpArticle::query()->count());
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

    public function test_seeded_articles_are_editable_and_reseeding_preserves_edits(): void
    {
        $article = HelpArticle::query()->where('slug', 'transfer')->firstOrFail();
        $system = AdminUser::factory()->create(['role' => AdminUser::ROLE_SYSTEM_ADMIN]);
        $this->actingAs($system, 'admin')->put(route('system-admin.help-articles.update', ['id' => $article->id]), [
            'slug' => 'transfer', 'title' => '更新済み', 'summary' => '手順',
            'category' => 'guide', 'body' => '更新した本文', 'is_published' => '0',
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->seed(HelpArticleSeeder::class);
        $article->refresh();
        $this->assertSame('更新した本文', $article->body);
        $this->assertFalse($article->is_published);
        $this->assertNull(app(AdminHelpShowUseCase::class)->handle('transfer'));
        $this->assertDatabaseCount('help_articles', count((new HelpArticleSeeder)->articles()));
    }

    public function test_system_admin_can_preview_drafts_with_images_without_publishing(): void
    {
        $system = AdminUser::factory()->create(['role' => AdminUser::ROLE_SYSTEM_ADMIN]);
        $article = HelpArticle::query()->where('slug', 'transfer')->firstOrFail();
        $article->update(['is_published' => false]);
        $this->actingAs($system, 'admin')->get(route('system-admin.help-articles.index'))
            ->assertOk()->assertSee(route('system-admin.help-articles.show-preview', ['id' => $article->id]));
        $this->get(route('system-admin.help-articles.show-preview', ['id' => $article->id]))
            ->assertOk()->assertSee($article->title)->assertSee('下書き')
            ->assertSee('<img src="/images/help/help-members.png"', false);
        $this->assertFalse($article->fresh()->is_published);
        $store = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN]);
        $this->actingAs($store, 'admin')->get(route('system-admin.help-articles.show-preview', ['id' => $article->id]))->assertForbidden();
    }

    public function test_every_seeded_instruction_has_an_existing_screen_image(): void
    {
        $seeder = new HelpArticleSeeder;
        foreach ($seeder->articles() as $article) {
            $this->assertNotEmpty($seeder->body($article), $article['slug']);
            foreach ($article['steps'] as $step) {
                $this->assertFileExists(public_path($step['image']), $article['slug'].' / '.$step['title']);
            }
            $stored = HelpArticle::where('slug', $article['slug'])->firstOrFail();
            $this->assertSame(substr_count($seeder->body($article), '](/images/help/'), substr_count($stored->body, '](/images/help/'));
        }
    }

    public function test_all_feature_guides_are_published_readable_and_searchable(): void
    {
        $project = Project::factory()->create(['project_key' => 'help-features', 'is_active' => true]);
        $store = AdminUser::factory()->create([
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);
        $store->projects()->sync([$project->id]);
        $this->actingAs($store, 'admin');

        foreach ((new HelpArticleSeeder)->articles() as $article) {
            $this->get(route('admin.help.show', [
                'project_key' => $project->project_key,
                'slug' => $article['slug'],
            ]))->assertOk()->assertSee($article['title']);
            $this->assertStringContainsString('](/', (new HelpArticleSeeder)->body($article), $article['slug']);
            $this->assertStringContainsString('](/images/help/', (new HelpArticleSeeder)->body($article), $article['slug']);

            if ($article['steps'] === []) {
                foreach (['操作手順', '処理の仕組み', '注意'] as $heading) {
                    $this->assertStringContainsString('## '.$heading, $article['body'], $article['slug']);
                }
            }
        }

        $this->get(route('admin.help', [
            'project_key' => $project->project_key,
            'q' => '合算',
        ]))->assertOk()->assertSee('スマレジの既存会員をアプリに連携する');
    }

    public function test_standard_upgrade_preserves_drafts_order_and_custom_titles(): void
    {
        $article = HelpArticle::where('slug', 'transfer')->firstOrFail();
        $previous = json_decode(file_get_contents(database_path('seeders/data/help-articles-v1.json')), true);
        $article->update(['body' => $previous['transfer'], 'is_published' => false, 'sort_order' => 999]);
        $this->seed(HelpArticleSeeder::class);
        $this->assertStringContainsString('](/admin/{project_key}/members)', $article->fresh()->body);
        $this->assertFalse($article->fresh()->is_published);
        $this->assertSame(999, $article->fresh()->sort_order);

        $article->update(['title' => '独自の案内タイトル']);
        $this->seed(HelpArticleSeeder::class);
        $this->assertSame('独自の案内タイトル', $article->fresh()->title);
    }

    public function test_guide_upgrade_preserves_custom_articles_and_publication_status(): void
    {
        $previous = json_decode(file_get_contents(database_path('seeders/data/help-articles-v1.json')), true);
        $transfer = HelpArticle::where('slug', 'transfer')->firstOrFail();
        $transfer->update(['body' => $previous['transfer'], 'is_published' => false]);
        $custom = HelpArticle::where('slug', 'coupon')->firstOrFail();
        $custom->update(['body' => '店舗に合わせて編集した本文']);
        $migration = require database_path('migrations/2026_10_07_130000_expand_help_screen_guides.php');
        $migration->up();
        $this->assertStringContainsString('help-app-transfer.png', $transfer->fresh()->body);
        $this->assertFalse($transfer->fresh()->is_published);
        $this->assertSame('店舗に合わせて編集した本文', $custom->fresh()->body);
    }
}

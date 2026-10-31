<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Settings\LiffSettingStore;
use App\Infrastructure\Persistence\Eloquent\Settings\OrderSettingStore;
use App\Models\AdminUser;
use App\Models\Project;
use App\Models\StoreAppCredential;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }

    private function createAdmin(Project $project): AdminUser
    {
        return AdminUser::factory()->create([
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);
    }

    public function test_settings_基本設定ページにアクセスできること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.basic', ['project_key' => $project->project_key]));

        $response->assertStatus(200);
        $response->assertSee('class="web-back"', false);
        $response->assertDontSee('settings-workspace-nav', false);
        $response->assertSee('設定一覧へ戻る', false);
    }

    public function test_settings_店舗管理者が一度きりの端末登録コードを発行できること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $response = $this->actingAs($admin, 'admin')->post(
            route('admin.settings.store-app-credential.store', ['project_key' => $project->project_key]),
            ['app' => 'kitchen'],
        );

        $response->assertRedirect()->assertSessionHas('store_app_enrollment');
        $credential = StoreAppCredential::query()->where('project_id', $project->id)->where('app', 'kitchen')->firstOrFail();
        $plainToken = session('store_app_enrollment.token');
        $this->assertNotSame($plainToken, $credential->enrollment_token_hash);
        $this->assertSame(hash('sha256', $plainToken), $credential->enrollment_token_hash);
    }

    public function test_settings_登録コード再発行で旧端末トークンを失効できること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $token = $admin->createToken('old-device', ['app:kitchen', 'project:'.$project->id]);
        StoreAppCredential::query()->create([
            'project_id' => $project->id, 'admin_user_id' => $admin->id, 'app' => 'kitchen',
            'personal_access_token_id' => $token->accessToken->id,
            'device_key_hash' => hash('sha256', 'old-key'), 'claimed_at' => now(),
        ]);

        $this->actingAs($admin, 'admin')->post(
            route('admin.settings.store-app-credential.store', ['project_key' => $project->project_key]),
            ['app' => 'kitchen'],
        )->assertRedirect();

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->accessToken->id]);
        $this->assertDatabaseHas('store_app_credentials', [
            'project_id' => $project->id, 'app' => 'kitchen',
            'personal_access_token_id' => null, 'claimed_at' => null,
        ]);
    }

    public function test_settings_スタッフは端末登録コードを発行できないこと(): void
    {
        $project = $this->createProject();
        $staff = AdminUser::factory()->create([
            'role' => AdminUser::ROLE_STAFF, 'project_id' => $project->id,
        ]);

        $this->actingAs($staff, 'admin')->post(
            route('admin.settings.store-app-credential.store', ['project_key' => $project->project_key]),
            ['app' => 'waiting'],
        )->assertForbidden();
        $this->assertDatabaseCount('store_app_credentials', 0);
    }

    public function test_settings_注文_lin_e通知トグルを保存できること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.basic.update', ['project_key' => $project->project_key]), [
                'business_hours_start' => '10:00',
                'business_hours_end' => '22:00',
                'order_mode' => 'both',
            ])
            ->assertRedirect();

        $this->assertFalse(app(OrderSettingStore::class)->isOrderLineNotifyEnabled((int) $project->id));
        $this->assertFalse(app(OrderSettingStore::class)->isOrderReadyLineNotifyEnabled((int) $project->id));

        $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.basic.update', ['project_key' => $project->project_key]), [
                'business_hours_start' => '10:00',
                'business_hours_end' => '22:00',
                'order_mode' => 'both',
                'order_line_notify_enabled' => '1',
                'order_ready_line_notify_enabled' => '1',
            ])
            ->assertRedirect();

        $this->assertTrue(app(OrderSettingStore::class)->isOrderLineNotifyEnabled((int) $project->id));
        $this->assertTrue(app(OrderSettingStore::class)->isOrderReadyLineNotifyEnabled((int) $project->id));
    }

    public function test_settings_詳細設定ページにアクセスできること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.advanced', ['project_key' => $project->project_key]));

        $response->assertStatus(200);
    }

    public function test_settings_ランク設定ページにアクセスできること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.ranks', ['project_key' => $project->project_key]));

        $response->assertStatus(200);
    }

    public function test_settings_設定ハブにアクセスでき各設定への案内があること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.index', ['project_key' => $project->project_key]));

        $response->assertStatus(200);
        $response->assertSee('店舗運用設定', false)
            ->assertSee('アプリデザイン', false)
            ->assertSee('ランク・ポイント・スタンプ', false);
        $response->assertSee('予約', false);
        $response->assertSee('クーポン', false);
        $response->assertSee('settings-hub-card', false);
        $response->assertSee('設定を検索', false);
        $response->assertSee('css/admin/admin.css', false);
        $response->assertSee(route('admin.settings.basic', ['project_key' => $project->project_key], false), false);
        $response->assertDontSee('スマレジ連携');
        $response->assertDontSee('ギャラリー設定');
        $response->assertDontSee('<h1>設定</h1>', false);
    }

    public function test_ギャラリーはホームメニューで非表示にすると管理メニューから消えること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        app(LiffSettingStore::class)->setFrontendMenuItems([
            ['tab' => 'gallery', 'label' => 'ギャラリー', 'enabled' => false, 'order' => 10],
        ], $project->id);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.index', ['project_key' => $project->project_key]))
            ->assertOk()
            ->assertDontSee(route('admin.gallery.items', ['project_key' => $project->project_key], false), false);

        $this->get(route('admin.settings.features', ['project_key' => $project->project_key]))
            ->assertOk()
            ->assertDontSee('features[feature_gallery_enabled]', false);
    }
}

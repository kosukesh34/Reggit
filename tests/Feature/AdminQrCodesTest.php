<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Settings\LineSettingStore;
use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\AdminUser;
use App\Models\Project;
use App\Models\Settings\ProjectAppLink;
use App\Models\Settings\ProjectLineLiffSetting;
use App\Models\StampCard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminQrCodesTest extends TestCase
{
    use RefreshDatabase;

    public function test_自店舗の_q_rとカードだけが表示される(): void
    {
        $project = Project::factory()->create();
        $other = Project::factory()->create();
        LineSettingStore::setLineLiffUrl('https://liff.line.me/own-app', $project->id);
        LineSettingStore::setLineLiffUrl('https://liff.line.me/other-app', $other->id);
        ProjectAppLink::create(['project_id' => $project->id, 'download_url' => 'https://example.com/own-download']);
        ProjectAppLink::create(['project_id' => $other->id, 'download_url' => 'https://example.com/other-download']);
        StampCard::factory()->create(['project_id' => $project->id, 'name' => '自店舗カード', 'is_active' => true]);
        StampCard::factory()->create(['project_id' => $other->id, 'name' => '他店舗カード', 'is_active' => true]);
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN, 'project_id' => $project->id]);
        $this->actingAs($admin, 'admin')->get(route('admin.qr-codes', ['project_key' => $project->project_key]))
            ->assertOk()->assertSee('QRコード発行')->assertSee('https://liff.line.me/own-app')
            ->assertSee('https://example.com/own-download')->assertSee('自店舗カード')
            ->assertDontSee('other-app')->assertDontSee('other-download')->assertDontSee('他店舗カード');
    }

    public function test_システム管理者が共通ダウンロード_ur_lを設定し解除できる(): void
    {
        $project = Project::factory()->create();
        $other = Project::factory()->create();
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_SYSTEM_ADMIN]);
        $url = route('system-admin.projects.app-links.update', $project->id);
        $this->actingAs($admin, 'admin')->put($url, ['download_url' => 'https://example.com/app'])
            ->assertRedirect(route('system-admin.projects.edit', $project->id));
        $this->assertDatabaseHas('project_app_links', ['project_id' => $project->id, 'download_url' => 'https://example.com/app']);
        $this->assertDatabaseMissing('project_app_links', ['project_id' => $other->id]);
        $this->get(route('system-admin.projects.edit', $project->id))->assertOk()->assertSee('https://example.com/app');
        $this->put($url, ['download_url' => 'javascript:alert(1)'])->assertSessionHasErrors('download_url');
        $this->put($url, ['download_url' => ''])->assertRedirect();
        $this->assertDatabaseHas('project_app_links', ['project_id' => $project->id, 'download_url' => null]);
    }

    public function test_店舗管理者はシステム管理の_ur_lを変更できない(): void
    {
        $project = Project::factory()->create();
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN, 'project_id' => $project->id]);
        $this->actingAs($admin, 'admin')->put(route('system-admin.projects.app-links.update', $project->id), ['download_url' => 'https://example.com/app'])->assertForbidden();
        $this->assertDatabaseMissing('project_app_links', ['project_id' => $project->id]);
    }

    public function test_stamp_qr_自店舗のカードで発行でき別店舗のカードでは発行できない(): void
    {
        $project = Project::factory()->create();
        $other = Project::factory()->create();
        $card = StampCard::factory()->create(['project_id' => $project->id, 'is_active' => true]);
        $otherCard = StampCard::factory()->create(['project_id' => $other->id, 'is_active' => true]);
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN, 'project_id' => $project->id]);
        $url = route('admin.qr-codes.stamps.store', ['project_key' => $project->project_key]);
        $data = ['stamp_card_id' => $card->id, 'amount' => 2, 'expires_minutes' => 60, 'limit' => 'member', 'quota' => 10];
        $this->actingAs($admin, 'admin')->post($url, $data)->assertRedirect()->assertSessionHas('issued_stamp_code_id');
        $this->get(route('admin.qr-codes', ['project_key' => $project->project_key]))->assertOk()->assertSee('2個付与')->assertSee('スタンプ付与QRコード');
        $this->post($url, [...$data, 'stamp_card_id' => $otherCard->id])->assertNotFound();
        $this->assertDatabaseCount('stamp_grant_codes', 1);
    }

    public function test_mobile_order_qr_自店舗の注文画面を直接開く_ur_lが表示される(): void
    {
        $project = Project::factory()->create();
        config(['line.liff_url' => 'https://liff.line.me/another-project']);
        ProjectLineLiffSetting::create(['project_id' => $project->id, 'line_liff_id' => 'order-app']);
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN, 'project_id' => $project->id]);
        $response = $this->actingAs($admin, 'admin')->get(route('admin.qr-codes', ['project_key' => $project->project_key]));
        $response->assertOk()->assertSee('モバイル注文');
        $url = $response->viewData('view')->links['mobile-order']['url'];
        $parts = parse_url($url);
        parse_str($parts['query'], $params);
        parse_str($params['liff_state'], $state);
        $this->assertSame('/order-app', $parts['path']);
        $this->assertSame($project->project_key, $state['project_key']);
        $this->assertSame('products', $state['tab']);
    }

    public function test_スタンプ管理のみのスタッフも_q_r画面を利用できる(): void
    {
        $project = Project::factory()->create();
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_STAFF, 'project_id' => $project->id]);
        $admin->syncPermissions(['stamps.manage']);
        $this->actingAs($admin, 'admin')->get(route('admin.qr-codes', ['project_key' => $project->project_key]))
            ->assertOk()->assertSee('スタンプ付与QR')->assertDontSee('流入経路QR');
        $this->post(route('admin.qr-codes.points.store', ['project_key' => $project->project_key]), [])->assertForbidden();
    }

    public function test_会員閲覧のみのスタッフは流入経路_q_rを見られるが追加できない(): void
    {
        $project = Project::factory()->create();
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_STAFF, 'project_id' => $project->id]);
        $admin->syncPermissions(['members.view']);
        $this->actingAs($admin, 'admin')->get(route('admin.qr-codes', ['project_key' => $project->project_key]))
            ->assertOk()->assertSee('流入経路QR')->assertDontSee('name="landing_tab"', false);
    }

    public function test_mobile_order_qr_lin_e未設定でも自店舗の_web注文画面を案内できる(): void
    {
        $project = Project::factory()->create();
        config(['line.liff_url' => 'https://liff.line.me/another-project']);
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN, 'project_id' => $project->id]);
        $response = $this->actingAs($admin, 'admin')->get(route('admin.qr-codes', ['project_key' => $project->project_key]));
        $response->assertOk();
        $url = $response->viewData('view')->links['mobile-order']['url'];
        $this->assertStringStartsWith(route('liff.project', ['project_key' => $project->project_key]), $url);
        $this->assertStringContainsString('tab=products', $url);
        $this->assertStringNotContainsString('another-project', $url);
    }

    public function test_ショップ有効時だけオンラインショップ_q_rが表示され独自ドメインを優先する(): void
    {
        $project = Project::factory()->create();
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN, 'project_id' => $project->id]);
        $response = $this->actingAs($admin, 'admin')->get(route('admin.qr-codes', ['project_key' => $project->project_key]));
        $response->assertOk()->assertDontSee('オンラインショップ');
        $this->assertArrayNotHasKey('shop', $response->viewData('view')->links);

        ShopSettingStore::setValue('feature_shop_enabled', true, 'boolean', $project->id);
        $enabled = $this->get(route('admin.qr-codes', ['project_key' => $project->project_key]));
        $enabled->assertOk()->assertSee('オンラインショップ');
        $this->assertSame(
            route('shop.show', ['project_key' => $project->project_key]),
            $enabled->viewData('view')->links['shop']['url']
        );

        $project->custom_shop_domain = 'shop.example.com';
        $project->save();
        $custom = $this->get(route('admin.qr-codes', ['project_key' => $project->project_key]));
        $custom->assertOk();
        $this->assertSame('https://shop.example.com/shop', $custom->viewData('view')->links['shop']['url']);
    }
}

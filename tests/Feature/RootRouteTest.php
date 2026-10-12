<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RootRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_未ログイン時はランディングページを表示すること(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertViewIs('landing');
        $response->assertSee('current-home.jpg', false);
        $response->assertSee('current-order.jpg', false);
        $response->assertSee('current-member.jpg', false);
        $response->assertSee('current-coupon.jpg', false);
        $response->assertSee('current-queue.jpg', false);
        $response->assertSee('current-admin.jpg', false);
        $response->assertSee('landing-home.css', false);
        $response->assertSee(route('demo.liff'), false);
        $response->assertSee('導入登録の流れ')
            ->assertSee('LINEプラン')
            ->assertSee('ネイティブアプリプラン');
        $response->assertDontSee('架空企業');
        $response->assertSee('LINEミニアプリ');
        $response->assertSee('iOS / Android');
        $response->assertSee('スタンプ');
        $response->assertSee('画面はデモデータを使用しています');
        $response->assertSee('認証メールを送る');
        $response->assertSee('name="email"', false);
    }

    public function test_root_ログイン済みもランディングページを表示すること(): void
    {
        $project = Project::factory()->create(['project_key' => 'store_a', 'is_active' => true]);
        $admin = AdminUser::factory()->create([
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);
        $admin->projects()->sync([$project->id]);

        $response = $this->actingAs($admin, 'admin')->get('/');

        $response->assertOk();
        $response->assertViewIs('landing');
    }
}

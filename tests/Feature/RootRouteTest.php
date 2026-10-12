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
        $response->assertSee('editorial/store-hero.webp', false);
        $response->assertSee('editorial/member-retail.webp', false);
        $response->assertSee('editorial/queue-family.webp', false);
        $response->assertSee('editorial/kitchen-team.webp', false);
        $response->assertSee('editorial/workshop-hands.webp', false);
        $response->assertSee('editorial/table-conversation.webp', false);
        $response->assertSee('editorial/storefront-detail.webp', false);
        $response->assertSee('actual-home.webp', false);
        $response->assertSee('actual-order.webp', false);
        $response->assertSee('actual-member.webp', false);
        $response->assertSee('actual-point.webp', false);
        $response->assertSee('actual-coupon.webp', false);
        $response->assertSee('actual-queue.webp', false);
        $response->assertSee('actual-admin.webp', false);
        $response->assertSee('landing-home.css', false);
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

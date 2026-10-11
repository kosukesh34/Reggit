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
        $response->assertSee('v3/hero-cafe.webp', false);
        $response->assertSee('v3/scene-member.webp', false);
        $response->assertSee('v3/scene-order.webp', false);
        $response->assertSee('v3/scene-queue.webp', false);
        $response->assertSee('v3/scene-reserve.webp', false);
        $response->assertSee('v3/scene-retail.webp', false);
        $response->assertSee('landing-home.css', false);
        $response->assertSee('LINEミニアプリ');
        $response->assertSee('iOS / Android');
        $response->assertSee('スタンプ');
        $response->assertSee('shots/home.webp', false);
        $response->assertSee('shots/member.webp', false);
        $response->assertSee('shots/order.webp', false);
        $response->assertSee('shots/queue.webp', false);
        $response->assertSee('shots/reserve.webp', false);
        $response->assertSee('shots/admin.webp', false);
        $response->assertSee('デモ店舗の実際の表示');
        $response->assertSee('メール認証へ進む');
        $response->assertSee('導入の流れ');
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

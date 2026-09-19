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
        $response->assertSee('actual-home.webp', false);
        $response->assertSee('actual-order.webp', false);
        $response->assertSee('actual-member.webp', false);
        $response->assertSee('actual-point.webp', false);
        $response->assertSee('actual-coupon.webp', false);
        $response->assertSee('actual-queue.webp', false);
        $response->assertSee('actual-admin.webp', false);
        $response->assertSee('brand-02.svg', false);
        $response->assertSee('lp-marquee', false);
        $response->assertSee('LINEミニアプリ');
        $response->assertSee('ネイティブアプリ');
        $response->assertSee('スタンプ');
        $response->assertSee('MacBook 14インチ想定');
        $response->assertSee('16:10ディスプレイ比率');
    }

    public function test_root_ログイン済みは管理画面へリダイレクトすること(): void
    {
        $project = Project::factory()->create(['project_key' => 'store_a', 'is_active' => true]);
        $admin = AdminUser::factory()->create([
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);
        $admin->projects()->sync([$project->id]);

        $response = $this->actingAs($admin, 'admin')->get('/');

        $response->assertRedirect(route('admin.root'));
    }
}

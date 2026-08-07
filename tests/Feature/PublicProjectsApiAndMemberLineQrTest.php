<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Project;
use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicProjectsApiAndMemberLineQrTest extends TestCase
{
    use RefreshDatabase;

    public function test_projects_api_有効なプロジェクト一覧を返すこと(): void
    {
        Project::create([
            'project_key' => 'shop_a',
            'name' => '店舗A',
            'description' => '',
            'is_active' => true,
        ]);
        Project::create([
            'project_key' => 'shop_b',
            'name' => '店舗B',
            'description' => '',
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/v1/projects');

        $response->assertOk();
        $keys = collect($response->json('projects'))->pluck('project_key')->all();
        $this->assertContains('shop_a', $keys);
        $this->assertNotContains('shop_b', $keys);
        $response->assertJsonFragment([
            'project_key' => 'shop_a',
            'name' => '店舗A',
        ]);
    }

    public function test_members_lin_e招待_q_rの_ur_lが表示されること(): void
    {
        $project = Project::create([
            'project_key' => 'shop_qr',
            'name' => 'QR店舗',
            'description' => '',
            'is_active' => true,
        ]);
        ShopSettingStore::setLineFriendAddUrl('https://line.me/R/ti/p/@example', $project->id);
        $admin = AdminUser::factory()->create([
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.members', ['project_key' => $project->project_key]));

        $response->assertOk();
        $response->assertSee('LINE招待QR', false);
        $response->assertSee('QRを表示', false);
        $response->assertSee('member-line-qr-toggle', false);
        $response->assertSee('https://line.me/R/ti/p/@example', false);
    }
}

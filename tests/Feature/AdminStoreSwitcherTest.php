<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStoreSwitcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_複数店舗があるとき店舗切り替えに検索欄があること(): void
    {
        $alpha = Project::factory()->create([
            'name' => 'アルファカフェ',
            'project_key' => 'alpha-cafe',
        ]);
        $beta = Project::factory()->create([
            'name' => 'ベータ食堂',
            'project_key' => 'beta-diner',
        ]);
        $admin = AdminUser::factory()->create([
            'role' => AdminUser::ROLE_SYSTEM_ADMIN,
            'project_id' => null,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard', ['project_key' => $alpha->project_key]));

        $response->assertStatus(200);
        $response->assertSee('placeholder="店舗名で検索"', false);
        $response->assertSee('アルファカフェ', false);
        $response->assertSee('ベータ食堂', false);
        $response->assertSee(route('admin.dashboard', ['project_key' => $beta->project_key]), false);
        $response->assertDontSee('sidebar-store-select', false);
    }

    public function test_店舗が1件のときは店舗切り替え検索が出ないこと(): void
    {
        $project = Project::factory()->create(['name' => '単独店舗']);
        $admin = AdminUser::factory()->create([
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);
        $admin->projects()->sync([$project->id]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard', ['project_key' => $project->project_key]));

        $response->assertStatus(200);
        $response->assertSee('単独店舗', false);
        $response->assertDontSee('placeholder="店舗名で検索"', false);
    }
}

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
        $response->assertSee('screen-register.png', false);
        $response->assertSee('会員登録画面');
        $response->assertSee('会員登録');
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

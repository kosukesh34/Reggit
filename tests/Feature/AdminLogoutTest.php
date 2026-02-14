<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLogoutTest extends TestCase
{
    use RefreshDatabase;

    public function testStoreAdminCanLogout(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
        $admin = AdminUser::factory()->create([
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.logout'));

        $response->assertRedirect();
        $this->assertGuest('admin');
    }

    public function testSystemAdminCanLogout(): void
    {
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_SYSTEM_ADMIN]);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('system-admin.logout'));

        $response->assertRedirect();
        $this->assertGuest('admin');
    }
}

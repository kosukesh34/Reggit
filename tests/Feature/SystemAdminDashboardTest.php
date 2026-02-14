<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemAdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function testSystemAdminDashboardIsAccessible(): void
    {
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_SYSTEM_ADMIN]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('system-admin.dashboard'));

        $response->assertStatus(200);
    }
}

<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Order;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrdersTest extends TestCase
{
    use RefreshDatabase;

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }

    private function createAdmin(Project $project): AdminUser
    {
        return AdminUser::factory()->create([
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);
    }

    public function test_orders_注文一覧にアクセスできること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.orders', ['project_key' => $project->project_key]));

        $response->assertStatus(200);
    }

    public function test_orders_user_idで会員の注文だけに絞り込めること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $user = User::factory()->create(['project_id' => $project->id]);
        $other = User::factory()->create(['project_id' => $project->id]);
        Order::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'order_number' => 'mine-order',
            'order_type' => 'take_out',
            'total_amount' => 1000,
            'status' => 'completed',
            'payment_method' => 'cash',
        ]);
        Order::create([
            'project_id' => $project->id,
            'user_id' => $other->id,
            'order_number' => 'other-order',
            'order_type' => 'take_out',
            'total_amount' => 2000,
            'status' => 'completed',
            'payment_method' => 'cash',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.orders', [
                'project_key' => $project->project_key,
                'user_id' => $user->id,
                'status' => 'all',
            ]));

        $response->assertStatus(200);
        $response->assertSee('mine-order');
        $response->assertDontSee('other-order');
        $response->assertSee('会員の絞り込みを解除');
    }
}

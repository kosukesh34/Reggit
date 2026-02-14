<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Product;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductsTest extends TestCase
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

    public function testProductsListIsAccessible(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        Product::factory()->create(['project_id' => $project->id]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.products', ['project_key' => $project->project_key]));

        $response->assertStatus(200);
    }

    public function testProductCreatePageIsAccessible(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.products.create', ['project_key' => $project->project_key]));

        $response->assertStatus(200);
    }
}

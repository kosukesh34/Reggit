<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAnalyticsControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createProject(): Project
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true, 'plan' => 'premium']
        );
        if ($project->plan !== 'premium') {
            $project->update(['plan' => 'premium']);
        }

        return $project;
    }

    private function createAdmin(int $projectId): AdminUser
    {
        return AdminUser::create([
            'login_id' => 'admin_analytics',
            'email' => 'admin_analytics@example.com',
            'password' => 'password',
            'name' => 'Admin',
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $projectId,
        ]);
    }

    public function test_analytics_分析ページにアクセスできること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project->id);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.analytics', ['project_key' => $project->project_key]));

        $response->assertStatus(200);
    }
}

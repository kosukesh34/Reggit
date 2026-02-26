<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Member;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMembersTest extends TestCase
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

    public function test_members_会員一覧にアクセスできること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $user = User::factory()->create(['project_id' => $project->id]);
        Member::factory()->create(['user_id' => $user->id, 'project_id' => $project->id]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.members', ['project_key' => $project->project_key]));

        $response->assertStatus(200);
    }
}

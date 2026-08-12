<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Project;
use App\Models\StampCard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStampsTest extends TestCase
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

    public function test_stamps_スタンプ一覧にアクセスできること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.stamps', ['project_key' => $project->project_key]));

        $response->assertStatus(200);
    }

    public function test_有効なスタンプカードの付与qr画面を表示できること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $card = StampCard::factory()->create([
            'project_id' => $project->id,
            'name' => '来店スタンプ',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.stamps.grant-qr', [
                'project_key' => $project->project_key,
                'id' => $card->id,
            ]));

        $response->assertOk()
            ->assertSee('スタンプ付与QR')
            ->assertSee('来店スタンプ')
            ->assertSee('grant=', false);
    }
}

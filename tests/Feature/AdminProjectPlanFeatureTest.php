<?php

namespace Tests\Feature;

use App\Enums\ProjectPlan;
use App\Models\AdminUser;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProjectPlanFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_distributionプランはpremium機能のルートに403でアクセスできないこと(): void
    {
        $project = Project::factory()->create([
            'project_key' => 'dist-project',
            'plan' => ProjectPlan::DISTRIBUTION,
        ]);
        $admin = AdminUser::factory()->create([
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.lottery', ['project_key' => $project->project_key]));

        $response->assertStatus(403);
    }

    public function test_premiumプランはlottery設定にアクセスできること(): void
    {
        $project = Project::factory()->create([
            'project_key' => 'premium-project',
            'plan' => ProjectPlan::PREMIUM,
        ]);
        $admin = AdminUser::factory()->create([
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.lottery', ['project_key' => $project->project_key]));

        $response->assertStatus(200);
    }
}

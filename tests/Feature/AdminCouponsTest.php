<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Coupon;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCouponsTest extends TestCase
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

    public function test_coupons_クーポン一覧にアクセスできること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.coupons', ['project_key' => $project->project_key]));

        $response->assertStatus(200);
    }

    public function test_bulk_issue_一括配布画面にアクセスできること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.coupons.bulk-issue', ['project_key' => $project->project_key]));

        $response->assertStatus(200)
            ->assertSee('既存クーポンを使う')
            ->assertSee('配信種別');
    }

    public function test_quick_create_クーポンを作成できること(): void
    {
        config(['smaregi.contract_id' => '']);

        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.coupons.quick-create', ['project_key' => $project->project_key]), [
                'code' => 'ADMIN100',
                'name' => '管理画面クーポン',
                'type' => 'fixed',
                'value' => 100,
            ]);

        $response->assertRedirect(route('admin.coupons.bulk-issue', ['project_key' => $project->project_key, 'coupon_source' => 'existing']));
        $this->assertDatabaseHas('coupons', [
            'project_id' => $project->id,
            'code' => 'ADMIN100',
            'name' => '管理画面クーポン',
        ]);
        $coupon = Coupon::where('project_id', $project->id)->where('code', 'ADMIN100')->first();
        $this->assertNotNull($coupon);
        $this->assertSame(Coupon::USE_DISPLAY_BARCODE, $coupon->use_display);
    }
}

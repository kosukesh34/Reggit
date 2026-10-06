<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\AdminUser;
use App\Models\Member;
use App\Models\Order;
use App\Models\Project;
use App\Models\Settings\ProjectRfmSetting;
use App\Models\User;
use App\Services\Admin\RfmAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRfmSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_defaults_and_tenant_settings_are_used_by_analysis(): void
    {
        $this->travelTo(now()->startOfDay());
        $project = Project::factory()->create();
        $other = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        $order = Order::create([
            'project_id' => $project->id, 'user_id' => $user->id,
            'order_number' => 'RFM-1', 'order_type' => 'take_out',
            'total_amount' => 10000, 'status' => OrderStatus::COMPLETED,
        ]);
        $order->forceFill(['created_at' => now()->subDays(30)])->save();
        $service = app(RfmAnalysisService::class);
        $this->assertSame('active', $service->analyzeByProject($project->id)->first()['segment']);
        ProjectRfmSetting::updateOrCreate(['project_id' => $project->id], [
            'recency_active_days' => 30, 'recency_sleeping_days' => 90,
            'frequency_high' => 1, 'monetary_high' => 10000,
        ]);
        $this->assertSame('champion', $service->analyzeByProject($project->id)->first()['segment']);
        $this->assertSame(5, ProjectRfmSetting::forProject($other->id)->frequency_high);
        foreach ([31 => 'at_risk', 90 => 'at_risk', 91 => 'dormant'] as $days => $segment) {
            $order->forceFill(['created_at' => now()->subDays($days)])->save();
            $this->assertSame($segment, $service->analyzeByProject($project->id)->first()['segment']);
        }
        $order->update(['status' => OrderStatus::CANCELLED]);
        $this->assertSame('never', $service->analyzeByProject($project->id)->first()['segment']);
    }

    public function test_admin_can_save_only_their_tenant_and_invalid_thresholds_are_rejected(): void
    {
        $project = Project::factory()->create();
        $other = Project::factory()->create();
        $admin = AdminUser::factory()->create(['project_id' => $project->id, 'role' => AdminUser::ROLE_ADMIN]);
        $url = route('admin.rfm.settings.update', ['project_key' => $project->project_key]);
        $values = ['recency_active_days' => 60, 'recency_sleeping_days' => 120, 'frequency_high' => 10, 'monetary_high' => 20000];
        $this->actingAs($admin, 'admin')->get(route('admin.rfm', ['project_key' => $project->project_key]))
            ->assertOk()->assertSee('RFMの判定基準');
        $this->put($url, $values + ['project_id' => $other->id])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('project_rfm_settings', ['project_id' => $project->id] + $values);
        $this->assertDatabaseMissing('project_rfm_settings', ['project_id' => $other->id]);
        $this->put($url, array_replace($values, ['recency_sleeping_days' => 60, 'frequency_high' => 0, 'monetary_high' => -1]))
            ->assertSessionHasErrors(['recency_sleeping_days', 'frequency_high', 'monetary_high']);
        $this->assertDatabaseHas('project_rfm_settings', ['project_id' => $project->id] + $values);
        $staff = AdminUser::factory()->create(['project_id' => $project->id, 'role' => AdminUser::ROLE_STAFF]);
        $this->actingAs($staff, 'admin')->put($url, $values)->assertForbidden();
    }
}

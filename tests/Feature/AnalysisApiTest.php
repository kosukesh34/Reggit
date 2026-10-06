<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\ApiToken;
use App\Models\Coupon;
use App\Models\Member;
use App\Models\Project;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalysisApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_分析_ap_iが集計を返し配信を実行できること(): void
    {
        $project = Project::factory()->create([
            'project_key' => 'analysis-store',
            'is_active' => true,
            'plan' => 'premium',
            'analysis_api_enabled' => true,
            'analysis_delivery_enabled' => true,
        ]);
        $issued = ApiToken::issue($project, 'analysis', null, 'analysis');
        Member::factory()->create(['project_id' => $project->id]);
        $coupon = Coupon::factory()->create([
            'project_id' => $project->id,
            'is_active' => true,
        ]);

        $summary = $this->withApiToken($issued['plain'])
            ->getJson('/api/v1/'.$project->project_key.'/analysis/summary');
        $summary->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['start', 'end', 'summary', 'chart']]);

        $this->withApiToken($issued['plain'])
            ->getJson('/api/v1/'.$project->project_key.'/analysis/coupons')
            ->assertOk()
            ->assertJsonPath('data.coupons.0.id', $coupon->id)
            ->assertJsonPath('data.coupons.0.name', $coupon->name);

        $delivery = $this->withApiToken($issued['plain'])
            ->postJson('/api/v1/'.$project->project_key.'/analysis/deliveries', [
                'coupon_id' => $coupon->id,
                'send_notification' => false,
            ]);
        $delivery->assertOk()
            ->assertJsonPath('data.issued', 1);

        $this->assertDatabaseHas('member_coupons', [
            'project_id' => $project->id,
            'coupon_id' => $coupon->id,
        ]);

        $this->withApiToken($issued['plain'])
            ->getJson('/api/v1/'.$project->project_key.'/products')
            ->assertForbidden();

        $general = ApiToken::issue($project, 'general');
        $this->withApiToken($general['plain'])
            ->getJson('/api/v1/'.$project->project_key.'/analysis/summary')
            ->assertForbidden();

        $project->update(['analysis_delivery_enabled' => false]);
        $this->withApiToken($issued['plain'])
            ->postJson('/api/v1/'.$project->project_key.'/analysis/deliveries', ['coupon_id' => $coupon->id])
            ->assertForbidden();

        $project->update(['analysis_api_enabled' => false]);
        $this->withApiToken($issued['plain'])
            ->getJson('/api/v1/'.$project->project_key.'/analysis/summary')
            ->assertForbidden();
    }

    private function withApiToken(string $plain): self
    {
        return $this->withHeaders([
            'Authorization' => 'Bearer '.$plain,
            'Accept' => 'application/json',
        ]);
    }

    public function test_legacy_multi_store_analysis_tokens_are_revoked_on_upgrade(): void
    {
        $multiStore = Project::factory()->create();
        $singleStore = Project::factory()->create();
        Store::create(['project_id' => $multiStore->id, 'store_key' => 'branch', 'name' => '支店', 'is_active' => true]);
        $legacy = ApiToken::issue($multiStore, 'legacy', null, 'analysis')['token'];
        $single = ApiToken::issue($singleStore, 'single', null, 'analysis')['token'];
        $general = ApiToken::issue($multiStore, 'general')['token'];
        $migration = require database_path('migrations/2026_10_05_100001_revoke_legacy_multi_store_analysis_tokens.php');
        $migration->up();
        $this->assertFalse($legacy->fresh()->is_active);
        $this->assertTrue($single->fresh()->is_active);
        $this->assertTrue($general->fresh()->is_active);
        $migration->down();
        $this->assertFalse($legacy->fresh()->is_active);
    }

    public function test_分析連携は初期無効で管理画面から有効化と失効ができる(): void
    {
        $project = Project::factory()->create(['project_key' => 'analysis-settings', 'plan' => 'premium']);
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN, 'project_id' => $project->id]);
        $admin->projects()->sync([$project->id]);
        $token = ApiToken::issue($project, 'analysis', null, 'analysis');

        $this->withApiToken($token['plain'])
            ->getJson('/api/v1/'.$project->project_key.'/analysis/summary')
            ->assertForbidden();

        $this->actingAs($admin, 'admin')->post('/admin/'.$project->project_key.'/analysis-deliveries/settings', [
            'analysis_api_enabled' => '1',
            'analysis_delivery_enabled' => '1',
        ])->assertRedirect();
        $this->assertTrue($project->fresh()->analysis_api_enabled);
        $this->assertTrue($project->fresh()->analysis_delivery_enabled);

        $this->post('/admin/'.$project->project_key.'/analysis-deliveries/token')->assertRedirect();
        $this->assertDatabaseHas('api_tokens', ['id' => $token['token']->id, 'is_active' => false]);
        $new = ApiToken::where('project_id', $project->id)->where('scope', 'analysis')->where('is_active', true)->firstOrFail();
        $this->assertNotNull($new->expires_at);

        $this->delete('/admin/'.$project->project_key.'/analysis-deliveries/token/'.$new->id)->assertRedirect();
        $this->assertDatabaseHas('api_tokens', ['id' => $new->id, 'is_active' => false]);
    }
}

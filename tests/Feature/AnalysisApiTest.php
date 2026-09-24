<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Coupon;
use App\Models\Member;
use App\Models\Project;
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
        ]);
        $issued = ApiToken::issue($project, 'analysis');
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
    }

    private function withApiToken(string $plain): self
    {
        return $this->withHeaders([
            'Authorization' => 'Bearer '.$plain,
            'Accept' => 'application/json',
        ]);
    }
}

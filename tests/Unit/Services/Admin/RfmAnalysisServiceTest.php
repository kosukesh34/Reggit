<?php

namespace Tests\Unit\Services\Admin;

use App\Enums\OrderStatus;
use App\Models\Member;
use App\Models\Order;
use App\Models\Project;
use App\Models\User;
use App\Services\Admin\RfmAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RfmAnalysisServiceTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    private RfmAnalysisService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Test', 'description' => 'Test', 'is_active' => true]
        );
        $this->service = new RfmAnalysisService;
    }

    public function test_analyze_by_project_未購買会員はneverセグメントになる(): void
    {
        $user = User::factory()->create(['project_id' => $this->project->id]);
        Member::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        $rows = $this->service->analyzeByProject($this->project->id);

        $this->assertCount(1, $rows);
        $this->assertSame('never', $rows->first()['segment']);
    }

    public function test_analyze_by_project_直近購買会員はactiveセグメントになる(): void
    {
        $user = User::factory()->create(['project_id' => $this->project->id]);
        Member::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $user->id,
            'status' => 'active',
        ]);
        $order = Order::create([
            'project_id' => $this->project->id,
            'user_id' => $user->id,
            'order_number' => 'ORD-'.uniqid(),
            'order_type' => 'take_out',
            'total_amount' => 1000,
            'status' => OrderStatus::COMPLETED,
        ]);
        $order->forceFill(['created_at' => now()->subDays(5)])->save();

        $rows = $this->service->analyzeByProject($this->project->id);

        $this->assertCount(1, $rows);
        $this->assertSame('active', $rows->first()['segment']);
    }

    public function test_analyze_by_project_90日以上未購買会員はdormantになる(): void
    {
        $user = User::factory()->create(['project_id' => $this->project->id]);
        Member::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $user->id,
            'status' => 'active',
        ]);
        $order = Order::create([
            'project_id' => $this->project->id,
            'user_id' => $user->id,
            'order_number' => 'ORD-'.uniqid(),
            'order_type' => 'take_out',
            'total_amount' => 500,
            'status' => OrderStatus::COMPLETED,
        ]);
        $order->forceFill(['created_at' => now()->subDays(120)])->save();

        $rows = $this->service->analyzeByProject($this->project->id);

        $this->assertCount(1, $rows);
        $this->assertSame('dormant', $rows->first()['segment']);
    }

    public function test_analyze_by_project_他プロジェクトの会員は含まれない(): void
    {
        $otherProject = Project::firstOrCreate(
            ['project_key' => 'other'],
            ['name' => 'Other', 'description' => 'Other', 'is_active' => true]
        );
        $user = User::factory()->create(['project_id' => $otherProject->id]);
        Member::factory()->create([
            'project_id' => $otherProject->id,
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        $rows = $this->service->analyzeByProject($this->project->id);

        $this->assertCount(0, $rows);
    }

    public function test_segment_label_全セグメントにラベルが返る(): void
    {
        foreach (['champion', 'loyal', 'active', 'at_risk', 'dormant', 'never'] as $seg) {
            $this->assertNotEmpty($this->service->segmentLabel($seg));
        }
    }
}

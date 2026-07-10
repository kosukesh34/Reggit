<?php

namespace Tests\Unit\Services\Admin;

use App\Enums\OrderStatus;
use App\Models\Member;
use App\Models\Order;
use App\Models\Project;
use App\Models\User;
use App\Services\Admin\SalesSegmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesSegmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    private SalesSegmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Test', 'description' => 'Test', 'is_active' => true]
        );
        $this->service = app(SalesSegmentService::class);
    }

    public function test_get_member_ids_by_sales_criteria_購入額以上で該当会員を返す(): void
    {
        $user1 = User::factory()->create(['project_id' => $this->project->id]);
        $user2 = User::factory()->create(['project_id' => $this->project->id]);
        $member1 = Member::factory()->create(['project_id' => $this->project->id, 'user_id' => $user1->id]);
        Member::factory()->create(['project_id' => $this->project->id, 'user_id' => $user2->id]);

        Order::create([
            'project_id' => $this->project->id,
            'user_id' => $user1->id,
            'order_number' => 'ORD-'.uniqid(),
            'order_type' => 'take_out',
            'total_amount' => 5000,
            'status' => OrderStatus::COMPLETED,
        ]);
        Order::create([
            'project_id' => $this->project->id,
            'user_id' => $user2->id,
            'order_number' => 'ORD-'.uniqid(),
            'order_type' => 'take_out',
            'total_amount' => 2000,
            'status' => OrderStatus::COMPLETED,
        ]);

        $ids = $this->service->getMemberIdsBySalesCriteria($this->project->id, 30, 3000, false);

        $this->assertCount(1, $ids);
        $this->assertTrue($ids->contains($member1->id));
    }

    public function test_get_member_ids_by_sales_criteria_未購入で該当会員を返す(): void
    {
        $user1 = User::factory()->create(['project_id' => $this->project->id]);
        $user2 = User::factory()->create(['project_id' => $this->project->id]);
        $memberNoOrder = Member::factory()->create(['project_id' => $this->project->id, 'user_id' => $user1->id]);
        Member::factory()->create(['project_id' => $this->project->id, 'user_id' => $user2->id]);

        Order::create([
            'project_id' => $this->project->id,
            'user_id' => $user2->id,
            'order_number' => 'ORD-'.uniqid(),
            'order_type' => 'take_out',
            'total_amount' => 1000,
            'status' => OrderStatus::COMPLETED,
        ]);

        $ids = $this->service->getMemberIdsBySalesCriteria($this->project->id, 30, null, true);

        $this->assertCount(1, $ids);
        $this->assertTrue($ids->contains($memberNoOrder->id));
    }
}

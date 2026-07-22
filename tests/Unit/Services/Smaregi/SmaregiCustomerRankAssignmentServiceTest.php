<?php

namespace Tests\Unit\Services\Smaregi;

use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Models\Member;
use App\Models\Project;
use App\Models\ShopSetting;
use App\Models\User;
use App\Services\Smaregi\SmaregiApiService;
use App\Services\Smaregi\SmaregiCustomerRankAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SmaregiCustomerRankAssignmentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_assignRankToCustomer_スマレジ顧客へrankを送信すること(): void
    {
        $project = Project::factory()->create();
        ShopSetting::setMemberRankSettings([
            ['key' => 'bronze', 'label' => 'ブロンズ', 'min_points' => 0, 'order' => 1, 'is_active' => true, 'smaregi_rank_id' => '1'],
            ['key' => 'silver', 'label' => 'シルバー', 'min_points' => 50, 'order' => 2, 'is_active' => true, 'smaregi_rank_id' => '2'],
        ], $project->id);

        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'rank' => 'bronze',
            'smaregi_customer_id' => 'cust-100',
        ]);

        $smaregiApi = Mockery::mock(SmaregiApiService::class);
        $smaregiApi->shouldReceive('getContractIdForProject')->once()->with($project->id)->andReturn('contract-1');
        $smaregiApi->shouldReceive('clearLastErrorDetail')->once();
        $smaregiApi->shouldReceive('updateCustomer')
            ->once()
            ->with('contract-1', 'cust-100', ['rank' => '2'])
            ->andReturn(['customerId' => 'cust-100']);

        $service = new SmaregiCustomerRankAssignmentService(
            $smaregiApi,
            app(ShopSettingRepository::class),
        );
        $service->assignRankToCustomer($member, 'silver');

        $this->addToAssertionCount(1);
    }

    public function test_assignRankToCustomer_smaregi_customer_idが無い場合は送信しないこと(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'smaregi_customer_id' => null,
        ]);

        $smaregiApi = Mockery::mock(SmaregiApiService::class);
        $smaregiApi->shouldNotReceive('updateCustomer');

        $service = new SmaregiCustomerRankAssignmentService(
            $smaregiApi,
            app(ShopSettingRepository::class),
        );
        $service->assignRankToCustomer($member, 'silver');

        $this->addToAssertionCount(1);
    }
}

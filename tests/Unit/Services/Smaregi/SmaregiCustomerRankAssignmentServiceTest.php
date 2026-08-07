<?php

namespace Tests\Unit\Services\Smaregi;

use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Models\Member;
use App\Models\Project;
use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\User;
use App\Services\Smaregi\SmaregiApiService;
use App\Services\Smaregi\SmaregiCustomerRankAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SmaregiCustomerRankAssignmentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_assign_rank_to_customer_スマレジ顧客へrankと付与率を送信すること(): void
    {
        $project = Project::factory()->create();
        ShopSettingStore::setValue('point_earn_rate', 100, 'integer', $project->id);
        ShopSettingStore::setMemberRankSettings([
            ['key' => 'bronze', 'label' => 'ブロンズ', 'min_points' => 0, 'order' => 1, 'is_active' => true, 'smaregi_rank_id' => '1', 'point_earn_rate' => 100],
            ['key' => 'silver', 'label' => 'シルバー', 'min_points' => 50, 'order' => 2, 'is_active' => true, 'smaregi_rank_id' => '2', 'point_earn_rate' => 50],
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
            ->with('contract-1', 'cust-100', [
                'rank' => '2',
                'pointGivingUnitPrice' => '50',
                'pointGivingUnit' => '1',
            ])
            ->andReturn(['customerId' => 'cust-100']);

        $service = new SmaregiCustomerRankAssignmentService(
            $smaregiApi,
            app(ShopSettingRepository::class),
        );
        $service->assignRankToCustomer($member, 'silver');

        $this->addToAssertionCount(1);
    }

    public function test_assign_rank_to_customer_smaregi_customer_idが無い場合は送信しないこと(): void
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

    public function test_sync_point_rates_for_project_連携済み会員へ付与率を反映すること(): void
    {
        $project = Project::factory()->create();
        ShopSettingStore::setValue('point_earn_rate', 100, 'integer', $project->id);
        ShopSettingStore::setMemberRankSettings([
            ['key' => 'gold', 'label' => 'ゴールド', 'min_points' => 100, 'order' => 1, 'is_active' => true, 'smaregi_rank_id' => '9', 'point_earn_rate' => 80],
        ], $project->id);

        $user = User::factory()->create(['project_id' => $project->id]);
        Member::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'rank' => 'gold',
            'smaregi_customer_id' => 'cust-200',
        ]);

        $smaregiApi = Mockery::mock(SmaregiApiService::class);
        $smaregiApi->shouldReceive('getContractIdForProject')->andReturn('contract-1');
        $smaregiApi->shouldReceive('clearLastErrorDetail')->once();
        $smaregiApi->shouldReceive('updateCustomer')
            ->once()
            ->with('contract-1', 'cust-200', [
                'rank' => '9',
                'pointGivingUnitPrice' => '80',
                'pointGivingUnit' => '1',
            ])
            ->andReturn(['customerId' => 'cust-200']);

        $service = new SmaregiCustomerRankAssignmentService(
            $smaregiApi,
            app(ShopSettingRepository::class),
        );
        $service->syncPointRatesForProject((int) $project->id);

        $this->addToAssertionCount(1);
    }
}

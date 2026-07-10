<?php

namespace Tests\Unit\Infrastructure\Events;

use App\Domain\Member\Events\PointsEarned;
use App\Domain\Member\Repositories\MemberRepository;
use App\Infrastructure\Events\SyncPointsEarnedToSmaregiListener;
use App\Models\Member;
use App\Models\Project;
use App\Models\User;
use App\Services\Smaregi\SmaregiApiService;
use App\Services\Smaregi\SmaregiPointsSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SyncPointsEarnedToSmaregiListenerTest extends TestCase
{
    use RefreshDatabase;

    private function makeListener(SmaregiApiService $smaregiApi): SyncPointsEarnedToSmaregiListener
    {
        return new SyncPointsEarnedToSmaregiListener(
            new SmaregiPointsSyncService(app(MemberRepository::class), $smaregiApi),
        );
    }

    public function test_負のポイント差分もスマレジへ同期すること(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'smaregi_customer_id' => 'cust-1',
        ]);

        $smaregiApi = Mockery::mock(SmaregiApiService::class);
        $smaregiApi->shouldReceive('syncCustomerPointsForProject')
            ->once()
            ->with((int) $project->id, 'cust-1', -50)
            ->andReturn(['customerId' => 'cust-1']);

        $this->makeListener($smaregiApi)->handle(new PointsEarned(
            (int) $member->id,
            (int) $project->id,
            -50,
            99,
        ));
    }

    public function test_ゼロポイントは同期しないこと(): void
    {
        $smaregiApi = Mockery::mock(SmaregiApiService::class);
        $smaregiApi->shouldNotReceive('syncCustomerPointsForProject');

        $this->makeListener($smaregiApi)->handle(new PointsEarned(1, 1, 0, 99));
    }

    public function test_ベストエフォート指定時は同期失敗でも例外にしないこと(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'smaregi_customer_id' => 'cust-1',
        ]);

        $smaregiApi = Mockery::mock(SmaregiApiService::class);
        $smaregiApi->shouldReceive('syncCustomerPointsForProject')
            ->once()
            ->andReturn(null);

        $this->makeListener($smaregiApi)->handle(new PointsEarned(
            (int) $member->id,
            (int) $project->id,
            10,
            null,
            isBestEffortSync: true,
        ));

        $this->assertTrue(true);
    }

    public function test_手動付与の同期失敗は例外になること(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'smaregi_customer_id' => 'cust-1',
        ]);

        $smaregiApi = Mockery::mock(SmaregiApiService::class);
        $smaregiApi->shouldReceive('syncCustomerPointsForProject')
            ->once()
            ->andReturn(null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('スマレジへのポイント反映に失敗しました');

        $this->makeListener($smaregiApi)->handle(new PointsEarned(
            (int) $member->id,
            (int) $project->id,
            10,
            null,
        ));
    }
}

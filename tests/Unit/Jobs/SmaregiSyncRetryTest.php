<?php

namespace Tests\Unit\Jobs;

use App\Domain\Order\Repositories\OrderRepository;
use App\Jobs\SyncOrderPointsToSmaregiJob;
use App\Services\Smaregi\SmaregiPointsSyncService;
use Illuminate\Bus\UniqueLock;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class SmaregiSyncRetryTest extends TestCase
{
    public function test_incomplete_sync_releases_the_same_job_while_its_unique_lock_is_held(): void
    {
        config(['queue.default' => 'database', 'cache.default' => 'array']);
        Queue::fake();
        $repository = Mockery::mock(OrderRepository::class);
        $repository->shouldReceive('isSmaregiPointsSynced')->andReturn(false);
        $points = Mockery::mock(SmaregiPointsSyncService::class);
        $points->shouldReceive('syncOrderNetPoints')->once()->with(1, 2, 3)->andReturn(false);

        $job = new SyncOrderPointsToSmaregiJob(1, 2, 3);
        $lock = new UniqueLock(Cache::store());
        $this->assertTrue($lock->acquire($job));
        $job->withFakeQueueInteractions();
        $job->job->attempts = 2;
        $job->handle($points, $repository);
        $job->assertReleased(120);
        $this->assertFalse($lock->acquire($job));
        $lock->release($job);
        Queue::assertNothingPushed();
    }
}

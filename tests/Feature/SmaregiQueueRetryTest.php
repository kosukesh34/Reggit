<?php

namespace Tests\Feature;

use App\Domain\Order\Repositories\OrderRepository;
use App\Jobs\SyncOrderPointsToSmaregiJob;
use App\Jobs\SyncOrderTransactionToSmaregiJob;
use App\Services\Smaregi\SmaregiOrderTransactionOutboundService;
use App\Services\Smaregi\SmaregiPointsSyncService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\WorkerOptions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SmaregiQueueRetryTest extends TestCase
{
    use RefreshDatabase;

    public static function syncJobs(): array
    {
        return [
            'points' => [SyncOrderPointsToSmaregiJob::class, SmaregiPointsSyncService::class, 'syncOrderNetPoints', 'isSmaregiPointsSynced'],
            'transaction' => [SyncOrderTransactionToSmaregiJob::class, SmaregiOrderTransactionOutboundService::class, 'syncOrderTransaction', 'isSmaregiTransactionSynced'],
        ];
    }

    #[DataProvider('syncJobs')]
    public function test_incomplete_sync_retries_the_existing_unique_job(string $jobClass, string $serviceClass, string $syncMethod, string $syncedMethod): void
    {
        Schema::create('retry_test_jobs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });
        config([
            'queue.default' => 'retry-test',
            'queue.connections.retry-test' => [
                'driver' => 'database', 'connection' => 'mysql',
                'table' => 'retry_test_jobs', 'queue' => 'default',
                'retry_after' => 90, 'after_commit' => false,
            ],
        ]);
        $this->mock(OrderRepository::class, function ($mock) use ($syncedMethod): void {
            $mock->shouldReceive($syncedMethod)->andReturn(false);
            $mock->shouldReceive('hasSmaregiLayaway')->andReturn(false);
        });
        $this->mock($serviceClass, function ($mock) use ($syncMethod): void {
            $mock->shouldReceive($syncMethod)->with(11, 22, 33)->twice()->andReturn(false, true);
        });

        $job = new $jobClass(11, 22, 33);
        $job->backoff = [0];
        dispatch($job);
        $worker = app('queue.worker');
        $options = new WorkerOptions(sleep: 0);
        $worker->runNextJob('retry-test', 'default', $options);

        $this->assertSame(1, DB::table('retry_test_jobs')->count());
        $this->assertSame(1, (int) DB::table('retry_test_jobs')->value('attempts'));
        $this->assertNull(DB::table('retry_test_jobs')->value('reserved_at'));

        // The existing job still owns the unique lock, blocking duplicates.
        dispatch(new $jobClass(11, 22, 33));
        $this->assertSame(1, DB::table('retry_test_jobs')->count());
        $worker->runNextJob('retry-test', 'default', $options);
        $this->assertSame(0, DB::table('retry_test_jobs')->count());
    }

    #[DataProvider('syncJobs')]
    public function test_sync_connection_keeps_incomplete_sync_from_failing_order_requests(string $jobClass, string $serviceClass, string $syncMethod, string $syncedMethod): void
    {
        config(['queue.default' => 'sync']);
        $repository = $this->mock(OrderRepository::class, function ($mock) use ($syncedMethod): void {
            $mock->shouldReceive($syncedMethod)->andReturn(false);
            $mock->shouldReceive('hasSmaregiLayaway')->andReturn(false);
        });
        $sync = $this->mock($serviceClass, function ($mock) use ($syncMethod): void {
            $mock->shouldReceive($syncMethod)->once()->andReturn(false);
        });

        (new $jobClass(11, 22, 33))->handle($sync, $repository);
        $this->assertTrue(true);
    }
}

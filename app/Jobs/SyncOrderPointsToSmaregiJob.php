<?php

namespace App\Jobs;

use App\Domain\Order\Repositories\OrderRepository;
use App\Services\Smaregi\SmaregiPointsSyncService;
use App\Util\LogHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * 注文ポイントのスマレジ同期リトライ（Cash 等、Confirm/Webhook 再送が無い経路向け）。
 * sync ドライバでは再帰ディスパッチしない（注文作成リクエストを壊さない）。
 */
class SyncOrderPointsToSmaregiJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [60, 120, 300, 600, 900];

    public int $uniqueFor = 3600;

    public function __construct(
        public int $orderId,
        public int $projectId,
        public int $userId,
        public int $attempt = 1,
    ) {}

    public function uniqueId(): string
    {
        return 'smaregi-order-points-sync:'.$this->orderId.':'.$this->projectId;
    }

    public function handle(SmaregiPointsSyncService $pointsSync, OrderRepository $orderRepository): void
    {
        try {
            if ($orderRepository->isSmaregiPointsSynced($this->orderId, $this->projectId)) {
                return;
            }

            if ($pointsSync->syncOrderNetPoints($this->orderId, $this->projectId, $this->userId)) {
                return;
            }

            if ($orderRepository->isSmaregiPointsSynced($this->orderId, $this->projectId)) {
                return;
            }

            LogHelper::warning('[Smaregi] order points sync job incomplete', [
                'order_id' => $this->orderId,
                'project_id' => $this->projectId,
                'user_id' => $this->userId,
                'attempt' => $this->job?->attempts() ?? $this->attempt,
            ]);
        } catch (\Throwable $e) {
            LogHelper::error('[Smaregi] order points sync job threw', [
                'order_id' => $this->orderId,
                'project_id' => $this->projectId,
                'error' => $e->getMessage(),
                'attempt' => $this->job?->attempts() ?? $this->attempt,
            ]);
            if (config('queue.default') === 'sync') {
                return;
            }
            throw $e;
        }

        // sync では例外・再ディスパッチしない（注文作成の HTTP を壊さない）
        if (config('queue.default') === 'sync') {
            return;
        }

        throw new \RuntimeException('Smaregi synchronization is incomplete.');
    }
}

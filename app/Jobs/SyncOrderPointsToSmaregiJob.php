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
use RuntimeException;

/**
 * 注文ポイントのスマレジ同期リトライ（Cash 等、Confirm/Webhook 再送が無い経路向け）。
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
    ) {}

    public function uniqueId(): string
    {
        return 'smaregi-order-points-sync:'.$this->orderId.':'.$this->projectId;
    }

    public function handle(SmaregiPointsSyncService $pointsSync, OrderRepository $orderRepository): void
    {
        if ($orderRepository->isSmaregiPointsSynced($this->orderId, $this->projectId)) {
            return;
        }

        if ($pointsSync->syncOrderNetPoints($this->orderId, $this->projectId, $this->userId)) {
            return;
        }

        if ($orderRepository->isSmaregiPointsSynced($this->orderId, $this->projectId)) {
            return;
        }

        LogHelper::warning('[Smaregi] order points sync job incomplete, will retry', [
            'order_id' => $this->orderId,
            'project_id' => $this->projectId,
            'user_id' => $this->userId,
            'attempt' => $this->attempts(),
        ]);

        throw new RuntimeException('Smaregi order points sync not completed');
    }
}

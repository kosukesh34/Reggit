<?php

namespace App\Jobs;

use App\Domain\Order\Repositories\OrderRepository;
use App\Services\Smaregi\SmaregiOrderTransactionOutboundService;
use App\Util\LogHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * 注文取引のスマレジ同期リトライ。
 * finalize=false: 作成時（現金=取置き / Stripe=通常販売）
 * finalize=true: 現金取置きの引取確定
 */
class SyncOrderTransactionToSmaregiJob implements ShouldBeUnique, ShouldQueue
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
        public bool $finalize = false,
    ) {}

    public function uniqueId(): string
    {
        $suffix = $this->finalize ? 'finalize' : 'create';

        return 'smaregi-order-transaction-sync:'.$this->orderId.':'.$this->projectId.':'.$suffix;
    }

    public function handle(
        SmaregiOrderTransactionOutboundService $transactionSync,
        OrderRepository $orderRepository,
    ): void {
        try {
            if ($orderRepository->isSmaregiTransactionSynced($this->orderId, $this->projectId)) {
                return;
            }

            $succeeded = $this->finalize
                ? $transactionSync->finalizeCashLayaway($this->orderId, $this->projectId, $this->userId)
                : $transactionSync->syncOrderTransaction($this->orderId, $this->projectId, $this->userId);

            if ($succeeded) {
                return;
            }

            if ($orderRepository->isSmaregiTransactionSynced($this->orderId, $this->projectId)) {
                return;
            }

            if (! $this->finalize && $orderRepository->hasSmaregiLayaway($this->orderId, $this->projectId)) {
                return;
            }

            LogHelper::warning('[Smaregi] order transaction sync job incomplete', [
                'order_id' => $this->orderId,
                'project_id' => $this->projectId,
                'user_id' => $this->userId,
                'attempt' => $this->attempts(),
                'finalize' => $this->finalize,
            ]);
        } catch (\Throwable $e) {
            LogHelper::error('[Smaregi] order transaction sync job threw', [
                'order_id' => $this->orderId,
                'project_id' => $this->projectId,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
                'finalize' => $this->finalize,
            ]);
            if (config('queue.default') === 'sync') {
                return;
            }
            throw $e;
        }

        if (config('queue.default') === 'sync') {
            return;
        }

        if ($this->attempts() >= $this->tries) {
            return;
        }

        $delaySeconds = $this->backoff[$this->attempts() - 1] ?? 900;
        $this->release($delaySeconds);
    }
}

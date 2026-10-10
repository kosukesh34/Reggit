<?php

namespace App\Infrastructure\Events;

use App\Domain\Order\Events\OrderTransactionSyncRequested;
use App\Domain\Shared\DomainEventDispatcher\DomainEventListener;
use App\Services\Smaregi\SmaregiOrderTransactionOutboundService;
use Illuminate\Support\Facades\DB;

/**
 * 注文をスマレジ取引として登録する。
 * OrderTransactionSyncRequested のみを処理する（ポイント同期とは独立）。
 */
class SyncOrderTransactionToSmaregiListener implements DomainEventListener
{
    public function __construct(
        private SmaregiOrderTransactionOutboundService $transactionSync,
    ) {}

    public function handle(object $event): void
    {
        if ($event instanceof OrderTransactionSyncRequested === false) {
            return;
        }

        DB::afterCommit(function () use ($event): void {
            $this->transactionSync->syncOrderTransactionWithRetry(
                $event->orderId, $event->projectId, $event->userId,
            );
        });
    }
}

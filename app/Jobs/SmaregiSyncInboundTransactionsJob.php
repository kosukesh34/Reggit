<?php

namespace App\Jobs;

use App\Services\Smaregi\SmaregiTransactionInboundSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Webhook 応答後に POS 取引を来店・購入履歴へ取り込む。
 */
class SmaregiSyncInboundTransactionsJob
{
    use Dispatchable, Queueable, SerializesModels;

    /**
     * @param  list<string>  $transactionHeadIds
     */
    public function __construct(
        public string $contractId,
        public array $transactionHeadIds,
    ) {}

    public function handle(SmaregiTransactionInboundSyncService $syncService): void
    {
        Log::info('[Smaregi] Inbound transaction sync job started', [
            'smaregi_contract_id' => $this->contractId,
            'transaction_head_id_count' => count($this->transactionHeadIds),
            'transaction_head_ids' => $this->transactionHeadIds,
        ]);

        $syncService->syncByTransactionHeadIds($this->contractId, $this->transactionHeadIds);

        Log::info('[Smaregi] Inbound transaction sync job finished', [
            'smaregi_contract_id' => $this->contractId,
        ]);
    }
}

<?php

namespace App\Jobs;

use App\Services\Smaregi\SmaregiCustomerPointsInboundSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Webhook 応答後に実行し、POS で変わった会員情報・ポイントを会員に反映する（ShouldQueue なし＝同一プロセスで実行）。
 */
class SmaregiSyncInboundCustomerPointsJob
{
    use Dispatchable, Queueable, SerializesModels;

    /**
     * @param  list<string>  $customerIds
     */
    public function __construct(
        public string $contractId,
        public array $customerIds,
        public ?string $action
    ) {}

    public function handle(SmaregiCustomerPointsInboundSyncService $syncService): void
    {
        Log::info('[Smaregi] Inbound customer sync job started（Webhook 後処理開始）', [
            'smaregi_contract_id' => $this->contractId,
            'action' => $this->action,
            'customer_id_count' => count($this->customerIds),
            'customer_ids' => $this->customerIds,
        ]);

        $syncService->syncByCustomerIds($this->contractId, $this->customerIds, $this->action);

        Log::info('[Smaregi] Inbound customer sync job finished（Webhook 後処理完了）', [
            'smaregi_contract_id' => $this->contractId,
        ]);
    }
}

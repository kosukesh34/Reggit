<?php

namespace App\Jobs;

use App\Services\Smaregi\SmaregiApiService;
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
     * @param  array<string, int>  $pointsByCustomerId  取引の totalPoint など、Webhook 起因のポイントヒント
     * @param  list<string>  $transactionHeadIds  customerIds 未解決時に Job 内で解決する
     */
    public function __construct(
        public string $contractId,
        public array $customerIds,
        public ?string $action,
        public array $pointsByCustomerId = [],
        public array $transactionHeadIds = [],
    ) {}

    public function handle(
        SmaregiCustomerPointsInboundSyncService $syncService,
        SmaregiApiService $smaregiApi,
    ): void {
        $customerIds = $this->customerIds;
        $pointsByCustomerId = $this->pointsByCustomerId;

        if ($customerIds === [] && $this->transactionHeadIds !== []) {
            $resolved = $smaregiApi->resolveCustomersFromTransactionHeads(
                $this->contractId,
                $this->transactionHeadIds,
            );
            $customerIds = $resolved['customer_ids'];
            $pointsByCustomerId = $resolved['points_by_customer_id'];
            if ($customerIds === []) {
                Log::warning('[Smaregi] Inbound customer sync job: transactionHeadIds から会員IDを解決できませんでした', [
                    'smaregi_contract_id' => $this->contractId,
                    'transaction_head_ids' => $this->transactionHeadIds,
                ]);

                return;
            }
            Log::info('[Smaregi] Inbound customer sync job: transactionHeadIds から会員IDを解決', [
                'smaregi_contract_id' => $this->contractId,
                'transaction_head_ids' => $this->transactionHeadIds,
                'customer_ids' => $customerIds,
                'points_by_customer_id' => $pointsByCustomerId,
            ]);
        }

        if ($customerIds === []) {
            return;
        }

        Log::info('[Smaregi] Inbound customer sync job started（Webhook 後処理開始）', [
            'smaregi_contract_id' => $this->contractId,
            'action' => $this->action,
            'customer_id_count' => count($customerIds),
            'customer_ids' => $customerIds,
            'points_hint_count' => count($pointsByCustomerId),
        ]);

        $syncService->syncByCustomerIds(
            $this->contractId,
            $customerIds,
            $this->action,
            $pointsByCustomerId,
        );

        Log::info('[Smaregi] Inbound customer sync job finished（Webhook 後処理完了）', [
            'smaregi_contract_id' => $this->contractId,
        ]);
    }
}

<?php

namespace App\Services\Smaregi;

use App\Jobs\SmaregiSyncInboundCustomerPointsJob;
use App\UseCases\Smaregi\Data\SmaregiWebhookInput;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class SmaregiWebhookNotificationService
{
    /** @var list<string> */
    private const INBOUND_SYNC_EVENTS = [
        'pos:customers',
        'pos:customer-points',
        'pos:transactions',
    ];

    public function __construct(
        private SmaregiApiService $smaregiApi,
    ) {}

    /** @return array{status: int, body: string} */
    public function handleNotification(SmaregiWebhookInput $input, bool $verifyWebhookSecret): array
    {
        if ($input->isGetOrHead()) {
            Log::info('[Smaregi] Webhook GET/HEAD 受信（接続確認など）。200 空ボディ');

            return ['status' => 200, 'body' => ''];
        }

        $eventFromHeader = $input->eventHeader;
        $eventFromBody = $input->input('event');
        $event = is_string($eventFromHeader) ? trim($eventFromHeader) : '';
        if ($event === '' && is_string($eventFromBody)) {
            $event = trim($eventFromBody);
        }
        if ($event === '') {
            Log::warning('[Smaregi] Webhook missing event (header smaregi-event or body event)');

            return ['status' => 400, 'body' => ''];
        }

        if ($verifyWebhookSecret) {
            $secret = config('smaregi.webhook_secret');
            if ($secret !== '' && $secret !== null) {
                $received = $input->secretHeader;
                if ($received === null || $received === '' || $received !== $secret) {
                    Log::warning('[Smaregi] Webhook secret mismatch');

                    return ['status' => 401, 'body' => ''];
                }
            }
        }

        $contractIdHeader = $input->contractIdHeader;
        $contractIdBody = $input->input('contractId');
        $contractId = (is_string($contractIdHeader) && $contractIdHeader !== '')
            ? $contractIdHeader
            : (is_string($contractIdBody) ? $contractIdBody : null);
        $action = $input->input('action');
        $action = is_string($action) ? $action : null;
        $eventResolvedFrom = (is_string($eventFromHeader) && trim($eventFromHeader) !== '')
            ? 'header_smaregi-event'
            : 'body_event';

        Log::info('[Smaregi] Webhook HTTP request accepted (受信)', [
            'smaregi_contract_id' => $contractId,
            'event' => $event,
            'event_resolved_from' => $eventResolvedFrom,
            'action' => $action,
            'json_body_keys' => array_keys($input->body),
        ]);

        if ($contractId !== null && $contractId !== '') {
            $customerIds = $this->collectCustomerIdsForInboundSync($input, strtolower($event), $contractId);
            if ($customerIds !== []) {
                $this->dispatchInboundCustomerSync($contractId, $customerIds, $action);
            }
        }

        return ['status' => 200, 'body' => ''];
    }

    /**
     * @return list<string>
     */
    private function collectCustomerIdsForInboundSync(SmaregiWebhookInput $input, string $event, string $contractId): array
    {
        if (! in_array($event, self::INBOUND_SYNC_EVENTS, true)) {
            return [];
        }

        if ($event === 'pos:customers') {
            $customerIds = $this->extractStringIds($input->input('customerIds'));
            if ($customerIds === []) {
                Log::info('[Smaregi] Webhook pos:customers だが customerIds が空のため会員同期はスキップ', [
                    'smaregi_contract_id' => $contractId,
                    'action' => $input->input('action'),
                ]);
            }

            return $customerIds;
        }

        $customerIds = $this->extractStringIds($input->input('customerIds'));
        if ($customerIds !== []) {
            return $customerIds;
        }

        $transactionHeadIds = $this->extractStringIds($input->input('transactionHeadIds'));
        if ($transactionHeadIds === []) {
            Log::info('[Smaregi] Webhook '.$event.' だが customerIds / transactionHeadIds が空のため会員同期はスキップ', [
                'smaregi_contract_id' => $contractId,
                'action' => $input->input('action'),
            ]);

            return [];
        }

        $resolved = $this->smaregiApi->resolveCustomerIdsFromTransactionHeads($contractId, $transactionHeadIds);
        if ($resolved === []) {
            Log::warning('[Smaregi] Webhook '.$event.' transactionHeadIds から会員IDを解決できませんでした', [
                'smaregi_contract_id' => $contractId,
                'transaction_head_ids' => $transactionHeadIds,
            ]);
        } else {
            Log::info('[Smaregi] Webhook '.$event.' transactionHeadIds から会員IDを解決', [
                'smaregi_contract_id' => $contractId,
                'transaction_head_ids' => $transactionHeadIds,
                'customer_ids' => $resolved,
            ]);
        }

        return $resolved;
    }

    /**
     * @param  list<string>  $customerIds
     */
    private function dispatchInboundCustomerSync(string $contractId, array $customerIds, ?string $action): void
    {
        if (config('smaregi.webhook_run_sync_inline', false)) {
            Log::info('[Smaregi] Webhook 会員同期ジョブを即時実行（inline）', [
                'smaregi_contract_id' => $contractId,
                'action' => $action,
                'customer_id_count' => count($customerIds),
                'customer_ids_sample' => array_slice($customerIds, 0, 30),
            ]);
            Bus::dispatchSync(new SmaregiSyncInboundCustomerPointsJob($contractId, $customerIds, $action));
        } else {
            Log::info('[Smaregi] Webhook 会員同期ジョブをレスポンス送信後に実行予定', [
                'smaregi_contract_id' => $contractId,
                'action' => $action,
                'customer_id_count' => count($customerIds),
                'customer_ids_sample' => array_slice($customerIds, 0, 30),
            ]);
            SmaregiSyncInboundCustomerPointsJob::dispatch($contractId, $customerIds, $action)->afterResponse();
        }
    }

    /**
     * @param  array<int|string, string|int>|null  $raw
     * @return list<string>
     */
    private function extractStringIds($raw): array
    {
        if (! is_array($raw)) {
            return [];
        }
        $ids = [];
        foreach ($raw as $v) {
            if (is_string($v)) {
                $t = trim($v);
                if ($t !== '') {
                    $ids[] = $t;
                }
            } elseif (is_int($v)) {
                $ids[] = (string) $v;
            }
        }

        return $ids;
    }
}

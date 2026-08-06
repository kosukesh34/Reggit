<?php

namespace App\Services\Smaregi;

use App\Jobs\SmaregiSyncInboundCustomerPointsJob;
use App\Jobs\SmaregiSyncInboundTransactionsJob;
use App\Enums\SmaregiEvent;
use App\UseCases\Smaregi\Data\SmaregiNotificationInput;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * スマレジ Webhook / IDP 通知の共通処理（ボディ解析・イベント受付・会員同期ジョブ投入）。
 */
class SmaregiWebhookNotificationService
{
    /** @var list<string> */
    private const INBOUND_SYNC_EVENTS = [
        SmaregiEvent::POS_CUSTOMERS,
        SmaregiEvent::POS_CUSTOMER_POINTS,
        SmaregiEvent::POS_TRANSACTIONS,
    ];

    /**
     * @return array{status: int, body: string}
     */
    public function handleNotification(SmaregiNotificationInput $input): array
    {
        if ($input->isGetOrHead()) {
            Log::info('[Smaregi] Webhook GET/HEAD 受信（接続確認など）。200 空ボディ');

            return ['status' => Response::HTTP_OK, 'body' => ''];
        }

        $eventFromHeader = $input->header('smaregi-event');
        $eventFromBody = $input->input('event');
        $event = is_string($eventFromHeader) ? trim($eventFromHeader) : '';
        if ($event === '' && is_string($eventFromBody)) {
            $event = trim($eventFromBody);
        }
        if ($event === '') {
            Log::warning('[Smaregi] Webhook missing event (header smaregi-event or body event)');

            return ['status' => Response::HTTP_BAD_REQUEST, 'body' => ''];
        }

        $contractIdHeader = $input->header('smaregi-contract-id');
        $contractIdBody = $input->input('contractId');
        $contractId = (is_string($contractIdHeader) && $contractIdHeader !== '')
            ? $contractIdHeader
            : (is_string($contractIdBody) ? $contractIdBody : null);
        $body = $input->all();
        $action = isset($body['action']) && is_string($body['action']) ? $body['action'] : null;
        $eventResolvedFrom = (is_string($eventFromHeader) && trim($eventFromHeader) !== '')
            ? 'header_smaregi-event'
            : 'body_event';

        Log::info('[Smaregi] Webhook HTTP request accepted (受信)', [
            'smaregi_contract_id' => $contractId,
            'event' => $event,
            'event_resolved_from' => $eventResolvedFrom,
            'action' => $action,
            'json_body_keys' => array_keys($body),
        ]);

        if ($contractId !== null && $contractId !== '') {
            $eventLower = strtolower($event);
            if ($eventLower === SmaregiEvent::POS_TRANSACTIONS) {
                $this->dispatchInboundTransactionSync(
                    $contractId,
                    $this->extractStringIds($input->input('transactionHeadIds')),
                );
            }

            $targets = $this->collectInboundSyncTargets($input, $eventLower, $contractId);
            if ($targets['customer_ids'] !== [] || $targets['transaction_head_ids'] !== []) {
                $this->dispatchInboundCustomerSync(
                    $contractId,
                    $targets['customer_ids'],
                    $action,
                    $targets['points_by_customer_id'],
                    $targets['transaction_head_ids'],
                );
            }
        }

        return ['status' => Response::HTTP_OK, 'body' => ''];
    }

    /**
     * Webhook 応答前に API 呼び出し（リトライ待機含む）をしない。
     * transactionHeadIds からの会員解決は Job 側で行う。
     *
     * @return array{
     *     customer_ids: list<string>,
     *     points_by_customer_id: array<string, int>,
     *     transaction_head_ids: list<string>
     * }
     */
    private function collectInboundSyncTargets(SmaregiNotificationInput $input, string $event, string $contractId): array
    {
        $empty = [
            'customer_ids' => [],
            'points_by_customer_id' => [],
            'transaction_head_ids' => [],
        ];
        if (! in_array($event, self::INBOUND_SYNC_EVENTS, true)) {
            return $empty;
        }

        if ($event === SmaregiEvent::POS_CUSTOMERS) {
            $customerIds = $this->extractStringIds($input->input('customerIds'));
            if ($customerIds === []) {
                Log::info('[Smaregi] Webhook pos:customers だが customerIds が空のため会員同期はスキップ', [
                    'smaregi_contract_id' => $contractId,
                    'action' => $input->input('action'),
                ]);
            }

            return [
                'customer_ids' => $customerIds,
                'points_by_customer_id' => [],
                'transaction_head_ids' => [],
            ];
        }

        $customerIds = $this->extractStringIds($input->input('customerIds'));
        if ($customerIds !== []) {
            return [
                'customer_ids' => $customerIds,
                'points_by_customer_id' => [],
                'transaction_head_ids' => [],
            ];
        }

        $transactionHeadIds = $this->extractStringIds($input->input('transactionHeadIds'));
        if ($transactionHeadIds === []) {
            Log::info('[Smaregi] Webhook '.$event.' だが customerIds / transactionHeadIds が空のため会員同期はスキップ', [
                'smaregi_contract_id' => $contractId,
                'action' => $input->input('action'),
            ]);

            return $empty;
        }

        return [
            'customer_ids' => [],
            'points_by_customer_id' => [],
            'transaction_head_ids' => $transactionHeadIds,
        ];
    }

    /**
     * @param  list<string>  $transactionHeadIds
     */
    private function dispatchInboundTransactionSync(string $contractId, array $transactionHeadIds): void
    {
        if ($transactionHeadIds === []) {
            Log::info('[Smaregi] Webhook pos:transactions だが transactionHeadIds が空のため取引履歴同期はスキップ', [
                'smaregi_contract_id' => $contractId,
            ]);

            return;
        }

        if (config('smaregi.webhook_run_sync_inline', false)) {
            Log::info('[Smaregi] Webhook 取引履歴同期ジョブを即時実行（inline）', [
                'smaregi_contract_id' => $contractId,
                'transaction_head_id_count' => count($transactionHeadIds),
                'transaction_head_ids_sample' => array_slice($transactionHeadIds, 0, 30),
            ]);
            Bus::dispatchSync(new SmaregiSyncInboundTransactionsJob($contractId, $transactionHeadIds));
        } else {
            Log::info('[Smaregi] Webhook 取引履歴同期ジョブをレスポンス送信後に実行予定', [
                'smaregi_contract_id' => $contractId,
                'transaction_head_id_count' => count($transactionHeadIds),
                'transaction_head_ids_sample' => array_slice($transactionHeadIds, 0, 30),
            ]);
            SmaregiSyncInboundTransactionsJob::dispatch($contractId, $transactionHeadIds)->afterResponse();
        }
    }

    /**
     * @param  list<string>  $customerIds
     * @param  array<string, int>  $pointsByCustomerId
     * @param  list<string>  $transactionHeadIds
     */
    private function dispatchInboundCustomerSync(
        string $contractId,
        array $customerIds,
        ?string $action,
        array $pointsByCustomerId = [],
        array $transactionHeadIds = [],
    ): void {
        if (config('smaregi.webhook_run_sync_inline', false)) {
            Log::info('[Smaregi] Webhook 会員同期ジョブを即時実行（inline）', [
                'smaregi_contract_id' => $contractId,
                'action' => $action,
                'customer_id_count' => count($customerIds),
                'customer_ids_sample' => array_slice($customerIds, 0, 30),
                'transaction_head_id_count' => count($transactionHeadIds),
            ]);
            Bus::dispatchSync(new SmaregiSyncInboundCustomerPointsJob(
                $contractId,
                $customerIds,
                $action,
                $pointsByCustomerId,
                $transactionHeadIds,
            ));
        } else {
            Log::info('[Smaregi] Webhook 会員同期ジョブをレスポンス送信後に実行予定', [
                'smaregi_contract_id' => $contractId,
                'action' => $action,
                'customer_id_count' => count($customerIds),
                'customer_ids_sample' => array_slice($customerIds, 0, 30),
                'transaction_head_id_count' => count($transactionHeadIds),
            ]);
            SmaregiSyncInboundCustomerPointsJob::dispatch(
                $contractId,
                $customerIds,
                $action,
                $pointsByCustomerId,
                $transactionHeadIds,
            )->afterResponse();
        }
    }

    /**
     * @return list<string>
     */
    private function extractStringIds(mixed $raw): array
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

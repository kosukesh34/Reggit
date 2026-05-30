<?php

namespace App\UseCases\Smaregi;

use App\Jobs\SmaregiSyncInboundCustomerPointsJob;
use App\Services\Smaregi\SmaregiApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class SmaregiWebhookHandleUseCase
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

    public function prepareRequestBody(Request $request): void
    {
        if (! $request->isMethod('POST')) {
            return;
        }
        $raw = $request->getContent();
        if ($raw === '') {
            return;
        }
        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return;
        }
        $contentType = strtolower((string) $request->header('Content-Type', ''));
        if ($contentType !== '' && ! str_contains($contentType, 'json')) {
            Log::info('[Smaregi] JSON ボディを raw からマージ（Content-Type が application/json 以外）', [
                'content_type' => $request->header('Content-Type'),
            ]);
        }
        $request->merge($decoded);
    }

    /**
     * POST /api/smaregi/webhook 用。Webhook 用カスタムヘッダー（シークレット）を検証する。
     *
     * @return array{status: int, body: string}
     */
    public function handle(Request $request): array
    {
        return $this->handleNotification($request, true);
    }

    /**
     * 利用者契約通知先 URL へ誤って届いた POS 系通知などを処理する場合に false（シークレット検証なし）。
     * 公式では Webhook 送信先と別 URL だが、同一 URL 設定の店舗向け。
     *
     * @return array{status: int, body: string}
     */
    public function handleNotification(Request $request, bool $verifyWebhookSecret): array
    {
        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            Log::info('[Smaregi] Webhook GET/HEAD 受信（接続確認など）。200 空ボディ');

            return ['status' => 200, 'body' => ''];
        }

        $this->prepareRequestBody($request);

        $eventFromHeader = $request->header('smaregi-event');
        $eventFromBody = $request->input('event');
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
            $secretHeaderName = config('smaregi.webhook_secret_header');
            if ($secret !== '' && $secret !== null && $secretHeaderName !== '') {
                $received = $request->header($secretHeaderName);
                if ($received === null || $received === '' || $received !== $secret) {
                    Log::warning('[Smaregi] Webhook secret mismatch');

                    return ['status' => 401, 'body' => ''];
                }
            }
        }

        $contractIdHeader = $request->header('smaregi-contract-id');
        $contractIdBody = $request->input('contractId');
        $contractId = (is_string($contractIdHeader) && $contractIdHeader !== '')
            ? $contractIdHeader
            : (is_string($contractIdBody) ? $contractIdBody : null);
        $body = $request->all();
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
            $customerIds = $this->collectCustomerIdsForInboundSync($request, strtolower($event), $contractId);
            if ($customerIds !== []) {
                $this->dispatchInboundCustomerSync($contractId, $customerIds, $action);
            }
        }

        return ['status' => 200, 'body' => ''];
    }

    /**
     * @return list<string>
     */
    private function collectCustomerIdsForInboundSync(Request $request, string $event, string $contractId): array
    {
        if (! in_array($event, self::INBOUND_SYNC_EVENTS, true)) {
            return [];
        }

        if ($event === 'pos:customers') {
            $customerIds = $this->extractStringIds($request->input('customerIds'));
            if ($customerIds === []) {
                Log::info('[Smaregi] Webhook pos:customers だが customerIds が空のため会員同期はスキップ', [
                    'smaregi_contract_id' => $contractId,
                    'action' => $request->input('action'),
                ]);
            }

            return $customerIds;
        }

        $customerIds = $this->extractStringIds($request->input('customerIds'));
        if ($customerIds !== []) {
            return $customerIds;
        }

        $transactionHeadIds = $this->extractStringIds($request->input('transactionHeadIds'));
        if ($transactionHeadIds === []) {
            Log::info('[Smaregi] Webhook '.$event.' だが customerIds / transactionHeadIds が空のため会員同期はスキップ', [
                'smaregi_contract_id' => $contractId,
                'action' => $request->input('action'),
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

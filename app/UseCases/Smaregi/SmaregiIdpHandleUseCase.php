<?php

namespace App\UseCases\Smaregi;

use App\Enums\SmaregiEvent;
use App\Models\SmaregiContract;
use App\Services\Smaregi\SmaregiWebhookNotificationService;
use App\UseCases\Smaregi\Data\SmaregiNotificationInput;
use App\Util\StrHelper;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SmaregiIdpHandleUseCase
{
    public function __construct(
        private SmaregiWebhookNotificationService $webhookNotificationService,
    ) {}

    /**
     * 利用者契約通知を処理し、契約を保存する。
     * 3秒以内に応答するため重い処理は行わない。
     *
     * @return array{status: int, body: string}
     */
    public function handle(SmaregiNotificationInput $input): array
    {
        if ($input->isGetOrHead()) {
            Log::info('[Smaregi] IDP GET/HEAD 受信（接続確認など）');

            return ['status' => Response::HTTP_OK, 'body' => ''];
        }

        $eventFromHeader = $input->header('smaregi-event');
        $eventFromBody = $input->input('event');
        $eventRaw = is_string($eventFromHeader) && trim($eventFromHeader) !== ''
            ? trim($eventFromHeader)
            : (is_string($eventFromBody) ? trim($eventFromBody) : '');
        $contractIdFromHeader = $input->header('smaregi-contract-id');
        $eventNormalized = $eventRaw !== '' ? strtolower($eventRaw) : '';

        if ($eventRaw === '') {
            Log::warning('[Smaregi] IDP missing event');

            return ['status' => Response::HTTP_BAD_REQUEST, 'body' => ''];
        }

        if ($eventNormalized !== strtolower(SmaregiEvent::APP_SUBSCRIPTION)) {
            if (str_starts_with($eventNormalized, 'pos:')) {
                Log::info('[Smaregi] 利用者契約通知先URLで POS 系イベントを受信。Webhook と同じ処理に振り替えます', [
                    'event' => $eventRaw,
                ]);

                return $this->webhookNotificationService->handleNotification($input);
            }

            Log::info('[Smaregi] IDP ignored non-subscription event (use Webhook URL for pos: etc.)', [
                'event' => $eventRaw,
                'has_contract_id_header' => $contractIdFromHeader !== null && $contractIdFromHeader !== '',
            ]);

            return ['status' => Response::HTTP_OK, 'body' => ''];
        }

        $contractId = $contractIdFromHeader ?? $input->input('contractId');
        if (! is_string($contractId) || StrHelper::isEmptyStringOrNull($contractId)) {
            Log::warning('[Smaregi] IDP missing contract_id');

            return ['status' => Response::HTTP_BAD_REQUEST, 'body' => ''];
        }

        $action = $input->input('action');
        $status = SmaregiContract::deriveStatusFromAction(is_string($action) ? $action : '');

        try {
            SmaregiContract::updateOrCreate(
                ['contract_id' => $contractId],
                [
                    'client_id' => $input->input('clientId'),
                    'plan' => $input->input('plan'),
                    'options' => $input->input('options'),
                    'last_action' => is_string($action) ? $action : null,
                    'status' => $status,
                    'last_notified_at' => now(),
                ]
            );
        } catch (\Throwable $e) {
            Log::error('[Smaregi] IDP save failed', [
                'smaregi_contract_id' => $contractId,
                'error' => $e->getMessage(),
            ]);

            return ['status' => Response::HTTP_INTERNAL_SERVER_ERROR, 'body' => ''];
        }

        return ['status' => Response::HTTP_OK, 'body' => ''];
    }
}

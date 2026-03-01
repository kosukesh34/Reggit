<?php

namespace App\UseCases\Smaregi;

use App\Enums\SmaregiEvent;
use App\Models\SmaregiContract;
use App\Util\StrHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SmaregiIdpHandleUseCase
{
    /**
     * 利用者契約通知を処理し、契約を保存する。
     * 3秒以内に応答するため重い処理は行わない。
     *
     * @return array{status: int, body: string}
     */
    public function handle(Request $request): array
    {
        $event = $request->header('smaregi-event');
        $contractIdFromHeader = $request->header('smaregi-contract-id');
        $eventNormalized = $event !== null ? strtolower($event) : '';

        if ($eventNormalized !== strtolower(SmaregiEvent::APP_SUBSCRIPTION)) {
            Log::warning('[Smaregi] IDP invalid event', [
                'event' => $event,
                'has_contract_id_header' => $contractIdFromHeader !== null && $contractIdFromHeader !== '',
            ]);

            return ['status' => 400, 'body' => ''];
        }

        $contractId = $contractIdFromHeader ?? $request->input('contractId');
        if (! is_string($contractId) || StrHelper::isEmptyStringOrNull($contractId)) {
            Log::warning('[Smaregi] IDP missing contract_id');

            return ['status' => 400, 'body' => ''];
        }

        $action = $request->input('action');
        $status = SmaregiContract::deriveStatusFromAction(is_string($action) ? $action : '');

        try {
            SmaregiContract::updateOrCreate(
                ['contract_id' => $contractId],
                [
                    'client_id' => $request->input('clientId'),
                    'plan' => $request->input('plan'),
                    'options' => $request->input('options'),
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

            return ['status' => 500, 'body' => ''];
        }

        return ['status' => 200, 'body' => ''];
    }
}

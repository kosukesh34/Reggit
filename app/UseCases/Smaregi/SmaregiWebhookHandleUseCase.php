<?php

namespace App\UseCases\Smaregi;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SmaregiWebhookHandleUseCase
{
    /**
     * Webhook通知を受理する。3秒以内に応答するため処理は最小限。
     *
     * @return array{status: int, body: string}
     */
    public function handle(Request $request): array
    {
        $event = $request->header('smaregi-event');
        if ($event === null || $event === '') {
            Log::warning('[Smaregi] Webhook missing smaregi-event');

            return ['status' => 400, 'body' => ''];
        }

        $secret = config('smaregi.webhook_secret');
        $secretHeaderName = config('smaregi.webhook_secret_header');
        if ($secret !== '' && $secret !== null && $secretHeaderName !== '') {
            $received = $request->header($secretHeaderName);
            if ($received === null || $received === '' || $received !== $secret) {
                Log::warning('[Smaregi] Webhook secret mismatch');

                return ['status' => 401, 'body' => ''];
            }
        }

        $contractId = $request->header('smaregi-contract-id');
        $body = $request->all();
        $action = isset($body['action']) && is_string($body['action']) ? $body['action'] : null;

        Log::info('[Smaregi] Webhook received', [
            'smaregi_contract_id' => $contractId,
            'event' => $event,
            'action' => $action,
        ]);

        return ['status' => 200, 'body' => ''];
    }
}

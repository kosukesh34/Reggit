<?php

namespace App\UseCases\Smaregi;

use App\Services\Smaregi\SmaregiWebhookNotificationService;
use App\UseCases\Smaregi\Data\SmaregiNotificationInput;

class SmaregiWebhookHandleUseCase
{
    public function __construct(
        private SmaregiWebhookNotificationService $webhookNotificationService,
    ) {}

    /**
     * POST /api/smaregi/webhook 用。Webhook 用カスタムヘッダー（シークレット）を検証する。
     *
     * @return array{status: int, body: string}
     */
    public function handle(SmaregiNotificationInput $input): array
    {
        return $this->webhookNotificationService->handleNotification($input, true);
    }
}

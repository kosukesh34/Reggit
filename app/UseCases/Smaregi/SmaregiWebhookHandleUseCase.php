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
     * @return array{status: int, body: string}
     */
    public function handle(SmaregiNotificationInput $input): array
    {
        return $this->webhookNotificationService->handleNotification($input);
    }
}

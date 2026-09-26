<?php

namespace App\UseCases\Smaregi;

use App\Services\Smaregi\Data\SmaregiNotificationInput;
use App\Services\Smaregi\SmaregiWebhookNotificationService;

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

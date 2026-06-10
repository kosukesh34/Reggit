<?php

namespace App\UseCases\Smaregi;

use App\Services\Smaregi\SmaregiWebhookNotificationService;
use App\UseCases\Smaregi\Data\SmaregiWebhookInput;

class SmaregiWebhookHandleUseCase
{
    public function __construct(
        private SmaregiWebhookNotificationService $notificationService,
    ) {}

    /** @return array{status: int, body: string} */
    public function handle(SmaregiWebhookInput $input): array
    {
        return $this->notificationService->handleNotification($input, true);
    }
}

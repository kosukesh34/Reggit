<?php

namespace App\UseCases\Admin\Queue;

use App\Services\Admin\QueueAdminService;

class AdminQueueUpdateStatusUseCase
{
    private QueueAdminService $queueAdminService;

    public function __construct(QueueAdminService $queueAdminService)
    {
        $this->queueAdminService = $queueAdminService;
    }

    public function handle(int $id, string $status): void
    {
        $this->queueAdminService->updateStatus($id, $status);
    }
}

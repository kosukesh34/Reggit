<?php

namespace App\UseCases\Admin\Queue;

use App\Services\Admin\QueueAdminService;
use App\UseCases\Admin\Queue\Data\AdminQueueIndexOutput;

class AdminQueueIndexUseCase
{
    private const PER_PAGE = 30;

    private QueueAdminService $queueAdminService;

    public function __construct(QueueAdminService $queueAdminService)
    {
        $this->queueAdminService = $queueAdminService;
    }

    public function handle(int $projectId): AdminQueueIndexOutput
    {
        $entries = $this->queueAdminService->getPaginatedList($projectId, self::PER_PAGE);
        $waitingCount = $this->queueAdminService->getWaitingCount($projectId);
        $estimatedWaitMinutes = $this->queueAdminService->getEstimatedWaitMinutes($projectId);
        $waitingQueueNumbers = $this->queueAdminService->getWaitingQueueNumbers($projectId);

        return new AdminQueueIndexOutput(
            $entries,
            $waitingCount,
            $estimatedWaitMinutes,
            $waitingQueueNumbers
        );
    }
}

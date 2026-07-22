<?php

namespace App\UseCases\Queue;

use App\Enums\QueueStatus;
use App\Models\QueueEntry as QueueEntryModel;
use App\Services\Admin\QueueAdminService;
use App\UseCases\Queue\Data\QueueEntry;
use App\UseCases\Queue\Data\QueueEntryFactory;
use App\Util\AdminContext;

/** 呼び出し済みエントリへの再通知。 */
class QueueRecallUseCase
{
    public function __construct(
        private QueueStatsService $statsService,
        private QueueEntryFactory $entryFactory,
        private QueueAdminService $queueAdminService,
    ) {}

    public function handle(int $entryId): ?QueueEntry
    {
        $projectId = AdminContext::getCurrentProjectId() ?? 1;
        $entry = QueueEntryModel::query()
            ->where('project_id', $projectId)
            ->whereKey($entryId)
            ->first();

        if ($entry === null) {
            return null;
        }

        if ($entry->status !== QueueStatus::CALLED) {
            throw new \InvalidArgumentException('呼び出し済みの受付のみ再呼び出しできます');
        }

        $entry->called_at = now();
        $entry->save();

        $this->queueAdminService->notifyCalled((int) $entry->id);

        $stats = $this->statsService->getStats($projectId, $entry->queue_number);

        return $this->entryFactory->create($entry->fresh(['user.devices']), $stats);
    }
}

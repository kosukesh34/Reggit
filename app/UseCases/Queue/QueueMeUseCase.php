<?php

namespace App\UseCases\Queue;

use App\Models\QueueEntry as QueueEntryModel;
use App\Services\UserService;
use App\UseCases\Queue\Data\QueueEntry;
use App\UseCases\Queue\Data\QueueEntryFactory;
use App\UseCases\Queue\Data\QueueMeOutput;
use App\Util\AdminContext;
use App\Enums\QueueStatus;
use Illuminate\Http\Request;

class QueueMeUseCase
{
    public function __construct(
        private UserService $userService,
        private QueueStatsService $statsService,
        private QueueEntryFactory $entryFactory
    ) {
    }

    public function handle(Request $request): QueueMeOutput
    {
        $user = $this->userService->getOrCreateUser($request);
        $member = $user->member;

        $entry = $this->getLatestEntry($user->id, $member?->id);
        $projectId = $entry ? $entry->project_id : ($member ? $member->project_id : (AdminContext::getCurrentProjectId() ?? 1));
        $stats = $this->statsService->getStats($projectId, $entry?->queue_number);

        if ($entry === null) {
            return new QueueMeOutput(null, $stats['waiting_count'], $stats['estimated_wait_minutes']);
        }

        $queueEntry = $this->entryFactory->create($entry, $stats);
        return new QueueMeOutput($queueEntry, $stats['waiting_count'], $stats['estimated_wait_minutes']);
    }

    private function getLatestEntry(int $userId, ?int $memberId): ?QueueEntryModel
    {
        $entry = QueueEntryModel::where('user_id', $userId)
            ->whereIn('status', [QueueStatus::WAITING, QueueStatus::CALLED])
            ->orderBy('created_at', 'desc')
            ->first();
        if ($entry !== null) {
            return $entry;
        }
        if ($memberId === null) {
            return null;
        }
        return QueueEntryModel::where('member_id', $memberId)
            ->whereIn('status', [QueueStatus::WAITING, QueueStatus::CALLED])
            ->orderBy('created_at', 'desc')
            ->first();
    }
}

<?php

namespace App\UseCases\Queue;

use App\Models\QueueEntry;
use App\Services\UserService;
use App\UseCases\Queue\Dto\QueueEntryDto;
use App\UseCases\Queue\Dto\QueueEntryDtoFactory;
use App\UseCases\Queue\Dto\QueueMeResultDto;
use App\Util\AdminContext;
use App\Enums\QueueStatus;
use Illuminate\Http\Request;

class QueueMeUseCase
{
    public function __construct(
        private UserService $userService,
        private QueueStatsService $statsService,
        private QueueEntryDtoFactory $dtoFactory
    ) {
    }

    public function handle(Request $request): QueueMeResultDto
    {
        $user = $this->userService->getOrCreateUser($request);
        $member = $user->member;

        $entry = $this->getLatestEntry($user->id, $member?->id);
        $projectId = $entry ? $entry->project_id : ($member ? $member->project_id : (AdminContext::getCurrentProjectId() ?? 1));
        $stats = $this->statsService->getStats($projectId, $entry?->queue_number);

        if ($entry === null) {
            return new QueueMeResultDto(null, $stats['waiting_count'], $stats['estimated_wait_minutes']);
        }

        $dto = $this->dtoFactory->create($entry, $stats);
        return new QueueMeResultDto($dto, $stats['waiting_count'], $stats['estimated_wait_minutes']);
    }

    private function getLatestEntry(int $userId, ?int $memberId): ?QueueEntry
    {
        $entry = QueueEntry::where('user_id', $userId)
            ->whereIn('status', [QueueStatus::WAITING, QueueStatus::CALLED])
            ->orderBy('created_at', 'desc')
            ->first();
        if ($entry !== null) {
            return $entry;
        }
        if ($memberId === null) {
            return null;
        }
        return QueueEntry::where('member_id', $memberId)
            ->whereIn('status', [QueueStatus::WAITING, QueueStatus::CALLED])
            ->orderBy('created_at', 'desc')
            ->first();
    }
}

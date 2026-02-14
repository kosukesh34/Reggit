<?php

namespace App\UseCases\Queue;

use App\Enums\QueueStatus;
use App\Models\QueueEntry;
use App\Services\UserService;
use App\UseCases\Queue\Dto\QueueEntryDto;
use App\UseCases\Queue\Dto\QueueEntryDtoFactory;
use App\Util\AdminContext;
use App\Util\StrHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class QueueStoreUseCase
{
    public function __construct(
        private UserService $userService,
        private QueueStatsService $statsService,
        private QueueEntryDtoFactory $dtoFactory
    ) {
    }

    public function handle(Request $request, int $partySize, ?string $guestName, ?string $guestPhone): QueueEntryDto
    {
        $user = $this->userService->getOrCreateUser($request);
        $member = $user->member;

        $projectId = $member ? $member->project_id : (AdminContext::getCurrentProjectId() ?? 1);
        $lineUserId = $request->header('X-Line-User-Id');

        if (!StrHelper::isEmptyStringOrNull($lineUserId)) {
            $existing = $this->getActiveEntryForUser($user->id, $projectId);
            if ($existing !== null) {
                $stats = $this->statsService->getStats($projectId, $existing->queue_number);
                if (StrHelper::isEmptyStringOrNull($existing->qr_token)) {
                    $existing->update(['qr_token' => $this->generateQrToken()]);
                }
                $existing->refresh();
                return $this->dtoFactory->create($existing, $stats);
            }
        }

        $nextNumber = $this->getNextQueueNumber($projectId);

        $entry = QueueEntry::create([
            'project_id' => $projectId,
            'member_id' => $member?->id,
            'user_id' => StrHelper::isEmptyStringOrNull($lineUserId) ? null : $user->id,
            'guest_name' => $guestName ?? $user->name,
            'guest_phone' => $guestPhone ?? $user->phone,
            'party_size' => $partySize,
            'status' => QueueStatus::WAITING,
            'queue_number' => $nextNumber,
            'qr_token' => $this->generateQrToken(),
        ]);

        $stats = $this->statsService->getStats($projectId, $entry->queue_number);
        $entry->refresh();

        return $this->dtoFactory->create($entry, $stats);
    }

    private function getActiveEntryForUser(int $userId, int $projectId): ?QueueEntry
    {
        return QueueEntry::where('project_id', $projectId)
            ->where('user_id', $userId)
            ->whereIn('status', [QueueStatus::WAITING, QueueStatus::CALLED])
            ->orderByDesc('created_at')
            ->first();
    }

    private function getNextQueueNumber(int $projectId): int
    {
        $maxNumber = QueueEntry::where('project_id', $projectId)
            ->whereDate('created_at', today())
            ->max('queue_number') ?? 0;
        return $maxNumber + 1;
    }

    private function generateQrToken(): string
    {
        $maxAttempts = 5;
        for ($i = 0; $i < $maxAttempts; $i++) {
            $token = Str::random(32);
            if (!QueueEntry::where('qr_token', $token)->exists()) {
                return $token;
            }
        }
        return (string) Str::uuid();
    }
}

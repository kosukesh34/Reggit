<?php

namespace App\UseCases\Queue;

use App\Enums\QueueStatus;
use App\Models\QueueEntry as QueueEntryModel;
use App\Services\UserService;
use App\UseCases\Queue\Data\QueueEntry;
use App\UseCases\Queue\Data\QueueEntryFactory;
use App\Util\AdminContext;
use App\Util\StrHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class QueueStoreUseCase
{
    public function __construct(
        private UserService $userService,
        private QueueStatsService $statsService,
        private QueueEntryFactory $entryFactory
    ) {
    }

    public function handle(Request $request, int $partySize, ?string $guestName, ?string $guestPhone): QueueEntry
    {
        $user = $this->userService->getOrCreateUser($request);
        $member = $user->member;

        $projectId = $member ? $member->project_id : (AdminContext::getCurrentProjectId() ?? 1);
        $lineUserId = $request->header('X-Line-User-Id');
        $effectiveLineUserId = $this->resolveLineUserId($lineUserId, $user->line_user_id);

        if (!StrHelper::isEmptyStringOrNull($effectiveLineUserId)) {
            $existing = $this->getActiveEntryForUser($user->id, $projectId);
            if ($existing !== null) {
                $stats = $this->statsService->getStats($projectId, $existing->queue_number);
                if (StrHelper::isEmptyStringOrNull($existing->qr_token)) {
                    $existing->update(['qr_token' => $this->generateQrToken()]);
                }
                $existing->refresh();
                return $this->entryFactory->create($existing, $stats);
            }
        }

        $nextNumber = $this->getNextQueueNumber($projectId);

        $entry = QueueEntryModel::create([
            'project_id' => $projectId,
            'member_id' => $member?->id,
            'user_id' => StrHelper::isEmptyStringOrNull($effectiveLineUserId) ? null : $user->id,
            'guest_name' => $guestName ?? $user->name,
            'guest_phone' => $guestPhone ?? $user->phone,
            'party_size' => $partySize,
            'status' => QueueStatus::WAITING,
            'queue_number' => $nextNumber,
            'qr_token' => $this->generateQrToken(),
        ]);

        $stats = $this->statsService->getStats($projectId, $entry->queue_number);
        $entry->refresh();

        return $this->entryFactory->create($entry, $stats);
    }

    private function getActiveEntryForUser(int $userId, int $projectId): ?QueueEntryModel
    {
        return QueueEntryModel::where('project_id', $projectId)
            ->where('user_id', $userId)
            ->whereIn('status', [QueueStatus::WAITING, QueueStatus::CALLED])
            ->orderByDesc('created_at')
            ->first();
    }

    private function getNextQueueNumber(int $projectId): int
    {
        $maxNumber = QueueEntryModel::where('project_id', $projectId)
            ->whereDate('created_at', today())
            ->max('queue_number') ?? 0;
        return $maxNumber + 1;
    }

    private function generateQrToken(): string
    {
        $maxAttempts = 5;
        for ($i = 0; $i < $maxAttempts; $i++) {
            $token = Str::random(32);
            if (!QueueEntryModel::where('qr_token', $token)->exists()) {
                return $token;
            }
        }
        return (string) Str::uuid();
    }

    private function resolveLineUserId(?string $headerLineUserId, ?string $userLineUserId): ?string
    {
        if (is_string($headerLineUserId) && $this->isValidLineUserId($headerLineUserId)) {
            return $headerLineUserId;
        }
        if (is_string($userLineUserId) && $this->isValidLineUserId($userLineUserId)) {
            return $userLineUserId;
        }

        return null;
    }

    private function isValidLineUserId(string $lineUserId): bool
    {
        return preg_match('/^U[0-9a-f]{32}$/i', $lineUserId) === 1;
    }
}

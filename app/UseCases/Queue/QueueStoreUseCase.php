<?php

namespace App\UseCases\Queue;

use App\Enums\QueueStatus;
use App\Models\QueueEntry as QueueEntryModel;
use App\UseCases\Queue\Data\QueueEntry;
use App\UseCases\Queue\Data\QueueEntryFactory;
use App\UseCases\Queue\Data\QueueStoreInput;
use App\Util\AdminContext;
use App\Util\StrHelper;
use Illuminate\Support\Str;

class QueueStoreUseCase
{
    public function __construct(
        private QueueStatsService $statsService,
        private QueueEntryFactory $entryFactory,
    ) {}

    public function handle(QueueStoreInput $input): QueueEntry
    {
        $user = $input->user;
        $member = $user->member;

        $projectId = $member ? $member->project_id : (AdminContext::getCurrentProjectId() ?? 1);
        $effectiveLineUserId = $this->resolveLineUserId($input->lineUserId, $user->line_user_id);

        $nextNumber = $this->getNextQueueNumber($projectId);

        $entry = QueueEntryModel::create([
            'project_id' => $projectId,
            'member_id' => $member?->id,
            'user_id' => StrHelper::isEmptyStringOrNull($effectiveLineUserId) ? null : $user->id,
            'guest_name' => $input->guestName ?? $user->name,
            'guest_phone' => $input->guestPhone ?? $user->phone,
            'party_size' => $input->partySize,
            'status' => QueueStatus::WAITING,
            'queue_number' => $nextNumber,
            'qr_token' => $this->generateQrToken(),
        ]);

        $stats = $this->statsService->getStats($projectId, $entry->queue_number);
        $entry->refresh();

        return $this->entryFactory->create($entry, $stats);
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
            if (! QueueEntryModel::where('qr_token', $token)->exists()) {
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

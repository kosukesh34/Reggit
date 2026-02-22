<?php

namespace App\UseCases\Queue;

use App\Enums\QueueStatus;
use App\Models\QueueEntry as QueueEntryModel;
use App\Services\UserService;
use App\UseCases\Queue\Data\QueueEntry;
use App\UseCases\Queue\Data\QueueEntryFactory;
use App\Util\StrHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class QueueQrStatusUseCase
{
    public function __construct(
        private UserService $userService,
        private QueueStatsService $statsService,
        private QueueEntryFactory $entryFactory
    ) {
    }

    public function handle(Request $request, string $token): ?QueueEntry
    {
        $entry = QueueEntryModel::where('qr_token', $token)->first();
        if ($entry === null && ctype_digit($token)) {
            $entry = QueueEntryModel::whereKey((int) $token)->first();
            if ($entry !== null && StrHelper::isEmptyStringOrNull($entry->qr_token)) {
                $entry->qr_token = $this->generateQrToken();
                $entry->save();
                $entry->refresh();
            }
        }
        if ($entry === null) {
            return null;
        }

        $lineUserId = $request->header('X-Line-User-Id');
        if (StrHelper::isEmptyStringOrNull($lineUserId)) {
            throw new \RuntimeException('LINEログインが必要です');
        }

        $user = $this->userService->getOrCreateUser($request);
        $projectId = $entry->project_id;

        if ($entry->user_id !== null && $entry->user_id !== $user->id) {
            throw new \RuntimeException('別のLINEアカウントで登録済みです');
        }

        if ($entry->user_id === null) {
            $existing = QueueEntryModel::where('project_id', $projectId)
                ->where('user_id', $user->id)
                ->whereIn('status', [QueueStatus::WAITING, QueueStatus::CALLED])
                ->where('id', '!=', $entry->id)
                ->first();
            if ($existing !== null) {
                throw new \RuntimeException('同じLINEアカウントで複数登録できません');
            }

            $entry->user_id = $user->id;
            if ($user->member !== null) {
                $entry->member_id = $user->member->id;
            }
            $entry->save();
            $entry->refresh();
        }

        $stats = $this->statsService->getStats($projectId, $entry->queue_number);
        return $this->entryFactory->create($entry, $stats);
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
}

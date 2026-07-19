<?php

namespace App\UseCases\Queue;

use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Enums\QueueStatus;
use App\Models\QueueEntry as QueueEntryModel;
use App\Support\DailySequenceNumber;
use App\UseCases\Queue\Data\QueueEntryFactory;
use App\UseCases\Queue\Data\QueueStoreInput;
use App\UseCases\Queue\Data\QueueStoreOutput;
use App\Util\AdminContext;
use App\Util\StrHelper;
use Illuminate\Support\Str;

class QueueStoreUseCase
{
    public const WAITING_SYSTEM_CLIENT = 'waiting-system';

    public function __construct(
        private QueueStatsService $statsService,
        private QueueEntryFactory $entryFactory,
        private ShopSettingRepository $shopSettingRepository,
    ) {}

    public function handle(QueueStoreInput $input): QueueStoreOutput
    {
        $user = $input->user;
        $member = $user->member;

        $projectId = $member !== null ? $member->project_id : (AdminContext::getCurrentProjectId() ?? 1);
        $effectiveLineUserId = $this->resolveLineUserId($input->lineUserId, $user->line_user_id);

        if (
            $this->shopSettingRepository->getQueueRequireMembership($projectId)
            && $member === null
            && ! $this->isWaitingSystemRequest()
        ) {
            return new QueueStoreOutput(true, null);
        }

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

        return new QueueStoreOutput(false, $this->entryFactory->create($entry, $stats));
    }

    /** iPad WaitingSystem からの店頭受付（会員必須の対象外） */
    private function isWaitingSystemRequest(): bool
    {
        $client = trim((string) request()->header('X-Queue-Client', ''));

        return hash_equals(self::WAITING_SYSTEM_CLIENT, $client);
    }

    private function getNextQueueNumber(int $projectId): int
    {
        // DB: YYYYMMDD+通番（例: 202604201）。表示は日+通番（例: 201）。
        $maxStored = QueueEntryModel::where('project_id', $projectId)
            ->whereDate('created_at', today())
            ->max('queue_number');
        $maxSeq = 0;
        if ($maxStored !== null) {
            $parsed = DailySequenceNumber::parseSeq((string) $maxStored);
            $maxSeq = $parsed ?? 0;
        }

        return (int) DailySequenceNumber::build(DailySequenceNumber::nextSeq($maxSeq));
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

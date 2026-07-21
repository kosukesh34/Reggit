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
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;

class QueueStoreUseCase
{
    public const WAITING_SYSTEM_CLIENT = 'waiting-system';

    private const MAX_CREATE_ATTEMPTS = 5;

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

        $entry = $this->createEntryWithUniqueQueueNumber(
            $projectId,
            $member?->id,
            StrHelper::isEmptyStringOrNull($effectiveLineUserId) ? null : $user->id,
            $input->guestName ?? $user->name,
            $input->guestPhone ?? $user->phone,
            $input->partySize,
        );

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

    /**
     * @param  string|null  $guestName
     * @param  string|null  $guestPhone
     */
    private function createEntryWithUniqueQueueNumber(
        int $projectId,
        ?int $memberId,
        ?int $userId,
        ?string $guestName,
        ?string $guestPhone,
        int $partySize,
    ): QueueEntryModel {
        $lastError = null;
        for ($attempt = 0; $attempt < self::MAX_CREATE_ATTEMPTS; $attempt++) {
            try {
                return QueueEntryModel::create([
                    'project_id' => $projectId,
                    'member_id' => $memberId,
                    'user_id' => $userId,
                    'guest_name' => $guestName,
                    'guest_phone' => $guestPhone,
                    'party_size' => $partySize,
                    'status' => QueueStatus::WAITING,
                    'queue_number' => $this->getNextQueueNumber($projectId),
                    'qr_token' => $this->generateQrToken(),
                ]);
            } catch (UniqueConstraintViolationException $e) {
                $lastError = $e;
            }
        }

        throw $lastError ?? new \RuntimeException('受付番号の採番に失敗しました');
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

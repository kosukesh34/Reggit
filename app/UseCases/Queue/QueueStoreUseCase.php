<?php

namespace App\UseCases\Queue;

use App\Domain\Settings\Repositories\QueueSettings;
use App\Enums\QueueStatus;
use App\Models\Member;
use App\Models\QueueEntry as QueueEntryModel;
use App\Models\User;
use App\Services\Member\CurrentMemberPrincipal;
use App\Support\DailySequenceNumber;
use App\Support\LineIdentityPolicy;
use App\Support\MemberChannel;
use App\UseCases\Queue\Data\QueueEntryFactory;
use App\UseCases\Queue\Data\QueueStoreInput;
use App\UseCases\Queue\Data\QueueStoreOutput;
use App\Util\AdminContext;
use App\Util\StrHelper;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QueueStoreUseCase
{
    public const WAITING_SYSTEM_CLIENT = 'waiting-system';

    private const MAX_CREATE_ATTEMPTS = 5;

    public function __construct(
        private QueueStatsService $statsService,
        private QueueEntryFactory $entryFactory,
        private QueueSettings $shopSettingRepository,
        private CurrentMemberPrincipal $principals,
    ) {}

    public function handle(QueueStoreInput $input): QueueStoreOutput
    {
        $projectId = AdminContext::requireCurrentProjectId();
        $entry = null;
        $lastError = null;
        for ($attempt = 0; $attempt < self::MAX_CREATE_ATTEMPTS; $attempt++) {
            try {
                $entry = DB::transaction(function () use ($input, $projectId): ?QueueEntryModel {
                    $user = $this->principals->lock($input->user, $projectId);
                    if ($user === null) {
                        return null;
                    }
                    $member = $user->member;
                    $effectiveLineUserId = $this->resolveLineUserId($input->lineUserId, $user->line_user_id);

                    if (
                        $this->shopSettingRepository->getQueueRequireMembership($projectId)
                        && $member === null
                        && ! $this->isWaitingSystemRequest($input->queueClient)
                    ) {
                        return null;
                    }

                    return $this->createEntry(
                        $projectId,
                        $member?->id,
                        $this->resolveQueueUserId($user, $member, $effectiveLineUserId),
                        $input->guestName ?? $user->name,
                        $input->guestPhone ?? $user->phone,
                        $input->partySize,
                    );
                });
                $lastError = null;
                break;
            } catch (UniqueConstraintViolationException $exception) {
                $lastError = $exception;
            }
        }
        if ($lastError !== null) {
            throw $lastError;
        }

        if ($entry === null) {
            return new QueueStoreOutput(true, null);
        }

        $stats = $this->statsService->getStats($projectId, $entry->queue_number);
        $entry->refresh();

        return new QueueStoreOutput(false, $this->entryFactory->create($entry, $stats));
    }

    /** iPad WaitingSystem からの店頭受付（会員必須の対象外） */
    private function isWaitingSystemRequest(?string $queueClient): bool
    {
        $client = trim((string) $queueClient);

        return $client !== '' && hash_equals(self::WAITING_SYSTEM_CLIENT, $client);
    }

    private function createEntry(
        int $projectId,
        ?int $memberId,
        ?int $userId,
        ?string $guestName,
        ?string $guestPhone,
        int $partySize,
    ): QueueEntryModel {
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
        if (is_string($userLineUserId) && MemberChannel::isValidLineUserId($userLineUserId)) {
            return $userLineUserId;
        }
        if (LineIdentityPolicy::requiresVerifiedToken()) {
            return null;
        }
        if (is_string($headerLineUserId) && MemberChannel::isValidLineUserId($headerLineUserId)) {
            return $headerLineUserId;
        }

        return null;
    }

    /**
     * 会員・LINE・ネイティブ端末ユーザーは券に紐付ける。
     * 店頭の使い捨てゲスト（会員なし・LINEなし・端末なし）は null のままにする。
     */
    private function resolveQueueUserId(User $user, ?Member $member, ?string $effectiveLineUserId): ?int
    {
        if ($member !== null) {
            return (int) $user->id;
        }
        if (! StrHelper::isEmptyStringOrNull($effectiveLineUserId)) {
            return (int) $user->id;
        }
        if (MemberChannel::hasApp($user)) {
            return (int) $user->id;
        }

        return null;
    }
}

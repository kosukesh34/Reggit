<?php

namespace App\Services\Stamp;

use App\Enums\StampCycleMode;
use App\Models\Member;
use App\Models\MemberStamp;
use App\Models\MemberStampTransaction;
use App\Models\StampCard;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class StampBalanceService
{
    private ?string $issueSourceType = null;

    private ?string $issueSourceId = null;

    public function __construct(
        private StampMilestoneRewardService $stampMilestoneRewardService,
    ) {}

    /**
     * @param  list<int>  $visitedCardIds
     */
    public function adjust(
        Member $member,
        StampCard $stampCard,
        int $delta,
        ?string $description,
        ?int $orderId,
        array $visitedCardIds = [],
        ?string $issueSourceType = null,
        ?string $issueSourceId = null,
    ): MemberStamp {
        return DB::transaction(function () use ($member, $stampCard, $delta, $description, $orderId, $visitedCardIds, $issueSourceType, $issueSourceId) {
            $member = Member::query()->whereKey($member->id)->where('project_id', $member->project_id)
                ->lockForUpdate()->firstOrFail();
            $this->issueSourceType = $issueSourceType;
            $this->issueSourceId = $issueSourceId;
            $memberStamp = $this->ensureMemberStamp($member, $stampCard);
            $memberStamp->cyclesCompletedNow = 0;
            if ($delta < 0) {
                $this->applyNegativeDelta($member, $stampCard, $memberStamp, $delta, $description, $orderId);

                return $memberStamp->refresh();
            }
            $mode = StampCycleMode::normalize($stampCard->cycle_mode);
            if (StampCycleMode::isGrantClosed($mode, $memberStamp) === true) {
                return $memberStamp;
            }
            if ($mode === StampCycleMode::REPEAT) {
                $this->applyRepeatDelta($member, $stampCard, $memberStamp, $delta, $description, $orderId);

                return $memberStamp;
            }

            $this->applyCappedDelta(
                $member,
                $stampCard,
                $memberStamp,
                $delta,
                $description,
                $orderId,
                $visitedCardIds,
            );

            return $memberStamp->refresh();
        });
    }

    private function ensureMemberStamp(Member $member, StampCard $stampCard): MemberStamp
    {
        $memberStamp = $this->lockMemberStamp($member, $stampCard);
        if ($memberStamp->valid_until === null && $stampCard->valid_days !== null && (int) $stampCard->valid_days > 0) {
            $memberStamp->update(['valid_until' => Carbon::now()->addDays((int) $stampCard->valid_days)]);
        }

        return $memberStamp->refresh();
    }

    private function lockMemberStamp(Member $member, StampCard $stampCard): MemberStamp
    {
        $memberStamp = $this->findMemberStampForUpdate($member, $stampCard);
        if ($memberStamp !== null) {
            return $memberStamp;
        }
        try {
            MemberStamp::query()->create([
                'project_id' => $member->project_id,
                'member_id' => $member->id,
                'stamp_card_id' => $stampCard->id,
                'current_stamps' => 0,
                'cycle_count' => 0,
            ]);
        } catch (UniqueConstraintViolationException) {
            // 同時作成。ロックし直す。
        }
        $memberStamp = $this->findMemberStampForUpdate($member, $stampCard);
        if ($memberStamp === null) {
            throw new \RuntimeException('member_stamps row missing');
        }

        return $memberStamp;
    }

    private function findMemberStampForUpdate(Member $member, StampCard $stampCard): ?MemberStamp
    {
        return MemberStamp::query()
            ->where('project_id', $member->project_id)
            ->where('member_id', $member->id)
            ->where('stamp_card_id', $stampCard->id)
            ->lockForUpdate()
            ->first();
    }

    private function applyNegativeDelta(
        Member $member,
        StampCard $stampCard,
        MemberStamp $memberStamp,
        int $delta,
        ?string $description,
        ?int $orderId,
    ): void {
        $mode = StampCycleMode::normalize($stampCard->cycle_mode);
        if ($mode === StampCycleMode::REPEAT) {
            $this->applyRepeatNegativeDelta($member, $stampCard, $memberStamp, $delta, $description, $orderId);

            return;
        }
        $previousBalance = (int) $memberStamp->current_stamps;
        $newBalance = max(0, $previousBalance + $delta);
        $appliedDelta = $newBalance - $previousBalance;
        if ($appliedDelta === 0) {
            return;
        }
        $required = max(1, (int) $stampCard->required_stamps);
        $completedAt = $memberStamp->completed_at;
        if ($newBalance >= $required && $completedAt === null) {
            $completedAt = now();
        } elseif ($newBalance < $required && $completedAt !== null) {
            $completedAt = null;
        }
        $this->writeBalance(
            $member,
            $stampCard,
            $memberStamp,
            $previousBalance,
            $newBalance,
            $appliedDelta,
            $description,
            $orderId,
            $completedAt,
            null,
            0,
        );
    }

    private function applyRepeatNegativeDelta(
        Member $member,
        StampCard $stampCard,
        MemberStamp $memberStamp,
        int $delta,
        ?string $description,
        ?int $orderId,
    ): void {
        $required = max(1, (int) $stampCard->required_stamps);
        $previousBalance = (int) $memberStamp->current_stamps;
        $cycleCount = (int) $memberStamp->cycle_count;
        $total = ($cycleCount * $required) + $previousBalance;
        $newTotal = max(0, $total + $delta);
        $appliedDelta = $newTotal - $total;
        if ($appliedDelta === 0) {
            return;
        }
        $this->writeBalance(
            $member,
            $stampCard,
            $memberStamp,
            $previousBalance,
            $newTotal % $required,
            $appliedDelta,
            $description,
            $orderId,
            null,
            intdiv($newTotal, $required),
            0,
        );
    }

    private function applyRepeatDelta(
        Member $member,
        StampCard $stampCard,
        MemberStamp $memberStamp,
        int $delta,
        ?string $description,
        ?int $orderId,
    ): void {
        $required = max(1, (int) $stampCard->required_stamps);
        $startBalance = (int) $memberStamp->current_stamps;
        $cycleCount = (int) $memberStamp->cycle_count;
        if ($startBalance >= $required) {
            $cycleCount += intdiv($startBalance, $required);
            $startBalance = $startBalance % $required;
            $memberStamp->update([
                'current_stamps' => $startBalance,
                'cycle_count' => $cycleCount,
                'completed_at' => null,
            ]);
        }

        $cursor = $startBalance;
        $remaining = $delta;
        $cyclesCompletedNow = 0;
        while ($remaining > 0) {
            $step = min($remaining, $required - $cursor);
            if ($step <= 0) {
                break;
            }
            $next = $cursor + $step;
            $this->stampMilestoneRewardService->grantReached(
                $member,
                $stampCard,
                $cursor,
                $next,
                $cycleCount,
                $this->issueSourceType,
                $this->issueSourceId,
            );
            $remaining -= $step;
            if ($next < $required) {
                $cursor = $next;

                continue;
            }
            $cycleCount++;
            $cyclesCompletedNow++;
            $cursor = 0;
        }
        $this->writeBalance(
            $member,
            $stampCard,
            $memberStamp,
            $startBalance,
            $cursor,
            $delta,
            $description,
            $orderId,
            null,
            $cycleCount,
            0,
            false,
        );
        $memberStamp->cyclesCompletedNow = $cyclesCompletedNow;
    }

    /**
     * @param  list<int>  $visitedCardIds
     */
    private function applyCappedDelta(
        Member $member,
        StampCard $stampCard,
        MemberStamp $memberStamp,
        int $delta,
        ?string $description,
        ?int $orderId,
        array $visitedCardIds,
    ): void {
        $required = max(1, (int) $stampCard->required_stamps);
        $previousBalance = (int) $memberStamp->current_stamps;
        $newBalance = $previousBalance + $delta;
        $remainder = 0;
        $completedAt = $memberStamp->completed_at;
        if ($newBalance >= $required) {
            $remainder = $newBalance - $required;
            $newBalance = $required;
            if ($completedAt === null) {
                $completedAt = now();
            }
        }
        $appliedDelta = $newBalance - $previousBalance;
        if ($appliedDelta !== 0 || $completedAt !== $memberStamp->completed_at) {
            $this->writeBalance(
                $member,
                $stampCard,
                $memberStamp,
                $previousBalance,
                $newBalance,
                $appliedDelta,
                $description,
                $orderId,
                $completedAt,
                null,
                0,
            );
        }
        if ($remainder <= 0 || StampCycleMode::normalize($stampCard->cycle_mode) !== StampCycleMode::NEXT_CARD) {
            return;
        }
        $this->applyRemainderToNextCard($member, $stampCard, $remainder, $description, $orderId, $visitedCardIds);
    }

    /**
     * @param  list<int>  $visitedCardIds
     */
    private function applyRemainderToNextCard(
        Member $member,
        StampCard $stampCard,
        int $remainder,
        ?string $description,
        ?int $orderId,
        array $visitedCardIds,
    ): void {
        $cardId = (int) $stampCard->id;
        if (in_array($cardId, $visitedCardIds, true)) {
            return;
        }
        $next = $this->resolveNextCard($stampCard);
        if ($next === null) {
            return;
        }
        $visitedCardIds[] = $cardId;
        $this->adjust(
            $member,
            $next,
            $remainder,
            $description,
            $orderId,
            $visitedCardIds,
            $this->issueSourceType,
            $this->issueSourceId,
        );
    }

    private function resolveNextCard(StampCard $stampCard): ?StampCard
    {
        $nextId = (int) ($stampCard->next_stamp_card_id ?? 0);
        if ($nextId <= 0 || $nextId === (int) $stampCard->id) {
            return null;
        }

        return StampCard::query()
            ->where('project_id', $stampCard->project_id)
            ->where('is_active', true)
            ->find($nextId);
    }

    private function writeBalance(
        Member $member,
        StampCard $stampCard,
        MemberStamp $memberStamp,
        int $previousBalance,
        int $newBalance,
        int $appliedDelta,
        ?string $description,
        ?int $orderId,
        DateTimeInterface|string|null $completedAt,
        ?int $cycleCount,
        int $milestoneCycleIndex,
        bool $shouldGrantMilestones = true,
    ): void {
        $payload = ['current_stamps' => $newBalance];
        if ($completedAt !== $memberStamp->completed_at) {
            $payload['completed_at'] = $completedAt;
        }
        if ($cycleCount !== null) {
            $payload['cycle_count'] = $cycleCount;
        }
        $memberStamp->issueSourceType = $this->issueSourceType;
        $memberStamp->issueSourceId = $this->issueSourceId;
        $memberStamp->update($payload);
        if ($appliedDelta !== 0) {
            MemberStampTransaction::query()->create([
                'project_id' => $member->project_id,
                'member_id' => $member->id,
                'stamp_card_id' => $stampCard->id,
                'delta' => $appliedDelta,
                'balance_after' => $newBalance,
                'description' => $description,
                'order_id' => $orderId,
            ]);
        }
        if ($shouldGrantMilestones && $appliedDelta > 0) {
            $this->stampMilestoneRewardService->grantReached(
                $member,
                $stampCard,
                $previousBalance,
                $newBalance,
                $milestoneCycleIndex,
                $this->issueSourceType,
                $this->issueSourceId,
            );
        }
    }
}

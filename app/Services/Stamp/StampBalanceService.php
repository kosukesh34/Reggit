<?php

namespace App\Services\Stamp;

use App\Enums\StampCycleMode;
use App\Models\Member;
use App\Models\MemberStamp;
use App\Models\MemberStampTransaction;
use App\Models\StampCard;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;

class StampBalanceService
{
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
    ): MemberStamp {
        return DB::transaction(function () use ($member, $stampCard, $delta, $description, $orderId, $visitedCardIds) {
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
        $memberStamp = MemberStamp::query()->firstOrCreate(
            [
                'project_id' => $member->project_id,
                'member_id' => $member->id,
                'stamp_card_id' => $stampCard->id,
            ],
            ['current_stamps' => 0, 'cycle_count' => 0]
        );
        if ($memberStamp->valid_until === null && $stampCard->valid_days !== null && (int) $stampCard->valid_days > 0) {
            $memberStamp->update(['valid_until' => Carbon::now()->addDays((int) $stampCard->valid_days)]);
        }

        return $memberStamp->refresh();
    }

    private function applyNegativeDelta(
        Member $member,
        StampCard $stampCard,
        MemberStamp $memberStamp,
        int $delta,
        ?string $description,
        ?int $orderId,
    ): void {
        $previousBalance = (int) $memberStamp->current_stamps;
        $newBalance = max(0, $previousBalance + $delta);
        $appliedDelta = $newBalance - $previousBalance;
        if ($appliedDelta === 0) {
            return;
        }
        $required = max(1, (int) $stampCard->required_stamps);
        $mode = StampCycleMode::normalize($stampCard->cycle_mode);
        $completedAt = $memberStamp->completed_at;
        if ($mode !== StampCycleMode::REPEAT && $newBalance >= $required && $completedAt === null) {
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

    private function applyRepeatDelta(
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
        $cyclesCompletedNow = 0;
        if ($previousBalance >= $required) {
            $cycleCount += intdiv($previousBalance, $required);
            $previousBalance = $previousBalance % $required;
            $memberStamp->update([
                'current_stamps' => $previousBalance,
                'cycle_count' => $cycleCount,
                'completed_at' => null,
            ]);
        }

        $remaining = $delta;
        while ($remaining > 0) {
            $step = min($remaining, $required - $previousBalance);
            if ($step <= 0) {
                break;
            }
            $newBalance = $previousBalance + $step;
            $this->writeBalance(
                $member,
                $stampCard,
                $memberStamp,
                $previousBalance,
                $newBalance,
                $step,
                $description,
                $orderId,
                null,
                $cycleCount,
                $cycleCount,
            );
            $remaining -= $step;
            if ($newBalance < $required) {
                $previousBalance = $newBalance;

                continue;
            }
            $cycleCount++;
            $cyclesCompletedNow++;
            $previousBalance = 0;
            $memberStamp->update([
                'current_stamps' => 0,
                'cycle_count' => $cycleCount,
                'completed_at' => null,
            ]);
        }
        $memberStamp->refresh();
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
        $this->adjust($member, $next, $remainder, $description, $orderId, $visitedCardIds);
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
    ): void {
        $payload = ['current_stamps' => $newBalance];
        if ($completedAt !== $memberStamp->completed_at) {
            $payload['completed_at'] = $completedAt;
        }
        if ($cycleCount !== null) {
            $payload['cycle_count'] = $cycleCount;
        }
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
        if ($appliedDelta > 0) {
            $this->stampMilestoneRewardService->grantReached(
                $member,
                $stampCard,
                $previousBalance,
                $newBalance,
                $milestoneCycleIndex,
            );
        }
    }
}

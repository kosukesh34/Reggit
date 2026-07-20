<?php

namespace App\Services\Smaregi;

use App\Domain\Member\Events\PointsEarned;
use App\Domain\Member\Repositories\MemberRepository;
use App\Domain\Order\Policies\StampEarnPolicy;
use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Domain\Shared\DomainEventDispatcher\DomainEventDispatcher;
use App\Domain\Smaregi\Services\PurchasePointCalculator;
use App\Domain\Smaregi\Services\SmaregiInboundAwardDescription;
use App\Enums\PointTransactionType;
use App\Models\Member;
use App\Models\MemberStampTransaction;
use App\Models\PointTransaction;
use App\Services\Admin\MemberAdminService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * スマレジ取引に紐づくポイント／スタンプの付与・取消を行う。
 */
class SmaregiInboundAwardApplier
{
    public function __construct(
        private MemberAdminService $memberAdminService,
        private ShopSettingRepository $shopSettingRepository,
        private StampEarnPolicy $stampEarnPolicy,
        private MemberRepository $memberRepository,
        private DomainEventDispatcher $domainEvents,
        private SmaregiPointsSyncService $pointsSync,
        private PurchasePointCalculator $pointCalculator,
        private SmaregiInboundAwardDescription $descriptions,
    ) {}

    public function awardForPurchase(
        Member $member,
        float $amount,
        string $headId,
        bool $hasSmaregiManagedPointDelta,
    ): void {
        $this->awardStamps($member, $amount, $headId);
        $this->awardPoints($member, $amount, $headId, $hasSmaregiManagedPointDelta);
    }

    public function reverseForCancel(Member $member, string $headId): void
    {
        $this->reverseAwardedPoints($member, $headId, SmaregiInboundAwardDescription::REASON_CANCEL);
        $this->reverseAwardedStamps($member, $headId, SmaregiInboundAwardDescription::REASON_CANCEL);
    }

    public function reverseForReturn(
        Member $member,
        float $absAmount,
        string $headId,
        bool $hasSmaregiManagedPointDelta,
    ): void {
        if (! $hasSmaregiManagedPointDelta) {
            $this->reversePointsByAmount(
                $member,
                $absAmount,
                $headId,
                SmaregiInboundAwardDescription::REASON_RETURN,
            );
        }
        $this->reverseStampsByAmount(
            $member,
            $absAmount,
            $headId,
            SmaregiInboundAwardDescription::REASON_RETURN,
        );
    }

    private function awardPoints(
        Member $member,
        float $amount,
        string $headId,
        bool $hasSmaregiManagedPointDelta,
    ): void {
        if ($amount <= 0 || $hasSmaregiManagedPointDelta) {
            return;
        }

        $projectId = (int) $member->project_id;
        $rankKey = is_string($member->rank) ? $member->rank : null;
        $rate = $this->shopSettingRepository->getPointEarnRateForRank($projectId, $rankKey);
        $points = $this->pointCalculator->calculate($amount, $rate);
        if ($points <= 0) {
            return;
        }

        $description = $this->descriptions->pointAward($headId);
        if ($this->hasPointTransaction($member, PointTransactionType::EARNED, $description)) {
            return;
        }

        $this->pointsSync->markOutboundPending((int) $member->id);

        $awarded = DB::transaction(function () use ($member, $projectId, $points, $description): int {
            $this->memberRepository->syncPointsLedger((int) $member->id, $projectId);
            $aggregate = $this->memberRepository->findByIdForUpdate((int) $member->id, $projectId);
            if ($aggregate === null) {
                return 0;
            }
            if ($this->hasPointTransaction($member, PointTransactionType::EARNED, $description)) {
                return 0;
            }
            $validDays = $this->shopSettingRepository->getPointsValidDays($projectId);
            $aggregate->earnPoints(
                $points,
                $description,
                null,
                now()->addDays($validDays),
            );
            $this->memberRepository->save($aggregate);

            return $points;
        });

        if ($awarded <= 0) {
            $this->pointsSync->clearOutboundPending((int) $member->id);

            return;
        }

        $this->domainEvents->dispatch(new PointsEarned(
            (int) $member->id,
            $projectId,
            $awarded,
            null,
            true,
        ));
        Log::info('[Smaregi] Inbound transaction sync points awarded', [
            'member_id' => (int) $member->id,
            'project_id' => $projectId,
            'transaction_head_id' => $headId,
            'points' => $awarded,
            'amount' => $amount,
            'rate' => $rate,
        ]);
    }

    private function awardStamps(Member $member, float $amount, string $headId): void
    {
        if ($amount <= 0) {
            return;
        }

        $projectId = (int) $member->project_id;
        $stampCardId = $this->shopSettingRepository->getOrderStampCardId($projectId);
        $stampEarnPerAmount = $this->shopSettingRepository->getStampEarnPerAmount($projectId);
        if ($stampCardId === null || $stampEarnPerAmount <= 0) {
            return;
        }

        $stampCount = $this->stampEarnPolicy->calculateCount($amount, $stampEarnPerAmount);
        if ($stampCount <= 0) {
            return;
        }

        $description = $this->descriptions->stampAward($headId);
        if ($this->hasStampTransaction($member, $description)) {
            return;
        }

        $this->memberAdminService->addStampsToMember(
            $member,
            $stampCardId,
            $stampCount,
            $description,
            null
        );
        Log::info('[Smaregi] Inbound transaction sync stamps awarded', [
            'member_id' => (int) $member->id,
            'project_id' => $projectId,
            'transaction_head_id' => $headId,
            'stamp_count' => $stampCount,
            'amount' => $amount,
        ]);
    }

    private function reverseAwardedPoints(Member $member, string $headId, string $reasonLabel): void
    {
        $earnDescription = $this->descriptions->pointAward($headId);
        $reverseDescription = $this->descriptions->pointReverse($headId, $reasonLabel);
        if ($this->hasPointReversal($member, $reverseDescription)) {
            return;
        }

        $points = (int) PointTransaction::query()
            ->where('project_id', $member->project_id)
            ->where('member_id', $member->id)
            ->where('type', PointTransactionType::EARNED)
            ->where('description', $earnDescription)
            ->sum('points');
        if ($points <= 0) {
            return;
        }

        $this->applyPointReversal($member, $points, $reverseDescription);
    }

    private function reversePointsByAmount(
        Member $member,
        float $absAmount,
        string $headId,
        string $reasonLabel,
    ): void {
        if ($absAmount <= 0) {
            return;
        }
        $reverseDescription = $this->descriptions->pointReverse($headId, $reasonLabel);
        if ($this->hasPointReversal($member, $reverseDescription)) {
            return;
        }

        $projectId = (int) $member->project_id;
        $rankKey = is_string($member->rank) ? $member->rank : null;
        $rate = $this->shopSettingRepository->getPointEarnRateForRank($projectId, $rankKey);
        $points = $this->pointCalculator->calculate($absAmount, $rate);
        if ($points <= 0) {
            return;
        }

        $this->applyPointReversal($member, $points, $reverseDescription);
    }

    private function applyPointReversal(Member $member, int $points, string $description): void
    {
        $projectId = (int) $member->project_id;
        $this->pointsSync->markOutboundPending((int) $member->id);

        $reversed = DB::transaction(function () use ($member, $projectId, $points, $description): int {
            $this->memberRepository->syncPointsLedger((int) $member->id, $projectId);
            $aggregate = $this->memberRepository->findByIdForUpdate((int) $member->id, $projectId);
            if ($aggregate === null) {
                return 0;
            }
            if ($this->hasPointReversal($member, $description)) {
                return 0;
            }
            $aggregate->reverseEarnedPoints($points, $description, null);
            $this->memberRepository->save($aggregate);

            return $points;
        });

        if ($reversed <= 0) {
            $this->pointsSync->clearOutboundPending((int) $member->id);

            return;
        }

        $this->domainEvents->dispatch(new PointsEarned(
            (int) $member->id,
            $projectId,
            -1 * $reversed,
            null,
            true,
        ));
    }

    private function reverseAwardedStamps(Member $member, string $headId, string $reasonLabel): void
    {
        $earnDescription = $this->descriptions->stampAward($headId);
        $reverseDescription = $this->descriptions->stampReverse($headId, $reasonLabel);
        if ($this->hasStampTransaction($member, $reverseDescription)) {
            return;
        }

        $earned = MemberStampTransaction::query()
            ->where('project_id', $member->project_id)
            ->where('member_id', $member->id)
            ->where('description', $earnDescription)
            ->where('delta', '>', 0)
            ->get();
        if ($earned->isEmpty()) {
            return;
        }

        foreach ($earned as $tx) {
            $delta = (int) $tx->delta;
            if ($delta <= 0) {
                continue;
            }
            $this->memberAdminService->adjustStampsToMember(
                $member,
                (int) $tx->stamp_card_id,
                -1 * $delta,
                $reverseDescription,
                null
            );
        }
    }

    private function reverseStampsByAmount(
        Member $member,
        float $absAmount,
        string $headId,
        string $reasonLabel,
    ): void {
        if ($absAmount <= 0) {
            return;
        }
        $reverseDescription = $this->descriptions->stampReverse($headId, $reasonLabel);
        if ($this->hasStampTransaction($member, $reverseDescription)) {
            return;
        }

        $projectId = (int) $member->project_id;
        $stampCardId = $this->shopSettingRepository->getOrderStampCardId($projectId);
        $stampEarnPerAmount = $this->shopSettingRepository->getStampEarnPerAmount($projectId);
        if ($stampCardId === null || $stampEarnPerAmount <= 0) {
            return;
        }
        $stampCount = $this->stampEarnPolicy->calculateCount($absAmount, $stampEarnPerAmount);
        if ($stampCount <= 0) {
            return;
        }

        $this->memberAdminService->adjustStampsToMember(
            $member,
            $stampCardId,
            -1 * $stampCount,
            $reverseDescription,
            null
        );
    }

    private function hasPointReversal(Member $member, string $description): bool
    {
        return $this->hasPointTransaction($member, PointTransactionType::USED, $description)
            || $this->hasPointTransaction($member, PointTransactionType::REVERSED, $description);
    }

    private function hasPointTransaction(Member $member, string $type, string $description): bool
    {
        return PointTransaction::query()
            ->where('project_id', $member->project_id)
            ->where('member_id', $member->id)
            ->where('type', $type)
            ->where('description', $description)
            ->exists();
    }

    private function hasStampTransaction(Member $member, string $description): bool
    {
        return MemberStampTransaction::query()
            ->where('project_id', $member->project_id)
            ->where('member_id', $member->id)
            ->where('description', $description)
            ->exists();
    }
}

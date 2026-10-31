<?php

namespace App\Services\Smaregi;

use App\Domain\Order\Policies\StampEarnPolicy;
use App\Domain\Settings\Repositories\OrderSettings;
use App\Domain\Smaregi\Services\SmaregiInboundAwardDescription;
use App\Enums\MemberCouponSource;
use App\Models\Member;
use App\Models\MemberStampTransaction;
use App\Models\StampCard;
use App\Services\Admin\MemberBenefitService;
use App\Services\Stamp\MemberCurrentStampCardResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/** スマレジ取引に紐づくスタンプの付与と、取消・返品による戻し（冪等）。 */
class SmaregiInboundStampAwarder
{
    public function __construct(
        private MemberBenefitService $memberBenefitService,
        private OrderSettings $shopSettingRepository,
        private StampEarnPolicy $stampEarnPolicy,
        private SmaregiInboundAwardDescription $descriptions,
        private MemberCurrentStampCardResolver $memberCurrentStampCardResolver,
    ) {}

    public function awardStamps(Member $member, float $amount, string $headId, ?int $orderId = null): void
    {
        if ($amount <= 0) {
            return;
        }

        $projectId = (int) $member->project_id;
        if ($this->shopSettingRepository->getUseTransactionStamps($projectId) !== true) {
            return;
        }
        $stampCard = $this->memberCurrentStampCardResolver->resolve($member);
        $rankKey = is_string($member->rank) ? $member->rank : null;
        $stampEarnPerAmount = $this->shopSettingRepository->getStampEarnPerAmountForRank($projectId, $rankKey);
        if ($stampCard === null || $stampEarnPerAmount <= 0) {
            return;
        }

        $stampCount = $this->stampEarnPolicy->calculateCount($amount, $stampEarnPerAmount);
        if ($stampCount <= 0) {
            return;
        }

        $description = $this->descriptions->stampAward($headId);
        $awarded = false;
        DB::transaction(function () use (
            $member,
            $stampCard,
            $stampCount,
            $description,
            $orderId,
            $headId,
            &$awarded,
        ): void {
            $this->lockMember($member);
            if ($this->hasStampTransaction($member, $description)) {
                return;
            }
            $this->memberBenefitService->addStampsToMember(
                $member,
                (int) $stampCard->id,
                $stampCount,
                $description,
                $orderId,
                MemberCouponSource::SMAREGI_TRANSACTION,
                $headId
            );
            $awarded = true;
        });
        if ($awarded !== true) {
            return;
        }
        Log::info('[Smaregi] Inbound transaction sync stamps awarded', [
            'member_id' => (int) $member->id,
            'project_id' => $projectId,
            'transaction_head_id' => $headId,
            'order_id' => $orderId,
            'stamp_count' => $stampCount,
            'amount' => $amount,
        ]);
    }

    public function reverseAwardedStamps(Member $member, string $headId, string $reasonLabel): void
    {
        $earnDescription = $this->descriptions->stampAward($headId);
        $reverseDescription = $this->descriptions->stampReverse($headId, $reasonLabel);
        DB::transaction(function () use ($member, $earnDescription, $reverseDescription): void {
            $this->lockMember($member);
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
                $this->memberBenefitService->adjustStampsToMember(
                    $member,
                    (int) $tx->stamp_card_id,
                    -1 * $delta,
                    $reverseDescription,
                    null
                );
            }
        });
    }

    public function reverseStampsByAmount(
        Member $member,
        float $absAmount,
        string $headId,
        string $reasonLabel,
    ): void {
        if ($absAmount <= 0) {
            return;
        }
        $reverseDescription = $this->descriptions->stampReverse($headId, $reasonLabel);
        $projectId = (int) $member->project_id;
        if ($this->shopSettingRepository->getUseTransactionStamps($projectId) !== true) {
            return;
        }
        $stampCard = $this->memberCurrentStampCardResolver->resolve($member);
        $rankKey = is_string($member->rank) ? $member->rank : null;
        $stampEarnPerAmount = $this->shopSettingRepository->getStampEarnPerAmountForRank($projectId, $rankKey);
        if ($stampCard === null || $stampEarnPerAmount <= 0) {
            return;
        }
        $stampCount = $this->stampEarnPolicy->calculateCount($absAmount, $stampEarnPerAmount);
        if ($stampCount <= 0) {
            return;
        }

        $this->applyStampReversal($member, $stampCard, $stampCount, $reverseDescription);
    }

    private function applyStampReversal(Member $member, StampCard $stampCard, int $stampCount, string $reverseDescription): void
    {
        DB::transaction(function () use ($member, $stampCard, $stampCount, $reverseDescription): void {
            $this->lockMember($member);
            if ($this->hasStampTransaction($member, $reverseDescription)) {
                return;
            }
            $this->memberBenefitService->adjustStampsToMember(
                $member,
                (int) $stampCard->id,
                -1 * $stampCount,
                $reverseDescription,
                null
            );
        });
    }

    private function hasStampTransaction(Member $member, string $description): bool
    {
        return MemberStampTransaction::query()
            ->where('project_id', $member->project_id)
            ->where('member_id', $member->id)
            ->where('description', $description)
            ->exists();
    }

    private function lockMember(Member $member): void
    {
        Member::query()
            ->withoutGlobalScopes()
            ->whereKey($member->id)
            ->where('project_id', $member->project_id)
            ->lockForUpdate()
            ->first();
    }
}

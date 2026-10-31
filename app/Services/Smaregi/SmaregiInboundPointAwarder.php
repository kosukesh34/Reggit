<?php

namespace App\Services\Smaregi;

use App\Domain\Member\Events\PointsEarned;
use App\Domain\Member\Repositories\MemberRepository;
use App\Domain\Settings\Repositories\OrderSettings;
use App\Domain\Shared\DomainEventDispatcher\DomainEventDispatcher;
use App\Domain\Smaregi\Services\PurchasePointCalculator;
use App\Domain\Smaregi\Services\SmaregiInboundAwardDescription;
use App\Enums\PointTransactionType;
use App\Models\Member;
use App\Models\PointTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/** スマレジ取引に紐づくポイントの付与と、取消・返品による戻し（冪等）。 */
class SmaregiInboundPointAwarder
{
    public function __construct(
        private OrderSettings $shopSettingRepository,
        private MemberRepository $memberRepository,
        private DomainEventDispatcher $domainEvents,
        private SmaregiPointsSyncService $pointsSync,
        private PurchasePointCalculator $pointCalculator,
        private SmaregiInboundAwardDescription $descriptions,
    ) {}

    public function awardPoints(
        Member $member,
        float $amount,
        string $headId,
        bool $hasSmaregiManagedPointDelta,
        ?int $orderId = null,
    ): void {
        if ($amount <= 0 || $hasSmaregiManagedPointDelta) {
            return;
        }

        $projectId = (int) $member->project_id;
        if ($this->shopSettingRepository->getUsePoints($projectId) !== true) {
            return;
        }
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

        $awarded = DB::transaction(function () use ($member, $projectId, $points, $description, $orderId): int {
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
                $orderId,
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
            $orderId,
            true,
        ));
        Log::info('[Smaregi] Inbound transaction sync points awarded', [
            'member_id' => (int) $member->id,
            'project_id' => $projectId,
            'transaction_head_id' => $headId,
            'order_id' => $orderId,
            'points' => $awarded,
            'amount' => $amount,
            'rate' => $rate,
        ]);
    }

    public function reverseAwardedPoints(Member $member, string $headId, string $reasonLabel): void
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

    public function reversePointsByAmount(
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
        if ($this->shopSettingRepository->getUsePoints($projectId) !== true) {
            return;
        }
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
}

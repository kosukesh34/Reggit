<?php

namespace App\Services;

use App\Domain\Member\Aggregates\Member as MemberAggregate;
use App\Domain\Member\Repositories\MemberRepository;
use App\Domain\Order\Exceptions\OrderOperationException;
use App\Domain\Order\Policies\StampEarnPolicy;
use App\Domain\Settings\Repositories\OrderSettings;
use App\Enums\LotterySourceType;
use App\Enums\MemberCouponSource;
use App\Enums\MemberVisitSource;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Models\MemberStampTransaction;
use App\Models\MemberVisit;
use App\Models\Order;
use App\Models\User;
use App\Services\Lottery\LotteryEntitlementService;
use App\Services\Stamp\MemberCurrentStampCardResolver;
use Illuminate\Support\Facades\DB;

class OrderRewardService
{
    private const DEFAULT_POINTS_EARN_RATE = 100;

    public function __construct(
        private MemberVisitRecorder $memberVisitRecorder,
        private MemberBenefitService $memberBenefitService,
        private MemberRepository $memberRepository,
        private OrderSettings $shopSettingRepository,
        private StampEarnPolicy $stampEarnPolicy,
        private LotteryEntitlementService $lotteryEntitlementService,
        private MemberRankSyncService $memberRankSyncService,
        private MemberCurrentStampCardResolver $memberCurrentStampCardResolver,
    ) {}

    public function revokeLottery(Order $order): void
    {
        if ($order->user?->member !== null) {
            $this->lotteryEntitlementService->revokePendingForSource(
                $order->user->member, LotterySourceType::ORDER, (string) $order->id,
            );
        }
    }

    public function applyStripePaymentRewardsIfNeeded(Order $order): void
    {
        if ($order->payment_method !== PaymentMethod::STRIPE) {
            return;
        }
        if ($order->status === OrderStatus::PENDING || $order->status === OrderStatus::CANCELLED) {
            return;
        }

        $order->loadMissing('user.member');
        $user = $order->user;
        if ($user === null) {
            return;
        }

        $finalAmount = (float) $order->total_amount;
        if ((int) $order->points_earned <= 0) {
            $this->awardPoints($user, $order, $finalAmount);
            $order->refresh();
        }

        $projectId = (int) $order->project_id;
        // 回数券の購入は来店ではないため、来店記録とスタンプは付けない
        $isVisit = $order->order_type !== OrderType::TICKET;
        $hasVisit = ! $isVisit || MemberVisit::query()
            ->where('project_id', $projectId)
            ->where('order_id', (int) $order->id)
            ->exists();
        if ($hasVisit === false) {
            $this->recordMemberVisit($user, $order);
        }

        $hasStamp = ! $isVisit || MemberStampTransaction::query()
            ->where('project_id', $projectId)
            ->where('order_id', (int) $order->id)
            ->exists();
        if ($hasStamp === false) {
            $this->awardStamps($user, $order, $finalAmount);
        }

        $this->awardLotteryEntitlements($user, $order, $finalAmount);
        $this->syncMemberRank($user);
    }

    public function applyCashOrderLotteryIfNeeded(Order $order): void
    {
        if ($order->payment_method !== PaymentMethod::CASH) {
            return;
        }
        if ($order->status !== OrderStatus::COMPLETED) {
            return;
        }

        $order->loadMissing('user.member');
        $user = $order->user;
        if ($user === null) {
            return;
        }

        $this->awardLotteryEntitlements($user, $order, (float) $order->total_amount);
    }

    public function applyOrderPointChanges(
        User $user,
        Order $order,
        int $pointsUsed,
        float $finalAmount,
    ): void {
        $this->consumePointsForOrder($user, $order, $pointsUsed);
        $this->awardPoints($user, $order, $finalAmount);
    }

    public function consumePointsForOrder(User $user, Order $order, int $pointsUsed): void
    {
        if ($pointsUsed <= 0) {
            return;
        }

        $member = $this->loadMemberForPointUpdate($user, (int) $order->project_id);
        if ($member->displayPoints() < $pointsUsed) {
            throw new OrderOperationException('ポイントが不足しています');
        }

        $member->usePoints($pointsUsed, '注文で使用', (int) $order->id);
        $this->memberRepository->save($member);
    }

    private function awardPoints(User $user, Order $order, float $finalAmount): void
    {
        $projectId = (int) $order->project_id;
        if ($this->shopSettingRepository->getUsePoints($projectId) !== true) {
            return;
        }
        $member = $this->memberRepository->findByUserId((int) $user->id, $projectId);
        if ($member === null) {
            return;
        }

        $pointsPerOrder = $this->shopSettingRepository->getPointsPerOrder($projectId);
        $rankKey = $user->member !== null && is_string($user->member->rank) ? (string) $user->member->rank : null;
        $rate = $this->getPointsEarnRate($projectId, $rankKey);
        $pointsFromAmount = $rate > 0 && $finalAmount > 0 ? (int) floor($finalAmount / $rate) : 0;
        $pointsEarned = $pointsPerOrder + $pointsFromAmount;

        if ($pointsEarned <= 0) {
            return;
        }

        $member = $this->loadMemberForPointUpdate($user, (int) $order->project_id);
        $validDays = $this->shopSettingRepository->getPointsValidDays($projectId);
        $member->earnPoints(
            $pointsEarned,
            '購入で獲得',
            (int) $order->id,
            now()->addDays($validDays),
        );
        $this->memberRepository->save($member);
        $order->update(['points_earned' => $pointsEarned]);
    }

    private function loadMemberForPointUpdate(User $user, int $projectId): MemberAggregate
    {
        $member = $this->memberRepository->findByUserIdForUpdate((int) $user->id, $projectId);
        if ($member === null) {
            throw new OrderOperationException('会員登録が必要です');
        }

        $this->memberRepository->syncPointsLedger($member->id()->value(), $projectId);
        $locked = $this->memberRepository->findByUserIdForUpdate((int) $user->id, $projectId);
        if ($locked === null) {
            throw new OrderOperationException('会員登録が必要です');
        }

        return $locked;
    }

    public function recordMemberVisit(User $user, Order $order): void
    {
        DB::transaction(function () use ($user, $order): void {
            $member = $user->member()->where('project_id', (int) $order->project_id)->lockForUpdate()->first();
            if ($member === null) {
                return;
            }

            $this->memberVisitRecorder->record(
                $member,
                $order->created_at ?? now(),
                MemberVisitSource::ORDER,
                (int) $order->id
            );
        }, 3);
    }

    public function awardStamps(User $user, Order $order, float $finalAmount): void
    {
        DB::transaction(function () use ($user, $order, $finalAmount): void {
            $member = $user->member()->where('project_id', (int) $order->project_id)->lockForUpdate()->first();
            if ($member === null || $finalAmount <= 0) {
                return;
            }

            $projectId = (int) $order->project_id;
            if ($this->shopSettingRepository->getUseStamps($projectId) !== true) {
                return;
            }
            $stampCard = $this->memberCurrentStampCardResolver->resolve($member);
            $rankKey = is_string($member->rank) ? $member->rank : null;
            $stampEarnPerAmount = $this->shopSettingRepository->getStampEarnPerAmountForRank($projectId, $rankKey);

            if ($stampCard === null || $stampEarnPerAmount <= 0) {
                return;
            }

            $stampCount = $this->stampEarnPolicy->calculateCount($finalAmount, $stampEarnPerAmount);
            if ($stampCount > 0) {
                $this->memberBenefitService->addStampsToMember(
                    $member,
                    (int) $stampCard->id,
                    $stampCount,
                    '注文によるスタンプ付与',
                    (int) $order->id,
                    MemberCouponSource::ORDER,
                    (string) $order->id,
                );
            }
        }, 3);
    }

    public function awardLotteryEntitlements(User $user, Order $order, float $finalAmount): void
    {
        DB::transaction(function () use ($user, $order, $finalAmount): void {
            $member = $user->member()->where('project_id', (int) $order->project_id)->lockForUpdate()->first();
            if ($member === null || $finalAmount <= 0) {
                return;
            }

            $this->lotteryEntitlementService->grantForPurchase(
                $member,
                $finalAmount,
                LotterySourceType::ORDER,
                (string) $order->id,
            );
        }, 3);
    }

    private function getPointsEarnRate(int $projectId, ?string $rankKey = null): int
    {
        $rate = $this->shopSettingRepository->getPointEarnRateForRank($projectId, $rankKey);

        return $rate > 0 ? $rate : self::DEFAULT_POINTS_EARN_RATE;
    }

    public function syncMemberRank(User $user): void
    {
        $member = $user->member;
        if ($member === null) {
            return;
        }
        $fresh = $member->fresh() ?? $member;
        $this->memberRankSyncService->sync($fresh);
    }
}

<?php

namespace App\Services;

use App\Domain\Member\Aggregates\Member as MemberAggregate;
use App\Domain\Member\Repositories\MemberRepository;
use App\Domain\Order\Events\OrderPointsSyncRequested;
use App\Domain\Order\Events\OrderTransactionSyncRequested;
use App\Domain\Order\Exceptions\OrderOperationException;
use App\Domain\Order\Policies\OrderPlacementPolicy;
use App\Domain\Order\Policies\ProductAvailabilityPolicy;
use App\Domain\Order\Policies\ProductOptionSelectionPolicy;
use App\Domain\Order\Policies\StampEarnPolicy;
use App\Domain\Order\Repositories\OrderRepository;
use App\Domain\Order\Repositories\ProductCatalogRepository;
use App\Domain\Order\Repositories\ProductStockRepository;
use App\Domain\Order\ValueObjects\OrderLineItem;
use App\Domain\Order\ValueObjects\PendingOrder;
use App\Domain\Settings\Repositories\OrderSettings;
use App\Enums\LotterySourceType;
use App\Enums\MemberCouponSource;
use App\Enums\MemberVisitSource;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Enums\PointTransactionType;
use App\Models\MemberStampTransaction;
use App\Models\MemberVisit;
use App\Models\Order;
use App\Models\Project;
use App\Models\Settings\ProjectPaymentSetting;
use App\Models\User;
use App\Services\Admin\MemberAdminService;
use App\Services\Admin\MemberBenefitService;
use App\Services\Lottery\LotteryEntitlementService;
use App\Services\Stamp\MemberCurrentStampCardResolver;
use App\Util\AdminContext;
use Illuminate\Support\Facades\DB;

class OrderService
{
    private const DEFAULT_POINTS_EARN_RATE = 100;

    public function __construct(
        private MemberAdminService $memberAdminService,
        private MemberBenefitService $memberBenefitService,
        private MemberRepository $memberRepository,
        private OrderRepository $orderRepository,
        private ProductStockRepository $productStockRepository,
        private ProductCatalogRepository $productCatalogRepository,
        private OrderSettings $shopSettingRepository,
        private PostCommitDomainEventDispatcher $postCommitEvents,
        private OrderPlacementPolicy $orderPlacementPolicy,
        private ProductAvailabilityPolicy $productAvailabilityPolicy,
        private ProductOptionSelectionPolicy $productOptionSelectionPolicy,
        private StampEarnPolicy $stampEarnPolicy,
        private OrderNumberAssigner $orderNumberAssigner,
        private LotteryEntitlementService $lotteryEntitlementService,
        private MemberRankSyncService $memberRankSyncService,
        private MemberCurrentStampCardResolver $memberCurrentStampCardResolver,
    ) {}

    private const DESC_REFUND_CANCEL = '注文キャンセルによる返却';

    private const DESC_REVERSED_CANCEL = '注文キャンセルによる取り消し';

    public function createOrder(array $orderData, User $user): Order
    {
        [$order, $projectId, $paymentMethod] = DB::transaction(function () use ($orderData, $user): array {
            $projectId = (int) (AdminContext::resolveProjectId($user->project_id !== null ? (int) $user->project_id : null));
            $orderType = (string) ($orderData['order_type'] ?? OrderType::MOBILE);
            $paymentMethod = (string) ($orderData['payment_method'] ?? '');

            $this->orderPlacementPolicy->assertValid(
                $orderType,
                $paymentMethod,
                $this->shopSettingRepository->getOrderMode($projectId),
                (string) config('native_app.order_type_mobile_label', 'Reggit'),
            );

            $orderItems = $this->validateAndPrepareOrderItems($orderData['items'], $projectId);
            $lineItems = $this->toOrderLineItems($orderItems);
            $totalAmount = OrderLineItem::sumTotal($lineItems);

            $paymentSetting = ProjectPaymentSetting::current($projectId);
            $brand = $paymentMethod === PaymentMethod::STRIPE ? ($orderData['payment_brand'] ?? 'card') : 'cash';
            if ($orderType === OrderType::MOBILE && $paymentSetting !== null && ! in_array($brand, $paymentSetting->methods, true)) {
                throw new OrderOperationException('このお支払い方法は現在利用できません');
            }
            if ($paymentSetting === null && ($brand !== 'card' && $brand !== 'cash')) {
                throw new OrderOperationException('このお支払い方法は現在利用できません');
            }
            if ($paymentMethod === PaymentMethod::STRIPE && $paymentSetting === null
                && Project::find($projectId)?->project_key !== (config('app.default_project_key') ?: 'default')) {
                throw new OrderOperationException('このプロジェクトの決済設定を登録してください');
            }
            $pointsUsed = (int) ($orderData['points_used'] ?? 0);
            $finalAmount = max(0, $totalAmount - $pointsUsed);
            if ($paymentMethod === PaymentMethod::STRIPE && $finalAmount < 1) {
                throw new OrderOperationException('オンライン決済の金額は1円以上必要です。ポイント利用額を減らしてください');
            }
            if ($brand === 'paypay' && ($finalAmount < 50 || $finalAmount > 1000000)) {
                throw new OrderOperationException('PayPayのお支払い金額は50円〜100万円です');
            }

            $orderId = $this->orderRepository->persistPending(
                new PendingOrder(
                    $projectId,
                    (int) $user->id,
                    $orderType,
                    $paymentMethod,
                    $finalAmount,
                    $pointsUsed,
                    $orderData['notes'] ?? null,
                ),
                $lineItems,
            );

            $order = Order::findOrFail($orderId);
            $order->payment_brand = $brand;
            $order->payment_setting_id = $paymentMethod === PaymentMethod::STRIPE ? $paymentSetting?->id : null;
            $order->save();
            if ($paymentMethod !== PaymentMethod::STRIPE) {
                $this->orderNumberAssigner->assignIfMissing((int) $order->id, $projectId);
                $order->refresh();
            }
            $this->updateProductStock($orderItems, $projectId);
            if ($paymentMethod === PaymentMethod::STRIPE) {
                // 決済前はポイント消費のみ。獲得・来店・スタンプ・ガチャは決済確定後
                $this->consumePointsForOrder($user, $order, $pointsUsed);
            } else {
                $this->applyOrderPointChanges($user, $order, $pointsUsed, $finalAmount);
                $this->recordMemberVisit($user, $order);
                $this->awardStamps($user, $order, $finalAmount);
                // 現金（店内注文・後で決済）のガチャは取置き時点では付与しない。注文完了時に付与する
                if ($paymentMethod !== PaymentMethod::CASH) {
                    $this->awardLotteryEntitlements($user, $order, $finalAmount);
                }
            }

            return [$order, $projectId, $paymentMethod];
        });

        // commit 後に同期する（失敗しても注文は残す。try/catch 外で rollBack しない）
        if ($paymentMethod !== PaymentMethod::STRIPE) {
            $this->syncMemberRank($user);
            $this->postCommitEvents->dispatch(new OrderPointsSyncRequested(
                (int) $order->id,
                $projectId,
                (int) $user->id,
            ));
            $this->postCommitEvents->dispatch(new OrderTransactionSyncRequested(
                (int) $order->id,
                $projectId,
                (int) $user->id,
            ));
        }

        return $order->load('items.product');
    }

    /**
     * Stripe 決済確定後の付与（冪等）。ポイント獲得・来店・スタンプ・ガチャ権利。
     */
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
        $hasVisit = MemberVisit::query()
            ->where('project_id', $projectId)
            ->where('order_id', (int) $order->id)
            ->exists();
        if ($hasVisit === false) {
            $this->recordMemberVisit($user, $order);
        }

        $hasStamp = MemberStampTransaction::query()
            ->where('project_id', $projectId)
            ->where('order_id', (int) $order->id)
            ->exists();
        if ($hasStamp === false) {
            $this->awardStamps($user, $order, $finalAmount);
        }

        $this->awardLotteryEntitlements($user, $order, $finalAmount);
        $this->syncMemberRank($user);
    }

    /**
     * 現金注文の取引完了後のガチャ権利付与（冪等）。
     * 取置き登録時点では付与せず、管理画面での完了（引取・会計確定）時に付与する。
     */
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

    /**
     * @param  array<int, array{product_id: int, quantity: int, price: float, notes?: string|null, product_name?: string|null}>  $orderItems
     * @return list<OrderLineItem>
     */
    private function toOrderLineItems(array $orderItems): array
    {
        $lineItems = [];
        foreach ($orderItems as $item) {
            $lineItems[] = new OrderLineItem(
                (int) $item['product_id'],
                (int) $item['quantity'],
                (float) $item['price'],
                $item['notes'] ?? null,
                isset($item['product_name']) ? (string) $item['product_name'] : null,
                $item['option_ids'] ?? [],
            );
        }

        return $lineItems;
    }

    private function validateAndPrepareOrderItems(array $items, int $projectId): array
    {
        $orderItems = [];

        foreach ($items as $item) {
            $product = $this->productCatalogRepository->findForOrderOrFail((int) $item['product_id'], $projectId);
            $quantity = (int) $item['quantity'];

            $this->productAvailabilityPolicy->assertAvailable(
                $product->name,
                $product->isAvailable,
                $product->stock,
                $quantity,
            );

            [$optionsDelta, $optionsSummary] = $this->productOptionSelectionPolicy->resolve(
                $product->name,
                $product->optionGroups,
                $item['option_ids'] ?? [],
            );

            $unitPrice = $product->price + $optionsDelta;
            $note = $item['notes'] ?? null;
            if ($optionsSummary !== '') {
                $note = $note ? $optionsSummary.' / '.$note : $optionsSummary;
            }

            $orderItems[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $quantity,
                'price' => $unitPrice,
                'notes' => $note,
                'option_ids' => array_values(array_unique(array_map('intval', $item['option_ids'] ?? []))),
            ];
        }

        return $orderItems;
    }

    private function applyOrderPointChanges(
        User $user,
        Order $order,
        int $pointsUsed,
        float $finalAmount,
    ): void {
        $this->consumePointsForOrder($user, $order, $pointsUsed);
        $this->awardPoints($user, $order, $finalAmount);
    }

    private function consumePointsForOrder(User $user, Order $order, int $pointsUsed): void
    {
        if ($pointsUsed <= 0) {
            return;
        }

        $member = $this->loadMemberForPointUpdate($user);
        if ($member->displayPoints() < $pointsUsed) {
            throw new OrderOperationException('ポイントが不足しています');
        }

        $member->usePoints($pointsUsed, '注文で使用', (int) $order->id);
        $this->memberRepository->save($member);
    }

    private function updateProductStock(array $orderItems, int $projectId): void
    {
        foreach ($orderItems as $item) {
            $this->productStockRepository->decrease(
                (int) $item['product_id'],
                $projectId,
                (int) $item['quantity'],
            );
        }
    }

    private function awardPoints(User $user, Order $order, float $finalAmount): void
    {
        $projectId = (int) ($user->project_id ?? 0);
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

        $member = $this->loadMemberForPointUpdate($user);
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

    private function loadMemberForPointUpdate(User $user): MemberAggregate
    {
        $projectId = (int) ($user->project_id ?? 0);
        $member = $this->memberRepository->findByUserId((int) $user->id, $projectId);
        if ($member === null) {
            throw new OrderOperationException('会員登録が必要です');
        }

        $this->memberRepository->syncPointsLedger($member->id()->value(), $projectId);
        $locked = $this->memberRepository->findByIdForUpdate($member->id()->value(), $projectId);
        if ($locked === null) {
            throw new OrderOperationException('会員登録が必要です');
        }

        return $locked;
    }

    private function recordMemberVisit(User $user, Order $order): void
    {
        $member = $user->member;
        if ($member === null) {
            return;
        }

        $this->memberAdminService->recordVisit(
            $member,
            $order->created_at ?? now(),
            MemberVisitSource::ORDER,
            (int) $order->id
        );
    }

    private function awardStamps(User $user, Order $order, float $finalAmount): void
    {
        $member = $user->member;
        if ($member === null || $finalAmount <= 0) {
            return;
        }

        $projectId = (int) ($user->project_id ?? 0);
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
    }

    private function awardLotteryEntitlements(User $user, Order $order, float $finalAmount): void
    {
        $member = $user->member;
        if ($member === null || $finalAmount <= 0) {
            return;
        }

        $this->lotteryEntitlementService->grantForPurchase(
            $member,
            $finalAmount,
            LotterySourceType::ORDER,
            (string) $order->id,
        );
    }

    private function getPointsEarnRate(int $projectId, ?string $rankKey = null): int
    {
        $rate = $this->shopSettingRepository->getPointEarnRateForRank($projectId, $rankKey);

        return $rate > 0 ? $rate : self::DEFAULT_POINTS_EARN_RATE;
    }

    private function syncMemberRank(User $user): void
    {
        $member = $user->member;
        if ($member === null) {
            return;
        }
        $fresh = $member->fresh() ?? $member;
        $this->memberRankSyncService->sync($fresh);
    }

    public function handleOrderCancellation(Order $order): void
    {
        $projectId = (int) ($order->project_id ?? 0);
        $aggregate = $this->orderRepository->findById((int) $order->id, $projectId);
        if ($aggregate === null || $aggregate->isCancelled() === false) {
            return;
        }

        $order->load('user', 'items.product');

        DB::transaction(function () use ($order, $projectId): void {
            if ($order->user !== null) {
                $member = $this->memberRepository->findByUserId((int) $order->user_id, $projectId);
                if ($member !== null) {
                    $member = $this->memberRepository->findByIdForUpdate($member->id()->value(), $projectId);
                }
                if ($member !== null) {
                    $this->refundPointsUsed($order, $member);
                    $this->reversePointsEarned($order, $member);
                    $this->memberRepository->save($member);
                }
                if ($order->user->member !== null) {
                    $this->lotteryEntitlementService->revokePendingForSource(
                        $order->user->member,
                        LotterySourceType::ORDER,
                        (string) $order->id,
                    );
                }
            }

            $this->restoreProductStock($order, $projectId);
        });
        if ($order->user !== null) {
            $this->syncMemberRank($order->user);
        }
        // 未決済キャンセル専用。作成時にスマレジ未同期のため差分同期しない。
    }

    private function refundPointsUsed(Order $order, MemberAggregate $member): int
    {
        if ($order->points_used <= 0) {
            return 0;
        }

        if ($this->memberRepository->hasPointTransactionForOrder(
            (int) $order->id,
            PointTransactionType::REFUNDED,
            self::DESC_REFUND_CANCEL,
        )) {
            return 0;
        }

        $points = (int) $order->points_used;
        $member->refundUsedPointsForOrder(
            (int) $order->id,
            $points,
            self::DESC_REFUND_CANCEL,
        );

        return $points;
    }

    private function reversePointsEarned(Order $order, MemberAggregate $member): int
    {
        if ($order->points_earned <= 0) {
            return 0;
        }

        if ($this->memberRepository->hasPointTransactionForOrder(
            (int) $order->id,
            PointTransactionType::REVERSED,
        )) {
            return 0;
        }

        $points = (int) $order->points_earned;
        $member->reverseEarnedPointsForOrder(
            (int) $order->id,
            $points,
            self::DESC_REVERSED_CANCEL,
        );

        return $points;
    }

    private function restoreProductStock(Order $order, int $projectId): void
    {
        foreach ($order->items as $item) {
            $this->productStockRepository->restore(
                (int) $item->product_id,
                $projectId,
                (int) $item->quantity,
            );
        }
    }
}

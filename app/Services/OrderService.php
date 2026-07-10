<?php

namespace App\Services;

use App\Domain\Member\Aggregates\Member as MemberAggregate;
use App\Domain\Member\Events\PointsEarned;
use App\Domain\Member\Repositories\MemberRepository;
use App\Domain\Order\Policies\OrderPlacementPolicy;
use App\Domain\Order\Policies\ProductAvailabilityPolicy;
use App\Domain\Order\Policies\ProductOptionSelectionPolicy;
use App\Domain\Order\Policies\StampEarnPolicy;
use App\Domain\Order\Repositories\OrderRepository;
use App\Domain\Order\Repositories\ProductCatalogRepository;
use App\Domain\Order\Repositories\ProductStockRepository;
use App\Domain\Order\ValueObjects\OrderLineItem;
use App\Domain\Order\ValueObjects\PendingOrder;
use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Domain\Shared\DomainEventDispatcher\DomainEventDispatcher;
use App\Enums\OrderType;
use App\Enums\PointTransactionType;
use App\Models\Order;
use App\Models\User;
use App\Services\Admin\MemberAdminService;
use App\Util\AdminContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    private const DEFAULT_POINTS_EARN_RATE = 100;

    public function __construct(
        private MemberAdminService $memberAdminService,
        private MemberRepository $memberRepository,
        private OrderRepository $orderRepository,
        private ProductStockRepository $productStockRepository,
        private ProductCatalogRepository $productCatalogRepository,
        private ShopSettingRepository $shopSettingRepository,
        private DomainEventDispatcher $domainEvents,
        private OrderPlacementPolicy $orderPlacementPolicy,
        private ProductAvailabilityPolicy $productAvailabilityPolicy,
        private ProductOptionSelectionPolicy $productOptionSelectionPolicy,
        private StampEarnPolicy $stampEarnPolicy,
    ) {}

    private const DESC_REFUND_CANCEL = '注文キャンセルによる返却';

    private const DESC_REVERSED_CANCEL = '注文キャンセルによる取り消し';

    public function createOrder(array $orderData, User $user): Order
    {
        DB::beginTransaction();
        try {
            $projectId = (int) ($user->project_id ?? AdminContext::getCurrentProjectId() ?? 1);
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

            $pointsUsed = (int) ($orderData['points_used'] ?? 0);
            $finalAmount = max(0, $totalAmount - $pointsUsed);

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
            $this->updateProductStock($orderItems, $projectId);
            $pointEvents = $this->applyOrderPointChanges($user, $order, $pointsUsed, $finalAmount);
            $this->awardStamps($user, $order, $finalAmount);

            DB::commit();

            foreach ($pointEvents as $pointEvent) {
                $this->domainEvents->dispatch($pointEvent);
            }

            return $order->load('items.product');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order creation error: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * @param  array<int, array{product_id: int, quantity: int, price: float, notes?: string|null}>  $orderItems
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
                'quantity' => $quantity,
                'price' => $unitPrice,
                'notes' => $note,
            ];
        }

        return $orderItems;
    }

    /**
     * @return list<PointsEarned>
     */
    private function applyOrderPointChanges(
        User $user,
        Order $order,
        int $pointsUsed,
        float $finalAmount,
    ): array {
        $events = [];
        $usedEvent = $this->consumePointsForOrder($user, $order, $pointsUsed);
        if ($usedEvent !== null) {
            $events[] = $usedEvent;
        }

        $earnedEvent = $this->awardPoints($user, $order, $finalAmount);
        if ($earnedEvent !== null) {
            $events[] = $earnedEvent;
        }

        return $events;
    }

    private function consumePointsForOrder(User $user, Order $order, int $pointsUsed): ?PointsEarned
    {
        if ($pointsUsed <= 0) {
            return null;
        }

        $member = $this->loadMemberForPointUpdate($user);
        if ($member->displayPoints() < $pointsUsed) {
            throw new \Exception('ポイントが不足しています');
        }

        $orderId = (int) $order->id;
        $member->usePoints($pointsUsed, '注文で使用', $orderId);
        $this->memberRepository->save($member);

        return new PointsEarned(
            $member->id()->value(),
            $member->projectId(),
            -$pointsUsed,
            $orderId,
        );
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

    private function awardPoints(User $user, Order $order, float $finalAmount): ?PointsEarned
    {
        $projectId = (int) ($user->project_id ?? 1);
        $member = $this->memberRepository->findByUserId((int) $user->id, $projectId);
        if ($member === null) {
            return null;
        }

        $pointsPerOrder = $this->shopSettingRepository->getPointsPerOrder($projectId);
        $rate = $this->getPointsEarnRate($projectId);
        $pointsFromAmount = $rate > 0 && $finalAmount > 0 ? (int) floor($finalAmount / $rate) : 0;
        $pointsEarned = $pointsPerOrder + $pointsFromAmount;

        if ($pointsEarned <= 0) {
            return null;
        }

        $validDays = $this->shopSettingRepository->getPointsValidDays($projectId);
        $orderId = (int) $order->id;
        $member->earnPoints(
            $pointsEarned,
            '購入で獲得',
            $orderId,
            now()->addDays($validDays),
        );
        $this->memberRepository->save($member);
        $order->update(['points_earned' => $pointsEarned]);

        return new PointsEarned(
            $member->id()->value(),
            $member->projectId(),
            $pointsEarned,
            $orderId,
        );
    }

    private function loadMemberForPointUpdate(User $user): MemberAggregate
    {
        $projectId = (int) ($user->project_id ?? 1);
        $member = $this->memberRepository->findByUserId((int) $user->id, $projectId);
        if ($member === null) {
            throw new \Exception('会員登録が必要です');
        }

        $this->memberRepository->syncPointsLedger($member->id()->value(), $projectId);
        $locked = $this->memberRepository->findByIdForUpdate($member->id()->value(), $projectId);
        if ($locked === null) {
            throw new \Exception('会員登録が必要です');
        }

        return $locked;
    }

    private function awardStamps(User $user, Order $order, float $finalAmount): void
    {
        $member = $user->member;
        if ($member === null || $finalAmount <= 0) {
            return;
        }

        $projectId = (int) ($user->project_id ?? 1);
        $stampCardId = $this->shopSettingRepository->getOrderStampCardId($projectId);
        $stampEarnPerAmount = $this->shopSettingRepository->getStampEarnPerAmount($projectId);

        if ($stampCardId === null || $stampEarnPerAmount <= 0) {
            return;
        }

        $stampCount = $this->stampEarnPolicy->calculateCount($finalAmount, $stampEarnPerAmount);
        if ($stampCount > 0) {
            $this->memberAdminService->addStampsToMember($member, $stampCardId, $stampCount);
        }
    }

    private function getPointsEarnRate(int $projectId): int
    {
        $rate = $this->shopSettingRepository->getPointEarnRate($projectId);

        return $rate > 0 ? $rate : self::DEFAULT_POINTS_EARN_RATE;
    }

    public function handleOrderCancellation(Order $order): void
    {
        $projectId = (int) ($order->project_id ?? 1);
        $aggregate = $this->orderRepository->findById((int) $order->id, $projectId);
        if ($aggregate === null || $aggregate->isCancelled() === false) {
            return;
        }

        $order->load('user', 'items.product');
        $pointEvents = [];

        DB::beginTransaction();
        try {
            if ($order->user !== null) {
                $member = $this->memberRepository->findByUserId((int) $order->user_id, $projectId);
                if ($member !== null) {
                    $refundedPoints = $this->refundPointsUsed($order, $member);
                    $reversedPoints = $this->reversePointsEarned($order, $member);
                    $this->memberRepository->save($member);
                    $orderId = (int) $order->id;
                    if ($refundedPoints > 0) {
                        $pointEvents[] = new PointsEarned(
                            $member->id()->value(),
                            $member->projectId(),
                            $refundedPoints,
                            $orderId,
                        );
                    }
                    if ($reversedPoints > 0) {
                        $pointEvents[] = new PointsEarned(
                            $member->id()->value(),
                            $member->projectId(),
                            -$reversedPoints,
                            $orderId,
                        );
                    }
                }
            }

            $this->restoreProductStock($order, $projectId);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order cancellation handling failed: '.$e->getMessage(), ['order_id' => $order->id]);
            throw $e;
        }

        foreach ($pointEvents as $pointEvent) {
            $this->domainEvents->dispatch($pointEvent);
        }
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

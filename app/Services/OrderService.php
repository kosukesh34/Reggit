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
use App\Domain\Order\Repositories\OrderRepository;
use App\Domain\Order\Repositories\ProductCatalogRepository;
use App\Domain\Order\Repositories\ProductStockRepository;
use App\Domain\Order\ValueObjects\OrderLineItem;
use App\Domain\Order\ValueObjects\PendingOrder;
use App\Domain\Settings\Repositories\OrderSettings;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Enums\PointTransactionType;
use App\Models\Order;
use App\Models\Project;
use App\Models\Settings\ProjectPaymentSetting;
use App\Models\User;
use App\Services\Member\CurrentMemberPrincipal;
use App\Services\Ticket\MemberTicketService;
use App\Support\JapanPrefectures;
use App\Support\MemberChannel;
use App\Util\AdminContext;
use App\Util\StoreContext;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private MemberRepository $memberRepository,
        private OrderRewardService $rewards,
        private StoreContext $storeContext,
        private OrderRepository $orderRepository,
        private ProductStockRepository $productStockRepository,
        private ProductCatalogRepository $productCatalogRepository,
        private OrderSettings $shopSettingRepository,
        private PostCommitDomainEventDispatcher $postCommitEvents,
        private OrderPlacementPolicy $orderPlacementPolicy,
        private ProductAvailabilityPolicy $productAvailabilityPolicy,
        private ProductOptionSelectionPolicy $productOptionSelectionPolicy,
        private OrderNumberAssigner $orderNumberAssigner,
        private CurrentMemberPrincipal $principals,
        private MemberTicketService $tickets,
    ) {}

    private const DESC_REFUND_CANCEL = '注文キャンセルによる返却';

    private const DESC_REVERSED_CANCEL = '注文キャンセルによる取り消し';

    public function createOrder(array $orderData, User $user): Order
    {
        [$order, $projectId, $paymentMethod] = DB::transaction(function () use ($orderData, $user): array {
            $projectId = (int) (AdminContext::resolveProjectId($user->project_id !== null ? (int) $user->project_id : null));
            $user = $this->principals->lock($user, $projectId);
            if ($user === null) {
                throw new OrderOperationException('ログインし直してください');
            }
            $orderType = (string) ($orderData['order_type'] ?? OrderType::MOBILE);
            $paymentMethod = (string) ($orderData['payment_method'] ?? '');
            $this->orderPlacementPolicy->assertValid(
                $orderType,
                $paymentMethod,
                $this->shopSettingRepository->getOrderMode($projectId),
                (string) config('native_app.order_type_mobile_label', 'Reggit'),
                $this->shopSettingRepository->isFeatureEnabled('feature_shop_enabled', $projectId),
                $this->shopSettingRepository->isFeatureEnabled('feature_tickets_enabled', $projectId),
            );
            if ($orderType === OrderType::TICKET && $user->member === null) {
                throw new OrderOperationException('回数券の購入には会員登録が必要です');
            }
            if ($orderType === OrderType::SHIPPING && ! $this->hasShopBuyerIdentity($user)) {
                throw new OrderOperationException('ショップでのご注文にはログインが必要です。');
            }

            $orderItems = $this->validateAndPrepareOrderItems($orderData['items'], $projectId, $orderType === OrderType::TICKET);
            $lineItems = $this->toOrderLineItems($orderItems);
            $totalAmount = OrderLineItem::sumTotal($lineItems);
            $shippingFee = 0;
            $shippingPrefecture = '';
            if ($orderType === OrderType::SHIPPING) {
                foreach (['shipping_name', 'shipping_phone', 'shipping_postal_code', 'shipping_prefecture', 'shipping_address'] as $field) {
                    if (trim((string) ($orderData[$field] ?? '')) === '') {
                        throw new OrderOperationException('配送先情報を入力してください');
                    }
                }
                $shippingPrefecture = trim((string) $orderData['shipping_prefecture']);
                if (! JapanPrefectures::isValid($shippingPrefecture)) {
                    throw new OrderOperationException('都道府県を選択してください');
                }
                $shippingFee = $this->shopSettingRepository->getShopShippingFeeForPrefecture($projectId, $shippingPrefecture);
                $totalAmount += $shippingFee;
            }

            $paymentSetting = ProjectPaymentSetting::current($projectId);
            $brand = $paymentMethod === PaymentMethod::STRIPE ? ($orderData['payment_brand'] ?? 'card') : 'cash';
            if (in_array($orderType, [OrderType::MOBILE, OrderType::SHIPPING], true)
                && $paymentSetting !== null
                && ! in_array($brand, $paymentSetting->methods, true)) {
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
            if ($pointsUsed > $totalAmount) {
                throw new OrderOperationException('ポイント利用額は注文金額以下にしてください');
            }
            if ($pointsUsed > 0 && ! $this->shopSettingRepository->getUsePoints($projectId)) {
                throw new OrderOperationException('ポイントは現在利用できません');
            }
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
                    $this->storeContext->resolveForWrite($projectId),
                ),
                $lineItems,
            );

            $order = Order::findOrFail($orderId);
            $order->payment_brand = $brand;
            $order->payment_setting_id = $paymentMethod === PaymentMethod::STRIPE ? $paymentSetting?->id : null;
            if ($orderType === OrderType::SHIPPING) {
                $order->shipping_name = trim((string) $orderData['shipping_name']);
                $order->shipping_phone = trim((string) $orderData['shipping_phone']);
                $order->shipping_postal_code = trim((string) $orderData['shipping_postal_code']);
                $order->shipping_prefecture = $shippingPrefecture;
                $order->shipping_address = trim((string) $orderData['shipping_address']);
                $order->shipping_fee = $shippingFee;
            }
            $order->save();
            if ($paymentMethod !== PaymentMethod::STRIPE) {
                $this->orderNumberAssigner->assignIfMissing((int) $order->id, $projectId);
                $order->refresh();
            }
            $this->updateProductStock($orderItems, $projectId);
            if ($paymentMethod === PaymentMethod::STRIPE) {
                // 決済前はポイント消費のみ。獲得・来店・スタンプ・ガチャは決済確定後
                $this->rewards->consumePointsForOrder($user, $order, $pointsUsed);
            } else {
                $this->rewards->applyOrderPointChanges($user, $order, $pointsUsed, $finalAmount);
                $this->rewards->recordMemberVisit($user, $order);
                $this->rewards->awardStamps($user, $order, $finalAmount);
                // 現金（店内注文・後で決済）のガチャは取置き時点では付与しない。注文完了時に付与する
                if ($paymentMethod !== PaymentMethod::CASH) {
                    $this->rewards->awardLotteryEntitlements($user, $order, $finalAmount);
                }
            }

            if ($paymentMethod !== PaymentMethod::STRIPE && $orderType !== OrderType::POS) {
                $order->smaregi_create_pending = true;
                $order->smaregi_create_retry_at = now();
                $order->save();
            }

            return [$order, $projectId, $paymentMethod];
        });

        // commit 後に同期する（失敗しても注文は残す。try/catch 外で rollBack しない）
        if ($paymentMethod !== PaymentMethod::STRIPE) {
            $this->rewards->syncMemberRank($user);
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
        $this->rewards->applyStripePaymentRewardsIfNeeded($order);
        $this->tickets->issueForPaidOrder($order);
    }

    /**
     * 現金注文の取引完了後のガチャ権利付与（冪等）。
     * 取置き登録時点では付与せず、管理画面での完了（引取・会計確定）時に付与する。
     */
    public function applyCashOrderLotteryIfNeeded(Order $order): void
    {
        $this->rewards->applyCashOrderLotteryIfNeeded($order);
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

    private function validateAndPrepareOrderItems(array $items, int $projectId, bool $ticketOrder = false): array
    {
        $orderItems = [];

        foreach ($items as $item) {
            $product = $this->productCatalogRepository->findForOrderOrFail((int) $item['product_id'], $projectId);
            $quantity = (int) $item['quantity'];
            if ($product->isSubscription) {
                throw new OrderOperationException('サブスクは回数券の画面から申し込んでください');
            }
            if ($product->isTicket !== $ticketOrder) {
                throw new OrderOperationException($ticketOrder
                    ? '回数券以外の商品は一緒に購入できません'
                    : '回数券は回数券の画面から購入してください');
            }

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
                $member = $this->memberRepository->findByUserIdForUpdate((int) $order->user_id, $projectId);
                if ($member !== null) {
                    $this->refundPointsUsed($order, $member);
                    $this->reversePointsEarned($order, $member);
                    $this->memberRepository->save($member);
                }
                if ($order->user->member !== null) {
                    $this->rewards->revokeLottery($order);
                }
            }

            $this->restoreProductStock($order, $projectId);
        });
        if ($order->user !== null) {
            $this->rewards->syncMemberRank($order->user);
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

    private function hasShopBuyerIdentity(User $user): bool
    {
        if (MemberChannel::hasLine($user)) {
            return true;
        }
        $email = is_string($user->email) ? trim($user->email) : '';

        return $email !== '' && $user->email_verified_at !== null;
    }
}

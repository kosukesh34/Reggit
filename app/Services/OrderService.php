<?php

namespace App\Services;

use App\Domain\Order\Events\OrderPointsSyncRequested;
use App\Domain\Order\Events\OrderTransactionSyncRequested;
use App\Domain\Order\Exceptions\OrderOperationException;
use App\Domain\Order\Policies\OrderPaymentPolicy;
use App\Domain\Order\Policies\OrderPlacementPolicy;
use App\Domain\Order\Repositories\OrderRepository;
use App\Domain\Order\Repositories\ProductStockRepository;
use App\Domain\Order\ValueObjects\OrderLineItem;
use App\Domain\Order\ValueObjects\PendingOrder;
use App\Domain\Settings\Repositories\OrderSettings;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Project;
use App\Models\Settings\ProjectPaymentSetting;
use App\Models\User;
use App\Services\Member\CurrentMemberPrincipal;
use App\Services\Shop\ShippingOrderPreparer;
use App\Util\AdminContext;
use App\Util\StoreContext;
use Illuminate\Support\Facades\DB;

/** モバイル・配送・回数券・POS 注文の受付（検証・確保・ポイント消費と付与・コミット後同期）。 */
class OrderService
{
    public function __construct(
        private OrderRewardService $rewards,
        private StoreContext $storeContext,
        private OrderRepository $orderRepository,
        private ProductStockRepository $productStockRepository,
        private OrderSettings $shopSettingRepository,
        private PostCommitDomainEventDispatcher $postCommitEvents,
        private OrderPlacementPolicy $orderPlacementPolicy,
        private OrderNumberAssigner $orderNumberAssigner,
        private CurrentMemberPrincipal $principals,
        private ShippingOrderPreparer $shipping,
        private OrderPaymentPolicy $paymentPolicy,
        private OrderItemPreparer $items,
    ) {}

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
            if ($orderType === OrderType::SHIPPING) {
                $this->shipping->assertCanOrder($user, $projectId);
            }

            $orderItems = $this->items->validateAndPrepareOrderItems($orderData['items'], $projectId, $orderType === OrderType::TICKET);
            $lineItems = $this->items->toOrderLineItems($orderItems);
            $totalAmount = OrderLineItem::sumTotal($lineItems);
            $shippingFee = 0;
            $shippingPrefecture = '';
            if ($orderType === OrderType::SHIPPING) {
                $shippingDetails = $this->shipping->prepare($orderData, $projectId, $totalAmount);
                $shippingPrefecture = $shippingDetails['prefecture'];
                $shippingFee = $shippingDetails['fee'];
                $totalAmount += $shippingFee;
                $deliveryDate = $shippingDetails['delivery_date'];
                $deliveryTimeSlot = $shippingDetails['delivery_time_slot'];
            }

            $paymentSetting = ProjectPaymentSetting::current($projectId);
            $pointsUsed = (int) ($orderData['points_used'] ?? 0);
            ['brand' => $brand, 'final_amount' => $finalAmount] = $this->paymentPolicy->resolve(
                $orderType,
                $paymentMethod,
                $orderData['payment_brand'] ?? null,
                $paymentSetting?->methods,
                fn (): bool => Project::find($projectId)?->project_key === (config('app.default_project_key') ?: 'default'),
                $totalAmount,
                $pointsUsed,
                fn (): bool => $this->shopSettingRepository->getUsePoints($projectId),
            );

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
                $order->delivery_date = $deliveryDate;
                $order->delivery_time_slot = $deliveryTimeSlot;
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
}

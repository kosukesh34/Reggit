<?php

namespace App\Services;

use App\Domain\Order\Events\OrderPointsSyncRequested;
use App\Domain\Order\Events\OrderTransactionSyncRequested;
use App\Domain\Order\Exceptions\OrderOperationException;
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
use App\Services\Shop\ShopOperations;
use App\Support\JapanPrefectures;
use App\Support\MemberChannel;
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
        private ShopOperations $shopOperations,
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
            if ($orderType === OrderType::SHIPPING && ! $this->hasShopBuyerIdentity($user)) {
                throw new OrderOperationException('ショップでのご注文にはログインが必要です。');
            }
            if ($orderType === OrderType::SHIPPING && ! $this->shopOperations->isLegalNoticeComplete($projectId)) {
                throw new OrderOperationException('特定商取引法に基づく表記の準備中のため、現在ご注文いただけません。');
            }

            $orderItems = $this->items->validateAndPrepareOrderItems($orderData['items'], $projectId, $orderType === OrderType::TICKET);
            $lineItems = $this->items->toOrderLineItems($orderItems);
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
                $freeShippingThreshold = $this->shopOperations->freeShippingThreshold($projectId);
                $shippingFee = $freeShippingThreshold !== null && $totalAmount >= $freeShippingThreshold
                    ? 0
                    : $this->shopSettingRepository->getShopShippingFeeForPrefecture($projectId, $shippingPrefecture);
                $totalAmount += $shippingFee;
                [$deliveryDate, $deliveryTimeSlot] = $this->validatedDelivery($orderData, $projectId);
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

    /**
     * お届け希望日・時間帯（任意）。設定の範囲外は受け付けない。
     *
     * @return array{0: ?string, 1: ?string}
     */
    private function validatedDelivery(array $orderData, int $projectId): array
    {
        $date = trim((string) ($orderData['delivery_date'] ?? ''));
        $slot = trim((string) ($orderData['delivery_time_slot'] ?? ''));
        if ($date === '' && $slot === '') {
            return [null, null];
        }
        $options = $this->shopOperations->deliveryOptions($projectId);
        if (! $options['enabled']) {
            throw new OrderOperationException('お届け日時の指定は現在受け付けていません');
        }
        if ($date !== '' && ($date < $options['earliest'] || $date > $options['latest'])) {
            throw new OrderOperationException('お届け希望日は'.$options['earliest'].'から'.$options['latest'].'の間で選択してください');
        }
        if ($slot !== '' && ! in_array($slot, $options['time_slots'], true)) {
            throw new OrderOperationException('お届け時間帯を選択し直してください');
        }

        return [$date !== '' ? $date : null, $slot !== '' ? $slot : null];
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

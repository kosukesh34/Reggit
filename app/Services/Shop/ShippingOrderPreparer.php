<?php

namespace App\Services\Shop;

use App\Domain\Order\Exceptions\OrderOperationException;
use App\Domain\Settings\Repositories\OrderSettings;
use App\Models\User;
use App\Support\JapanPrefectures;
use App\Support\MemberChannel;

/** ショップ（配送）注文の受付条件と、配送先・送料・お届け日時の確定。 */
class ShippingOrderPreparer
{
    public function __construct(
        private ShopOperations $shopOperations,
        private OrderSettings $shopSettingRepository,
    ) {}

    /** 本人確認できる購入者で、特定商取引法の表記が揃っていること */
    public function assertCanOrder(User $user, int $projectId): void
    {
        if (! $this->hasShopBuyerIdentity($user)) {
            throw new OrderOperationException('ショップでのご注文にはログインが必要です。');
        }
        if (! $this->shopOperations->isLegalNoticeComplete($projectId)) {
            throw new OrderOperationException('特定商取引法に基づく表記の準備中のため、現在ご注文いただけません。');
        }
    }

    /**
     * 配送先を検証し、送料（送料無料の基準を超えれば0）とお届け希望日時を決める。
     *
     * @return array{prefecture: string, fee: int, delivery_date: ?string, delivery_time_slot: ?string}
     */
    public function prepare(array $orderData, int $projectId, float $itemsTotal): array
    {
        foreach (['shipping_name', 'shipping_phone', 'shipping_postal_code', 'shipping_prefecture', 'shipping_address'] as $field) {
            if (trim((string) ($orderData[$field] ?? '')) === '') {
                throw new OrderOperationException('配送先情報を入力してください');
            }
        }
        $prefecture = trim((string) $orderData['shipping_prefecture']);
        if (! JapanPrefectures::isValid($prefecture)) {
            throw new OrderOperationException('都道府県を選択してください');
        }
        $freeShippingThreshold = $this->shopOperations->freeShippingThreshold($projectId);
        $fee = $freeShippingThreshold !== null && $itemsTotal >= $freeShippingThreshold
            ? 0
            : $this->shopSettingRepository->getShopShippingFeeForPrefecture($projectId, $prefecture);
        [$deliveryDate, $deliveryTimeSlot] = $this->validatedDelivery($orderData, $projectId);

        return ['prefecture' => $prefecture, 'fee' => $fee, 'delivery_date' => $deliveryDate, 'delivery_time_slot' => $deliveryTimeSlot];
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

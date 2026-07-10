<?php

namespace App\Domain\Order\Policies;

use App\Enums\OrderType;
use App\Enums\PaymentMethod;

final class OrderPlacementPolicy
{
    public const MODE_BOTH = 'both';

    public const MODE_MOBILE_ONLY = 'mobile_only';

    public const MODE_IN_STORE_ONLY = 'in_store_only';

    public function assertValid(
        string $orderType,
        string $paymentMethod,
        string $configuredOrderMode,
        string $mobileOrderLabel,
    ): void {
        if ($configuredOrderMode === self::MODE_MOBILE_ONLY && $orderType !== OrderType::MOBILE) {
            throw new \Exception('現在はモバイル注文のみ利用できます。');
        }

        if ($configuredOrderMode === self::MODE_IN_STORE_ONLY && $orderType !== OrderType::IN_STORE) {
            throw new \Exception('現在は店内注文のみ利用できます。');
        }

        if ($orderType === OrderType::IN_STORE) {
            if ($paymentMethod !== PaymentMethod::CASH) {
                throw new \Exception('店内注文は後払い（現金）で作成してください。');
            }

            return;
        }

        if ($orderType === OrderType::MOBILE) {
            if (! in_array($paymentMethod, [PaymentMethod::STRIPE, PaymentMethod::CASH], true)) {
                throw new \Exception($mobileOrderLabel.'の決済方法が不正です。');
            }
        }
    }
}

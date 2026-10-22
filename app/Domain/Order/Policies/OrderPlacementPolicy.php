<?php

namespace App\Domain\Order\Policies;

use App\Domain\Order\Exceptions\OrderOperationException;
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
        bool $shopEnabled = false,
        bool $ticketsEnabled = false,
    ): void {
        if ($orderType === OrderType::TICKET) {
            if (! $ticketsEnabled) {
                throw new OrderOperationException('回数券は現在購入できません。');
            }
            if ($paymentMethod !== PaymentMethod::STRIPE) {
                throw new OrderOperationException('回数券はオンライン決済のみ利用できます。');
            }

            return;
        }

        if ($orderType === OrderType::SHIPPING) {
            if (! $shopEnabled) {
                throw new OrderOperationException('ショップ機能は現在利用できません。');
            }
            if ($paymentMethod !== PaymentMethod::STRIPE) {
                throw new OrderOperationException('配送注文はオンライン決済のみ利用できます。');
            }

            return;
        }

        if ($configuredOrderMode === self::MODE_MOBILE_ONLY && $orderType !== OrderType::MOBILE) {
            throw new OrderOperationException('現在はモバイル注文のみ利用できます。');
        }

        if ($configuredOrderMode === self::MODE_IN_STORE_ONLY && $orderType !== OrderType::IN_STORE) {
            throw new OrderOperationException('現在は店内注文のみ利用できます。');
        }

        if ($orderType === OrderType::IN_STORE) {
            if ($paymentMethod !== PaymentMethod::CASH) {
                throw new OrderOperationException('店内注文は後払い（現金）で作成してください。');
            }

            return;
        }

        if ($orderType === OrderType::MOBILE) {
            if (! in_array($paymentMethod, [PaymentMethod::STRIPE, PaymentMethod::CASH], true)) {
                throw new OrderOperationException($mobileOrderLabel.'の決済方法が不正です。');
            }
        }
    }
}

<?php

namespace Tests\Unit\Domain\Order;

use App\Domain\Order\Policies\OrderPlacementPolicy;
use App\Domain\Order\ValueObjects\OrderLineItem;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use Tests\TestCase;

class OrderPlacementPolicyTest extends TestCase
{
    public function test_assert_valid_モバイル注文と_stripeは許可されること(): void
    {
        (new OrderPlacementPolicy)->assertValid(
            OrderType::MOBILE,
            PaymentMethod::STRIPE,
            OrderPlacementPolicy::MODE_BOTH,
            'Reggit',
        );

        $this->assertTrue(true);
    }

    public function test_assert_valid_モバイル注文と現金は許可されること(): void
    {
        (new OrderPlacementPolicy)->assertValid(
            OrderType::MOBILE,
            PaymentMethod::CASH,
            OrderPlacementPolicy::MODE_BOTH,
            'Reggit',
        );

        $this->assertTrue(true);
    }

    public function test_assert_valid_店内注文は現金のみ許可されること(): void
    {
        (new OrderPlacementPolicy)->assertValid(
            OrderType::IN_STORE,
            PaymentMethod::CASH,
            OrderPlacementPolicy::MODE_BOTH,
            'Reggit',
        );

        $this->assertTrue(true);
    }

    public function test_assert_valid_モバイル限定設定で店内注文は拒否されること(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('現在はモバイル注文のみ利用できます。');

        (new OrderPlacementPolicy)->assertValid(
            OrderType::IN_STORE,
            PaymentMethod::CASH,
            OrderPlacementPolicy::MODE_MOBILE_ONLY,
            'Reggit',
        );
    }

    public function test_assert_valid_配送注文はショップ有効かつ_stripeのみ許可されること(): void
    {
        (new OrderPlacementPolicy)->assertValid(
            OrderType::SHIPPING,
            PaymentMethod::STRIPE,
            OrderPlacementPolicy::MODE_MOBILE_ONLY,
            'Reggit',
            true,
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('ショップ機能は現在利用できません。');
        (new OrderPlacementPolicy)->assertValid(
            OrderType::SHIPPING,
            PaymentMethod::STRIPE,
            OrderPlacementPolicy::MODE_BOTH,
            'Reggit',
            false,
        );
    }

    public function test_order_line_item_sum_total_明細合計を計算できること(): void
    {
        $items = [
            new OrderLineItem(1, 2, 500.0, null),
            new OrderLineItem(2, 1, 300.0, 'note'),
        ];

        $this->assertSame(1300.0, OrderLineItem::sumTotal($items));
    }
}

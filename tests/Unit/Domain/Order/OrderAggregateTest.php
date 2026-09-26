<?php

namespace Tests\Unit\Domain\Order;

use App\Domain\Order\Aggregates\Order;
use App\Domain\Order\ValueObjects\OrderId;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use Tests\TestCase;

class OrderAggregateTest extends TestCase
{
    private function createOrder(
        string $status = OrderStatus::PENDING,
        string $orderType = OrderType::MOBILE,
        string $paymentMethod = PaymentMethod::STRIPE,
    ): Order {
        return new Order(
            new OrderId(1),
            1,
            10,
            $orderType,
            $paymentMethod,
            $status,
            0,
            0,
            1000,
        );
    }

    public function test_confirm_stripe_payment_モバイル注文を確定できること(): void
    {
        $order = $this->createOrder();

        $didConfirm = $order->confirmStripePayment();

        $this->assertTrue($didConfirm);
        $this->assertSame(OrderStatus::CONFIRMED, $order->status());
        $this->assertSame(PaymentMethod::STRIPE, $order->paymentMethod());
    }

    public function test_confirm_stripe_payment_確定済みは冪等であること(): void
    {
        $order = $this->createOrder(OrderStatus::CONFIRMED);

        $didConfirm = $order->confirmStripePayment();

        $this->assertFalse($didConfirm);
        $this->assertSame(OrderStatus::CONFIRMED, $order->status());
    }

    public function test_confirm_stripe_payment_進行中は冪等で上書きしないこと(): void
    {
        $order = $this->createOrder(OrderStatus::PREPARING);

        $didConfirm = $order->confirmStripePayment();

        $this->assertFalse($didConfirm);
        $this->assertSame(OrderStatus::PREPARING, $order->status());
    }

    public function test_confirm_stripe_payment_店内注文は拒否されること(): void
    {
        $order = $this->createOrder(
            OrderStatus::PENDING,
            OrderType::IN_STORE,
            PaymentMethod::CASH,
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('店内注文は決済確認の対象外です');

        $order->confirmStripePayment();
    }

    public function test_confirm_stripe_payment_キャンセル済みは拒否されること(): void
    {
        $order = $this->createOrder(OrderStatus::CANCELLED);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('キャンセル済みの注文は決済確認できません');

        $order->confirmStripePayment();
    }

    public function test_cancel_unpaid_stripe_mobile_未決済注文をキャンセルできること(): void
    {
        $order = $this->createOrder();

        $order->cancelUnpaidStripeMobile();

        $this->assertSame(OrderStatus::CANCELLED, $order->status());
    }

    public function test_cancel_unpaid_stripe_mobile_キャンセル済みは冪等であること(): void
    {
        $order = $this->createOrder(OrderStatus::CANCELLED);

        $order->cancelUnpaidStripeMobile();

        $this->assertSame(OrderStatus::CANCELLED, $order->status());
    }

    public function test_cancel_unpaid_stripe_mobile_決済済みは拒否されること(): void
    {
        $order = $this->createOrder(OrderStatus::CONFIRMED);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('決済済みまたは進行中の注文はキャンセルできません');

        $order->cancelUnpaidStripeMobile();
    }

    public function test_cancel_unpaid_stripe_mobile_店内注文は拒否されること(): void
    {
        $order = $this->createOrder(
            OrderStatus::PENDING,
            OrderType::IN_STORE,
            PaymentMethod::CASH,
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('この注文は未決済キャンセル対象ではありません');

        $order->cancelUnpaidStripeMobile();
    }

    public function test_smaregi_sync_未確定注文は対象外であること(): void
    {
        $this->assertFalse($this->createOrder(OrderStatus::PENDING)->shouldSyncToSmaregi());
    }

    public function test_smaregi_sync_確定後の有効な注文は対象であること(): void
    {
        foreach ([OrderStatus::CONFIRMED, OrderStatus::PREPARING, OrderStatus::READY, OrderStatus::COMPLETED] as $status) {
            $this->assertTrue($this->createOrder($status)->shouldSyncToSmaregi(), $status);
        }
    }

    public function test_smaregi_sync_キャンセル済み注文は対象外であること(): void
    {
        $this->assertFalse($this->createOrder(OrderStatus::CANCELLED)->shouldSyncToSmaregi());
    }
}

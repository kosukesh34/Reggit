<?php

namespace App\Domain\Order\Aggregates;

use App\Domain\Order\Exceptions\OrderOperationException;
use App\Domain\Order\ValueObjects\OrderId;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;

final class Order
{
    private const NON_CANCELLABLE_UNPAID_STATUSES = [
        OrderStatus::CONFIRMED,
        OrderStatus::PREPARING,
        OrderStatus::READY,
        OrderStatus::COMPLETED,
    ];

    public function __construct(
        private OrderId $id,
        private int $projectId,
        private int $userId,
        private string $orderType,
        private string $paymentMethod,
        private string $status,
        private int $pointsUsed,
        private int $pointsEarned,
        private int $totalAmount,
    ) {}

    public function id(): OrderId
    {
        return $this->id;
    }

    public function projectId(): int
    {
        return $this->projectId;
    }

    public function userId(): int
    {
        return $this->userId;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function pointsUsed(): int
    {
        return $this->pointsUsed;
    }

    public function pointsEarned(): int
    {
        return $this->pointsEarned;
    }

    public function paymentMethod(): string
    {
        return $this->paymentMethod;
    }

    public function orderType(): string
    {
        return $this->orderType;
    }

    public function totalAmount(): int
    {
        return $this->totalAmount;
    }

    public function isCancelled(): bool
    {
        return $this->status === OrderStatus::CANCELLED;
    }

    /**
     * A confirmed, active order is eligible for downstream Smaregi synchronization.
     * Keeping this rule on the aggregate prevents each payment entry point from
     * interpreting order statuses independently.
     */
    public function shouldSyncToSmaregi(): bool
    {
        return ! $this->isCancelled() && $this->status !== OrderStatus::PENDING;
    }

    /** All staff surfaces share the same transition rules as the payment flow. */
    public function applyStaffStatus(string $status): bool
    {
        $states = [OrderStatus::PENDING, OrderStatus::CONFIRMED, OrderStatus::PREPARING, OrderStatus::READY, OrderStatus::COMPLETED];
        if (! in_array($status, $states, true)) {
            throw new OrderOperationException('更新できないステータスです');
        }
        if ($this->isCancelled()) {
            throw new OrderOperationException('キャンセル済みの注文は変更できません');
        }
        if ($this->status === $status) {
            return false;
        }
        if ($this->status === OrderStatus::PENDING && $this->paymentMethod === PaymentMethod::STRIPE) {
            throw new OrderOperationException('オンライン決済が完了するまで注文を進めることはできません');
        }
        if (array_search($status, $states, true) < array_search($this->status, $states, true)) {
            throw new OrderOperationException('注文ステータスを前の状態に戻すことはできません');
        }
        $this->status = $status;

        return true;
    }

    public function cancelUnpaidStripeMobile(): void
    {
        if ($this->isCancelled()) {
            return;
        }

        if ($this->orderType !== OrderType::MOBILE || $this->paymentMethod !== PaymentMethod::STRIPE) {
            throw new OrderOperationException('この注文は未決済キャンセル対象ではありません');
        }

        if (in_array($this->status, self::NON_CANCELLABLE_UNPAID_STATUSES, true)) {
            throw new OrderOperationException('決済済みまたは進行中の注文はキャンセルできません');
        }

        $this->status = OrderStatus::CANCELLED;
    }

    /**
     * @return bool 状態が変わったとき true（既に決済済み・進行中なら false）
     */
    public function confirmStripePayment(): bool
    {
        if ($this->orderType !== OrderType::MOBILE) {
            throw new OrderOperationException('店内注文は決済確認の対象外です');
        }

        if ($this->isCancelled()) {
            throw new OrderOperationException('キャンセル済みの注文は決済確認できません');
        }

        if ($this->status !== OrderStatus::PENDING) {
            return false;
        }

        $this->status = OrderStatus::CONFIRMED;
        $this->paymentMethod = PaymentMethod::STRIPE;

        return true;
    }
}

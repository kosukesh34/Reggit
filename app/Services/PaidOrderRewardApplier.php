<?php

namespace App\Services;

use App\Models\Order;
use App\Services\Ticket\MemberTicketService;

/** Stripe 決済確定後の付与（冪等）。ポイント獲得・来店・スタンプ・ガチャ権利と回数券の発行。 */
class PaidOrderRewardApplier
{
    public function __construct(
        private OrderRewardService $rewards,
        private MemberTicketService $tickets,
    ) {}

    public function applyStripePaymentRewardsIfNeeded(Order $order): void
    {
        $this->rewards->applyStripePaymentRewardsIfNeeded($order);
        $this->tickets->issueForPaidOrder($order);
    }
}

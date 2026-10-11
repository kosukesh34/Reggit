<?php

namespace App\UseCases\Payment;

use App\Models\Order;
use App\Models\User;
use App\Models\Settings\ProjectPaymentSetting;
use App\UseCases\Payment\Data\PaymentIntentOutput;

class PaymentCreateIntentUseCase
{
    public function __construct(
        private OrderPaymentSessionService $sessions,
    ) {}

    public function handle(int $orderId, int $projectId, User $user): PaymentIntentOutput
    {
        $result = $this->sessions->create($orderId, $projectId, $user, 'intent');

        $order = Order::where('project_id', $projectId)->findOrFail($orderId);
        $key = $order->payment_setting_id === null ? config('services.stripe.key')
            : ProjectPaymentSetting::where('project_id', $projectId)->findOrFail($order->payment_setting_id)->publishable_key;

        return new PaymentIntentOutput($result['client_secret'], $result['id'], $key);
    }
}

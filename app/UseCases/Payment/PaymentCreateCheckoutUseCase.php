<?php

namespace App\UseCases\Payment;

use App\Models\Order;
use App\Models\Project;
use App\UseCases\Payment\Data\PaymentCheckoutOutput;
use Illuminate\Support\Facades\URL;

class PaymentCreateCheckoutUseCase
{
    public function __construct(
        private OrderPaymentSessionService $sessions,
    ) {}

    public function handle(int $orderId, int $projectId, int $userId): PaymentCheckoutOutput
    {
        $order = Order::where('project_id', $projectId)->findOrFail($orderId);
        // Preserve an existing Checkout session during the switch to card-only payments.
        $channel = ($order->payment_session['channel'] ?? null) === 'checkout' ? 'checkout' : 'card';
        $result = $this->sessions->create($orderId, $projectId, $userId, $channel);
        if ($channel === 'checkout') {
            return new PaymentCheckoutOutput($result['url'], $result['id']);
        }

        return new PaymentCheckoutOutput(
            URL::temporarySignedRoute('mobile.payment.card', now()->addDay(), [
                'order' => $orderId,
                'project_key' => Project::findOrFail($projectId)->project_key,
            ]),
            $result['id'],
        );
    }
}

<?php

namespace App\UseCases\Payment;

use App\Models\Order;
use App\Models\Project;
use App\Models\User;
use App\UseCases\Payment\Data\PaymentCheckoutOutput;
use Illuminate\Support\Facades\URL;

class PaymentCreateCheckoutUseCase
{
    public function __construct(
        private OrderPaymentSessionService $sessions,
    ) {}

    public function handle(int $orderId, int $projectId, User $user, string $surface = 'native'): PaymentCheckoutOutput
    {
        $order = Order::where('project_id', $projectId)->findOrFail($orderId);
        $savedChannel = $order->payment_session['channel'] ?? null;
        if ($savedChannel !== null && ! in_array($savedChannel, ['checkout', 'checkout_web', 'card'], true)) {
            throw new \InvalidArgumentException('作成済みの決済画面からお支払いください');
        }
        $channel = $savedChannel ?? ($order->payment_setting_id !== null || $surface === 'web'
            ? ($surface === 'web' ? 'checkout_web' : 'checkout') : 'card');
        $result = $this->sessions->create($orderId, $projectId, $user, $channel);
        if (in_array($channel, ['checkout', 'checkout_web'], true)) {
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

<?php

namespace App\Http\Controllers\Payment;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Scopes\StoreAccessScope;
use App\Models\Settings\ProjectPaymentSetting;
use App\Util\AdminContext;

class PaymentCardController extends Controller
{
    public function __invoke(int $order)
    {
        // The signed URL grants access to this order independently of a browser login.
        $paymentOrder = Order::withoutGlobalScope(StoreAccessScope::class)
            ->where('project_id', AdminContext::requireCurrentProjectId())->findOrFail($order);
        abort_unless(in_array($paymentOrder->order_type, [OrderType::MOBILE, OrderType::SHIPPING], true)
            && $paymentOrder->payment_method === PaymentMethod::STRIPE, 404);
        $session = $paymentOrder->payment_session;
        abort_unless(($session['channel'] ?? null) === 'card', 404);
        $returnUrl = route('mobile.payment.return', ['order_id' => $order]);
        if ($paymentOrder->status !== OrderStatus::PENDING) {
            return redirect($returnUrl.'&status='.($paymentOrder->status === OrderStatus::CANCELLED ? 'cancel' : 'success'));
        }
        $clientSecret = $session['result']['client_secret'] ?? null;
        abort_unless(is_string($clientSecret) && $clientSecret !== '', 404);

        return response()->view('payment.card', [
            'amount' => (int) $paymentOrder->total_amount,
            'stripeKey' => $paymentOrder->payment_setting_id === null ? config('services.stripe.key')
                : ProjectPaymentSetting::where('project_id', $paymentOrder->project_id)->findOrFail($paymentOrder->payment_setting_id)->publishable_key,
            'clientSecret' => $clientSecret,
            'successUrl' => $returnUrl.'&status=success',
            'cancelUrl' => $returnUrl.'&status=cancel',
        ])->header('Cache-Control', 'private, no-store')->header('Referrer-Policy', 'no-referrer');
    }
}

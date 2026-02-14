<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Order;
use App\Util\AdminContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class PaymentController extends BaseApiController
{
    public function __construct()
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));
    }

    public function createPaymentIntent(Request $request)
    {
        $projectId = AdminContext::getCurrentProjectId() ?? 1;
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'order_id' => [
                'nullable',
                Rule::exists('orders', 'id')->where('project_id', $projectId),
            ],
        ]);

        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => (int) ($request->amount * 100),
                'currency' => 'jpy',
                'metadata' => [
                    'order_id' => $request->order_id ?? null,
                ],
            ]);

            return $this->success([
                'clientSecret' => $paymentIntent->client_secret,
                'paymentIntentId' => $paymentIntent->id,
            ]);
        } catch (ApiErrorException $e) {
            Log::error('Stripe Payment Intent Error: ' . $e->getMessage());
            return $this->error('決済の作成に失敗しました', 400, ['detail' => $e->getMessage()]);
        }
    }

    public function confirmPayment(Request $request)
    {
        $projectId = AdminContext::getCurrentProjectId() ?? 1;
        $request->validate([
            'payment_intent_id' => 'required|string',
            'order_id' => [
                'required',
                Rule::exists('orders', 'id')->where('project_id', $projectId),
            ],
        ]);

        try {
            $paymentIntent = PaymentIntent::retrieve($request->payment_intent_id);

            if ($paymentIntent->status === 'succeeded') {
                $order = Order::findOrFail($request->order_id);
                $order->update([
                    'status' => 'confirmed',
                    'payment_method' => 'card',
                ]);

                return $this->success([
                    'order' => $order,
                ], '決済が完了しました');
            }

            return $this->error('決済が完了していません', 400, ['status' => $paymentIntent->status]);
        } catch (ApiErrorException $e) {
            Log::error('Stripe Payment Confirmation Error: ' . $e->getMessage());
            return $this->error('決済の確認に失敗しました', 400, ['detail' => $e->getMessage()]);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Exception\ApiErrorException;

class PaymentController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));
    }

    public function createPaymentIntent(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'order_id' => 'nullable|exists:orders,id',
        ]);

        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => (int)($request->amount * 100), // 円単位からセント単位に変換
                'currency' => 'jpy',
                'metadata' => [
                    'order_id' => $request->order_id ?? null,
                ],
            ]);

            return response()->json([
                'clientSecret' => $paymentIntent->client_secret,
                'paymentIntentId' => $paymentIntent->id,
            ]);
        } catch (ApiErrorException $e) {
            Log::error('Stripe Payment Intent Error: ' . $e->getMessage());
            return response()->json([
                'error' => '決済の作成に失敗しました',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function confirmPayment(Request $request)
    {
        $request->validate([
            'payment_intent_id' => 'required|string',
            'order_id' => 'required|exists:orders,id',
        ]);

        try {
            $paymentIntent = PaymentIntent::retrieve($request->payment_intent_id);

            if ($paymentIntent->status === 'succeeded') {
                $order = Order::findOrFail($request->order_id);
                $order->update([
                    'status' => 'confirmed',
                    'payment_method' => 'card',
                ]);

                return response()->json([
                    'success' => true,
                    'message' => '決済が完了しました',
                    'order' => $order,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => '決済が完了していません',
                    'status' => $paymentIntent->status,
                ], 400);
            }
        } catch (ApiErrorException $e) {
            Log::error('Stripe Payment Confirmation Error: ' . $e->getMessage());
            return response()->json([
                'error' => '決済の確認に失敗しました',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = env('STRIPE_WEBHOOK_SECRET');

        if (!$endpointSecret) {
            Log::warning('Stripe Webhook Secret not configured');
            return response()->json(['error' => 'Webhook secret not configured'], 400);
        }

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sigHeader,
                $endpointSecret
            );
        } catch (\Exception $e) {
            Log::error('Stripe Webhook Error: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        switch ($event->type) {
            case 'payment_intent.succeeded':
                $paymentIntent = $event->data->object;
                $orderId = $paymentIntent->metadata->order_id ?? null;
                
                if ($orderId) {
                    $order = Order::find($orderId);
                    if ($order) {
                        $order->update([
                            'status' => 'confirmed',
                            'payment_method' => 'card',
                        ]);
                        Log::info("Order {$orderId} confirmed via webhook");
                    }
                }
                break;
        }

        return response()->json(['received' => true]);
    }
}


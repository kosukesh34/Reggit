<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\UseCases\Payment\PaymentWebhookUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use App\Util\StrHelper;
use Illuminate\Http\Request;

class PaymentWebhookController extends Controller
{
    public function __construct(
        private PaymentWebhookUseCase $webhookUseCase
    ) {}

    public function __invoke(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = env('STRIPE_WEBHOOK_SECRET');

        if (StrHelper::isEmptyStringOrNull($endpointSecret)) {
            LogHelper::warning('[Payment] webhook secret missing', [
                'project_id' => AdminContext::getCurrentProjectId(),
            ]);

            return response()->json(['error' => 'Webhook secret not configured'], 400);
        }

        try {
            $this->webhookUseCase->handle($payload, $sigHeader, $endpointSecret);
        } catch (\Exception $e) {
            LogHelper::error('[Payment] webhook failed', [
                'project_id' => AdminContext::getCurrentProjectId(),
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Invalid signature'], 400);
        }

        return response()->json(['received' => true]);
    }
}

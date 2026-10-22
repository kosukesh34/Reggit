<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Settings\ProjectPaymentSetting;
use App\Services\Subscription\SubscriptionWebhookHandler;
use App\UseCases\Payment\PaymentWebhookUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use App\Util\StrHelper;
use Illuminate\Http\Request;
use Stripe\Exception\SignatureVerificationException;

class PaymentWebhookController extends Controller
{
    public function __construct(
        private PaymentWebhookUseCase $webhookUseCase,
        private SubscriptionWebhookHandler $subscriptionWebhook,
    ) {}

    public function __invoke(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = (string) $request->header('Stripe-Signature', '');
        $settingId = $request->route('setting');
        $projectId = null;
        if ($settingId !== null) {
            $projectId = AdminContext::requireCurrentProjectId();
            $setting = ProjectPaymentSetting::where('project_id', $projectId)->findOrFail($settingId);
        }
        $endpointSecret = isset($setting) ? $setting->webhook_secret : (string) config('services.stripe.webhook_secret', '');

        if (StrHelper::isEmptyStringOrNull($endpointSecret)) {
            LogHelper::warning('[Payment] webhook secret missing', [
                'project_id' => AdminContext::getCurrentProjectId(),
            ]);

            return response()->json(['error' => 'Webhook secret not configured'], 400);
        }

        try {
            // サブスクはプロジェクト別の決済設定がある場合だけ扱う
            if (isset($setting) && SubscriptionWebhookHandler::handles($payload)) {
                $this->subscriptionWebhook->handle($payload, $sigHeader, $endpointSecret, (int) $projectId, (int) $setting->id);

                return response()->json(['received' => true]);
            }
            $this->webhookUseCase->handle($payload, $sigHeader, $endpointSecret, $projectId, isset($setting) ? (int) $setting->id : null);
        } catch (SignatureVerificationException|\UnexpectedValueException $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        } catch (\Exception $e) {
            LogHelper::error('[Payment] webhook failed', [
                'project_id' => AdminContext::getCurrentProjectId(),
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Payment processing failed'], 503);
        }

        return response()->json(['received' => true]);
    }
}

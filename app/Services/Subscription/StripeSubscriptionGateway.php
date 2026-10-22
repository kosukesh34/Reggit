<?php

namespace App\Services\Subscription;

use App\Models\Settings\ProjectPaymentSetting;
use Stripe\StripeClient;

class StripeSubscriptionGateway implements SubscriptionGateway
{
    public function createCheckout(ProjectPaymentSetting $setting, string $name, int $monthlyAmount, array $metadata, string $successUrl, string $cancelUrl): string
    {
        $session = $this->client($setting)->checkout->sessions->create([
            'mode' => 'subscription',
            'payment_method_types' => ['card'],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => 'jpy',
                    'unit_amount' => $monthlyAmount,
                    'recurring' => ['interval' => 'month'],
                    'product_data' => ['name' => $name],
                ],
            ]],
            'subscription_data' => ['metadata' => $metadata],
            'metadata' => $metadata,
        ]);

        return (string) $session->url;
    }

    public function cancelAtPeriodEnd(ProjectPaymentSetting $setting, string $subscriptionId): void
    {
        $this->client($setting)->subscriptions->update($subscriptionId, ['cancel_at_period_end' => true]);
    }

    private function client(ProjectPaymentSetting $setting): StripeClient
    {
        return new StripeClient([
            'api_key' => $setting->secret_key,
            'stripe_version' => '2026-02-25.clover',
        ]);
    }
}

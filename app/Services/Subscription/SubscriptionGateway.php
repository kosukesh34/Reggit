<?php

namespace App\Services\Subscription;

use App\Models\Settings\ProjectPaymentSetting;

interface SubscriptionGateway
{
    /**
     * 月額の Checkout を作成して URL を返す。
     *
     * @param  array<string, string>  $metadata
     */
    public function createCheckout(ProjectPaymentSetting $setting, string $name, int $monthlyAmount, array $metadata, string $successUrl, string $cancelUrl): string;

    public function cancelAtPeriodEnd(ProjectPaymentSetting $setting, string $subscriptionId): void;
}

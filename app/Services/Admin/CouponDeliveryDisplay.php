<?php

namespace App\Services\Admin;

use App\Enums\CouponDeliveryStatus;
use App\Models\CouponDelivery;

class CouponDeliveryDisplay
{
    public function timingLabel(CouponDelivery $delivery): string
    {
        if ($delivery->status === CouponDeliveryStatus::ACTIVE) {
            return '有効（昇格時に自動発行）';
        }
        if ($delivery->status === CouponDeliveryStatus::SCHEDULED && $delivery->scheduled_at !== null) {
            return '予約: '.$delivery->scheduled_at->format('Y/m/d H:i');
        }
        if ($delivery->sent_at !== null) {
            return '配信: '.$delivery->sent_at->format('Y/m/d H:i');
        }
        if ($delivery->created_at !== null) {
            return '作成: '.$delivery->created_at->format('Y/m/d H:i');
        }

        return '—';
    }

    public function statusBadgeClass(string $status): string
    {
        return match ($status) {
            CouponDeliveryStatus::SENT => 'success',
            CouponDeliveryStatus::SCHEDULED => 'info',
            CouponDeliveryStatus::ACTIVE => 'primary',
            CouponDeliveryStatus::FAILED => 'danger',
            CouponDeliveryStatus::CANCELLED => 'secondary',
            default => 'secondary',
        };
    }
}

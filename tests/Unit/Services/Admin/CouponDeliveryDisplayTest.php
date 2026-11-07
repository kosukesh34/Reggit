<?php

namespace Tests\Unit\Services\Admin;

use App\Enums\CouponDeliveryStatus;
use App\Models\CouponDelivery;
use App\Services\Admin\CouponDeliveryDisplay;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CouponDeliveryDisplayTest extends TestCase
{
    #[Test]
    public function timing_label_for_active_rank_up_delivery_is_consistent(): void
    {
        $delivery = new CouponDelivery([
            'status' => CouponDeliveryStatus::ACTIVE,
        ]);

        $this->assertSame(
            '有効（昇格時に自動発行）',
            (new CouponDeliveryDisplay)->timingLabel($delivery),
        );
    }

    #[Test]
    public function timing_label_prefers_scheduled_at_for_scheduled_delivery(): void
    {
        $delivery = new CouponDelivery([
            'status' => CouponDeliveryStatus::SCHEDULED,
            'scheduled_at' => Carbon::parse('2026-11-07 15:30:00'),
        ]);

        $this->assertSame(
            '予約: 2026/11/07 15:30',
            (new CouponDeliveryDisplay)->timingLabel($delivery),
        );
    }

    #[Test]
    public function status_badge_class_maps_known_statuses(): void
    {
        $display = new CouponDeliveryDisplay;

        $this->assertSame('success', $display->statusBadgeClass(CouponDeliveryStatus::SENT));
        $this->assertSame('primary', $display->statusBadgeClass(CouponDeliveryStatus::ACTIVE));
        $this->assertSame('secondary', $display->statusBadgeClass('unknown'));
    }
}

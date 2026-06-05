<?php

namespace App\Jobs;

use App\Models\MemberCoupon;
use App\Services\CouponNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendCouponIssuedNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $memberCouponId
    ) {}

    public function handle(CouponNotificationService $couponNotificationService): void
    {
        $memberCoupon = MemberCoupon::query()->find($this->memberCouponId);
        if ($memberCoupon === null) {
            return;
        }

        $couponNotificationService->notifyIssued($memberCoupon);
    }
}

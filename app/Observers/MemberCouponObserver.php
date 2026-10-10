<?php

namespace App\Observers;

use App\Jobs\SendCouponIssuedNotificationJob;
use App\Models\MemberCoupon;
use Illuminate\Support\Facades\DB;

class MemberCouponObserver
{
    public function creating(MemberCoupon $memberCoupon): void
    {
        $memberCoupon->notification_requested_at = now();
        $memberCoupon->notification_pending = true;
        $memberCoupon->notification_retry_at = now();
    }

    public function created(MemberCoupon $memberCoupon): void
    {
        DB::afterCommit(function () use ($memberCoupon): void {
            try {
                SendCouponIssuedNotificationJob::dispatch((int) $memberCoupon->id);
            } catch (\Throwable $exception) {
                // The saved pending notification survives sync failures and enqueue errors.
                report($exception);
            }
        });
    }
}

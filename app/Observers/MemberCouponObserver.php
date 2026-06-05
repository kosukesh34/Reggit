<?php

namespace App\Observers;

use App\Jobs\SendCouponIssuedNotificationJob;
use App\Models\MemberCoupon;

class MemberCouponObserver
{
    public function created(MemberCoupon $memberCoupon): void
    {
        SendCouponIssuedNotificationJob::dispatch($memberCoupon->id);
    }
}

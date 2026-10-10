<?php

namespace App\Console\Commands;

use App\Jobs\SendCouponIssuedNotificationJob;
use App\Models\MemberCoupon;
use Illuminate\Console\Command;

class RetryCouponNotificationsCommand extends Command
{
    protected $signature = 'coupons:retry-notifications';

    protected $description = '未完了のクーポン通知を再送する';

    public function handle(): int
    {
        $ids = MemberCoupon::withoutGlobalScopes()
            ->where('notification_pending', true)
            ->where('notification_retry_at', '<=', now())
            ->orderBy('notification_retry_at')
            ->limit(100)
            ->pluck('id');

        foreach ($ids as $id) {
            try {
                SendCouponIssuedNotificationJob::dispatch((int) $id);
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return self::SUCCESS;
    }
}

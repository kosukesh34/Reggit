<?php

namespace App\Jobs;

use App\Models\MemberCoupon;
use App\Models\Project;
use App\Services\CouponNotificationService;
use App\Util\AdminContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendCouponIssuedNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public array $backoff = [60, 120, 300, 600];

    public int $timeout = 60;

    public function __construct(public int $memberCouponId) {}

    public function handle(CouponNotificationService $notifications): void
    {
        $leasedUntil = now()->addMinutes(5);
        $claimed = MemberCoupon::withoutGlobalScopes()->whereKey($this->memberCouponId)
            ->where('notification_pending', true)->where('notification_retry_at', '<=', now())
            ->update(['notification_retry_at' => $leasedUntil]);
        if ($claimed === 0) {
            return;
        }
        $previous = AdminContext::getCurrentProject();
        try {
            $memberCoupon = MemberCoupon::withoutGlobalScopes()->find($this->memberCouponId);
            if ($memberCoupon === null) {
                return;
            }
            AdminContext::setCurrentProject(Project::findOrFail($memberCoupon->project_id));
            $notifications->notifyIssued($memberCoupon);
            MemberCoupon::withoutGlobalScopes()->whereKey($this->memberCouponId)
                ->where('notification_retry_at', $leasedUntil)->update([
                    'notification_pending' => false, 'notification_retry_at' => null,
                ]);
        } catch (\Throwable $exception) {
            MemberCoupon::withoutGlobalScopes()->whereKey($this->memberCouponId)
                ->where('notification_retry_at', $leasedUntil)->update(['notification_retry_at' => now()->addMinute()]);
            throw $exception;
        } finally {
            AdminContext::setCurrentProject($previous);
        }
    }
}

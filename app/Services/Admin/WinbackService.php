<?php

namespace App\Services\Admin;

use App\Models\Coupon;
use App\Models\Member;
use App\Models\Order;
use App\Services\CouponIssueService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 休眠顧客（ウィンバック）向けのクーポン自動発行サービス。
 */
class WinbackService
{
    private CouponIssueService $couponIssueService;

    public function __construct(CouponIssueService $couponIssueService)
    {
        $this->couponIssueService = $couponIssueService;
    }

    /**
     * 指定日数以上注文のない会員にウィンバッククーポンを発行する。
     *
     * @return array{issued: int, skipped: int}
     */
    public function processWinback(int $projectId, int $inactiveDays, int $couponId): array
    {
        $coupon = Coupon::where('project_id', $projectId)
            ->where('is_active', true)
            ->findOrFail($couponId);

        $targetMembers = $this->resolveDormantMembers($projectId, $inactiveDays);

        $issued = 0;
        $skipped = 0;
        foreach ($targetMembers as $member) {
            $alreadyIssued = $member->memberCoupons()
                ->where('coupon_id', $coupon->id)
                ->whereDate('issued_at', Carbon::today())
                ->exists();

            if ($alreadyIssued) {
                $skipped++;

                continue;
            }
            DB::transaction(function () use ($member, $coupon): void {
                $this->couponIssueService->issueManualCoupon($member, $coupon);
            });
            $issued++;
        }

        Log::info('[Winback] 処理完了', [
            'project_id' => $projectId,
            'inactive_days' => $inactiveDays,
            'coupon_id' => $couponId,
            'issued' => $issued,
            'skipped' => $skipped,
        ]);

        return ['issued' => $issued, 'skipped' => $skipped];
    }

    /**
     * 指定日数以上注文のないアクティブ会員を返す。
     *
     * @return Collection<int, Member>
     */
    private function resolveDormantMembers(int $projectId, int $inactiveDays)
    {
        $threshold = Carbon::now()->subDays($inactiveDays);

        $activeUserIds = Order::where('project_id', $projectId)
            ->where('created_at', '>=', $threshold)
            ->distinct()
            ->pluck('user_id');

        return Member::with('memberCoupons')
            ->where('project_id', $projectId)
            ->where('status', 'active')
            ->whereNotNull('user_id')
            ->when($activeUserIds->isNotEmpty(), fn ($q) => $q->whereNotIn('user_id', $activeUserIds->all()))
            ->get();
    }
}

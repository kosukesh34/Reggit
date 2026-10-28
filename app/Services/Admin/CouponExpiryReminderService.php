<?php

namespace App\Services\Admin;

use App\Models\Announcement;
use App\Models\Member;
use App\Models\MemberCoupon;
use App\Services\PersonalAnnouncementSender;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * クーポン有効期限前リマインダーを会員に配信するサービス。
 */
class CouponExpiryReminderService
{
    private const TITLE = 'クーポン期限のお知らせ';

    public function __construct(
        private PersonalAnnouncementSender $announcements,
    ) {}

    /**
     * @return array{notified: int, skipped: int}
     */
    public function processReminders(int $projectId, int $daysBeforeExpiry): array
    {
        $members = Member::query()
            ->where('project_id', $projectId)
            ->where('status', 'active')
            ->whereHas('memberCoupons', function ($query) use ($projectId, $daysBeforeExpiry): void {
                $query->where('project_id', $projectId)
                    ->where('status', MemberCoupon::STATUS_UNUSED)
                    ->whereNotNull('valid_until')
                    ->whereDate('valid_until', '>=', now()->toDateString())
                    ->whereDate('valid_until', '<=', now()->addDays($daysBeforeExpiry)->toDateString());
            })
            ->get();

        $notified = 0;
        $skipped = 0;
        foreach ($members as $member) {
            $result = $this->processForMember($member, $projectId, $daysBeforeExpiry);
            $result ? $notified++ : $skipped++;
        }

        Log::info('[CouponExpiryReminder] 処理完了', [
            'project_id' => $projectId,
            'days_before_expiry' => $daysBeforeExpiry,
            'notified' => $notified,
            'skipped' => $skipped,
        ]);

        return ['notified' => $notified, 'skipped' => $skipped];
    }

    private function processForMember(Member $member, int $projectId, int $daysBeforeExpiry): bool
    {
        // 日次実行で同じクーポンを毎日通知しないよう、前回通知時点で対象外だった期限のクーポンだけを数える
        $lastRemindedAt = Announcement::query()
            ->where('project_id', $projectId)
            ->where('title', self::TITLE)
            ->where('delivery_type', Announcement::DELIVERY_TYPE_PERSONAL)
            ->whereJsonContains('target_member_ids', (int) $member->id)
            ->max('created_at');

        $coupons = MemberCoupon::query()
            ->where('project_id', $projectId)
            ->where('member_id', $member->id)
            ->where('status', MemberCoupon::STATUS_UNUSED)
            ->whereNotNull('valid_until')
            ->whereDate('valid_until', '>=', now()->toDateString())
            ->whereDate('valid_until', '<=', now()->addDays($daysBeforeExpiry)->toDateString())
            ->when($lastRemindedAt !== null, fn ($query) => $query->whereDate(
                'valid_until',
                '>',
                Carbon::parse($lastRemindedAt)->addDays($daysBeforeExpiry)->toDateString(),
            ))
            ->orderBy('valid_until')
            ->get();

        if ($coupons->isEmpty()) {
            return false;
        }

        $soonest = $coupons->first();
        $daysLeft = (int) now()->startOfDay()->diffInDays($soonest->valid_until->copy()->startOfDay(), false);
        $name = (string) ($soonest->coupon_name_snapshot !== '' && $soonest->coupon_name_snapshot !== null
            ? $soonest->coupon_name_snapshot
            : 'クーポン');
        $count = $coupons->count();
        $body = $count === 1
            ? "「{$name}」の有効期限まであと{$daysLeft}日です。お早めにご利用ください。"
            : "期限が近いクーポンが{$count}件あります。最短は「{$name}」（あと{$daysLeft}日）です。お早めにご利用ください。";

        $this->announcements->send($projectId, (int) $member->id, self::TITLE, $body, '/coupon', 'クーポンを確認する');

        return true;
    }
}

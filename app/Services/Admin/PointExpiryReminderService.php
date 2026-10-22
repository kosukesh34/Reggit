<?php

namespace App\Services\Admin;

use App\Models\Announcement;
use App\Models\Member;
use App\Services\AnnouncementNotificationService;
use App\Services\MemberPointsExpiryCalculator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * ポイント期限切れ前リマインダーを会員に配信するサービス。
 */
class PointExpiryReminderService
{
    private const TITLE = 'ポイント期限のお知らせ';

    private MemberPointsExpiryCalculator $expiryCalculator;

    private AnnouncementNotificationService $announcementNotificationService;

    public function __construct(
        MemberPointsExpiryCalculator $expiryCalculator,
        AnnouncementNotificationService $announcementNotificationService
    ) {
        $this->expiryCalculator = $expiryCalculator;
        $this->announcementNotificationService = $announcementNotificationService;
    }

    /**
     * 期限切れポイントがある会員にプッシュ通知を送る。
     *
     * @return array{notified: int, skipped: int}
     */
    public function processReminders(int $projectId, int $daysBeforeExpiry): array
    {
        $members = Member::where('project_id', $projectId)
            ->where('status', 'active')
            ->where('points', '>', 0)
            ->get();

        $notified = 0;
        $skipped = 0;
        foreach ($members as $member) {
            $result = $this->processForMember($member, $projectId, $daysBeforeExpiry);
            $result ? $notified++ : $skipped++;
        }

        Log::info('[PointExpiryReminder] 処理完了', [
            'project_id' => $projectId,
            'days_before_expiry' => $daysBeforeExpiry,
            'notified' => $notified,
            'skipped' => $skipped,
        ]);

        return ['notified' => $notified, 'skipped' => $skipped];
    }

    private function processForMember(Member $member, int $projectId, int $daysBeforeExpiry): bool
    {
        $expiry = $this->expiryCalculator->getExpiryInfo($member);
        $expiresAt = $expiry['expires_at'] ?? null;
        $expiringPoints = (int) ($expiry['points_expiring'] ?? 0);

        if ($expiresAt === null || $expiringPoints <= 0) {
            return false;
        }
        $daysLeft = (int) now()->diffInDays($expiresAt, false);
        if ($daysLeft < 0 || $daysLeft > $daysBeforeExpiry) {
            return false;
        }
        // 日次実行で同じ失効日を毎日通知しないよう、この失効日の通知期間内に送信済みならスキップする
        $alreadyReminded = Announcement::query()
            ->where('project_id', $projectId)
            ->where('title', self::TITLE)
            ->where('delivery_type', Announcement::DELIVERY_TYPE_PERSONAL)
            ->whereJsonContains('target_member_ids', (int) $member->id)
            ->where('created_at', '>=', Carbon::parse($expiresAt)->subDays($daysBeforeExpiry))
            ->exists();
        if ($alreadyReminded) {
            return false;
        }

        $announcement = Announcement::create([
            'project_id' => $projectId,
            'title' => self::TITLE,
            'body' => "{$expiringPoints}ptが{$daysLeft}日後に失効します。お早めにご利用ください。",
            'is_pinned' => false,
            'delivery_type' => Announcement::DELIVERY_TYPE_PERSONAL,
            'delivery_status' => Announcement::DELIVERY_STATUS_SENT,
            'target_member_ids' => [(int) $member->id],
            'published_at' => now(),
            'sent_at' => now(),
        ]);
        $this->announcementNotificationService->send($announcement);

        return true;
    }
}

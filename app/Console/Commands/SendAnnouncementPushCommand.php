<?php

namespace App\Console\Commands;

use App\Models\Announcement;
use App\Services\PushNotificationService;
use Illuminate\Console\Command;

class SendAnnouncementPushCommand extends Command
{
    protected $signature = 'push:announcement
                            {--project_id=1 : プロジェクトID}
                            {--title= : 通知タイトル（省略時: テスト通知）}
                            {--body= : 通知本文（省略時: これはテストです）}
                            {--force : 確認なしで送信}';

    protected $description = 'お知らせ配信と同じ経路でプッシュ通知を送信する（PushNotificationService::sendAnnouncement）';

    public function handle(PushNotificationService $pushService): int
    {
        $projectId = (int) $this->option('project_id');
        $title = $this->option('title') ?: 'テスト通知';
        $body = $this->option('body') ?: 'これはテストです';

        $announcement = Announcement::create([
            'project_id' => $projectId,
            'title' => $title,
            'body' => $body,
            'is_pinned' => false,
            'expires_at' => null,
            'delivery_type' => Announcement::DELIVERY_TYPE_IMMEDIATE_BULK,
            'scheduled_at' => null,
            'delivery_status' => Announcement::DELIVERY_STATUS_SENT,
            'published_at' => now(),
            'sent_at' => now(),
            'target_member_ids' => null,
        ]);

        $this->info("送信内容: タイトル={$title}, 本文={$body}");
        $this->info('配信: お知らせ配信と同じ経路（project_id=' . $projectId . ' の登録端末）');

        if (! $this->option('force') && ! $this->confirm('送信しますか？', true)) {
            $announcement->delete();
            $this->info('キャンセルしました。');
            return self::SUCCESS;
        }

        $result = $pushService->sendAnnouncement($announcement);

        $this->info('送信完了: 送信=' . $result['sent'] . ' 件, 失敗=' . $result['failed'] . ' 件, スキップ=' . $result['skipped'] . ' 件（対象端末: ' . $result['target_tokens'] . ' 件）');

        return self::SUCCESS;
    }
}

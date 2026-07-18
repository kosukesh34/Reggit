<?php

namespace App\Console\Commands;

use App\Models\Announcement;
use App\Services\AnnouncementNotificationService;
use Illuminate\Console\Command;

class SendAnnouncementPushCommand extends Command
{
    protected $signature = 'push:announcement
                            {--project_id=1 : プロジェクトID}
                            {--title= : 通知タイトル（省略時: テスト通知）}
                            {--body= : 通知本文（省略時: これはテストです）}
                            {--force : 確認なしで送信}';

    protected $description = 'お知らせ配信と同じ経路でアプリ・LINE 通知を送信する（AnnouncementNotificationService）';

    public function handle(AnnouncementNotificationService $announcementNotificationService): int
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
        $this->info('配信: お知らせ配信と同じ経路（アプリ + LINE / project_id='.$projectId.'）');

        if (! $this->option('force') && ! $this->confirm('送信しますか？', true)) {
            $announcement->delete();
            $this->info('キャンセルしました。');

            return self::SUCCESS;
        }

        $result = $announcementNotificationService->send($announcement);

        $this->info('送信完了: アプリ 送信='.$result['sent'].' 件, 失敗='.$result['failed'].' 件, スキップ='.$result['skipped'].' 件（対象端末: '.$result['target_tokens'].' 件）');
        $this->info('LINE: 送信='.$result['line_sent'].' 件, 失敗='.$result['line_failed'].' 件, スキップ='.$result['line_skipped'].' 件');

        return self::SUCCESS;
    }
}

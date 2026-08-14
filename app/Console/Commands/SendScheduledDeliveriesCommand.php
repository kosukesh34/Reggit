<?php

namespace App\Console\Commands;

use App\Services\ScheduledDeliverySendService;
use Illuminate\Console\Command;

class SendScheduledDeliveriesCommand extends Command
{
    protected $signature = 'deliveries:send-scheduled
                            {--dry-run : 実行せずに対象のみ表示}';

    protected $description = 'スケジュール配信の条件に合う会員へ配信する（毎分実行を推奨）';

    public function handle(ScheduledDeliverySendService $sendService): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        if ($isDryRun) {
            $this->info('dry-run のため送信しません');

            return self::SUCCESS;
        }

        $sent = $sendService->processDue(now());
        $this->info($sent === 0 ? '対象のスケジュール配信はありません' : "スケジュール配信を実行しました（送信: {$sent} 件）");

        return self::SUCCESS;
    }
}

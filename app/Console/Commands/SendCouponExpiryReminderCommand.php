<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Services\Admin\CouponExpiryReminderService;
use Illuminate\Console\Command;

class SendCouponExpiryReminderCommand extends Command
{
    protected $signature = 'crm:coupon-expiry-reminder
                            {--days=7 : 期限切れ何日前に通知するか（デフォルト: 7日）}
                            {--dry-run : 実行せずに対象件数のみ表示}';

    protected $description = 'クーポン有効期限前の会員にリマインダー通知を送る（日次実行を推奨）';

    public function handle(CouponExpiryReminderService $service): int
    {
        $daysBeforeExpiry = (int) $this->option('days');
        $isDryRun = (bool) $this->option('dry-run');

        if ($isDryRun) {
            $this->info("--dry-run のため送信しません (days_before_expiry={$daysBeforeExpiry})");

            return self::SUCCESS;
        }

        $projects = Project::where('is_active', true)->get();

        foreach ($projects as $project) {
            $result = $service->processReminders($project->id, $daysBeforeExpiry);
            $this->info("project_id={$project->id}: 通知={$result['notified']}件, スキップ={$result['skipped']}件");
        }

        return self::SUCCESS;
    }
}

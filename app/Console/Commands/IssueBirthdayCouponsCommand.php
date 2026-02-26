<?php

namespace App\Console\Commands;

use App\Models\Member;
use App\Services\CouponIssueService;
use Illuminate\Console\Command;

class IssueBirthdayCouponsCommand extends Command
{
    protected $signature = 'coupons:issue-birthday
                            {--dry-run : 発行せずに対象のみ表示}';

    protected $description = '誕生月の会員に誕生日クーポンを発行する（日次実行を推奨）';

    public function handle(CouponIssueService $couponIssueService): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $month = (int) now()->format('n');
        $members = Member::whereNotNull('birthday')
            ->whereRaw('MONTH(birthday) = ?', [$month])
            ->get();
        $count = 0;
        foreach ($members as $member) {
            if ($dryRun) {
                $this->line("Member #{$member->id} (project: {$member->project_id})");
                $count++;
                continue;
            }
            $couponIssueService->issueForBirthday($member);
            $count++;
        }
        if ($dryRun) {
            $this->info("対象会員: {$count} 件（--dry-run のため発行していません）");
        } else {
            $this->info("誕生日クーポン発行処理を完了しました（対象: {$count} 件）");
        }
        return self::SUCCESS;
    }
}

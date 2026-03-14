<?php

namespace App\Console\Commands;

use App\Models\Coupon;
use App\Models\Project;
use App\Services\Admin\WinbackService;
use Illuminate\Console\Command;

class SendWinbackDeliveryCommand extends Command
{
    protected $signature = 'crm:winback
                            {--inactive-days=90 : 最終購買からの経過日数（デフォルト: 90日）}
                            {--dry-run : 実行せずに対象件数のみ表示}';

    protected $description = '指定日数以上注文のない休眠顧客にウィンバッククーポンを発行する（日次実行を推奨）';

    public function handle(WinbackService $winbackService): int
    {
        $inactiveDays = (int) $this->option('inactive-days');
        $isDryRun = (bool) $this->option('dry-run');

        $projects = Project::where('is_active', true)->get();

        foreach ($projects as $project) {
            $this->processProject($winbackService, $project, $inactiveDays, $isDryRun);
        }

        return self::SUCCESS;
    }

    private function processProject(WinbackService $winbackService, Project $project, int $inactiveDays, bool $isDryRun): void
    {
        $coupon = Coupon::where('project_id', $project->id)
            ->where('is_active', true)
            ->where('issue_trigger', 'winback')
            ->first();

        if ($coupon === null) {
            $this->line("project_id={$project->id}: ウィンバッククーポン未設定のためスキップ");

            return;
        }

        if ($isDryRun) {
            $this->info("project_id={$project->id}: --dry-run のため送信しません (coupon_id={$coupon->id})");

            return;
        }

        $result = $winbackService->processWinback($project->id, $inactiveDays, $coupon->id);
        $this->info("project_id={$project->id}: 発行={$result['issued']}件, スキップ={$result['skipped']}件");
    }
}

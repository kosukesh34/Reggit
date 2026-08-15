<?php

namespace App\Console\Commands;

use App\Services\RecalculateMemberRanksService;
use Illuminate\Console\Command;

class RecalculateMemberRanksCommand extends Command
{
    protected $signature = 'member-ranks:recalculate
                            {--project-id= : 対象プロジェクトID（未指定時は全店舗）}';

    protected $description = '期間集計のランクを再判定する（日次実行を推奨）';

    public function handle(RecalculateMemberRanksService $service): int
    {
        $projectIdOption = $this->option('project-id');
        $projectId = $projectIdOption !== null && $projectIdOption !== ''
            ? (int) $projectIdOption
            : null;
        $result = $service->handle($projectId);
        $this->info(
            'projects='.$result['projects']
            .' members='.$result['members']
            .' changed='.$result['changed']
        );

        return self::SUCCESS;
    }
}

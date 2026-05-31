<?php

namespace App\Console\Commands;

use App\Models\Member;
use Illuminate\Console\Command;

class ReconcileMemberPointsCommand extends Command
{
    protected $signature = 'members:reconcile-points
                            {--project_id= : 対象プロジェクトID（未指定時は全件）}';

    protected $description = 'members.points と point_transactions の不整合を修復する';

    public function handle(): int
    {
        $projectId = $this->option('project_id');
        $query = Member::query();
        if ($projectId !== null && $projectId !== '') {
            $query->where('project_id', (int) $projectId);
        }

        $fixed = 0;
        $total = 0;
        foreach ($query->cursor() as $member) {
            $total++;
            $beforeColumn = (int) $member->points;
            $beforeLedger = $member->getTotalPointsFromTransactions();
            if ($beforeColumn === $beforeLedger) {
                continue;
            }
            $member->syncPointsLedger();
            $member->refresh();
            $fixed++;
            $this->line(sprintf(
                'member #%d: column %d / ledger %d -> %d',
                $member->id,
                $beforeColumn,
                $beforeLedger,
                $member->getTotalPointsFromTransactions()
            ));
        }

        $this->info("完了: {$fixed} / {$total} 件を整合しました。");

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\SmaregiMemberRankSync;
use App\Models\SmaregiRankSettingsSync;
use App\Services\Smaregi\SmaregiCustomerRankAssignmentService;
use App\Services\Smaregi\SmaregiRankSettingsRecoveryService;
use Illuminate\Console\Command;

class RetryMemberRanksCommand extends Command
{
    protected $signature = 'smaregi:retry-member-ranks';

    protected $description = 'Retry persisted member rank synchronization requests';

    public function handle(SmaregiCustomerRankAssignmentService $ranks, SmaregiRankSettingsRecoveryService $settings): int
    {
        $failed = false;
        $deadline = now()->addSeconds(45);
        $projects = SmaregiRankSettingsSync::whereNull('delivered_at')->where('retry_at', '<=', now())
            ->where(fn ($q) => $q->whereNull('leased_until')->orWhere('leased_until', '<=', now()))
            ->orderBy('retry_at')->limit(20)->pluck('project_id');
        foreach ($projects as $projectId) {
            if (now()->greaterThanOrEqualTo($deadline)) {
                break;
            }
            try {
                if (! $settings->retryPending((int) $projectId)) {
                    $failed = true;
                }
            } catch (\Throwable $exception) {
                report($exception);
                $failed = true;
            }
        }
        if (now()->greaterThanOrEqualTo($deadline)) {
            return $failed ? self::FAILURE : self::SUCCESS;
        }
        $ids = SmaregiMemberRankSync::where('retry_at', '<=', now())
            ->where(fn ($query) => $query->whereNull('leased_until')->orWhere('leased_until', '<=', now()))
            ->orderBy('retry_at')->limit(100)->pluck('member_id');
        foreach ($ids as $id) {
            if (now()->greaterThanOrEqualTo($deadline)) {
                break;
            }
            try {
                if (! $ranks->retryPending((int) $id)) {
                    $failed = true;
                }
            } catch (\Throwable $exception) {
                report($exception);
                $failed = true;
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}

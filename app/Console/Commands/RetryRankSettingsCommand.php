<?php

namespace App\Console\Commands;

use App\Models\SmaregiRankSettingsSync;
use App\Services\Smaregi\SmaregiRankSettingsSyncService;
use Illuminate\Console\Command;

class RetryRankSettingsCommand extends Command
{
    protected $signature = 'smaregi:retry-rank-settings';

    protected $description = 'Retry persisted project rank settings synchronization requests';

    public function handle(SmaregiRankSettingsSyncService $ranks): int
    {
        $ids = SmaregiRankSettingsSync::where('retry_at', '<=', now())
            ->where(fn ($query) => $query->whereNull('leased_until')->orWhere('leased_until', '<=', now()))
            ->orderBy('retry_at')->limit(20)->pluck('project_id');
        $failed = false;
        foreach ($ids as $id) {
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

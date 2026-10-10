<?php

namespace App\Console\Commands;

use App\Models\SmaregiMemberRankSync;
use App\Services\Smaregi\SmaregiCustomerRankAssignmentService;
use Illuminate\Console\Command;

class RetryMemberRanksCommand extends Command
{
    protected $signature = 'smaregi:retry-member-ranks';

    protected $description = 'Retry persisted member rank synchronization requests';

    public function handle(SmaregiCustomerRankAssignmentService $ranks): int
    {
        $ids = SmaregiMemberRankSync::where('retry_at', '<=', now())
            ->where(fn ($query) => $query->whereNull('leased_until')->orWhere('leased_until', '<=', now()))
            ->orderBy('retry_at')->limit(100)->pluck('member_id');
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

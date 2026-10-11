<?php

namespace App\Console\Commands;

use App\Models\SmaregiMemberProfileSync;
use App\Services\Smaregi\SmaregiMemberProfileSyncService;
use Illuminate\Console\Command;

class RetryMemberProfilesCommand extends Command
{
    protected $signature = 'smaregi:retry-member-profiles';

    protected $description = 'Retry persisted member profile synchronization requests';

    public function handle(SmaregiMemberProfileSyncService $profiles): int
    {
        $failed = false;
        $ids = SmaregiMemberProfileSync::whereNull('delivered_at')->where('retry_at', '<=', now())
            ->where(fn ($query) => $query->whereNull('leased_until')->orWhere('leased_until', '<=', now()))
            ->orderBy('retry_at')->limit(100)->pluck('member_id');
        foreach ($ids as $id) {
            try {
                $failed = ! $profiles->retryPending((int) $id) || $failed;
            } catch (\Throwable $exception) {
                report($exception);
                $failed = true;
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\SmaregiMemberProfileSync;
use App\Services\Smaregi\SmaregiMemberProfileSyncService;
use App\Util\LogHelper;
use Illuminate\Console\Command;

class RetryMemberProfilesCommand extends Command
{
    protected $signature = 'smaregi:retry-member-profiles';

    protected $description = 'Retry persisted member profile synchronization requests';

    public function handle(SmaregiMemberProfileSyncService $profiles): int
    {
        $retrying = 0;
        $ids = SmaregiMemberProfileSync::whereNull('delivered_at')->where('retry_at', '<=', now())
            ->where(fn ($query) => $query->whereNull('leased_until')->orWhere('leased_until', '<=', now()))
            ->orderBy('retry_at')->limit(100)->pluck('member_id');
        foreach ($ids as $id) {
            try {
                $retrying += $profiles->retryPending((int) $id) ? 0 : 1;
            } catch (\Throwable $exception) {
                report($exception);
                $retrying++;
            }
        }

        if ($retrying > 0) {
            // 再送待ちは次回以降に送り直すので失敗扱いにしない。毎分の定期実行が失敗としてエラー通知を出し続けるため
            LogHelper::warning('[Smaregi] member profile sync will retry', ['retrying' => $retrying]);
        }

        return self::SUCCESS;
    }
}

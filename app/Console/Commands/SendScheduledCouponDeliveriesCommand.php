<?php

namespace App\Console\Commands;

use App\Enums\CouponDeliveryStatus;
use App\Jobs\SendScheduledCouponDeliveryJob;
use App\Models\CouponDelivery;
use Illuminate\Console\Command;

class SendScheduledCouponDeliveriesCommand extends Command
{
    protected $signature = 'coupons:send-scheduled
                            {--dry-run : 実行せずに対象のみ表示}';

    protected $description = '予約時刻を過ぎたクーポン配信を実行する（毎分実行を推奨）';

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        // ponytail: 1回100件。配信が増えたらキューワーカーへ移す
        $deliveries = CouponDelivery::withoutGlobalScopes()
            ->where('status', CouponDeliveryStatus::SCHEDULED)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->orderBy('scheduled_at')
            ->limit(100)
            ->get();

        if ($deliveries->isEmpty()) {
            $this->info('対象の予約配信はありません');

            return self::SUCCESS;
        }

        foreach ($deliveries as $delivery) {
            $scheduledAt = $delivery->scheduled_at !== null
                ? $delivery->scheduled_at->format('Y-m-d H:i')
                : '-';
            $this->line("project_id={$delivery->project_id} delivery_id={$delivery->id} scheduled_at={$scheduledAt}");
            if ($isDryRun) {
                continue;
            }
            SendScheduledCouponDeliveryJob::dispatchSync($delivery->id);
        }

        $this->info($isDryRun
            ? "対象: {$deliveries->count()} 件（--dry-run のため実行していません）"
            : "予約配信を実行しました（対象: {$deliveries->count()} 件）");

        return self::SUCCESS;
    }
}

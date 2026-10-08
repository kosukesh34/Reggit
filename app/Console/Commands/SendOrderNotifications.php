<?php

namespace App\Console\Commands;

use App\Services\OrderNotificationOutbox;
use Illuminate\Console\Command;

class SendOrderNotifications extends Command
{
    protected $signature = 'orders:send-notifications {--limit=100}';

    protected $description = 'Retry pending order notifications from the transactional outbox';

    public function handle(OrderNotificationOutbox $outbox): int
    {
        $count = $outbox->drain(max(1, min(1000, (int) $this->option('limit'))));
        $this->info("Processed {$count} notification deliveries.");

        return self::SUCCESS;
    }
}

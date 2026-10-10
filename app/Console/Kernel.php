<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('smaregi:retry-rank-settings')->everyMinute()->withoutOverlapping(5);
        $schedule->command('smaregi:retry-member-ranks')->everyMinute()->withoutOverlapping(5);
        $schedule->command('coupons:retry-notifications')->everyMinute()->withoutOverlapping();
        $schedule->command('announcements:retry-notifications')->everyMinute()->withoutOverlapping();
        $schedule->command('smaregi:retry-order-transactions')->everyMinute()->withoutOverlapping();
        $schedule->command('smaregi:retry-cash-pickups')->everyMinute()->withoutOverlapping();
        $schedule->command('orders:send-notifications')->everyMinute()->withoutOverlapping();
        $schedule->command('coupons:send-scheduled')->everyMinute()->withoutOverlapping();
        $schedule->command('deliveries:send-scheduled')->everyMinute()->withoutOverlapping();
        $schedule->command('coupons:issue-birthday')->daily();
        $schedule->command('crm:point-expiry-reminder')->daily();
        $schedule->command('member-ranks:recalculate')->daily()->withoutOverlapping();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

<?php

namespace App\Console\Commands;

use App\UseCases\Reservation\SendReservationRemindersUseCase;
use Illuminate\Console\Command;

class SendReservationRemindersCommand extends Command
{
    protected $signature = 'reservations:send-reminders';

    protected $description = '予約設定の時間前になった予約へリマインドを送る';

    public function handle(SendReservationRemindersUseCase $useCase): int
    {
        $this->info('送信: '.$useCase->handle().'件');

        return self::SUCCESS;
    }
}

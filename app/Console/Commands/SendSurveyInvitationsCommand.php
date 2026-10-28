<?php

namespace App\Console\Commands;

use App\UseCases\Survey\SendSurveyInvitationsUseCase;
use Illuminate\Console\Command;

class SendSurveyInvitationsCommand extends Command
{
    protected $signature = 'surveys:send-invitations';

    protected $description = '来店・注文・予約・会員登録から指定日数後の会員へアンケートの回答依頼を送る（日次実行）';

    public function handle(SendSurveyInvitationsUseCase $useCase): int
    {
        $this->info('送信: '.$useCase->handle().'件');

        return self::SUCCESS;
    }
}

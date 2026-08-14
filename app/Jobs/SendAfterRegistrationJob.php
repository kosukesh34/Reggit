<?php

namespace App\Jobs;

use App\Models\AfterRegistrationDelivery;
use App\Models\Member;
use App\Services\ScheduledDeliverySendService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendAfterRegistrationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $memberId,
        public int $afterRegistrationDeliveryId
    ) {}

    public function handle(ScheduledDeliverySendService $sendService): void
    {
        $member = Member::find($this->memberId);
        $config = AfterRegistrationDelivery::find($this->afterRegistrationDeliveryId);
        if ($member === null || $config === null) {
            return;
        }

        $sendService->sendToMember($config, $member);
    }
}

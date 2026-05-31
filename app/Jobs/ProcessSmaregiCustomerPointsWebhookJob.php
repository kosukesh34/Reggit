<?php

namespace App\Jobs;

use App\Services\Smaregi\SmaregiCustomerPointsWebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessSmaregiCustomerPointsWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<int|string, string|int|null>  $transactionHeadIds
     */
    public function __construct(
        public string $contractId,
        public array $transactionHeadIds,
    ) {}

    public function handle(SmaregiCustomerPointsWebhookService $service): void
    {
        $service->process($this->contractId, $this->transactionHeadIds);
    }
}

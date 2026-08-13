<?php

namespace App\UseCases\Kitchen;

use App\Services\Kitchen\KitchenOrderService;

class KitchenUnregisterDeviceUseCase
{
    public function __construct(
        private KitchenOrderService $kitchenOrderService,
    ) {}

    public function handle(int $projectId, string $pushToken): void
    {
        $this->kitchenOrderService->unregisterDevice($projectId, $pushToken);
    }
}

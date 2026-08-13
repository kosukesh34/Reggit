<?php

namespace App\UseCases\Kitchen;

use App\Models\KitchenDevice;
use App\Services\Kitchen\KitchenOrderService;

class KitchenRegisterDeviceUseCase
{
    public function __construct(
        private KitchenOrderService $kitchenOrderService,
    ) {}

    public function handle(int $projectId, string $pushToken, string $platform, ?string $deviceName): KitchenDevice
    {
        return $this->kitchenOrderService->registerDevice($projectId, $pushToken, $platform, $deviceName);
    }
}

<?php

namespace App\UseCases\Device;

use App\Services\DeviceService;
use App\UseCases\Device\Data\DeviceRegisterInput;
use App\UseCases\Device\Data\DeviceRegisterOutput;

class DeviceRegisterUseCase
{
    public function __construct(
        private DeviceService $deviceService,
    ) {}

    public function handle(DeviceRegisterInput $input): DeviceRegisterOutput
    {
        $pushTokenRaw = $input->payload['push_token'] ?? null;
        $hasPushToken = is_string($pushTokenRaw) && trim($pushTokenRaw) !== '';

        $device = $this->deviceService->register($input->user, $input->payload);

        $savedTokenLen = $device->push_token !== null && $device->push_token !== ''
            ? strlen($device->push_token)
            : 0;

        return new DeviceRegisterOutput(
            (int) $device->id,
            $savedTokenLen,
            $hasPushToken && $savedTokenLen === 0,
        );
    }
}

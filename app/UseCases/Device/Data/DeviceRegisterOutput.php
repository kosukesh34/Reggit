<?php

namespace App\UseCases\Device\Data;

class DeviceRegisterOutput
{
    public function __construct(
        public readonly int $deviceId,
        public readonly int $savedPushTokenLength,
        public readonly bool $hasPushTokenWarning,
        public readonly string $deviceToken,
    ) {}
}

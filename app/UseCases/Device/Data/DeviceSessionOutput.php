<?php

namespace App\UseCases\Device\Data;

class DeviceSessionOutput
{
    public function __construct(
        public readonly ?string $lineUserId,
    ) {}
}

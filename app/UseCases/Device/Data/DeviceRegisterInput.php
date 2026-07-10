<?php

namespace App\UseCases\Device\Data;

use App\Models\User;

class DeviceRegisterInput
{
    /**
     * @param  array{
     *     platform: string,
     *     device_identifier: string,
     *     push_token?: string|null,
     *     os_version?: string|null,
     *     app_version?: string|null,
     * }  $payload
     */
    public function __construct(
        public readonly User $user,
        public readonly array $payload,
    ) {}
}

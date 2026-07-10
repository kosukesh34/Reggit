<?php

namespace App\Repositories\Contracts;

use App\Models\UserDevice;

interface UserDeviceRepository
{
    public function findOrNewByProjectAndIdentifier(int $projectId, string $deviceIdentifier): UserDevice;

    public function findLatestByProjectAndIdentifier(int $projectId, string $deviceIdentifier): ?UserDevice;

    public function touchLastUsedAt(UserDevice $device): void;
}

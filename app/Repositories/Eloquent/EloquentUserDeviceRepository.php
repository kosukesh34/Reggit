<?php

namespace App\Repositories\Eloquent;

use App\Models\UserDevice;
use App\Repositories\Contracts\UserDeviceRepository;

class EloquentUserDeviceRepository implements UserDeviceRepository
{
    public function findOrNewByProjectAndIdentifier(int $projectId, string $deviceIdentifier): UserDevice
    {
        return UserDevice::query()->lockForUpdate()->firstOrNew([
            'project_id' => $projectId,
            'device_identifier' => $deviceIdentifier,
        ]);
    }

    public function findLatestByProjectAndIdentifier(int $projectId, string $deviceIdentifier): ?UserDevice
    {
        return UserDevice::query()
            ->where('project_id', $projectId)
            ->where('device_identifier', $deviceIdentifier)
            ->orderByDesc('last_used_at')
            ->first();
    }

    public function touchLastUsedAt(UserDevice $device): void
    {
        $device->forceFill(['last_used_at' => now()])->save();
    }
}

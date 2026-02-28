<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserDevice;
use App\Util\AdminContext;

class DeviceService
{
    public function register(User $user, array $payload): UserDevice
    {
        $projectId = (int) ($user->project_id ?? AdminContext::getCurrentProjectId() ?? 1);
        $deviceIdentifier = trim((string) ($payload['device_identifier'] ?? ''));

        $device = UserDevice::firstOrNew([
            'project_id' => $projectId,
            'device_identifier' => $deviceIdentifier,
        ]);

        $device->fill([
            'project_id' => $projectId,
            'user_id' => (int) $user->id,
            'platform' => (string) ($payload['platform'] ?? 'unknown'),
            'push_token' => $payload['push_token'] ?? $device->push_token,
            'os_version' => $payload['os_version'] ?? $device->os_version,
            'app_version' => $payload['app_version'] ?? $device->app_version,
            'last_used_at' => now(),
        ]);

        $device->save();

        return $device;
    }
}


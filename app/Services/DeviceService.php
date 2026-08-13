<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserDevice;
use App\Repositories\Contracts\UserDeviceRepository;
use App\Util\AdminContext;
use App\Util\LogHelper;

class DeviceService
{
    public function __construct(
        private UserDeviceRepository $userDeviceRepository,
    ) {}

    public function register(User $user, array $payload): UserDevice
    {
        $projectId = (int) (AdminContext::resolveProjectId($user->project_id !== null ? (int) $user->project_id : null));
        $deviceIdentifier = trim((string) ($payload['device_identifier'] ?? ''));
        $incomingPushToken = isset($payload['push_token']) && is_string($payload['push_token'])
            ? trim($payload['push_token'])
            : '';

        $device = $this->userDeviceRepository->findOrNewByProjectAndIdentifier($projectId, $deviceIdentifier);

        $effectivePushToken = $incomingPushToken !== ''
            ? $incomingPushToken
            : ($device->push_token ?? null);
        if ($effectivePushToken === '') {
            $effectivePushToken = null;
        }

        $device->fill([
            'project_id' => $projectId,
            'user_id' => (int) $user->id,
            'platform' => (string) ($payload['platform'] ?? 'unknown'),
            'push_token' => $effectivePushToken,
            'os_version' => $payload['os_version'] ?? $device->os_version,
            'app_version' => $payload['app_version'] ?? $device->app_version,
            'last_used_at' => now(),
        ]);

        $device->save();

        $savedLen = $device->push_token !== null && $device->push_token !== '' ? strlen($device->push_token) : 0;
        LogHelper::info('[Device] DeviceService::register saved', [
            'project_id' => $projectId,
            'device_id' => $device->id,
            'incoming_push_token_length' => $incomingPushToken !== '' ? strlen($incomingPushToken) : 0,
            'saved_push_token_length' => $savedLen,
        ]);

        return $device;
    }
}

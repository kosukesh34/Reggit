<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserDevice;
use App\Repositories\Contracts\UserDeviceRepository;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\DB;

class DeviceService
{
    public function __construct(
        private UserDeviceRepository $userDeviceRepository,
    ) {}

    public function register(User $user, array $payload, ?string $deviceToken = null): UserDevice
    {
        return DB::transaction(fn () => $this->saveRegistration($user, $payload, $deviceToken), 3);
    }

    private function saveRegistration(User $user, array $payload, ?string $deviceToken): UserDevice
    {
        $projectId = (int) (AdminContext::resolveProjectId($user->project_id !== null ? (int) $user->project_id : null));
        $owner = User::query()->where('project_id', $projectId)->whereKey($user->id)->lockForUpdate()->first();
        if ($owner === null || (int) $owner->authentication_version !== (int) $user->authentication_version) {
            throw new AuthenticationException('端末の再認証が必要です');
        }

        $deviceIdentifier = trim((string) ($payload['device_identifier'] ?? ''));
        $incomingPushToken = isset($payload['push_token']) && is_string($payload['push_token'])
            ? trim($payload['push_token'])
            : '';

        $device = $this->userDeviceRepository->findOrNewByProjectAndIdentifier($projectId, $deviceIdentifier);

        if ($device->exists && (int) $device->user_id !== (int) $owner->id) {
            if (! $device->authenticates($deviceToken)) {
                throw new AuthenticationException('端末の再認証が必要です');
            }
            $device->push_token = null;
        }
        if (! is_string($device->auth_token) || $device->auth_token === '') {
            $device->auth_token = bin2hex(random_bytes(32));
        }

        $effectivePushToken = $incomingPushToken !== ''
            ? $incomingPushToken
            : ($device->push_token ?? null);
        if ($effectivePushToken === '') {
            $effectivePushToken = null;
        }

        $device->fill([
            'project_id' => $projectId,
            'user_id' => (int) $owner->id,
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

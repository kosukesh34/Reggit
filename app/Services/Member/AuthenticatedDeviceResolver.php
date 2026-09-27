<?php

namespace App\Services\Member;

use App\Models\UserDevice;
use App\Repositories\Contracts\UserDeviceRepository;
use App\Util\AdminContext;
use Illuminate\Http\Request;
use InvalidArgumentException;

class AuthenticatedDeviceResolver
{
    public function __construct(private UserDeviceRepository $userDeviceRepository) {}

    public function resolve(Request $request, int $userId): ?UserDevice
    {
        $identifier = trim((string) $request->header('X-Device-Identifier'));
        if ($identifier === '') {
            return null;
        }

        $device = $this->userDeviceRepository->findLatestByProjectAndIdentifier(
            AdminContext::requireCurrentProjectId(),
            $identifier,
        );
        $token = $request->header('X-Device-Token');
        $token = is_string($token) ? $token : null;
        if ($device === null || ! $device->authenticates($token) || (int) $device->user_id !== $userId) {
            throw new InvalidArgumentException('端末を確認できません。アプリを開き直してください');
        }

        return $device;
    }
}

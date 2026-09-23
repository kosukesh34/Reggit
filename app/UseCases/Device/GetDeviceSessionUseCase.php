<?php

namespace App\UseCases\Device;

use App\Repositories\Contracts\UserDeviceRepository;
use App\Repositories\Contracts\UserRepository;
use App\UseCases\Device\Data\DeviceSessionOutput;

class GetDeviceSessionUseCase
{
    public function __construct(
        private UserDeviceRepository $userDeviceRepository,
        private UserRepository $userRepository,
    ) {}

    public function handle(int $projectId, ?string $deviceIdentifier, ?string $deviceToken = null): DeviceSessionOutput
    {
        if ($deviceIdentifier === null || trim($deviceIdentifier) === '') {
            return new DeviceSessionOutput(null);
        }

        $device = $this->userDeviceRepository->findLatestByProjectAndIdentifier(
            $projectId,
            trim($deviceIdentifier),
        );

        if ($device === null || ! $device->authenticates($deviceToken)) {
            return new DeviceSessionOutput(null);
        }

        $user = $this->userRepository->findByIdAndProject((int) $device->user_id, $projectId);
        if ($user === null || $user->line_user_id === null || $user->line_user_id === '') {
            return new DeviceSessionOutput(null);
        }

        if (str_starts_with($user->line_user_id, 'guest-')) {
            return new DeviceSessionOutput(null);
        }

        return new DeviceSessionOutput($user->line_user_id);
    }
}

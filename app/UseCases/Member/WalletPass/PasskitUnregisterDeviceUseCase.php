<?php

namespace App\UseCases\Member\WalletPass;

use App\Services\AppleWalletPasskitService;
use App\UseCases\Member\WalletPass\Data\PasskitHttpOutput;
use App\UseCases\Member\WalletPass\Data\PasskitUnregisterDeviceInput;

class PasskitUnregisterDeviceUseCase
{
    public function __construct(
        private AppleWalletPasskitService $passkitService,
    ) {}

    public function handle(PasskitUnregisterDeviceInput $input): PasskitHttpOutput
    {
        $authToken = $this->passkitService->parseAuthToken($input->authorizationHeader);
        if ($authToken === null) {
            return PasskitHttpOutput::unauthorized();
        }

        $member = $this->passkitService->resolveMember($input->passTypeIdentifier, $input->serialNumber);
        if ($member === null || $this->passkitService->isAuthorizedForMember($member, $authToken) !== true) {
            return PasskitHttpOutput::unauthorized();
        }

        $this->passkitService->unregisterDevice(
            $input->deviceLibraryIdentifier,
            $input->passTypeIdentifier,
            $input->serialNumber,
        );

        return PasskitHttpOutput::empty(200);
    }
}

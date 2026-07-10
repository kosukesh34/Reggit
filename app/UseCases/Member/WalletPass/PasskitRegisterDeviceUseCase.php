<?php

namespace App\UseCases\Member\WalletPass;

use App\Services\AppleWalletPasskitService;
use App\UseCases\Member\WalletPass\Data\PasskitHttpOutput;
use App\UseCases\Member\WalletPass\Data\PasskitRegisterDeviceInput;

class PasskitRegisterDeviceUseCase
{
    public function __construct(
        private AppleWalletPasskitService $passkitService,
    ) {}

    public function handle(PasskitRegisterDeviceInput $input): PasskitHttpOutput
    {
        $authToken = $this->passkitService->parseAuthToken($input->authorizationHeader);
        if ($authToken === null) {
            return PasskitHttpOutput::unauthorized();
        }

        $member = $this->passkitService->resolveMember($input->passTypeIdentifier, $input->serialNumber);
        if ($member === null || $this->passkitService->isAuthorizedForMember($member, $authToken) !== true) {
            return PasskitHttpOutput::unauthorized();
        }

        $pushToken = trim($input->pushToken);
        if ($pushToken === '') {
            return PasskitHttpOutput::badRequest();
        }

        $this->passkitService->registerDevice(
            $member,
            $input->deviceLibraryIdentifier,
            $input->passTypeIdentifier,
            $input->serialNumber,
            $pushToken,
        );

        return PasskitHttpOutput::empty(201);
    }
}

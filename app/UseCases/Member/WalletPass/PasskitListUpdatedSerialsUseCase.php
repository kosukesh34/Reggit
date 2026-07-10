<?php

namespace App\UseCases\Member\WalletPass;

use App\Services\AppleWalletPasskitService;
use App\UseCases\Member\WalletPass\Data\PasskitHttpOutput;
use App\UseCases\Member\WalletPass\Data\PasskitListUpdatedSerialsInput;

class PasskitListUpdatedSerialsUseCase
{
    public function __construct(
        private AppleWalletPasskitService $passkitService,
    ) {}

    public function handle(PasskitListUpdatedSerialsInput $input): PasskitHttpOutput
    {
        $authToken = $this->passkitService->parseAuthToken($input->authorizationHeader);
        if ($authToken === null) {
            return PasskitHttpOutput::unauthorized();
        }

        if ($this->passkitService->isAuthorizedForDevice(
            $input->deviceLibraryIdentifier,
            $input->passTypeIdentifier,
            $authToken,
        ) !== true) {
            return PasskitHttpOutput::unauthorized();
        }

        $result = $this->passkitService->listUpdatedSerials(
            $input->deviceLibraryIdentifier,
            $input->passTypeIdentifier,
            $input->passesUpdatedSince,
        );

        return PasskitHttpOutput::json(200, $result);
    }
}

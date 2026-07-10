<?php

namespace App\UseCases\Member\WalletPass;

use App\Services\AppleWalletPasskitService;
use App\UseCases\Member\WalletPass\Data\PasskitGetLatestPassInput;
use App\UseCases\Member\WalletPass\Data\PasskitHttpOutput;

class PasskitGetLatestPassUseCase
{
    public function __construct(
        private AppleWalletPasskitService $passkitService,
    ) {}

    public function handle(PasskitGetLatestPassInput $input): PasskitHttpOutput
    {
        $authToken = $this->passkitService->parseAuthToken($input->authorizationHeader);
        if ($authToken === null) {
            return PasskitHttpOutput::unauthorized();
        }

        $member = $this->passkitService->resolveMember($input->passTypeIdentifier, $input->serialNumber);
        if ($member === null || $this->passkitService->isAuthorizedForMember($member, $authToken) !== true) {
            return PasskitHttpOutput::unauthorized();
        }

        $content = $this->passkitService->generatePassContent($member);
        if ($content === null) {
            return PasskitHttpOutput::passGenerateFailed();
        }

        return PasskitHttpOutput::passContent($content);
    }
}

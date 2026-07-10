<?php

namespace App\Http\Support;

use App\UseCases\Member\WalletPass\Data\PasskitGetLatestPassInput;
use App\UseCases\Member\WalletPass\Data\PasskitListUpdatedSerialsInput;
use App\UseCases\Member\WalletPass\Data\PasskitRegisterDeviceInput;
use App\UseCases\Member\WalletPass\Data\PasskitUnregisterDeviceInput;
use Illuminate\Http\Request;

class AppleWalletPasskitInputFactory
{
    public function registerDevice(
        Request $request,
        string $deviceLibraryIdentifier,
        string $passTypeIdentifier,
        string $serialNumber,
    ): PasskitRegisterDeviceInput {
        return new PasskitRegisterDeviceInput(
            authorizationHeader: (string) $request->header('Authorization', ''),
            deviceLibraryIdentifier: $deviceLibraryIdentifier,
            passTypeIdentifier: $passTypeIdentifier,
            serialNumber: $serialNumber,
            pushToken: (string) $request->input('pushToken', ''),
        );
    }

    public function unregisterDevice(
        Request $request,
        string $deviceLibraryIdentifier,
        string $passTypeIdentifier,
        string $serialNumber,
    ): PasskitUnregisterDeviceInput {
        return new PasskitUnregisterDeviceInput(
            authorizationHeader: (string) $request->header('Authorization', ''),
            deviceLibraryIdentifier: $deviceLibraryIdentifier,
            passTypeIdentifier: $passTypeIdentifier,
            serialNumber: $serialNumber,
        );
    }

    public function listUpdatedSerials(
        Request $request,
        string $deviceLibraryIdentifier,
        string $passTypeIdentifier,
    ): PasskitListUpdatedSerialsInput {
        $since = $request->query('passesUpdatedSince');

        return new PasskitListUpdatedSerialsInput(
            authorizationHeader: (string) $request->header('Authorization', ''),
            deviceLibraryIdentifier: $deviceLibraryIdentifier,
            passTypeIdentifier: $passTypeIdentifier,
            passesUpdatedSince: is_string($since) && $since !== '' ? $since : null,
        );
    }

    public function getLatestPass(
        Request $request,
        string $passTypeIdentifier,
        string $serialNumber,
    ): PasskitGetLatestPassInput {
        return new PasskitGetLatestPassInput(
            authorizationHeader: (string) $request->header('Authorization', ''),
            passTypeIdentifier: $passTypeIdentifier,
            serialNumber: $serialNumber,
        );
    }
}

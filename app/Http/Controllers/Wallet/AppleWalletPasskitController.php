<?php

namespace App\Http\Controllers\Wallet;

use App\Http\Controllers\Controller;
use App\Http\Support\AppleWalletPasskitInputFactory;
use App\UseCases\Member\WalletPass\Data\PasskitHttpOutput;
use App\UseCases\Member\WalletPass\PasskitGetLatestPassUseCase;
use App\UseCases\Member\WalletPass\PasskitListUpdatedSerialsUseCase;
use App\UseCases\Member\WalletPass\PasskitRegisterDeviceUseCase;
use App\UseCases\Member\WalletPass\PasskitUnregisterDeviceUseCase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AppleWalletPasskitController extends Controller
{
    public function __construct(
        private AppleWalletPasskitInputFactory $inputFactory,
        private PasskitRegisterDeviceUseCase $registerDeviceUseCase,
        private PasskitUnregisterDeviceUseCase $unregisterDeviceUseCase,
        private PasskitListUpdatedSerialsUseCase $listUpdatedSerialsUseCase,
        private PasskitGetLatestPassUseCase $getLatestPassUseCase,
    ) {}

    public function registerDevice(
        Request $request,
        string $deviceLibraryIdentifier,
        string $passTypeIdentifier,
        string $serialNumber
    ): Response {
        return $this->toResponse($this->registerDeviceUseCase->handle(
            $this->inputFactory->registerDevice($request, $deviceLibraryIdentifier, $passTypeIdentifier, $serialNumber),
        ));
    }

    public function unregisterDevice(
        Request $request,
        string $deviceLibraryIdentifier,
        string $passTypeIdentifier,
        string $serialNumber
    ): Response {
        return $this->toResponse($this->unregisterDeviceUseCase->handle(
            $this->inputFactory->unregisterDevice($request, $deviceLibraryIdentifier, $passTypeIdentifier, $serialNumber),
        ));
    }

    public function listUpdatedSerials(
        Request $request,
        string $deviceLibraryIdentifier,
        string $passTypeIdentifier
    ): Response {
        return $this->toResponse($this->listUpdatedSerialsUseCase->handle(
            $this->inputFactory->listUpdatedSerials($request, $deviceLibraryIdentifier, $passTypeIdentifier),
        ));
    }

    public function getLatestPass(
        Request $request,
        string $passTypeIdentifier,
        string $serialNumber
    ): Response {
        return $this->toResponse($this->getLatestPassUseCase->handle(
            $this->inputFactory->getLatestPass($request, $passTypeIdentifier, $serialNumber),
        ));
    }

    private function toResponse(PasskitHttpOutput $output): Response
    {
        if ($output->json !== null) {
            return response()->json($output->json, $output->status);
        }

        return response($output->body, $output->status, $output->headers);
    }
}

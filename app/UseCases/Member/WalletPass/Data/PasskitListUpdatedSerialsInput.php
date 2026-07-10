<?php

namespace App\UseCases\Member\WalletPass\Data;

final class PasskitListUpdatedSerialsInput
{
    public function __construct(
        public readonly string $authorizationHeader,
        public readonly string $deviceLibraryIdentifier,
        public readonly string $passTypeIdentifier,
        public readonly ?string $passesUpdatedSince,
    ) {}
}

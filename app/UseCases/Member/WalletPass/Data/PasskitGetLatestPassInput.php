<?php

namespace App\UseCases\Member\WalletPass\Data;

final class PasskitGetLatestPassInput
{
    public function __construct(
        public readonly string $authorizationHeader,
        public readonly string $passTypeIdentifier,
        public readonly string $serialNumber,
    ) {}
}

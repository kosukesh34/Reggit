<?php

namespace App\UseCases\Member\GoogleWallet\Data;

final class GetGoogleWalletUrlOutput
{
    public const ERROR_UNAVAILABLE = 'unavailable';

    public const ERROR_NOT_MEMBER = 'not_member';

    public const ERROR_URL_FAILED = 'url_failed';

    /** @param array<int, string> $reasons */
    public function __construct(
        public bool $success,
        public ?string $url = null,
        public ?string $errorType = null,
        public ?string $errorMessage = null,
        public array $reasons = [],
    ) {}
}

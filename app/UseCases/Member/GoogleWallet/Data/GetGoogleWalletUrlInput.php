<?php

namespace App\UseCases\Member\GoogleWallet\Data;

use App\Models\User;

/**
 * Google Wallet URL 発行 UseCase の入力。
 * トークン指定時は token と projectId を渡す。セッション時は user と project 情報を渡す。
 */
final class GetGoogleWalletUrlInput
{
    public function __construct(
        public ?string $token = null,
        public ?User $user = null,
        public int|false|null $projectId = null,
        public ?string $projectKey = null,
        public ?string $projectName = null,
        public string $requestOrigin = '',
    ) {}
}

<?php

namespace App\UseCases\Member\WalletPass\Data;

use App\Models\User;

/**
 * Wallet Pass ダウンロード UseCase の入力。
 * トークン指定時は token のみ。セッション時は user を渡す。
 */
final class WalletPassDownloadInput
{
    public function __construct(
        public readonly ?string $token = null,
        public readonly ?User $user = null,
    ) {
    }
}

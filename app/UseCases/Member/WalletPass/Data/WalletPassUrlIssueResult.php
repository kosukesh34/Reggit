<?php

namespace App\UseCases\Member\WalletPass\Data;

/**
 * Wallet Pass 用ワンタイム URL 発行 UseCase の結果。
 */
final class WalletPassUrlIssueResult
{
    public function __construct(
        public readonly string $url,
    ) {
    }
}

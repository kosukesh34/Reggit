<?php

namespace App\UseCases\Member\WalletPass\Data;

/**
 * Wallet Pass 用ワンタイム URL 発行 UseCase の出力。
 */
final class WalletPassUrlIssueOutput
{
    public function __construct(
        public readonly string $url,
    ) {
    }
}

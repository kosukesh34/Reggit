<?php

namespace App\UseCases\Member\WalletPass\Data;

/**
 * Wallet Pass ダウンロード UseCase の結果。
 * content が null のときは unavailableReasons に理由が入る。
 */
final class WalletPassDownloadResult
{
    /**
     * @param array<int, string> $unavailableReasons
     */
    public function __construct(
        public readonly ?string $content,
        public readonly array $unavailableReasons = [],
    ) {
    }

    public function isAvailable(): bool
    {
        return $this->content !== null;
    }
}

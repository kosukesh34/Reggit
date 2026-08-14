<?php

namespace App\UseCases\Member\WalletPass\Data;

/** Wallet 追加中間ページの表示用データ。 */
class WalletAddView
{
    public function __construct(
        public ?string $walletUrl,
        public ?string $returnUrl,
        public string $typeLabel,
        public string $addButtonLabel,
        public string $returnBackLabel,
    ) {}
}

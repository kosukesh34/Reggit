<?php

namespace App\UseCases\Admin\Member\Data;

/**
 * 会員一覧画面の付帯表示データ（LINE招待QRなど）。
 */
class AdminMemberListExtras
{
    public function __construct(
        public readonly string $lineInviteUrl,
        public readonly string $lineInviteLabel,
    ) {}
}

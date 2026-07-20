<?php

namespace App\Domain\Smaregi\Services;

/**
 * スマレジ取引由来のポイント／スタンプ履歴の description（冪等キー）。
 */
final class SmaregiInboundAwardDescription
{
    public const REASON_CANCEL = '取消';

    public const REASON_RETURN = '返品';

    public function pointAward(string $headId): string
    {
        return 'スマレジ取引によるポイント付与 #'.$headId;
    }

    public function pointReverse(string $headId, string $reasonLabel): string
    {
        return 'スマレジ取引'.$reasonLabel.'によるポイント取消 #'.$headId;
    }

    public function stampAward(string $headId): string
    {
        return 'スマレジ取引によるスタンプ付与 #'.$headId;
    }

    public function stampReverse(string $headId, string $reasonLabel): string
    {
        return 'スマレジ取引'.$reasonLabel.'によるスタンプ取消 #'.$headId;
    }
}

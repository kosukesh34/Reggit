<?php

namespace App\Domain\Promotion;

use App\Util\EnumBase;

/** アプリから送られるポップアップの反応。日ごとに件数を数える。 */
class PromoPopupEvent extends EnumBase
{
    public const VIEW = 'view';

    public const CLICK = 'click';

    public const SECONDARY_CLICK = 'secondary_click';

    public const CLOSE = 'close';

    /** @var array<string, string> */
    protected static array $values = [
        self::VIEW => '表示',
        self::CLICK => '1つ目のボタン',
        self::SECONDARY_CLICK => '2つ目のボタン',
        self::CLOSE => '閉じる',
    ];
}

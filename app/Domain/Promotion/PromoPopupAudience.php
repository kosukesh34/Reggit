<?php

namespace App\Domain\Promotion;

use App\Util\EnumBase;

/** ポップアップを表示する相手。 */
class PromoPopupAudience extends EnumBase
{
    public const ALL = 'all';

    public const MEMBERS = 'members';

    public const GUESTS = 'guests';

    public const SEGMENT = 'segment';

    /** @var array<string, string> */
    protected static array $values = [
        self::ALL => 'すべての利用者',
        self::MEMBERS => '会員だけ',
        self::GUESTS => '会員登録していない人だけ',
        self::SEGMENT => '指定したタグ・ランクの会員',
    ];
}

<?php

namespace App\Enums;

use App\Util\EnumBase;

/**
 * スマレジ POS 取消区分（cancelDivision）。
 *
 * @see https://developers.smaregi.dev/platform-api-reference/apis/pos/operations/gettransactionstransactionid/
 */
class SmaregiCancelDivision extends EnumBase
{
    protected static array $values = [
        '0' => '通常',
        '1' => '取消',
    ];

    public const NORMAL = '0';

    public const CANCELLED = '1';
}

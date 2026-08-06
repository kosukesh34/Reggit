<?php

namespace App\Enums;

use App\Util\EnumBase;

/**
 * スマレジ POS 返品取引区分（returnSales）。
 *
 * @see https://developers.smaregi.dev/platform-api-reference/apis/pos/operations/gettransactionstransactionid/
 */
class SmaregiReturnSalesDivision extends EnumBase
{
    protected static array $values = [
        '0' => '通常',
        '1' => '返品販売',
    ];

    public const NORMAL = '0';

    public const RETURN = '1';
}

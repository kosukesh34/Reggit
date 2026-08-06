<?php

namespace App\Enums;

use App\Util\EnumBase;

/**
 * スマレジ POS 取引明細区分（transactionDetailDivision）。
 *
 * @see https://developers.smaregi.dev/platform-api-reference/apis/pos/operations/gettransactionstransactionid/
 */
class SmaregiTransactionDetailDivision extends EnumBase
{
    protected static array $values = [
        '1' => '通常',
        '2' => '返品',
        '3' => '部門売り',
    ];

    public const NORMAL = '1';

    public const RETURN = '2';

    public const DEPARTMENT_SALE = '3';
}

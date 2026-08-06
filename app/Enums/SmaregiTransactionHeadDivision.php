<?php

namespace App\Enums;

use App\Util\EnumBase;

/**
 * スマレジ POS 取引区分（transactionHeadDivision）。
 *
 * @see https://developers.smaregi.dev/platform-api-reference/apis/pos/operations/gettransactionstransactionid/
 */
class SmaregiTransactionHeadDivision extends EnumBase
{
    protected static array $values = [
        '1' => '通常',
        '2' => '入金',
        '3' => '出金',
        '4' => '預かり金',
        '5' => '預かり金返金',
        '6' => 'ポイント加算',
        '7' => 'ポイント減算',
        '8' => 'ポイント失効',
        '9' => '回数券',
        '10' => '取置き',
        '11' => 'チップ',
        '13' => 'マイル加算',
        '14' => 'マイル減算',
        '15' => 'バリューカード入金',
        '16' => '領収証',
    ];

    public const NORMAL = '1';

    public const DEPOSIT = '2';

    public const WITHDRAWAL = '3';

    public const CUSTODY = '4';

    public const CUSTODY_REFUND = '5';

    public const POINT_ADD = '6';

    public const POINT_SUBTRACT = '7';

    public const POINT_EXPIRE = '8';

    public const COUPON_TICKET = '9';

    public const LAYAWAY = '10';

    public const TIP = '11';

    public const MILE_ADD = '13';

    public const MILE_SUBTRACT = '14';

    public const VALUE_CARD_DEPOSIT = '15';

    public const RECEIPT = '16';
}

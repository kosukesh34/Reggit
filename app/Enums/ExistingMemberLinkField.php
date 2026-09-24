<?php

namespace App\Enums;

use App\Util\EnumBase;

class ExistingMemberLinkField extends EnumBase
{
    public const CUSTOMER_CODE = 'customer_code';

    public const EMAIL = 'email';

    public const PHONE = 'phone';

    public const NAME = 'name';

    public const BIRTHDAY = 'birthday';

    public const ADDRESS = 'address';

    protected static array $values = [
        self::CUSTOMER_CODE => '会員コード',
        self::EMAIL => 'メールアドレス',
        self::PHONE => '電話番号',
        self::NAME => '氏名',
        self::BIRTHDAY => '生年月日',
        self::ADDRESS => '住所',
    ];

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(static::$values);
    }
}

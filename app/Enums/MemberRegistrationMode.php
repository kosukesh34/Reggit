<?php

namespace App\Enums;

use App\Util\EnumBase;

class MemberRegistrationMode extends EnumBase
{
    protected static array $values = [
        self::OPTIONAL => 'そのまま使える',
        self::PROMPT => '登録を促す（フォーム表示）',
        self::FORCE => '会員登録を必須にする',
    ];

    public const OPTIONAL = 'optional';

    public const PROMPT = 'prompt';

    public const FORCE = 'force';
}

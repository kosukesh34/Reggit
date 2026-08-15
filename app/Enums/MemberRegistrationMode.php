<?php

namespace App\Enums;

use App\Util\EnumBase;

class MemberRegistrationMode extends EnumBase
{
    protected static array $values = [
        self::OPTIONAL => 'ゲスト可能',
        self::POPUP => 'ポップアップを表示する',
        self::PROMPT => '登録画面を表示する（任意登録）',
        self::FORCE => '会員必須',
    ];

    public const OPTIONAL = 'optional';

    public const POPUP = 'popup';

    public const PROMPT = 'prompt';

    public const FORCE = 'force';

    public static function shouldAutoCreateMember(string $mode): bool
    {
        return $mode === self::OPTIONAL;
    }
}

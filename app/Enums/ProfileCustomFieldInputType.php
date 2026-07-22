<?php

namespace App\Enums;

use App\Util\EnumBase;

class ProfileCustomFieldInputType extends EnumBase
{
    protected static array $values = [
        self::TEXT => 'テキスト',
        self::TEXTAREA => 'テキストエリア',
        self::RADIO => 'ラジオボタン',
        self::SELECTBOX => 'セレクトボックス',
        self::CHECKBOX => 'チェックボックス',
    ];

    public const TEXT = 'text';

    public const TEXTAREA = 'textarea';

    public const RADIO = 'radio';

    public const SELECTBOX = 'selectbox';

    public const CHECKBOX = 'checkbox';

    public const VALUES_MAX = 20;

    public static function needsValues(string $inputType): bool
    {
        return in_array($inputType, [self::RADIO, self::SELECTBOX, self::CHECKBOX], true);
    }
}

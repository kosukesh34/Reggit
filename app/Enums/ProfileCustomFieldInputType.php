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

    /**
     * @return array<string, string>
     */
    public static function descriptions(): array
    {
        return [
            self::TEXT => '短い文章を入力してもらいます',
            self::TEXTAREA => '長い文章を入力してもらいます',
            self::RADIO => '選択肢から1つだけ選んでもらいます',
            self::SELECTBOX => 'リストから1つ選んでもらいます',
            self::CHECKBOX => '選択肢から複数選んでもらいます',
        ];
    }
}

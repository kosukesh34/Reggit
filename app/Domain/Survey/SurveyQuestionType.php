<?php

namespace App\Domain\Survey;

use App\Util\EnumBase;

class SurveyQuestionType extends EnumBase
{
    public const NPS = 'nps';

    public const CHOICE = 'choice';

    public const MULTI_CHOICE = 'multi_choice';

    public const RATING = 'rating';

    public const NUMBER = 'number';

    public const DATE = 'date';

    public const TEXT = 'text';

    /** 1〜5 の評価の最大値 */
    public const RATING_MAX = 5;

    /** @var array<string, string> */
    protected static array $values = [
        self::NPS => 'NPS 0〜10',
        self::CHOICE => '選択式（1つ）',
        self::MULTI_CHOICE => '選択式（複数）',
        self::RATING => '5段階評価',
        self::NUMBER => '数値',
        self::DATE => '日付',
        self::TEXT => '自由記述',
    ];

    /** 管理画面で追加できる形式 */
    public static function addable(): array
    {
        return array_keys(static::$values);
    }

    /** 管理画面の形式ボタンに出すアイコン（Font Awesome） */
    public static function icon(string $type): string
    {
        return match ($type) {
            self::NPS => 'fa-gauge-high',
            self::CHOICE => 'fa-circle-dot',
            self::MULTI_CHOICE => 'fa-square-check',
            self::RATING => 'fa-star',
            self::NUMBER => 'fa-hashtag',
            self::DATE => 'fa-calendar-day',
            default => 'fa-align-left',
        };
    }

    public static function hasOptions(string $type): bool
    {
        return in_array($type, [self::CHOICE, self::MULTI_CHOICE], true);
    }
}

<?php

namespace App\Enums;

use App\Util\EnumBase;

class StampGrantLimit extends EnumBase
{
    public const DAILY = 'daily';

    public const PER_MEMBER = 'member';

    public const ONCE = 'once';

    public const UNLIMITED = 'unlimited';

    public const NEVER_EXPIRES = 0;

    public const DEFAULT_EXPIRES_MINUTES = 15;

    public const MAX_AMOUNT = 20;

    public const DEFAULT_QUOTA = 10;

    public const MAX_QUOTA = 999;

    /** @var array<string, string> */
    protected static array $values = [
        self::DAILY => '1日1回（お客様ごと）',
        self::PER_MEMBER => 'お客様1人につき1回（再利用不可）',
        self::ONCE => '先着○名',
        self::UNLIMITED => '期限内は何度でも',
    ];

    /** @var array<int, string> */
    public const EXPIRES_OPTIONS = [
        15 => '15分',
        60 => '1時間',
        1440 => '1日',
        10080 => '7日',
        43200 => '30日',
        129600 => '90日',
        525600 => '1年',
        self::NEVER_EXPIRES => '無期限',
    ];

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(static::$values);
    }

    /**
     * @return array<string, array{label: string, description: string, example: string}>
     */
    public static function useCases(): array
    {
        return [
            self::DAILY => [
                'label' => static::$values[self::DAILY],
                'description' => '同じお客様は、日付が変わるまでこのQRを再利用できません。翌日になるとまた押せます。',
                'example' => 'レジ横に貼っておく来店スタンプ',
            ],
            self::PER_MEMBER => [
                'label' => static::$values[self::PER_MEMBER],
                'description' => '同じお客様は、一度使うと二度と押せません。別のお客様は使えます。',
                'example' => 'チラシ・DM・初回限定のQR',
            ],
            self::ONCE => [
                'label' => static::$values[self::ONCE],
                'description' => '指定した人数まで使えます。同じお客様は1回だけです。人数に達すると終了します。',
                'example' => 'オープニングキャンペーンの来店スタンプ',
            ],
            self::UNLIMITED => [
                'label' => static::$values[self::UNLIMITED],
                'description' => '有効期限までは、同じお客様でも何度でも押せます。',
                'example' => '動作確認や、連続で押す必要があるとき',
            ],
        ];
    }

    public static function normalize(string $value): string
    {
        return array_key_exists($value, static::$values) ? $value : self::DAILY;
    }

    public static function redeemedMessage(string $limit): string
    {
        $limit = self::normalize($limit);
        if ($limit === self::ONCE) {
            return '先着人数に達したため、このQRは終了しました';
        }
        if ($limit === self::DAILY) {
            return '本日はこのQRからのスタンプを付与済みです';
        }

        return 'このQRからのスタンプは付与済みです';
    }

    public static function displayLabel(string $limit, int $quota = 1): string
    {
        $limit = self::normalize($limit);
        if ($limit === self::ONCE) {
            return '先着'.self::normalizeQuota($quota).'名';
        }

        return self::getLabel($limit);
    }

    public static function normalizeAmount(int $amount): int
    {
        return max(1, min(self::MAX_AMOUNT, $amount));
    }

    public static function normalizeQuota(int $quota): int
    {
        return max(1, min(self::MAX_QUOTA, $quota));
    }

    public static function normalizeExpiresMinutes(int $minutes): int
    {
        return array_key_exists($minutes, self::EXPIRES_OPTIONS) ? $minutes : self::DEFAULT_EXPIRES_MINUTES;
    }
}

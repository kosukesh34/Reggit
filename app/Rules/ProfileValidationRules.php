<?php

namespace App\Rules;

/**
 * プロフィール／会員情報の形式バリデーション。
 * スマレジ API 制約に合わせて桁数・形式を揃える。
 */
class ProfileValidationRules
{
    public const EMAIL_REGEX = '/^[a-zA-Z0-9._%+-]+@(?:[_\\p{L}0-9][-_\\p{L}0-9]*\.)*(?:[\\p{L}0-9][-\\p{L}0-9]{0,62})\.(?:(?:[a-z]{2}\.)?[a-z]{2,})$/ui';

    public const PHONE_REGEX = '/^[a-zA-Z0-9 \-\/:@\[\]`{}~]{1,20}$/';

    public const KANA_REGEX = '/^[ァ-ヶ　 -~ー－・]+$/u';

    /** 姓・名それぞれ（スマレジ会員項目の桁数に合わせる） */
    public const NAME_PART_MAX = 85;

    public const KANA_PART_MAX = 85;

    /** 結合後の氏名・カナ（姓 + 半角スペース + 名） */
    public const NAME_MAX = 171;

    public const KANA_MAX = 171;

    public const EMAIL_MAX = 255;

    public const PHONE_MAX = 20;

    public const POSTAL_CODE_MAX = 8;

    public const ADDRESS_MAX = 85;

    public const PREFECTURE_MAX = 64;

    public const CITY_MAX = 128;

    public const STREET_MAX = 256;

    public const CUSTOM_TEXT_MAX = 500;

    /**
     * 基本項目の形式ルール（required / nullable は呼び出し側で付与する）。
     *
     * @return array<string, list<string>>
     */
    public static function formatRulesByField(): array
    {
        return [
            'name' => ['string', 'max:'.self::NAME_MAX],
            'kana' => ['string', 'max:'.self::KANA_MAX, 'regex:'.self::KANA_REGEX],
            'email' => ['string', 'max:'.self::EMAIL_MAX, 'regex:'.self::EMAIL_REGEX],
            'phone' => ['string', 'max:'.self::PHONE_MAX, 'regex:'.self::PHONE_REGEX],
            'postal_code' => ['string', 'max:'.self::POSTAL_CODE_MAX],
            'prefecture' => ['string', 'max:'.self::PREFECTURE_MAX],
            'city' => ['string', 'max:'.self::CITY_MAX],
            'street' => ['string', 'max:'.self::STREET_MAX],
            'address' => ['string', 'max:'.self::ADDRESS_MAX],
            'birthday' => ['date'],
            'sex' => ['in:0,1,2'],
            'mail_receive_flag' => ['in:0,1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'kana.regex' => 'フリガナはカタカナで入力してください',
            'phone.regex' => '電話番号の形式が正しくありません',
            'email.regex' => 'メールアドレスの形式が正しくありません',
            'postal_code.max' => '郵便番号は'.self::POSTAL_CODE_MAX.'文字以内で入力してください',
            'address.max' => '住所は'.self::ADDRESS_MAX.'文字以内で入力してください',
        ];
    }
}

<?php

namespace App\Domain\Settings;

/**
 * ShopSetting のドメイン定数。Blade や外部コードから直接参照するための定数群。
 */
class ShopSettingKeys
{
    public const ORDER_MODE_BOTH = 'both';

    public const ORDER_MODE_MOBILE_ONLY = 'mobile_only';

    public const ORDER_MODE_IN_STORE_ONLY = 'in_store_only';

    public const LIFF_SAVED_SCREENS_MAX = 10;

    /** スマレジ会員自由項目と同じ上限 */
    public const PROFILE_CUSTOM_FIELD_MAX = 99;

    public const FEATURE_FLAGS = [
        'feature_home_enabled' => true,
        'feature_orders_enabled' => true,
        'feature_reservations_enabled' => true,
        'feature_queue_enabled' => true,
        'feature_stamps_enabled' => true,
        'feature_coupons_enabled' => true,
        'feature_members_enabled' => true,
        'feature_announcements_enabled' => true,
        'feature_payments_enabled' => true,
        'feature_gallery_enabled' => true,
        'feature_gacha_enabled' => false,
        'feature_scratch_enabled' => false,
        'feature_roulette_enabled' => false,
    ];

    public const FEATURE_FLAG_LABELS = [
        'feature_home_enabled' => 'ホーム',
        'feature_orders_enabled' => '注文（オーダー・履歴）',
        'feature_reservations_enabled' => '予約',
        'feature_queue_enabled' => '順番待ち',
        'feature_stamps_enabled' => 'スタンプカード',
        'feature_coupons_enabled' => 'クーポン',
        'feature_members_enabled' => '会員証',
        'feature_announcements_enabled' => 'お知らせ',
    ];

    public const LOTTERY_MANAGED_FEATURE_FLAGS = [
        'feature_gacha_enabled',
        'feature_scratch_enabled',
        'feature_roulette_enabled',
    ];

    public const PROFILE_SMAREGI_FIELDS = [
        'profile_image' => 'プロフィール画像',
        'name' => 'お名前（姓・名）',
        'kana' => 'フリガナ（姓・名）',
        'phone' => '電話番号',
        'email' => 'メールアドレス',
        'postal_code' => '郵便番号',
        'address' => '住所',
        'birthday' => '生年月日',
        'sex' => '性別',
        'mail_receive_flag' => '店舗からの案内メールを受け取る',
        'customer_store_id' => '対象店舗',
    ];

    public const PROFILE_SMAREGI_LINKED_KEYS = [
        'name',
        'kana',
        'phone',
        'email',
        'postal_code',
        'address',
        'birthday',
        'sex',
        'mail_receive_flag',
        'customer_store_id',
    ];

    public static function isSmaregiLinkedProfileField(string $key): bool
    {
        return in_array($key, self::PROFILE_SMAREGI_LINKED_KEYS, true);
    }
}

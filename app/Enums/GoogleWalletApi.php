<?php

namespace App\Enums;

use App\Util\EnumBase;

/**
 * Google Wallet API で使用する定数（マジックストリング排除用）。
 * API 仕様の文字列はここで一元管理する。
 */
class GoogleWalletApi extends EnumBase
{
    protected static array $values = [
        self::REVIEW_STATUS_UNDER_REVIEW => self::REVIEW_STATUS_UNDER_REVIEW,
        self::OBJECT_STATE_ACTIVE => self::OBJECT_STATE_ACTIVE,
        self::BARCODE_TYPE_CODE_128 => self::BARCODE_TYPE_CODE_128,
        self::DEFAULT_LANGUAGE => self::DEFAULT_LANGUAGE,
    ];

    /** LoyaltyClass / LoyaltyObject の reviewStatus */
    public const REVIEW_STATUS_UNDER_REVIEW = 'UNDER_REVIEW';

    /** LoyaltyObject の state */
    public const OBJECT_STATE_ACTIVE = 'ACTIVE';

    /** バーコード種別（CODE_128） */
    public const BARCODE_TYPE_CODE_128 = 'CODE_128';

    /** 表示文言のデフォルト言語（API 仕様） */
    public const DEFAULT_LANGUAGE = 'en-US';

    /** 表示用文字列の最大長（API Recommended maximum length） */
    public const MAX_DISPLAY_LENGTH = 20;

    /** OAuth2 アサーションの有効期限（秒） */
    public const OAUTH2_TOKEN_TTL_SECONDS = 3600;

    /** 表示ラベル（LoyaltyClass / LoyaltyObject の accountNameLabel 等） */
    public const LABEL_ACCOUNT_NAME = '会員名';

    public const LABEL_ACCOUNT_ID = '会員番号';

    public const LABEL_RANK = 'ランク';

    public const LABEL_POINTS = 'ポイント';

    /** 会員名未設定時の表示プレフィックス（例: 会員#12345） */
    public const ACCOUNT_NAME_FALLBACK_PREFIX = '会員#';
}

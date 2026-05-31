<?php

return [
    'enabled' => filter_var(env('GOOGLE_WALLET_ENABLED', false), FILTER_VALIDATE_BOOLEAN),

    /*
    | Google Wallet API (Pay Passes) の Issuer ID（数値または文字列）。
    | Google Pay & Wallet Console で発行された Issuer ID を指定します。
    | 例: 3388000000023085556
    */
    'issuer_id' => env('GOOGLE_WALLET_ISSUER_ID', ''),

    /*
    | Google Cloud サービスアカウントの秘密鍵 JSON ファイルのパス。
    | このキーで JWT に署名し、Add to Google Wallet リンクを生成します。
    | 相対パス（storage/... など）の場合はプロジェクトルートからのパスとして解決します。
    */
    'credentials_path' => (function () {
        $path = env('GOOGLE_WALLET_CREDENTIALS_PATH', '');
        if ($path === '') {
            return storage_path('app/google_wallet/credentials.json');
        }
        if ($path[0] === '/' || (strlen($path) > 1 && $path[1] === ':')) {
            return $path;
        }

        return base_path($path);
    })(),

    /*
    | Add to Google Wallet ボタンを表示してよいオリジン（ドメイン）のリスト。
    | 例: ['https://example.com', 'https://liff.line.me']
    */
    'origins' => array_filter(array_map('trim', explode(',', env('GOOGLE_WALLET_ORIGINS', '')))),

    /*
    | 会員証パスのクラス識別子（issuer_id の後ろに付くサフィックス）。
    | 値を変えると「別のカード」として新規クラスが作られ、新しいデザインで発行される。
    | 例: membership_v2 → 新デザイン（日本語ラベル・バーコード）のカードとして追加される。
    */
    'class_suffix' => env('GOOGLE_WALLET_CLASS_SUFFIX', 'membership_v3'),

    /*
    | LoyaltyClass で必須のプログラムロゴ画像の URL。
    | HTTPS で公開アクセス可能な画像（推奨: PNG、660x660 以上）。本番では自社ロゴの URL を .env で指定すること。
    */
    'program_logo_uri' => env('GOOGLE_WALLET_PROGRAM_LOGO_URI', 'https://www.gstatic.com/images/branding/product/2x/wallet_96dp.png'),

    /*
    | LoyaltyObject のヒーロー画像 URL（カード上部のメイン画像）。
    | 未設定時は program_logo_uri と同じ画像を使用。
    */
    'hero_image_uri' => env('GOOGLE_WALLET_HERO_IMAGE_URI', 'https://www.gstatic.com/images/branding/product/2x/wallet_96dp.png'),

    /*
    | 検証用: 会員未登録でも Google Wallet 追加用 URL を発行する。
    | true のときは未会員ユーザーにもデモ用の会員証 URL を返す（本番では false にすること）。
    */
    'allow_demo_user' => filter_var(env('GOOGLE_WALLET_ALLOW_DEMO_USER', false), FILTER_VALIDATE_BOOLEAN),

    /*
    | カードの背景色（LoyaltyClass）。#rrggbb 形式。未設定時はヒーロー画像の主色などが使われる。
    */
    'hex_background_color' => env('GOOGLE_WALLET_HEX_BACKGROUND_COLOR', ''),
];

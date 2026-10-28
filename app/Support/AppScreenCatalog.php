<?php

namespace App\Support;

/** 管理画面でリンク先・QRの開き先として選べる、会員アプリの画面。 */
final class AppScreenCatalog
{
    /** タブキー（LiffTabUrlBuilder の tab） => [アプリ内パス, 名前] */
    private const SCREENS = [
        'home' => ['/home', 'ホーム'],
        'member' => ['/membership', '会員証'],
        'coupons' => ['/coupon', 'クーポン'],
        'stamps' => ['/stamp', 'スタンプ'],
        'products' => ['/menu', 'メニュー・注文'],
        'reservations' => ['/reservation', '予約'],
        'queue' => ['/queue', '順番待ち'],
        'announcements' => ['/news', 'お知らせ'],
        'survey' => ['/survey', 'アンケート一覧'],
    ];

    /** @return array<string, string> アプリ内パス => 名前 */
    public static function paths(): array
    {
        return array_column(self::SCREENS, 1, 0);
    }

    /** @return array<string, string> タブキー => 名前 */
    public static function tabs(): array
    {
        return array_map(fn (array $screen) => $screen[1], self::SCREENS);
    }
}

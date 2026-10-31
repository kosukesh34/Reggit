<?php

namespace App\Support\LiffAppStudio;

use App\Support\LiffAppStudioTemplates;

/** App Studio の単体画面テンプレート（1タブ分の画面）。 */
final class Screens
{
    /** @return array<int, array<string, mixed>> */
    public static function all(): array
    {
        return [
            self::screenEntry('rg-screen-rewards', 'リワードホーム', 'loyalty', 'Starbucks Rewards 風。ポイント・特典・ランクを一望するリワードダッシュボード。', ['home'], [], self::screenRewards()),
            self::screenEntry('rg-screen-flash', 'フラッシュセール', 'retail', 'SHEIN 風のタイムセール画面。カウントダウンと限定商品グリッドで即購買を促す。', ['home', 'products'], ['accent_color' => '#dc2626'], self::screenFlash()),
            self::screenEntry('rg-screen-pass', '会員証フォーカス', 'loyalty', 'Apple Wallet 風の会員証提示画面。QRとステータスだけに集中したミニマル構成。', ['member'], [], self::screenPass()),
            self::screenEntry('rg-screen-stamp', 'スタンプクエスト', 'loyalty', 'Pokémon GO 風のゲーミフィケーション。クエスト形式でスタンプ収集を促す。', ['stamps'], ['accent_color' => '#f59e0b'], self::screenStamp()),
            self::screenEntry('rg-screen-menu', 'メニュー棚', 'order', 'マクドナルド／BK風。プロモ→カテゴリpill→商品グリッドの注文特化画面。', ['products'], ['theme_preset' => 'citrus', 'accent_color' => '#A81612'], self::screenMenu()),
            self::screenEntry('rg-screen-live-order', '注文ライブ状況', 'order', 'Uber Eats 風のトラッキング。注文のリアルタイム進捗を大きく表示する。', ['orders'], ['accent_color' => '#16a34a'], self::screenLiveOrder()),
            self::screenEntry('rg-screen-next-visit', '次回来店予約', 'booking', 'ホットペッパー風の再来店訴求。空き枠表示とワンタップ再予約に特化。', ['reservations'], ['theme_preset' => 'lavender', 'accent_color' => '#af52de'], self::screenNextVisit()),
            self::screenEntry('rg-screen-queue', '今すぐ順番待ち', 'booking', 'EPARK 順番待ち風。受付とLINE通知、待ち状況をシンプルに表示。', ['queue'], ['accent_color' => '#0284c7'], self::screenQueue()),
            self::screenEntry('rg-screen-lookbook', 'ルックブック', 'salon', 'Instagram 風の大判ギャラリー。スタイル写真を主役に予約へ誘導。', ['gallery'], ['theme_preset' => 'sakura', 'accent_color' => '#ff2d55'], self::screenLookbook()),
            self::screenEntry('rg-screen-digest', 'お知らせダイジェスト', 'screen', 'ニュースアプリ風。最新のお知らせをハイライト付きでまとめる。', ['announcements'], [], self::screenDigest()),
            self::screenEntry('rg-screen-store', '店舗情報', 'screen', 'Google ビジネスプロフィール風。営業時間・アクセス・SNS・FAQの案内。', ['home'], ['accent_color' => '#475569'], self::screenStore()),
            self::screenEntry('rg-screen-vip', 'VIPラウンジ', 'loyalty', 'American Express 風の上位会員ラウンジ。限定オファーと優待を編集的に見せる。', ['home', 'coupons'], ['theme_preset' => 'noir', 'accent_color' => '#92400e'], self::screenVip()),
            self::screenEntry('rg-screen-morning', 'モーニング特化カフェ', 'cafe', 'スターバックス朝アプリ風。挨拶→新着→オーダー/会員証。', ['home', 'products'], ['theme_preset' => 'sand', 'accent_color' => '#00754A'], self::screenMorning()),
            self::screenEntry('rg-screen-workout', 'ジムチェックイン', 'loyalty', 'Nike Training 風。来館チェックインと実績トラッキングを一画面に。', ['home', 'stamps', 'reservations'], ['accent_color' => '#16a34a'], self::screenWorkout()),
            self::screenEntry('rg-screen-sweets-home', '菓子店ホーム', 'sweets', 'コージーコーナー風。マイル/ポイント→季節ヒーロー→会員証・クーポン大ボタン。', ['home'], ['theme_preset' => 'pastry', 'accent_color' => '#5C3A21'], self::screenSweetsHome()),
            self::screenEntry('rg-screen-barcode-dock', 'バーコード提示', 'loyalty', 'シャトレーゼ風。ポイントカードとレジ提示バーコードだけに集中した会計画面。', ['member'], ['theme_preset' => 'pastry', 'accent_color' => '#5C3A21'], self::screenBarcodeDock()),
            self::screenEntry('rg-screen-chocolate-club', 'チョコクラブ', 'sweets', 'GODIVA / Lindt 風。ラグジュアリーな会員クラブとスタンプ特典。', ['home', 'member', 'coupons'], ['theme_preset' => 'chocolate', 'accent_color' => '#C5A059'], self::screenChocolateClub()),
            self::screenEntry('rg-screen-pickup-ready', '受取準備完了', 'order', 'Chipotle Pickup 風。時刻・場所・袋数・ヘルプで受取不安を消す。', ['orders'], ['theme_preset' => 'citrus', 'accent_color' => '#A81612'], self::screenPickupReady()),
            self::screenEntry('rg-screen-category-browse', 'カテゴリ閲覧', 'retail', 'UNIQLO 風。検索導線と写真カテゴリグリッドで商品発見。', ['home', 'products'], ['theme_preset' => 'noir', 'accent_color' => '#111111'], self::screenCategoryBrowse()),
        ];
    }

    /**
     * @param  array<int, string>  $targetTabs
     * @param  array<string, mixed>  $themeOver
     * @param  array<int, array<string, mixed>>  $blocks
     * @return array<string, mixed>
     */
    private static function screenEntry(string $id, string $name, string $category, string $description, array $targetTabs, array $themeOver, array $blocks): array
    {
        $theme = LiffAppStudioTemplates::theme($themeOver);

        return [
            'id' => $id,
            'name' => $name,
            'category' => $category,
            'description' => $description,
            'target_tabs' => $targetTabs,
            'device_support' => ['line', 'ios', 'android'],
            'theme' => $theme,
            'tab_settings' => ['_global' => $theme],
            'blocks' => $blocks,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private static function screenRewards(): array
    {
        return [
            ['type' => 'member_summary', 'design' => 'rewards', 'greeting' => 'おはようございます'],
            ['type' => 'stats_row', 'section_label' => 'マイリワード', 'items' => [
                ['label' => 'ポイント', 'value' => '70', 'hint' => 'pt', 'field' => 'points'],
                ['label' => 'ランク', 'value' => 'グリーン', 'hint' => '', 'field' => 'rank'],
                ['label' => '次の特典まで', 'value' => '20', 'hint' => 'pt', 'field' => 'points_to_next'],
            ]],
            ['type' => 'card', 'design' => 'featured', 'title' => 'モバイルオーダー', 'description' => 'レジに並ばず、お店で受け取り', 'image_url' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=800', 'action' => '#products'],
            ['type' => 'quick_actions', 'design' => 'pill', 'actions' => [
                ['icon' => 'fa-qrcode', 'label' => '会員証', 'action' => '#member'],
                ['icon' => 'fa-gift', 'label' => '特典交換', 'action' => '#coupons'],
                ['icon' => 'fa-envelope', 'label' => 'お知らせ', 'action' => '#announcements'],
            ]],
            ['type' => 'button', 'design' => 'sticky', 'text' => '特典に交換', 'action' => '#coupons', 'color' => '#00754A', 'full_width' => true],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private static function screenFlash(): array
    {
        return [
            ['type' => 'banner', 'design' => 'countdown', 'icon' => 'fa-fire', 'text' => 'フラッシュセール — 本日23:59まで最大60%OFF', 'color' => '#dc2626', 'text_color' => '#ffffff', 'action' => '#products'],
            ['type' => 'stats_row', 'items' => [
                ['label' => '残り時間', 'value' => '5:42:18', 'hint' => ''],
                ['label' => '対象商品', 'value' => '48', 'hint' => '点'],
            ]],
            ['type' => 'data', 'source' => 'products', 'design' => 'cards'],
            ['type' => 'button', 'design' => 'sticky', 'text' => 'セール商品をすべて見る', 'action' => '#products', 'color' => '#dc2626', 'full_width' => true],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private static function screenPass(): array
    {
        return [
            ['type' => 'member_summary', 'design' => 'wallet', 'greeting' => '会員証'],
            ['type' => 'data', 'source' => 'member_card', 'card_design' => 'emblem'],
            ['type' => 'stats_row', 'items' => [
                ['label' => 'ポイント', 'value' => '2,480', 'hint' => 'pt', 'field' => 'points'],
                ['label' => '次の特典まで', 'value' => '20', 'hint' => 'pt', 'field' => 'points_to_next'],
            ]],
            ['type' => 'icon_text', 'rows' => [
                ['icon' => 'fa-barcode', 'text' => 'レジでバーコードをご提示ください', 'color' => '#00754A'],
            ]],
            ['type' => 'button', 'design' => 'sticky', 'text' => 'バーコードを拡大', 'action' => '#member', 'color' => '#00754A', 'full_width' => true],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private static function screenStamp(): array
    {
        return [
            ['type' => 'member_summary', 'design' => 'compact'],
            ['type' => 'heading', 'design' => 'section', 'text' => 'スタンプクエスト', 'size' => 'large'],
            ['type' => 'stats_row', 'section_label' => '進捗', 'items' => [
                ['label' => '今月', 'value' => '7', 'hint' => '/10'],
                ['label' => '累計', 'value' => '42', 'hint' => '個'],
                ['label' => '達成特典', 'value' => '4', 'hint' => '回'],
            ]],
            ['type' => 'banner', 'design' => 'promo', 'icon' => 'fa-trophy', 'text' => 'あと3個で「ドリンク無料」達成！', 'color' => '#f59e0b'],
            ['type' => 'icon_text', 'rows' => [
                ['icon' => 'fa-stamp', 'text' => 'ご来店・ご注文ごとにスタンプ1個', 'color' => '#f59e0b'],
                ['icon' => 'fa-gift', 'text' => '10個達成: ドリンク無料', 'color' => '#f59e0b'],
                ['icon' => 'fa-crown', 'text' => '30個達成: ランクアップ', 'color' => '#f59e0b'],
            ]],
            Blocks::stampData('circle_board', 'circle', 'icon', '#f59e0b'),
            ['type' => 'button', 'design' => 'sticky', 'text' => '特典を確認', 'action' => '#coupons', 'color' => '#f59e0b', 'full_width' => true],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private static function screenMenu(): array
    {
        return [
            ['type' => 'slider', 'source' => 'gallery', 'design' => 'hero', 'section_label' => 'おすすめ', 'slide_height' => 180],
            ['type' => 'quick_actions', 'design' => 'pill', 'actions' => [
                ['icon' => 'fa-fire', 'label' => '人気', 'action' => '#products'],
                ['icon' => 'fa-hamburger', 'label' => 'バーガー', 'action' => '#products'],
                ['icon' => 'fa-coffee', 'label' => 'ドリンク', 'action' => '#products'],
                ['icon' => 'fa-ice-cream', 'label' => 'サイド', 'action' => '#products'],
            ]],
            ['type' => 'data', 'source' => 'products', 'design' => 'cards'],
            ['type' => 'button', 'design' => 'sticky', 'text' => 'カートに進む', 'action' => '#order/cart', 'color' => '#A81612', 'full_width' => true],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private static function screenLiveOrder(): array
    {
        return [
            ['type' => 'heading', 'design' => 'section', 'text' => '注文ステータス', 'size' => 'large'],
            ['type' => 'banner', 'design' => 'pill', 'icon' => 'fa-clock', 'text' => '調理中 — あと約8分', 'color' => '#16a34a', 'text_color' => '#ffffff'],
            ['type' => 'stats_row', 'items' => [
                ['label' => '注文番号', 'value' => '#248', 'hint' => ''],
                ['label' => '予定', 'value' => '12:35', 'hint' => ''],
                ['label' => 'ステータス', 'value' => '調理中', 'hint' => ''],
            ]],
            ['type' => 'icon_text', 'rows' => [
                ['icon' => 'fa-check-circle', 'text' => '注文受付 12:20', 'color' => '#16a34a'],
                ['icon' => 'fa-fire', 'text' => '調理中 12:22〜', 'color' => '#16a34a'],
                ['icon' => 'fa-bell', 'text' => '準備完了で LINE 通知', 'color' => '#6b7280'],
            ]],
            ['type' => 'data', 'source' => 'orders', 'design' => 'timeline'],
            ['type' => 'button', 'design' => 'cta', 'text' => '追加注文する', 'action' => '#products', 'color' => '#16a34a'],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private static function screenNextVisit(): array
    {
        return [
            ['type' => 'hero', 'design' => 'split', 'title' => '次回のご予約', 'subtitle' => '空いている日時から最短で予約', 'image_url' => 'https://images.unsplash.com/photo-1560066984-138dadb4c035?w=1400&q=80'],
            ['type' => 'stats_row', 'items' => [
                ['label' => '前回来店', 'value' => '6/15', 'hint' => ''],
                ['label' => '推奨', 'value' => '4週後', 'hint' => ''],
                ['label' => '空き', 'value' => '5', 'hint' => '枠'],
            ]],
            ['type' => 'banner', 'design' => 'promo', 'icon' => 'fa-calendar-check', 'text' => '前回と同じメニューで再予約できます', 'color' => '#af52de', 'action' => '#reservations'],
            ['type' => 'list', 'style' => 'number', 'items' => ['メニューを確認', '希望日時を選択', '予約を確定']],
            ['type' => 'data', 'source' => 'reservations', 'design' => 'cards'],
            ['type' => 'button', 'design' => 'sticky', 'text' => '空き枠を見る', 'action' => '#reservations', 'color' => '#af52de', 'full_width' => true],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private static function screenQueue(): array
    {
        return [
            ['type' => 'heading', 'design' => 'section', 'text' => '順番待ち受付', 'size' => 'large'],
            ['type' => 'stats_row', 'section_label' => '現在の状況', 'items' => [
                ['label' => '待ち組数', 'value' => '4', 'hint' => '組'],
                ['label' => '目安時間', 'value' => '15', 'hint' => '分'],
            ]],
            ['type' => 'icon_text', 'rows' => [
                ['icon' => 'fa-user-friends', 'text' => '人数を入力して受付', 'color' => '#0284c7'],
                ['icon' => 'fa-mobile-alt', 'text' => '順番が近づいたら LINE で通知', 'color' => '#0284c7'],
                ['icon' => 'fa-map-marker-alt', 'text' => '呼び出しまで外出OK', 'color' => '#0284c7'],
            ]],
            ['type' => 'data', 'source' => 'queue', 'design' => 'cards'],
            ['type' => 'button', 'design' => 'cta', 'text' => '受付を開始する', 'action' => '#queue', 'color' => '#0284c7', 'full_width' => true],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private static function screenLookbook(): array
    {
        return [
            ['type' => 'slider', 'source' => 'gallery', 'design' => 'hero', 'section_label' => 'スタイルギャラリー', 'slide_height' => 300],
            ['type' => 'data', 'source' => 'gallery', 'design' => 'cards'],
            ['type' => 'button', 'design' => 'sticky', 'text' => 'このスタイルで予約', 'action' => '#reservations', 'color' => '#ff2d55', 'full_width' => true],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private static function screenDigest(): array
    {
        return [
            ['type' => 'heading', 'design' => 'section', 'text' => 'お知らせ', 'size' => 'large'],
            ['type' => 'banner', 'design' => 'pill', 'icon' => 'fa-bullhorn', 'text' => '最新のお知らせ', 'color' => '#007aff', 'text_color' => '#ffffff'],
            ['type' => 'data', 'source' => 'announcements', 'design' => 'cards'],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private static function screenStore(): array
    {
        return [
            ['type' => 'heading', 'design' => 'section', 'text' => '店舗情報', 'size' => 'large'],
            ['type' => 'hours', 'design' => 'card', 'title' => '営業時間', 'rows' => [
                ['day' => '月〜金', 'time' => '10:00 - 20:00'],
                ['day' => '土日祝', 'time' => '10:00 - 18:00'],
                ['day' => '定休日', 'time' => '水曜'],
            ]],
            ['type' => 'access', 'design' => 'card', 'title' => 'アクセス', 'address' => '店舗住所（編集してください）', 'note' => '最寄駅からの案内を編集'],
            ['type' => 'social_links', 'design' => 'icon_row', 'links' => [
                ['type' => 'instagram', 'url' => 'https://instagram.com/'],
                ['type' => 'x', 'url' => 'https://x.com/'],
                ['type' => 'line', 'url' => 'https://line.me/'],
            ]],
            ['type' => 'faq', 'design' => 'accordion', 'title' => 'よくある質問', 'items' => [
                ['q' => '駐車場はありますか？', 'a' => '専用駐車場を5台分ご用意しています。'],
                ['q' => '予約なしでも利用できますか？', 'a' => 'はい、当日のご来店も歓迎です。'],
                ['q' => '支払い方法は？', 'a' => '現金・クレジットカード・電子マネーに対応しています。'],
            ]],
            ['type' => 'friend_add', 'design' => 'solid', 'text' => 'LINE公式アカウントを友だち追加'],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private static function screenVip(): array
    {
        return [
            ['type' => 'hero', 'design' => 'editorial', 'eyebrow' => 'VIPラウンジ', 'title' => 'プラチナ会員限定', 'subtitle' => '特別なオファーと優待をお楽しみください', 'image_url' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=1400&q=80'],
            ['type' => 'member_summary', 'design' => 'wallet'],
            ['type' => 'data', 'source' => 'coupons', 'design' => 'cards'],
            ['type' => 'banner', 'design' => 'promo', 'icon' => 'fa-gem', 'text' => '年間お買い上げ ¥100,000 でプラチナ維持', 'color' => '#92400e'],
            ['type' => 'icon_text', 'rows' => [
                ['icon' => 'fa-crown', 'text' => 'ポイント2倍還元', 'color' => '#92400e'],
                ['icon' => 'fa-gift', 'text' => '毎月の限定特典', 'color' => '#92400e'],
                ['icon' => 'fa-concierge-bell', 'text' => '優先予約', 'color' => '#92400e'],
            ]],
            ['type' => 'button', 'design' => 'cta', 'text' => '限定特典を見る', 'action' => '#coupons', 'color' => '#92400e', 'full_width' => true],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private static function screenMorning(): array
    {
        return [
            ['type' => 'member_summary', 'design' => 'rewards', 'greeting' => 'おはようございます'],
            ['type' => 'hero', 'design' => 'apple', 'eyebrow' => '新着', 'title' => 'モーニングメニュー', 'subtitle' => '7:00〜10:30限定のお得なセット', 'image_url' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=1400&q=80'],
            ['type' => 'quick_actions', 'design' => 'pill', 'actions' => [
                ['icon' => 'fa-mug-hot', 'label' => 'オーダー', 'action' => '#products'],
                ['icon' => 'fa-barcode', 'label' => '会員証', 'action' => '#member'],
            ]],
            ['type' => 'button', 'design' => 'sticky', 'text' => 'モーニングを注文', 'action' => '#products', 'color' => '#00754A', 'full_width' => true],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private static function screenWorkout(): array
    {
        return [
            ['type' => 'member_summary', 'design' => 'tier', 'greeting' => '今日もお疲れさまです'],
            ['type' => 'stats_row', 'section_label' => '今月の記録', 'items' => [
                ['label' => '来館', 'value' => '12', 'hint' => '回'],
                ['label' => '連続', 'value' => '5', 'hint' => '日'],
                ['label' => 'ポイント', 'value' => '1,200', 'hint' => 'pt', 'field' => 'points'],
            ]],
            ['type' => 'banner', 'design' => 'promo', 'icon' => 'fa-medal', 'text' => '今月の来館15回でボーナスポイント', 'color' => '#16a34a', 'action' => '#stamps'],
            ['type' => 'quick_actions', 'design' => 'pill', 'actions' => [
                ['icon' => 'fa-dumbbell', 'label' => 'チェックイン', 'action' => '#stamps'],
                ['icon' => 'fa-calendar-check', 'label' => 'レッスン予約', 'action' => '#reservations'],
                ['icon' => 'fa-id-card', 'label' => '会員証', 'action' => '#member'],
            ]],
            ['type' => 'data', 'source' => 'reservations', 'design' => 'cards'],
            Blocks::stampData('grid_card', 'circle', 'solid', '#16a34a'),
            ['type' => 'button', 'design' => 'sticky', 'text' => 'チェックイン', 'action' => '#stamps', 'color' => '#16a34a', 'full_width' => true],
        ];
    }

    /** コージーコーナー風ホーム */
    /** @return array<int, array<string, mixed>> */
    private static function screenSweetsHome(): array
    {
        $a = '#5C3A21';

        return [
            ['type' => 'member_summary', 'design' => 'minimal', 'greeting' => 'こんにちは'],
            ['type' => 'slider', 'source' => 'gallery', 'design' => 'hero', 'section_label' => 'おすすめスイーツ', 'slide_height' => 220],
            ['type' => 'quick_actions', 'design' => 'grid', 'actions' => [
                ['icon' => 'fa-barcode', 'label' => '会員証', 'action' => '#member'],
                ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
            ]],
            ['type' => 'button', 'design' => 'sticky', 'text' => '会員証を提示', 'action' => '#member', 'color' => $a, 'full_width' => true],
        ];
    }

    /** シャトレーゼ風バーコード提示 */
    /** @return array<int, array<string, mixed>> */
    private static function screenBarcodeDock(): array
    {
        $a = '#5C3A21';

        return [
            ['type' => 'heading', 'design' => 'section', 'text' => 'ポイントが貯まる・使える', 'size' => 'large'],
            ['type' => 'member_summary', 'design' => 'wallet'],
            ['type' => 'data', 'source' => 'member_card', 'card_design' => 'emblem'],
            ['type' => 'stats_row', 'items' => [
                ['label' => 'ポイント', 'value' => '10,000', 'hint' => 'pt', 'field' => 'points'],
                ['label' => '有効期限', 'value' => '2030.01.01', 'hint' => ''],
            ]],
            ['type' => 'icon_text', 'rows' => [
                ['icon' => 'fa-barcode', 'text' => 'レジでバーコードをご提示ください', 'color' => $a],
            ]],
            ['type' => 'button', 'design' => 'sticky', 'text' => 'バーコードを拡大表示', 'action' => '#member', 'color' => $a, 'full_width' => true],
        ];
    }

    /** GODIVA / Lindt 風クラブ */
    /** @return array<int, array<string, mixed>> */
    private static function screenChocolateClub(): array
    {
        $a = '#C5A059';

        return [
            ['type' => 'hero', 'design' => 'editorial', 'eyebrow' => 'メンバークラブ', 'title' => 'あなたにぴったりな Happiness を', 'subtitle' => 'いつでもどこでも、もっと楽しく', 'image_url' => 'https://images.unsplash.com/photo-1549007994-cb72beaad946?w=1400&q=80'],
            ['type' => 'member_summary', 'design' => 'wallet'],
            ['type' => 'stats_row', 'section_label' => 'クラブステータス', 'items' => [
                ['label' => 'ランクP', 'value' => '2,400', 'hint' => '', 'field' => 'points'],
                ['label' => '交換P', 'value' => '180', 'hint' => ''],
                ['label' => 'スタンプ', 'value' => '4', 'hint' => '/8'],
            ]],
            ['type' => 'banner', 'design' => 'promo', 'icon' => 'fa-gift', 'text' => 'スタンプ達成で限定クーポン', 'color' => $a, 'action' => '#stamps'],
            ['type' => 'data', 'source' => 'coupons', 'design' => 'cards'],
            ['type' => 'button', 'design' => 'cta', 'text' => '会員証を提示', 'action' => '#member', 'color' => '#1c1917', 'full_width' => true],
        ];
    }

    /** Chipotle Pickup 風 */
    /** @return array<int, array<string, mixed>> */
    private static function screenPickupReady(): array
    {
        $a = '#A81612';

        return [
            ['type' => 'heading', 'design' => 'section', 'text' => '受取', 'size' => 'large'],
            ['type' => 'banner', 'design' => 'pill', 'icon' => 'fa-bag-shopping', 'text' => '受取準備完了 — 12:00 / 棚の2袋', 'color' => $a, 'text_color' => '#ffffff'],
            ['type' => 'stats_row', 'items' => [
                ['label' => '受取時刻', 'value' => '12:00', 'hint' => ''],
                ['label' => '袋数', 'value' => '2', 'hint' => '袋'],
                ['label' => '店舗', 'value' => '本店', 'hint' => ''],
            ]],
            ['type' => 'access', 'design' => 'card', 'title' => '受取店舗', 'address' => '店舗住所（編集してください）', 'note' => 'ピックアップ棚でお受け取りください'],
            ['type' => 'icon_text', 'rows' => [
                ['icon' => 'fa-map-marker-alt', 'text' => '店舗マップで場所を確認', 'color' => $a],
                ['icon' => 'fa-comment', 'text' => 'わからないときはスタッフへ', 'color' => $a],
            ]],
            ['type' => 'data', 'source' => 'orders', 'design' => 'timeline'],
            ['type' => 'button', 'design' => 'sticky', 'text' => '注文詳細を見る', 'action' => '#orders', 'color' => $a, 'full_width' => true],
        ];
    }

    /** UNIQLO 風カテゴリ */
    /** @return array<int, array<string, mixed>> */
    private static function screenCategoryBrowse(): array
    {
        $a = '#111111';

        return [
            ['type' => 'banner', 'design' => 'promo', 'icon' => 'fa-search', 'text' => '何をお探しですか？', 'color' => $a, 'text_color' => '#ffffff', 'action' => '#products'],
            ['type' => 'heading', 'design' => 'section', 'text' => 'カテゴリから探す', 'size' => 'large'],
            ['type' => 'quick_actions', 'design' => 'grid', 'actions' => [
                ['icon' => 'fa-female', 'label' => 'ウィメンズ', 'action' => '#products'],
                ['icon' => 'fa-male', 'label' => 'メンズ', 'action' => '#products'],
                ['icon' => 'fa-child', 'label' => 'キッズ', 'action' => '#products'],
                ['icon' => 'fa-barcode', 'label' => '会員証', 'action' => '#member'],
            ]],
            ['type' => 'data', 'source' => 'products', 'design' => 'cards'],
            ['type' => 'button', 'design' => 'sticky', 'text' => '商品一覧へ', 'action' => '#products', 'color' => $a, 'full_width' => true],
        ];
    }
}

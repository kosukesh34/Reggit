<?php

namespace App\Support;

/**
 * Reggit LIFF デザイナー用 SaaS テンプレートキット。
 *
 * Reggit は LINE を基盤にした「店舗の会員アプリ + モバイルオーダー / 予約」の SaaS。
 * ここで返すテンプレートは、Starbucks / サロン予約 / リテールロイヤリティのような
 * 本番ネイティブアプリの質感（Apple iOS トーン・#f2f2f7・フローティングタブ・
 * apple_system プリセット）を、日本語のみのコピーで再現する。
 *
 * 店名は「〇〇店」、業種別は「〇〇カフェ」等のプレースホルダで表現し、
 * デモデータは各店舗がそのまま差し替えられる SaaS 前提の内容にしている。
 *
 * このクラスは他の Reggit クラスに依存せず、プレーンな配列のみを返す。
 * ブロックスキーマは既存デザイナー互換:
 *   hero / heading / text / banner / collection / quick_actions / member_summary /
 *   stats_row / data / button / list / icon_text / faq / access / slider / card / hours
 * データソース: products / coupons / stamps / orders / member_card / member /
 *   announcements / gallery / reservations / queue
 */
class LiffSaaSTemplateKit
{
    /** メインタブ（全テンプレート共通の 10 タブ） */
    private const MAIN_TABS = [
        'home', 'products', 'orders', 'stamps', 'coupons',
        'queue', 'reservations', 'member', 'gallery', 'announcements',
    ];

    /**
     * apple_system をベースにした標準テーマ。$overrides で上書きできる。
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function appleTheme(array $overrides = []): array
    {
        return array_merge([
            'theme_preset' => 'apple_system',
            'accent_color' => '#007aff',
            'font_family' => 'sans',
            'surface_tone' => 'ios',
            'card_style' => 'elevated',
            'button_shape' => 'pill',
            'tab_style' => 'floating',
            'density' => 'default',
            'background_style' => 'solid',
            'bg_color' => '#f2f2f7',
            'content_width' => 'comfy',
            'system_ui_style' => 'refined',
            'system_header_style' => 'minimal',
            'member_screen_design' => 'apple',
            'coupon_screen_design' => 'apple',
            'reservation_screen_design' => 'apple',
            'activity_screen_design' => 'cards',
            'app_display_name' => '〇〇店',
        ], $overrides);
    }

    /**
     * 全メインタブの背景を「未設定（テーマ継承）」にした tab_settings。
     *
     * @return array<string, array<string, string>>
     */
    public static function emptyTabBackgrounds(): array
    {
        $settings = [];
        foreach (self::MAIN_TABS as $tab) {
            $settings[$tab] = ['background_color' => '', 'background_image' => ''];
        }

        return $settings;
    }

    /**
     * 業種別のフル 10 タブレイアウトを返す。
     *
     * 対応業種: classic / cafe / fast / salon / retail / fitness / hotel / clinic / luxury / delivery
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function layouts(string $industry): array
    {
        switch ($industry) {
            case 'cafe':
                return self::cafeLayout();
            case 'fast':
                return self::fastLayout();
            case 'salon':
                return self::salonLayout();
            case 'retail':
                return self::retailLayout();
            case 'fitness':
                return self::fitnessLayout();
            case 'hotel':
                return self::hotelLayout();
            case 'clinic':
                return self::clinicLayout();
            case 'luxury':
                return self::luxuryLayout();
            case 'delivery':
                return self::deliveryLayout();
            default:
                return self::classicLayout();
        }
    }

    /**
     * Apple パックテンプレート（既存 apple-* を SaaS 品質でリライト、ID は維持）。
     *
     * @return array<int, array<string, mixed>>
     */
    public static function applePackTemplates(): array
    {
        return [
            [
                'id' => 'apple-home-dashboard',
                'name' => 'ホーム（定番アプリ）',
                'category' => 'apple',
                'description' => 'iOS 風のホーム。会員ステータス・カテゴリ・キャンペーン・おすすめをネイティブ品質でまとめた定番構成。',
                'target_tabs' => ['home'],
                'device_support' => ['line', 'ios', 'android'],
                'theme' => self::appleTheme(),
                'blocks' => [
                    ['type' => 'member_summary', 'design' => 'wallet', 'greeting' => 'おかえりなさい'],
                    ['type' => 'stats_row', 'section_label' => 'マイステータス', 'items' => [
                        ['label' => 'ポイント', 'value' => '1,240', 'hint' => 'pt', 'field' => 'points'],
                        ['label' => 'ランク', 'value' => 'ゴールド', 'hint' => '', 'field' => 'rank'],
                        ['label' => 'クーポン', 'value' => '3', 'hint' => '枚'],
                    ]],
                    ['type' => 'collection', 'design' => 'chip_scroll', 'section_label' => 'カテゴリ', 'items' => [
                        ['title' => 'すべて', 'action' => '#products'],
                        ['title' => '人気', 'action' => '#products'],
                        ['title' => '新着', 'action' => '#products'],
                        ['title' => 'セット', 'action' => '#products'],
                    ]],
                    ['type' => 'banner', 'design' => 'countdown', 'icon' => 'fa-bolt', 'text' => '本日限定 — 会員ポイント2倍キャンペーン', 'color' => '#007aff', 'text_color' => '#ffffff', 'action' => '#coupons'],
                    ['type' => 'collection', 'design' => 'picks', 'section_label' => 'あなたへのおすすめ', 'layout' => 'scroll', 'item_image_height' => 120, 'items' => [
                        ['title' => '季節のプレート', 'description' => '¥1,280', 'badge' => 'おすすめ', 'image_url' => 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=800'],
                        ['title' => 'スペシャルドリンク', 'description' => '¥680', 'badge' => '人気', 'image_url' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=800'],
                        ['title' => '本日のスイーツ', 'description' => '¥520', 'badge' => '新着', 'image_url' => 'https://images.unsplash.com/photo-1488477181946-6428a0291777?w=800'],
                    ]],
                    ['type' => 'quick_actions', 'design' => 'pill', 'actions' => [
                        ['icon' => 'fa-bag-shopping', 'label' => '注文', 'action' => '#products'],
                        ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
                        ['icon' => 'fa-id-card', 'label' => '会員証', 'action' => '#member'],
                        ['icon' => 'fa-calendar-check', 'label' => '予約', 'action' => '#reservations'],
                    ]],
                    ['type' => 'button', 'design' => 'cta', 'text' => 'メニューを見る', 'action' => '#products', 'color' => '#007aff', 'full_width' => true],
                ],
            ],
            [
                'id' => 'apple-cafe-loyalty',
                'name' => 'カフェ会員アプリ',
                'category' => 'apple',
                'description' => '〇〇カフェ向け。ウォレット会員・チップメニュー・シグネチャー訴求で毎日開きたくなるロイヤリティホーム。',
                'target_tabs' => ['home', 'products', 'coupons', 'member'],
                'device_support' => ['line', 'ios', 'android'],
                'theme' => self::appleTheme(['accent_color' => '#8a5a2b']),
                'blocks' => [
                    ['type' => 'hero', 'design' => 'apple', 'eyebrow' => 'ようこそ', 'title' => '今日の一杯を、すぐオーダー', 'subtitle' => '〇〇カフェのモバイルオーダーとスタンプ特典', 'image_url' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=1400&q=80'],
                    ['type' => 'member_summary', 'design' => 'tier', 'greeting' => 'おはようございます'],
                    ['type' => 'collection', 'design' => 'chip_scroll', 'section_label' => 'カテゴリ', 'items' => [
                        ['title' => 'コーヒー', 'action' => '#products'],
                        ['title' => 'ラテ', 'action' => '#products'],
                        ['title' => 'スイーツ', 'action' => '#products'],
                        ['title' => 'フード', 'action' => '#products'],
                    ]],
                    ['type' => 'card', 'design' => 'featured', 'title' => '本日のシグネチャー', 'description' => '季節のラテ ¥620', 'image_url' => 'https://images.unsplash.com/photo-1511920170033-f8396924c348?w=800', 'action' => '#products'],
                    ['type' => 'banner', 'design' => 'soft', 'icon' => 'fa-mug-hot', 'text' => 'スタンプあと3個でドリンク1杯無料', 'color' => '#8a5a2b', 'action' => '#stamps'],
                    ['type' => 'button', 'design' => 'cta', 'text' => 'モバイルオーダーを始める', 'action' => '#products', 'color' => '#8a5a2b', 'full_width' => true],
                ],
            ],
            [
                'id' => 'apple-beauty-salon',
                'name' => 'サロン予約アプリ',
                'category' => 'apple',
                'description' => '〇〇サロン向け。次回予約・スタイルギャラリー・会員特典を前面にした美容室・ネイル向けホーム。',
                'target_tabs' => ['home', 'reservations', 'gallery', 'member'],
                'device_support' => ['line', 'ios', 'android'],
                'theme' => self::appleTheme(['accent_color' => '#db2777']),
                'blocks' => [
                    ['type' => 'hero', 'design' => 'apple', 'eyebrow' => 'Beauty', 'title' => '次回のご予約をかんたんに', 'subtitle' => '空き枠から最短3ステップで予約完了', 'image_url' => 'https://images.unsplash.com/photo-1560066984-138dadb4c035?w=1400&q=80'],
                    ['type' => 'stats_row', 'section_label' => 'マイステータス', 'items' => [
                        ['label' => '次回予約', 'value' => '3/28', 'hint' => ''],
                        ['label' => 'ポイント', 'value' => '820', 'hint' => 'pt', 'field' => 'points'],
                        ['label' => '特典', 'value' => '2', 'hint' => '枚'],
                    ]],
                    ['type' => 'quick_actions', 'design' => 'grid', 'actions' => [
                        ['icon' => 'fa-calendar-plus', 'label' => '予約する', 'action' => '#reservations'],
                        ['icon' => 'fa-images', 'label' => 'スタイル', 'action' => '#gallery'],
                        ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
                        ['icon' => 'fa-id-card', 'label' => '会員証', 'action' => '#member'],
                    ]],
                    ['type' => 'collection', 'design' => 'picks', 'section_label' => 'おすすめメニュー', 'layout' => 'scroll', 'item_image_height' => 130, 'items' => [
                        ['title' => 'カット＋カラー', 'description' => '¥12,000〜', 'image_url' => 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?w=800'],
                        ['title' => 'ヘッドスパ', 'description' => '¥4,500', 'image_url' => 'https://images.unsplash.com/photo-1516975080664-ed2fc6a32937?w=800'],
                    ]],
                    ['type' => 'button', 'design' => 'cta', 'text' => '空き枠を確認して予約', 'action' => '#reservations', 'color' => '#db2777', 'full_width' => true],
                ],
            ],
            [
                'id' => 'apple-clinic-trust',
                'name' => 'クリニック受付アプリ',
                'category' => 'apple',
                'description' => '〇〇クリニック向け。信頼感のある情報設計で、ネット予約・順番待ち・診療案内をまとめた受付ホーム。',
                'target_tabs' => ['home', 'reservations', 'queue', 'announcements'],
                'device_support' => ['line', 'ios', 'android'],
                'theme' => self::appleTheme(['accent_color' => '#0284c7']),
                'blocks' => [
                    ['type' => 'heading', 'design' => 'section', 'text' => 'ご予約・受付', 'size' => 'large'],
                    ['type' => 'text', 'design' => 'lead', 'text' => '当日の受付状況とご予約をアプリから確認できます。'],
                    ['type' => 'stats_row', 'items' => [
                        ['label' => '現在の待ち', 'value' => '12', 'hint' => '分'],
                        ['label' => '次回予約', 'value' => '4/2', 'hint' => ''],
                        ['label' => 'お知らせ', 'value' => '2', 'hint' => '件'],
                    ]],
                    ['type' => 'quick_actions', 'design' => 'compact', 'actions' => [
                        ['icon' => 'fa-calendar-check', 'label' => '予約', 'action' => '#reservations'],
                        ['icon' => 'fa-users', 'label' => '順番待ち', 'action' => '#queue'],
                        ['icon' => 'fa-file-medical', 'label' => '診療案内', 'action' => '#announcements'],
                        ['icon' => 'fa-id-card', 'label' => '診察券', 'action' => '#member'],
                    ]],
                    ['type' => 'hours', 'design' => 'card', 'title' => '診療時間', 'rows' => [
                        ['day' => '月〜金', 'time' => '9:00 - 18:00'],
                        ['day' => '土', 'time' => '9:00 - 13:00'],
                        ['day' => '日・祝', 'time' => '休診'],
                    ]],
                    ['type' => 'faq', 'design' => 'accordion', 'title' => 'よくある質問', 'items' => [
                        ['q' => '初診の流れは？', 'a' => 'アプリで予約後、受付で会員証をご提示ください。'],
                        ['q' => 'キャンセルはできますか？', 'a' => '予約詳細から変更・キャンセルが可能です。'],
                    ]],
                    ['type' => 'button', 'design' => 'cta', 'text' => '診察を予約する', 'action' => '#reservations', 'color' => '#0284c7', 'full_width' => true],
                ],
            ],
            [
                'id' => 'apple-retail-flash',
                'name' => 'リテール・セールアプリ',
                'category' => 'apple',
                'description' => '〇〇ショップ向け。セール訴求と商品棚をネイティブ風に。会員限定クーポンで来店・購入を後押し。',
                'target_tabs' => ['home', 'products', 'coupons', 'orders'],
                'device_support' => ['line', 'ios', 'android'],
                'theme' => self::appleTheme(['accent_color' => '#111111']),
                'blocks' => [
                    ['type' => 'banner', 'design' => 'countdown', 'icon' => 'fa-fire', 'text' => '週末限定 — 最大40%OFF', 'color' => '#111111', 'text_color' => '#ffffff', 'action' => '#products'],
                    ['type' => 'member_summary', 'design' => 'wallet'],
                    ['type' => 'collection', 'design' => 'chip_scroll', 'section_label' => 'カテゴリ', 'items' => [
                        ['title' => '新着', 'action' => '#products'],
                        ['title' => 'セール', 'action' => '#coupons'],
                        ['title' => '定番', 'action' => '#products'],
                        ['title' => 'ランキング', 'action' => '#products'],
                    ]],
                    ['type' => 'collection', 'design' => 'product_shelf', 'section_label' => '注目のアイテム', 'layout' => 'scroll', 'item_image_height' => 140, 'items' => [
                        ['title' => 'ライトコート', 'description' => '¥18,900', 'badge' => '新着', 'image_url' => 'https://images.unsplash.com/photo-1483985988355-763728e1935b?w=800'],
                        ['title' => 'レザーバッグ', 'description' => '¥24,800', 'badge' => '人気', 'image_url' => 'https://images.unsplash.com/photo-1548036328-387074abb645?w=800'],
                    ]],
                    ['type' => 'card', 'design' => 'featured', 'title' => '今季のおすすめ', 'description' => 'スタッフが選んだ定番アイテム', 'image_url' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=800', 'action' => '#products'],
                    ['type' => 'button', 'design' => 'cta', 'text' => '商品一覧へ', 'action' => '#products', 'color' => '#111111', 'full_width' => true],
                ],
            ],
            [
                'id' => 'apple-fitness-studio',
                'name' => 'フィットネス会員アプリ',
                'category' => 'apple',
                'description' => '〇〇スタジオ向け。レッスン予約・会員ランク・実績サマリーでモチベーションを高めるホーム。',
                'target_tabs' => ['home', 'reservations', 'member', 'stamps'],
                'device_support' => ['line', 'ios', 'android'],
                'theme' => self::appleTheme(['accent_color' => '#16a34a']),
                'blocks' => [
                    ['type' => 'member_summary', 'design' => 'tier', 'greeting' => '今日もお疲れさまです'],
                    ['type' => 'stats_row', 'section_label' => '今週の記録', 'items' => [
                        ['label' => 'ワークアウト', 'value' => '4', 'hint' => '回'],
                        ['label' => '連続来館', 'value' => '12', 'hint' => '日'],
                        ['label' => 'ポイント', 'value' => '560', 'hint' => 'pt', 'field' => 'points'],
                    ]],
                    ['type' => 'quick_actions', 'design' => 'pill', 'actions' => [
                        ['icon' => 'fa-dumbbell', 'label' => 'レッスン予約', 'action' => '#reservations'],
                        ['icon' => 'fa-stamp', 'label' => 'チェックイン', 'action' => '#stamps'],
                        ['icon' => 'fa-id-card', 'label' => '会員証', 'action' => '#member'],
                    ]],
                    ['type' => 'collection', 'design' => 'picks', 'section_label' => '人気のレッスン', 'layout' => 'scroll', 'item_image_height' => 130, 'items' => [
                        ['title' => 'HIIT 30分', 'description' => '空きあり', 'image_url' => 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=800'],
                        ['title' => 'ヨガフロー', 'description' => '残席2', 'image_url' => 'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?w=800'],
                    ]],
                    ['type' => 'banner', 'design' => 'soft', 'icon' => 'fa-medal', 'text' => '今月の来館8回 — あと2回で特典クーポン獲得', 'color' => '#16a34a', 'action' => '#stamps'],
                    ['type' => 'button', 'design' => 'cta', 'text' => 'レッスンを予約', 'action' => '#reservations', 'color' => '#16a34a', 'full_width' => true],
                ],
            ],
            [
                'id' => 'apple-hotel-concierge',
                'name' => 'ホテルコンシェルジュ',
                'category' => 'apple',
                'description' => '〇〇ホテル向け。チェックイン・レストラン予約・館内案内を上品にまとめたコンシェルジュホーム。',
                'target_tabs' => ['home', 'reservations', 'announcements', 'member'],
                'device_support' => ['line', 'ios', 'android'],
                'theme' => self::appleTheme(['accent_color' => '#b8860b', 'font_family' => 'serif-display']),
                'blocks' => [
                    ['type' => 'hero', 'design' => 'apple', 'eyebrow' => 'Concierge', 'title' => 'ご滞在をより快適に', 'subtitle' => 'レストラン・スパ・ルームサービスをアプリから', 'image_url' => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1400&q=80'],
                    ['type' => 'member_summary', 'design' => 'emblem', 'greeting' => 'ご宿泊ありがとうございます'],
                    ['type' => 'quick_actions', 'design' => 'grid', 'actions' => [
                        ['icon' => 'fa-utensils', 'label' => 'レストラン', 'action' => '#reservations'],
                        ['icon' => 'fa-concierge-bell', 'label' => 'ルームサービス', 'action' => '#products'],
                        ['icon' => 'fa-spa', 'label' => 'スパ予約', 'action' => '#reservations'],
                        ['icon' => 'fa-id-card', 'label' => '会員', 'action' => '#member'],
                    ]],
                    ['type' => 'card', 'design' => 'featured', 'title' => 'スパのご予約', 'description' => '空室状況を確認する', 'image_url' => 'https://images.unsplash.com/photo-1540555700478-4be289fbecef?w=800', 'action' => '#reservations'],
                    ['type' => 'data', 'source' => 'announcements', 'design' => 'compact'],
                    ['type' => 'button', 'design' => 'outline', 'text' => 'ご予約・お問い合わせ', 'action' => '#reservations', 'color' => '#b8860b', 'full_width' => true],
                ],
            ],
            [
                'id' => 'apple-food-delivery',
                'name' => 'デリバリー注文アプリ',
                'category' => 'apple',
                'description' => '〇〇店のテイクアウト・デリバリー向け。最短注文と配達進捗、クーポンを前面にした注文ホーム。',
                'target_tabs' => ['home', 'products', 'orders', 'coupons'],
                'device_support' => ['line', 'ios', 'android'],
                'theme' => self::appleTheme(['accent_color' => '#e11d48']),
                'blocks' => [
                    ['type' => 'member_summary', 'design' => 'compact'],
                    ['type' => 'banner', 'design' => 'countdown', 'icon' => 'fa-motorcycle', 'text' => '今なら配達料無料キャンペーン', 'color' => '#e11d48', 'text_color' => '#ffffff', 'action' => '#products'],
                    ['type' => 'collection', 'design' => 'chip_scroll', 'section_label' => 'カテゴリ', 'items' => [
                        ['title' => 'バーガー', 'action' => '#products'],
                        ['title' => '麺類', 'action' => '#products'],
                        ['title' => '丼もの', 'action' => '#products'],
                        ['title' => 'デザート', 'action' => '#products'],
                    ]],
                    ['type' => 'collection', 'design' => 'picks', 'section_label' => '人気メニュー', 'layout' => 'scroll', 'item_image_height' => 120, 'items' => [
                        ['title' => 'クラシックバーガー', 'description' => '¥890', 'badge' => '人気', 'image_url' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=800'],
                        ['title' => 'マルゲリータ', 'description' => '¥1,280', 'image_url' => 'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=800'],
                    ]],
                    ['type' => 'button', 'design' => 'cta', 'text' => '今すぐ注文', 'action' => '#products', 'color' => '#e11d48', 'full_width' => true],
                ],
            ],
            [
                'id' => 'apple-member-club',
                'name' => '会員クラブアプリ',
                'category' => 'apple',
                'description' => '会員証・ランク・クーポンを主役にしたクラブ体験。Apple / Google ウォレット対応の会員ホーム。',
                'target_tabs' => ['home', 'member', 'coupons', 'stamps'],
                'device_support' => ['line', 'ios', 'android'],
                'theme' => self::appleTheme(),
                'blocks' => [
                    ['type' => 'member_summary', 'design' => 'tier', 'greeting' => '〇〇店 会員クラブ'],
                    ['type' => 'stats_row', 'items' => [
                        ['label' => 'ランク', 'value' => 'ゴールド', 'hint' => '', 'field' => 'rank'],
                        ['label' => 'ポイント', 'value' => '3,420', 'hint' => 'pt', 'field' => 'points'],
                        ['label' => '特典', 'value' => '5', 'hint' => '枚'],
                    ]],
                    ['type' => 'banner', 'design' => 'pill', 'icon' => 'fa-gift', 'text' => '誕生月の特別クーポンを受け取る', 'color' => '#007aff', 'text_color' => '#ffffff', 'action' => '#coupons'],
                    ['type' => 'data', 'source' => 'member_card', 'card_design' => 'emblem'],
                    ['type' => 'quick_actions', 'design' => 'pill', 'actions' => [
                        ['icon' => 'fa-qrcode', 'label' => '会員証', 'action' => '#member'],
                        ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
                        ['icon' => 'fa-stamp', 'label' => 'スタンプ', 'action' => '#stamps'],
                        ['icon' => 'fa-history', 'label' => '履歴', 'action' => '#orders'],
                    ]],
                    ['type' => 'icon_text', 'rows' => [
                        ['icon' => 'fa-wallet', 'text' => 'Apple Wallet / Google ウォレットに会員証を追加', 'color' => '#007aff'],
                    ]],
                ],
            ],
            [
                'id' => 'apple-super-app',
                'name' => 'フルアプリ（全画面）',
                'scope' => 'all_tabs',
                'category' => 'apple',
                'description' => 'ホーム・商品・注文・会員・クーポン・予約まで全画面を横断した iOS 風フルアプリ構成。定番レイアウトをそのまま適用。',
                'target_tabs' => self::MAIN_TABS,
                'device_support' => ['line', 'ios', 'android'],
                'theme' => self::appleTheme(),
                'layouts' => self::layouts('classic'),
                'tab_settings' => self::emptyTabBackgrounds(),
                'custom_css' => '',
            ],
        ];
    }

    /**
     * Suite（全画面）テンプレート 10 件。既存 super-pattern-* ID を維持。
     *
     * @return array<int, array<string, mixed>>
     */
    public static function suiteCatalog(): array
    {
        return [
            self::suiteEntry(
                'super-pattern-classic',
                'レストラン総合アプリ',
                '注文・予約・会員・クーポンまで全画面対応。〇〇店向けの定番フルアプリ構成。',
                'classic',
                ['accent_color' => '#7f1d1d', 'app_display_name' => '〇〇店']
            ),
            self::suiteEntry(
                'super-pattern-cafe',
                'カフェ総合アプリ',
                'モバイルオーダーとスタンプ特典を中心にした、〇〇カフェ向けフルアプリ。',
                'cafe',
                ['accent_color' => '#7B4F2E', 'app_display_name' => '〇〇カフェ']
            ),
            self::suiteEntry(
                'super-pattern-fast',
                'ファストフード注文アプリ',
                '最短注文と受取ステータスを前面にした、ファストフード向けフルアプリ。',
                'fast',
                ['accent_color' => '#ea580c', 'app_display_name' => '〇〇店', 'density' => 'compact', 'content_width' => 'edge']
            ),
            self::suiteEntry(
                'super-pattern-salon',
                '美容サロン総合アプリ',
                '予約・ギャラリー・会員特典を統合した、〇〇サロン向けフルアプリ。',
                'salon',
                ['accent_color' => '#db2777', 'app_display_name' => '〇〇サロン']
            ),
            self::suiteEntry(
                'super-pattern-retail',
                '小売ショップ総合アプリ',
                '商品棚・セール・会員ロイヤリティを横断する、〇〇ショップ向けフルアプリ。',
                'retail',
                ['accent_color' => '#111111', 'app_display_name' => '〇〇ショップ']
            ),
            self::suiteEntry(
                'super-pattern-fitness',
                'フィットネス総合アプリ',
                'レッスン予約・来館スタンプ・会員証を統合した、〇〇スタジオ向けフルアプリ。',
                'fitness',
                ['accent_color' => '#16a34a', 'app_display_name' => '〇〇スタジオ']
            ),
            self::suiteEntry(
                'super-pattern-hotel',
                'ホテル総合アプリ',
                'コンシェルジュ・予約・会員ステータスを上品にまとめた、〇〇ホテル向けフルアプリ。',
                'hotel',
                ['accent_color' => '#b8860b', 'font_family' => 'serif-display', 'app_display_name' => '〇〇ホテル']
            ),
            self::suiteEntry(
                'super-pattern-clinic',
                'クリニック総合アプリ',
                'ネット予約・順番待ち・診療案内をまとめた、〇〇クリニック向けフルアプリ。',
                'clinic',
                ['accent_color' => '#0284c7', 'app_display_name' => '〇〇クリニック']
            ),
            self::suiteEntry(
                'super-pattern-luxury',
                'ラグジュアリー店舗アプリ',
                'エディトリアル訴求とVIP会員体験の、高級ブティック向けフルアプリ。',
                'luxury',
                ['accent_color' => '#92400e', 'font_family' => 'serif-display', 'app_display_name' => '〇〇ブティック', 'content_width' => 'narrow', 'density' => 'relaxed']
            ),
            self::suiteEntry(
                'super-pattern-delivery',
                'デリバリー総合アプリ',
                '最短注文と配達追跡を前面にした、テイクアウト・デリバリー向けフルアプリ。',
                'delivery',
                ['accent_color' => '#ea580c', 'app_display_name' => '〇〇店', 'density' => 'compact', 'content_width' => 'edge']
            ),
        ];
    }

    /**
     * ExtraTemplates::superPatterns() 互換の追加 Suite。
     * ユニーク3件＋既存 Suite と同 ID の hotel/clinic/delivery（マージ呼び出し用）。
     *
     * @return array<int, array<string, mixed>>
     */
    public static function extraSuiteCatalog(): array
    {
        return [
            self::suiteEntry(
                'super-pattern-hotel',
                'ホテル総合アプリ',
                'コンシェルジュ・予約・会員ステータスを上品にまとめた、〇〇ホテル向けフルアプリ。',
                'hotel',
                ['accent_color' => '#b8860b', 'font_family' => 'serif-display', 'app_display_name' => '〇〇ホテル']
            ),
            self::suiteEntry(
                'super-pattern-clinic',
                'クリニック総合アプリ',
                'ネット予約・順番待ち・診療案内をまとめた、〇〇クリニック向けフルアプリ。',
                'clinic',
                ['accent_color' => '#0284c7', 'app_display_name' => '〇〇クリニック']
            ),
            self::suiteEntry(
                'super-pattern-luxury-retail',
                'ラグジュアリー小売アプリ',
                '高級ブティック向け。エディトリアルとVIP会員体験の全画面構成。',
                'luxury',
                ['accent_color' => '#92400e', 'font_family' => 'serif-display', 'app_display_name' => '〇〇ブティック', 'content_width' => 'narrow', 'density' => 'relaxed']
            ),
            self::suiteEntry(
                'super-pattern-delivery',
                'デリバリー総合アプリ',
                '最短注文と配達追跡を前面にした、テイクアウト・デリバリー向けフルアプリ。',
                'delivery',
                ['accent_color' => '#ea580c', 'app_display_name' => '〇〇店', 'density' => 'compact', 'content_width' => 'edge']
            ),
            self::suiteEntry(
                'super-pattern-salon-native',
                'サロン・ネイティブ総合アプリ',
                '予約導線・ギャラリー・会員特典を統合した、美容サロン向けネイティブ体験。',
                'salon',
                ['accent_color' => '#db2777', 'app_display_name' => '〇〇サロン']
            ),
            self::suiteEntry(
                'super-pattern-fitness-native',
                'フィットネス・ネイティブ総合アプリ',
                'レッスン予約・会員ステータス・クーポンを統合したジム・スタジオ向けフルアプリ。',
                'fitness',
                ['accent_color' => '#16a34a', 'app_display_name' => '〇〇スタジオ']
            ),
        ];
    }

    /**
     * 単画面 / 複数タブ（非 Suite）テンプレート一覧。
     * ShopSettingLiffDefaults（super-pattern 以前）＋ ExtraTemplates::tabTemplates() の全 ID を維持してリライト。
     *
     * @return array<int, array<string, mixed>>
     */
    public static function singleScreenCatalog(): array
    {
        return self::buildSingleScreenCatalog();
    }

    /**
     * Suite エントリを組み立てる。
     *
     * @param  array<string, mixed>  $themeOverrides
     * @return array<string, mixed>
     */
    private static function suiteEntry(string $id, string $name, string $description, string $industry, array $themeOverrides): array
    {
        return [
            'id' => $id,
            'name' => $name,
            'scope' => 'all_tabs',
            'category' => 'super',
            'description' => $description,
            'target_tabs' => self::MAIN_TABS,
            'device_support' => ['line', 'ios', 'android'],
            'layouts' => self::layouts($industry),
            'tab_settings' => self::emptyTabBackgrounds(),
            'custom_css' => '',
            'theme' => self::appleTheme($themeOverrides),
        ];
    }

    /**
     * 単画面テンプレートエントリを組み立てる。
     *
     * @param  array<int, string>  $targetTabs
     * @param  array<string, mixed>  $themeOverrides
     * @param  array<int, array<string, mixed>>  $blocks
     * @return array<string, mixed>
     */
    private static function screenEntry(string $id, string $name, string $category, string $description, array $targetTabs, array $themeOverrides, array $blocks): array
    {
        return [
            'id' => $id,
            'name' => $name,
            'category' => $category,
            'description' => $description,
            'target_tabs' => $targetTabs,
            'device_support' => ['line', 'ios', 'android'],
            'theme' => self::appleTheme($themeOverrides),
            'blocks' => $blocks,
        ];
    }

    /**
     * 全業種共通のベース 10 タブ。各業種メソッドが home と主要タブを上書きする。
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    private static function baseTabs(string $accent): array
    {
        return [
            'home' => [
                ['type' => 'member_summary', 'design' => 'wallet', 'greeting' => 'おかえりなさい'],
                ['type' => 'stats_row', 'section_label' => 'マイステータス', 'items' => [
                    ['label' => 'ポイント', 'value' => '1,240', 'hint' => 'pt', 'field' => 'points'],
                    ['label' => 'ランク', 'value' => 'ゴールド', 'hint' => '', 'field' => 'rank'],
                    ['label' => 'クーポン', 'value' => '3', 'hint' => '枚'],
                ]],
                ['type' => 'quick_actions', 'design' => 'pill', 'actions' => [
                    ['icon' => 'fa-bag-shopping', 'label' => '注文', 'action' => '#products'],
                    ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
                    ['icon' => 'fa-stamp', 'label' => 'スタンプ', 'action' => '#stamps'],
                    ['icon' => 'fa-id-card', 'label' => '会員証', 'action' => '#member'],
                ]],
                ['type' => 'banner', 'design' => 'countdown', 'icon' => 'fa-bolt', 'text' => '本日限定 — 会員ポイント2倍キャンペーン', 'color' => $accent, 'text_color' => '#ffffff', 'action' => '#coupons'],
                ['type' => 'collection', 'design' => 'chip_scroll', 'section_label' => 'カテゴリ', 'items' => [
                    ['title' => 'すべて', 'action' => '#products'],
                    ['title' => '人気', 'action' => '#products'],
                    ['title' => '新着', 'action' => '#products'],
                    ['title' => 'セット', 'action' => '#products'],
                ]],
                ['type' => 'collection', 'design' => 'picks', 'section_label' => 'あなたへのおすすめ', 'layout' => 'scroll', 'item_image_height' => 120, 'items' => [
                    ['title' => 'シグネチャープレート', 'description' => '¥1,280', 'badge' => 'おすすめ', 'image_url' => 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=800', 'action' => '#products'],
                    ['title' => '季節のスイーツ', 'description' => '¥520', 'badge' => '新着', 'image_url' => 'https://images.unsplash.com/photo-1488477181946-6428a0291777?w=800', 'action' => '#products'],
                    ['title' => 'クラフトドリンク', 'description' => '¥580', 'image_url' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=800', 'action' => '#products'],
                ]],
                ['type' => 'card', 'design' => 'featured', 'title' => '来店予約はこちら', 'description' => '空いている枠からすぐ予約できます', 'image_url' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=800', 'action' => '#reservations'],
                ['type' => 'button', 'design' => 'cta', 'text' => 'メニューを見る', 'action' => '#products', 'color' => $accent, 'full_width' => true],
            ],
            'products' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'メニュー', 'size' => 'large'],
                ['type' => 'text', 'design' => 'lead', 'text' => 'カテゴリから選んで、そのままカートへ。'],
                ['type' => 'collection', 'design' => 'chip_scroll', 'section_label' => 'カテゴリ', 'items' => [
                    ['title' => '人気', 'action' => '#products'],
                    ['title' => '定番', 'action' => '#products'],
                    ['title' => '新着', 'action' => '#products'],
                    ['title' => 'セット', 'action' => '#products'],
                ]],
                ['type' => 'collection', 'design' => 'product_shelf', 'section_label' => '人気の商品', 'layout' => 'grid', 'columns' => 2, 'item_image_height' => 120, 'items' => [
                    ['title' => 'シグネチャープレート', 'description' => '¥1,280', 'image_url' => 'https://images.unsplash.com/photo-1550547660-d9450f859349?w=600', 'action' => '#products'],
                    ['title' => '季節のサラダ', 'description' => '¥880', 'image_url' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=600', 'action' => '#products'],
                    ['title' => '本日のパスタ', 'description' => '¥1,100', 'image_url' => 'https://images.unsplash.com/photo-1473093295043-cdd812d0e601?w=600', 'action' => '#products'],
                    ['title' => 'クラフトラテ', 'description' => '¥580', 'image_url' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=600', 'action' => '#products'],
                ]],
                ['type' => 'data', 'source' => 'products', 'design' => 'cards'],
            ],
            'orders' => [
                ['type' => 'heading', 'design' => 'section', 'text' => '注文履歴', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'pill', 'icon' => 'fa-bell', 'text' => '受取準備が整い次第 LINE でお知らせします', 'color' => $accent, 'text_color' => '#ffffff'],
                ['type' => 'icon_text', 'rows' => [
                    ['icon' => 'fa-receipt', 'text' => '受付 → 準備中 → 受取可能', 'color' => $accent],
                    ['icon' => 'fa-clock', 'text' => '進捗はリアルタイムで更新されます', 'color' => $accent],
                ]],
                ['type' => 'data', 'source' => 'orders', 'design' => 'timeline'],
                ['type' => 'button', 'design' => 'soft', 'text' => 'もう一度注文する', 'action' => '#products', 'color' => $accent],
            ],
            'stamps' => [
                ['type' => 'member_summary', 'design' => 'compact'],
                ['type' => 'heading', 'design' => 'section', 'text' => 'スタンプ', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'soft', 'icon' => 'fa-gift', 'text' => '満了でドリンク無料クーポンをプレゼント', 'color' => $accent],
                ['type' => 'icon_text', 'rows' => [
                    ['icon' => 'fa-stamp', 'text' => 'ご来店・ご注文ごとにスタンプを付与', 'color' => $accent],
                    ['icon' => 'fa-crown', 'text' => 'ランクアップで特典が増えます', 'color' => $accent],
                ]],
                ['type' => 'data', 'source' => 'stamps', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'cta', 'text' => 'クーポンを見る', 'action' => '#coupons', 'color' => $accent],
            ],
            'coupons' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'クーポン', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'pill', 'icon' => 'fa-ticket-alt', 'text' => '会員限定クーポンを配信中', 'color' => $accent, 'text_color' => '#ffffff'],
                ['type' => 'collection', 'design' => 'compact', 'section_label' => '今週のクーポン', 'layout' => 'scroll', 'item_image_height' => 96, 'items' => [
                    ['title' => 'ドリンク1杯無料', 'description' => '〜今月末', 'badge' => '人気', 'image_url' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=600', 'action' => '#coupons'],
                    ['title' => 'お会計10%OFF', 'description' => '平日限定', 'image_url' => 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=600', 'action' => '#coupons'],
                ]],
                ['type' => 'data', 'source' => 'coupons', 'design' => 'cards'],
            ],
            'queue' => [
                ['type' => 'heading', 'design' => 'section', 'text' => '順番待ち', 'size' => 'large'],
                ['type' => 'hero', 'design' => 'minimal', 'title' => '順番待ち受付', 'subtitle' => '人数を入力して受付。呼び出しは LINE でお知らせします。', 'image_url' => 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=1400&q=80'],
                ['type' => 'icon_text', 'rows' => [
                    ['icon' => 'fa-user-friends', 'text' => '人数を入力して受付開始', 'color' => $accent],
                    ['icon' => 'fa-mobile-alt', 'text' => '呼び出し通知を受け取る', 'color' => $accent],
                    ['icon' => 'fa-chart-line', 'text' => '待ち組数をリアルタイム確認', 'color' => $accent],
                ]],
                ['type' => 'data', 'source' => 'queue', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'cta', 'text' => '順番待ちを開始', 'action' => '#queue', 'color' => $accent],
            ],
            'reservations' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'ご予約', 'size' => 'large'],
                ['type' => 'hero', 'design' => 'split', 'title' => '来店予約', 'subtitle' => '希望の日時を選んで、スムーズにご来店ください。', 'image_url' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=1400&q=80'],
                ['type' => 'list', 'style' => 'number', 'items' => ['希望日時を選択', '人数と連絡先を入力', '内容を確認して予約確定']],
                ['type' => 'banner', 'design' => 'soft', 'icon' => 'fa-info-circle', 'text' => '当日の変更はお電話がスムーズです', 'color' => $accent],
                ['type' => 'data', 'source' => 'reservations', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'cta', 'text' => '予約をはじめる', 'action' => '#reservations', 'color' => $accent],
            ],
            'member' => [
                ['type' => 'member_summary', 'design' => 'wallet', 'greeting' => 'こんにちは'],
                ['type' => 'heading', 'design' => 'section', 'text' => '会員証', 'size' => 'large'],
                ['type' => 'quick_actions', 'design' => 'pill', 'actions' => [
                    ['icon' => 'fa-qrcode', 'label' => '提示', 'action' => '#member'],
                    ['icon' => 'fa-stamp', 'label' => 'スタンプ', 'action' => '#stamps'],
                    ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
                    ['icon' => 'fa-history', 'label' => '履歴', 'action' => '#orders'],
                ]],
                ['type' => 'icon_text', 'rows' => [
                    ['icon' => 'fa-coins', 'text' => 'ご来店・お買い上げでポイントが貯まります', 'color' => $accent],
                    ['icon' => 'fa-crown', 'text' => 'ランクアップで特典が広がります', 'color' => $accent],
                    ['icon' => 'fa-wallet', 'text' => 'Apple Wallet / Google ウォレットに会員証を追加できます', 'color' => $accent],
                ]],
                ['type' => 'data', 'source' => 'member_card', 'card_design' => 'emblem'],
            ],
            'gallery' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'ギャラリー', 'size' => 'large'],
                ['type' => 'text', 'design' => 'lead', 'text' => '店内・メニュー・季節の風景を随時更新しています。'],
                ['type' => 'slider', 'design' => 'hero', 'section_label' => '注目', 'slide_height' => 200, 'slides' => [
                    ['image_url' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=1200', 'caption' => '店内の雰囲気'],
                    ['image_url' => 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=1200', 'caption' => '本日のプレート'],
                    ['image_url' => 'https://images.unsplash.com/photo-1559339352-11d035aa65de?w=1200', 'caption' => 'カウンター席'],
                ]],
                ['type' => 'data', 'source' => 'gallery', 'design' => 'cards'],
            ],
            'announcements' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'お知らせ', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'pill', 'icon' => 'fa-bullhorn', 'text' => '営業時間・限定情報をいち早くお届け', 'color' => $accent, 'text_color' => '#ffffff'],
                ['type' => 'collection', 'design' => 'magazine', 'section_label' => 'ハイライト', 'layout' => 'scroll', 'item_image_height' => 110, 'items' => [
                    ['title' => '季節限定メニュー', 'description' => '公開中', 'badge' => '新着', 'image_url' => 'https://images.unsplash.com/photo-1498837167922-ddd27525d352?w=800'],
                    ['title' => '週末セット', 'description' => '土日限定', 'badge' => '人気', 'image_url' => 'https://images.unsplash.com/photo-1466978913421-dad2ebd01d17?w=800'],
                ]],
                ['type' => 'data', 'source' => 'announcements', 'design' => 'cards'],
            ],
        ];
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    private static function classicLayout(): array
    {
        $accent = '#007aff';
        $layouts = self::baseTabs($accent);
        $layouts['home'] = [
            ['type' => 'member_summary', 'design' => 'wallet', 'greeting' => 'おかえりなさい、〇〇さん'],
            ['type' => 'stats_row', 'section_label' => 'マイステータス', 'items' => [
                ['label' => 'ポイント', 'value' => '1,240', 'hint' => 'pt', 'field' => 'points'],
                ['label' => 'ランク', 'value' => 'ゴールド', 'hint' => '', 'field' => 'rank'],
                ['label' => 'スタンプ', 'value' => '7', 'hint' => '/10'],
            ]],
            ['type' => 'quick_actions', 'design' => 'pill', 'actions' => [
                ['icon' => 'fa-bag-shopping', 'label' => '注文', 'action' => '#products'],
                ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
                ['icon' => 'fa-calendar-check', 'label' => '予約', 'action' => '#reservations'],
                ['icon' => 'fa-id-card', 'label' => '会員証', 'action' => '#member'],
            ]],
            ['type' => 'banner', 'design' => 'countdown', 'icon' => 'fa-bolt', 'text' => '本日限定 — 会員ポイント2倍', 'color' => $accent, 'text_color' => '#ffffff', 'action' => '#coupons'],
            ['type' => 'collection', 'design' => 'chip_scroll', 'section_label' => 'カテゴリ', 'items' => [
                ['title' => 'すべて', 'action' => '#products'],
                ['title' => '人気', 'action' => '#products'],
                ['title' => '新着', 'action' => '#products'],
                ['title' => 'セット', 'action' => '#products'],
            ]],
            ['type' => 'collection', 'design' => 'picks', 'section_label' => 'あなたへのおすすめ', 'layout' => 'scroll', 'item_image_height' => 130, 'items' => [
                ['title' => 'シグネチャープレート', 'description' => '¥1,280', 'badge' => 'おすすめ', 'image_url' => 'https://images.unsplash.com/photo-1550547660-d9450f859349?w=800', 'action' => '#products'],
                ['title' => '季節のサラダ', 'description' => '¥880', 'badge' => '新着', 'image_url' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=800', 'action' => '#products'],
                ['title' => '本日のパスタ', 'description' => '¥1,100', 'image_url' => 'https://images.unsplash.com/photo-1473093295043-cdd812d0e601?w=800', 'action' => '#products'],
            ]],
            ['type' => 'card', 'design' => 'featured', 'title' => '来店予約はこちら', 'description' => '空いている枠からすぐ予約できます', 'image_url' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=800', 'action' => '#reservations'],
            ['type' => 'data', 'source' => 'announcements', 'design' => 'compact'],
            ['type' => 'button', 'design' => 'cta', 'text' => 'メニューを見る', 'action' => '#products', 'color' => $accent, 'full_width' => true],
        ];

        return $layouts;
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    private static function cafeLayout(): array
    {
        $accent = '#8a5a2b';
        $layouts = self::baseTabs($accent);
        $layouts['home'] = [
            ['type' => 'hero', 'design' => 'apple', 'eyebrow' => 'ようこそ', 'title' => '今日の一杯を、すぐオーダー', 'subtitle' => '〇〇カフェのモバイルオーダー', 'image_url' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=1400&q=80'],
            ['type' => 'member_summary', 'design' => 'tier', 'greeting' => 'おはようございます'],
            ['type' => 'stats_row', 'section_label' => 'マイステータス', 'items' => [
                ['label' => 'スタンプ', 'value' => '7', 'hint' => '/10'],
                ['label' => 'ポイント', 'value' => '640', 'hint' => 'pt', 'field' => 'points'],
                ['label' => 'クーポン', 'value' => '2', 'hint' => '枚'],
            ]],
            ['type' => 'collection', 'design' => 'chip_scroll', 'section_label' => 'カテゴリ', 'items' => [
                ['title' => 'コーヒー', 'action' => '#products'],
                ['title' => 'ラテ', 'action' => '#products'],
                ['title' => 'スイーツ', 'action' => '#products'],
                ['title' => 'フード', 'action' => '#products'],
            ]],
            ['type' => 'card', 'design' => 'featured', 'title' => '本日のシグネチャー', 'description' => '季節のラテ ¥620', 'image_url' => 'https://images.unsplash.com/photo-1511920170033-f8396924c348?w=800', 'action' => '#products'],
            ['type' => 'collection', 'design' => 'picks', 'section_label' => '人気メニュー', 'layout' => 'scroll', 'item_image_height' => 120, 'items' => [
                ['title' => 'カフェラテ', 'description' => '¥480', 'badge' => '定番', 'image_url' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=800', 'action' => '#products'],
                ['title' => 'チーズケーキ', 'description' => '¥520', 'badge' => '人気', 'image_url' => 'https://images.unsplash.com/photo-1488477181946-6428a0291777?w=800', 'action' => '#products'],
                ['title' => 'コールドブリュー', 'description' => '¥560', 'image_url' => 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=800', 'action' => '#products'],
            ]],
            ['type' => 'banner', 'design' => 'soft', 'icon' => 'fa-mug-hot', 'text' => 'スタンプあと3個でドリンク1杯無料', 'color' => $accent, 'action' => '#stamps'],
            ['type' => 'button', 'design' => 'cta', 'text' => 'モバイルオーダーを始める', 'action' => '#products', 'color' => $accent, 'full_width' => true],
        ];
        $layouts['products'] = [
            ['type' => 'heading', 'design' => 'section', 'text' => 'ドリンク & フード', 'size' => 'large'],
            ['type' => 'collection', 'design' => 'chip_scroll', 'section_label' => 'カテゴリ', 'items' => [
                ['title' => 'ホット', 'action' => '#products'],
                ['title' => 'アイス', 'action' => '#products'],
                ['title' => 'スイーツ', 'action' => '#products'],
                ['title' => 'フード', 'action' => '#products'],
            ]],
            ['type' => 'collection', 'design' => 'product_shelf', 'section_label' => 'コーヒー', 'layout' => 'grid', 'columns' => 2, 'item_image_height' => 120, 'items' => [
                ['title' => 'ドリップコーヒー', 'description' => '¥380', 'image_url' => 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=600', 'action' => '#products'],
                ['title' => 'カフェラテ', 'description' => '¥480', 'image_url' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=600', 'action' => '#products'],
                ['title' => 'カプチーノ', 'description' => '¥480', 'image_url' => 'https://images.unsplash.com/photo-1511920170033-f8396924c348?w=600', 'action' => '#products'],
                ['title' => 'コールドブリュー', 'description' => '¥560', 'image_url' => 'https://images.unsplash.com/photo-1461023058943-07fcbe16d735?w=600', 'action' => '#products'],
            ]],
            ['type' => 'data', 'source' => 'products', 'design' => 'cards'],
        ];
        $layouts['stamps'] = [
            ['type' => 'member_summary', 'design' => 'compact'],
            ['type' => 'heading', 'design' => 'section', 'text' => 'スタンプカード', 'size' => 'large'],
            ['type' => 'banner', 'design' => 'soft', 'icon' => 'fa-mug-hot', 'text' => 'スタンプ10個でお好きなドリンク1杯無料', 'color' => $accent],
            ['type' => 'icon_text', 'rows' => [
                ['icon' => 'fa-stamp', 'text' => 'ご来店・ご注文でスタンプ1個', 'color' => $accent],
                ['icon' => 'fa-gift', 'text' => '満了で無料ドリンクチケットをプレゼント', 'color' => $accent],
            ]],
            ['type' => 'data', 'source' => 'stamps', 'design' => 'cards'],
        ];

        return $layouts;
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    private static function fastLayout(): array
    {
        $accent = '#ea580c';
        $layouts = self::baseTabs($accent);
        $layouts['home'] = [
            ['type' => 'member_summary', 'design' => 'compact'],
            ['type' => 'hero', 'design' => 'split', 'title' => 'かんたんモバイルオーダー', 'subtitle' => '並ばずに注文、受取もスムーズ。', 'image_url' => 'https://images.unsplash.com/photo-1561758033-d066a3efea2f?w=1400&q=80'],
            ['type' => 'quick_actions', 'design' => 'compact', 'actions' => [
                ['icon' => 'fa-hamburger', 'label' => '今すぐ注文', 'action' => '#products'],
                ['icon' => 'fa-receipt', 'label' => '注文状況', 'action' => '#orders'],
                ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
                ['icon' => 'fa-id-card', 'label' => '会員証', 'action' => '#member'],
            ]],
            ['type' => 'banner', 'design' => 'countdown', 'icon' => 'fa-bolt', 'text' => 'ランチタイム限定 — セット100円引き', 'color' => $accent, 'text_color' => '#ffffff', 'action' => '#coupons'],
            ['type' => 'collection', 'design' => 'chip_scroll', 'section_label' => 'カテゴリ', 'items' => [
                ['title' => 'バーガー', 'action' => '#products'],
                ['title' => 'サイド', 'action' => '#products'],
                ['title' => 'ドリンク', 'action' => '#products'],
                ['title' => 'セット', 'action' => '#products'],
            ]],
            ['type' => 'collection', 'design' => 'product_shelf', 'section_label' => '人気メニュー', 'layout' => 'grid', 'columns' => 2, 'item_image_height' => 110, 'items' => [
                ['title' => 'クラシックバーガー', 'description' => '¥490', 'image_url' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=600', 'action' => '#products'],
                ['title' => 'ポテト', 'description' => '¥290', 'image_url' => 'https://images.unsplash.com/photo-1573080496219-bb080dd4f877?w=600', 'action' => '#products'],
                ['title' => 'チキン', 'description' => '¥390', 'image_url' => 'https://images.unsplash.com/photo-1562967914-608f82629710?w=600', 'action' => '#products'],
                ['title' => 'シェイク', 'description' => '¥350', 'image_url' => 'https://images.unsplash.com/photo-1572490122747-3968b75cc699?w=600', 'action' => '#products'],
            ]],
            ['type' => 'button', 'design' => 'cta', 'text' => 'カートに進む', 'action' => '#products', 'color' => $accent, 'full_width' => true],
        ];
        $layouts['orders'] = [
            ['type' => 'member_summary', 'design' => 'compact'],
            ['type' => 'heading', 'design' => 'section', 'text' => '注文状況', 'size' => 'large'],
            ['type' => 'banner', 'design' => 'soft', 'icon' => 'fa-receipt', 'text' => 'ご注文の準備ができ次第、通知します', 'color' => $accent],
            ['type' => 'icon_text', 'rows' => [
                ['icon' => 'fa-check-circle', 'text' => '注文受付', 'color' => $accent],
                ['icon' => 'fa-fire', 'text' => '調理中', 'color' => $accent],
                ['icon' => 'fa-bell', 'text' => '受取準備完了', 'color' => $accent],
            ]],
            ['type' => 'data', 'source' => 'orders', 'design' => 'timeline'],
            ['type' => 'button', 'design' => 'cta', 'text' => '新しい注文', 'action' => '#products', 'color' => $accent],
        ];

        return $layouts;
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    private static function salonLayout(): array
    {
        $accent = '#db2777';
        $layouts = self::baseTabs($accent);
        $layouts['home'] = [
            ['type' => 'hero', 'design' => 'apple', 'eyebrow' => 'Beauty', 'title' => '次回のご予約をかんたんに', 'subtitle' => '〇〇サロンの空き枠と会員特典', 'image_url' => 'https://images.unsplash.com/photo-1560066984-138dadb4c035?w=1400&q=80'],
            ['type' => 'member_summary', 'design' => 'glass', 'greeting' => 'いらっしゃいませ'],
            ['type' => 'stats_row', 'section_label' => 'マイステータス', 'items' => [
                ['label' => '次回予約', 'value' => '3/28', 'hint' => ''],
                ['label' => 'ポイント', 'value' => '820', 'hint' => 'pt', 'field' => 'points'],
                ['label' => '特典', 'value' => '2', 'hint' => '枚'],
            ]],
            ['type' => 'quick_actions', 'design' => 'grid', 'actions' => [
                ['icon' => 'fa-calendar-plus', 'label' => '予約する', 'action' => '#reservations'],
                ['icon' => 'fa-images', 'label' => 'スタイル', 'action' => '#gallery'],
                ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
                ['icon' => 'fa-id-card', 'label' => '会員証', 'action' => '#member'],
            ]],
            ['type' => 'slider', 'design' => 'cards', 'section_label' => '今月のスタイル', 'slide_height' => 200, 'slides' => [
                ['image_url' => 'https://images.unsplash.com/photo-1560066984-138dadb4c035?w=1200', 'caption' => '春の新色カラー'],
                ['image_url' => 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?w=1200', 'caption' => 'ボブスタイル'],
            ]],
            ['type' => 'collection', 'design' => 'picks', 'section_label' => 'おすすめメニュー', 'layout' => 'scroll', 'item_image_height' => 130, 'items' => [
                ['title' => 'カット＋カラー', 'description' => '¥12,000〜', 'image_url' => 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?w=800', 'action' => '#products'],
                ['title' => 'ヘッドスパ', 'description' => '¥4,500', 'image_url' => 'https://images.unsplash.com/photo-1516975080664-ed2fc6a32937?w=800', 'action' => '#products'],
            ]],
            ['type' => 'button', 'design' => 'cta', 'text' => '空き枠を確認して予約', 'action' => '#reservations', 'color' => $accent, 'full_width' => true],
        ];
        $layouts['reservations'] = [
            ['type' => 'heading', 'design' => 'section', 'text' => 'ご予約', 'size' => 'large'],
            ['type' => 'banner', 'design' => 'soft', 'icon' => 'fa-heart', 'text' => '担当スタイリストの空き状況を確認できます', 'color' => $accent],
            ['type' => 'list', 'style' => 'number', 'items' => ['メニューを選択', '日時を選択', '担当者を指名（任意）', '内容を確認して確定']],
            ['type' => 'data', 'source' => 'reservations', 'design' => 'cards'],
            ['type' => 'button', 'design' => 'cta', 'text' => '予約をはじめる', 'action' => '#reservations', 'color' => $accent],
        ];
        $layouts['gallery'] = [
            ['type' => 'heading', 'design' => 'section', 'text' => 'スタイルギャラリー', 'size' => 'large'],
            ['type' => 'text', 'design' => 'lead', 'text' => '施術例やスタッフのおすすめスタイルをご覧ください。'],
            ['type' => 'slider', 'design' => 'hero', 'section_label' => 'ヘアカタログ', 'slide_height' => 210, 'slides' => [
                ['image_url' => 'https://images.unsplash.com/photo-1560066984-138dadb4c035?w=1200', 'caption' => 'ナチュラルカラー'],
                ['image_url' => 'https://images.unsplash.com/photo-1487412947517-5cebf100ffc2?w=1200', 'caption' => 'ネイルアート'],
            ]],
            ['type' => 'data', 'source' => 'gallery', 'design' => 'cards'],
        ];

        return $layouts;
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    private static function retailLayout(): array
    {
        $accent = '#111111';
        $layouts = self::baseTabs($accent);
        $layouts['home'] = [
            ['type' => 'banner', 'design' => 'countdown', 'icon' => 'fa-fire', 'text' => '週末限定 — 最大40%OFF', 'color' => $accent, 'text_color' => '#ffffff', 'action' => '#products'],
            ['type' => 'member_summary', 'design' => 'wallet', 'greeting' => 'ようこそ〇〇ショップへ'],
            ['type' => 'stats_row', 'section_label' => 'マイステータス', 'items' => [
                ['label' => 'ポイント', 'value' => '2,180', 'hint' => 'pt', 'field' => 'points'],
                ['label' => 'ランク', 'value' => 'プラチナ', 'hint' => '', 'field' => 'rank'],
                ['label' => 'クーポン', 'value' => '4', 'hint' => '枚'],
            ]],
            ['type' => 'collection', 'design' => 'chip_scroll', 'section_label' => 'カテゴリ', 'items' => [
                ['title' => '新着', 'action' => '#products'],
                ['title' => 'セール', 'action' => '#coupons'],
                ['title' => 'アウター', 'action' => '#products'],
                ['title' => 'バッグ', 'action' => '#products'],
            ]],
            ['type' => 'slider', 'design' => 'cards', 'section_label' => '今シーズン', 'slide_height' => 200, 'slides' => [
                ['image_url' => 'https://images.unsplash.com/photo-1483985988355-763728e1935b?w=1200', 'caption' => 'New Season Collection'],
                ['image_url' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=1200', 'caption' => '会員限定セール'],
            ]],
            ['type' => 'collection', 'design' => 'product_shelf', 'section_label' => '注目のアイテム', 'layout' => 'grid', 'columns' => 2, 'item_image_height' => 150, 'items' => [
                ['title' => 'ライトコート', 'description' => '¥18,900', 'badge' => '新着', 'image_url' => 'https://images.unsplash.com/photo-1483985988355-763728e1935b?w=600', 'action' => '#products'],
                ['title' => 'レザーバッグ', 'description' => '¥24,800', 'badge' => '人気', 'image_url' => 'https://images.unsplash.com/photo-1548036328-387074abb645?w=600', 'action' => '#products'],
                ['title' => 'ニットベスト', 'description' => '¥6,800', 'image_url' => 'https://images.unsplash.com/photo-1434389677669-e08b4cac3105?w=600', 'action' => '#products'],
                ['title' => 'トートバッグ', 'description' => '¥4,200', 'image_url' => 'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=600', 'action' => '#products'],
            ]],
            ['type' => 'button', 'design' => 'cta', 'text' => '商品一覧へ', 'action' => '#products', 'color' => $accent, 'full_width' => true],
        ];
        $layouts['products'] = [
            ['type' => 'heading', 'design' => 'section', 'text' => '商品カタログ', 'size' => 'large'],
            ['type' => 'text', 'design' => 'lead', 'text' => 'カテゴリから商品を探して、カートに追加できます。'],
            ['type' => 'collection', 'design' => 'chip_scroll', 'section_label' => 'カテゴリ', 'items' => [
                ['title' => 'トップス', 'action' => '#products'],
                ['title' => 'アウター', 'action' => '#products'],
                ['title' => 'バッグ', 'action' => '#products'],
                ['title' => '小物', 'action' => '#products'],
            ]],
            ['type' => 'collection', 'design' => 'product_shelf', 'section_label' => '新着アイテム', 'layout' => 'grid', 'columns' => 2, 'item_image_height' => 160, 'items' => [
                ['title' => 'リネンシャツ', 'description' => '¥8,900', 'image_url' => 'https://images.unsplash.com/photo-1434389677669-e08b4cac3105?w=600', 'action' => '#products'],
                ['title' => 'ワイドパンツ', 'description' => '¥12,400', 'image_url' => 'https://images.unsplash.com/photo-1473966968600-fa801b869a1a?w=600', 'action' => '#products'],
            ]],
            ['type' => 'data', 'source' => 'products', 'design' => 'cards'],
        ];
        $layouts['coupons'] = [
            ['type' => 'heading', 'design' => 'section', 'text' => 'お得なクーポン', 'size' => 'large'],
            ['type' => 'banner', 'design' => 'pill', 'icon' => 'fa-percentage', 'text' => '会員ランクに応じたクーポンを毎月配信', 'color' => $accent, 'text_color' => '#ffffff'],
            ['type' => 'collection', 'design' => 'compact', 'section_label' => '今週のクーポン', 'layout' => 'scroll', 'item_image_height' => 96, 'items' => [
                ['title' => '10%OFF', 'description' => '全品対象', 'badge' => '人気', 'image_url' => 'https://images.unsplash.com/photo-1607082349566-187342175e2f?w=600', 'action' => '#coupons'],
                ['title' => '送料無料', 'description' => '会員限定', 'image_url' => 'https://images.unsplash.com/photo-1472851294608-062f824d29cc?w=600', 'action' => '#coupons'],
            ]],
            ['type' => 'data', 'source' => 'coupons', 'design' => 'cards'],
        ];

        return $layouts;
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    private static function fitnessLayout(): array
    {
        $accent = '#16a34a';
        $layouts = self::baseTabs($accent);
        $layouts['home'] = [
            ['type' => 'member_summary', 'design' => 'tier', 'greeting' => '今日もお疲れさまです'],
            ['type' => 'hero', 'design' => 'split', 'title' => '次回のレッスン', 'subtitle' => 'パーソナル / グループレッスンを予約', 'image_url' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=1400&q=80'],
            ['type' => 'stats_row', 'section_label' => '今週の記録', 'items' => [
                ['label' => 'ワークアウト', 'value' => '4', 'hint' => '回'],
                ['label' => '連続来館', 'value' => '12', 'hint' => '日'],
                ['label' => 'ポイント', 'value' => '560', 'hint' => 'pt', 'field' => 'points'],
            ]],
            ['type' => 'quick_actions', 'design' => 'pill', 'actions' => [
                ['icon' => 'fa-dumbbell', 'label' => 'レッスン予約', 'action' => '#reservations'],
                ['icon' => 'fa-stamp', 'label' => 'チェックイン', 'action' => '#stamps'],
                ['icon' => 'fa-chart-line', 'label' => '実績', 'action' => '#member'],
                ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
            ]],
            ['type' => 'collection', 'design' => 'picks', 'section_label' => '人気のレッスン', 'layout' => 'scroll', 'item_image_height' => 130, 'items' => [
                ['title' => 'HIIT 30分', 'description' => '空きあり', 'image_url' => 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=800', 'action' => '#reservations'],
                ['title' => 'ヨガフロー', 'description' => '残席2', 'image_url' => 'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?w=800', 'action' => '#reservations'],
            ]],
            ['type' => 'banner', 'design' => 'soft', 'icon' => 'fa-medal', 'text' => '今月の来館8回 — あと2回で特典クーポン獲得', 'color' => $accent, 'action' => '#stamps'],
            ['type' => 'button', 'design' => 'cta', 'text' => 'レッスンを予約', 'action' => '#reservations', 'color' => $accent, 'full_width' => true],
        ];
        $layouts['reservations'] = [
            ['type' => 'heading', 'design' => 'section', 'text' => 'レッスン予約', 'size' => 'large'],
            ['type' => 'text', 'design' => 'lead', 'text' => '空きのあるレッスンを選んで予約してください。'],
            ['type' => 'banner', 'design' => 'soft', 'icon' => 'fa-clock', 'text' => 'キャンセルは開始2時間前まで可能です', 'color' => $accent],
            ['type' => 'data', 'source' => 'reservations', 'design' => 'cards'],
            ['type' => 'button', 'design' => 'cta', 'text' => '予約をはじめる', 'action' => '#reservations', 'color' => $accent],
        ];
        $layouts['stamps'] = [
            ['type' => 'member_summary', 'design' => 'compact'],
            ['type' => 'heading', 'design' => 'section', 'text' => '来館ポイント', 'size' => 'large'],
            ['type' => 'banner', 'design' => 'soft', 'icon' => 'fa-fire', 'text' => '来館ごとにスタンプ、満了でプロテインなどの特典', 'color' => $accent],
            ['type' => 'icon_text', 'rows' => [
                ['icon' => 'fa-check-circle', 'text' => '来館ごとにスタンプ獲得', 'color' => $accent],
                ['icon' => 'fa-gift', 'text' => '満了でプロテイン・グッズ特典', 'color' => $accent],
            ]],
            ['type' => 'data', 'source' => 'stamps', 'design' => 'cards'],
        ];
        $layouts['member'] = [
            ['type' => 'member_summary', 'design' => 'tier', 'greeting' => '会員ステータス'],
            ['type' => 'heading', 'design' => 'section', 'text' => '会員情報', 'size' => 'large'],
            ['type' => 'icon_text', 'rows' => [
                ['icon' => 'fa-id-card', 'text' => '会員証をスマホで提示してチェックイン', 'color' => $accent],
                ['icon' => 'fa-fire', 'text' => '今月の来館回数を確認', 'color' => $accent],
                ['icon' => 'fa-wallet', 'text' => 'Apple Wallet / Google ウォレットに会員証を追加', 'color' => $accent],
            ]],
            ['type' => 'data', 'source' => 'member_card', 'card_design' => 'emblem'],
        ];

        return $layouts;
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    private static function hotelLayout(): array
    {
        $accent = '#b8860b';
        $layouts = self::baseTabs($accent);
        $layouts['home'] = [
            ['type' => 'hero', 'design' => 'apple', 'eyebrow' => 'Concierge', 'title' => 'ご滞在をより快適に', 'subtitle' => 'レストラン・スパ・ルームサービスをアプリから', 'image_url' => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1400&q=80'],
            ['type' => 'member_summary', 'design' => 'emblem', 'greeting' => 'ご宿泊ありがとうございます'],
            ['type' => 'quick_actions', 'design' => 'grid', 'actions' => [
                ['icon' => 'fa-utensils', 'label' => 'レストラン', 'action' => '#reservations'],
                ['icon' => 'fa-concierge-bell', 'label' => 'ルームサービス', 'action' => '#products'],
                ['icon' => 'fa-spa', 'label' => 'スパ', 'action' => '#reservations'],
                ['icon' => 'fa-id-card', 'label' => '会員', 'action' => '#member'],
            ]],
            ['type' => 'banner', 'design' => 'outline', 'icon' => 'fa-moon', 'text' => '平日宿泊15%OFF（要予約）', 'color' => $accent],
            ['type' => 'card', 'design' => 'horizontal', 'title' => '本日のディナー', 'description' => 'シェフおすすめコース — 19:00〜', 'image_url' => 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=800', 'image_height' => 104, 'action' => '#reservations'],
            ['type' => 'card', 'design' => 'featured', 'title' => 'スパのご予約', 'description' => '空室状況を確認する', 'image_url' => 'https://images.unsplash.com/photo-1540555700478-4be289fbecef?w=800', 'action' => '#reservations'],
            ['type' => 'data', 'source' => 'announcements', 'design' => 'compact'],
            ['type' => 'button', 'design' => 'outline', 'text' => 'ご予約・お問い合わせ', 'action' => '#reservations', 'color' => $accent, 'full_width' => true],
        ];
        $layouts['reservations'] = [
            ['type' => 'heading', 'design' => 'section', 'text' => 'ご予約', 'size' => 'large'],
            ['type' => 'banner', 'design' => 'soft', 'icon' => 'fa-calendar-check', 'text' => 'レストラン・スパのご予約', 'color' => $accent, 'text_color' => '#ffffff'],
            ['type' => 'card', 'design' => 'horizontal', 'title' => 'スパトリートメント', 'description' => '空室状況を確認', 'image_url' => 'https://images.unsplash.com/photo-1540555700478-4be289fbecef?w=800', 'image_height' => 100, 'action' => '#reservations'],
            ['type' => 'data', 'source' => 'reservations', 'design' => 'cards'],
        ];
        $layouts['member'] = [
            ['type' => 'member_summary', 'design' => 'emblem', 'greeting' => 'ゴールド会員'],
            ['type' => 'heading', 'design' => 'section', 'text' => '会員ステータス', 'size' => 'large'],
            ['type' => 'icon_text', 'rows' => [
                ['icon' => 'fa-crown', 'text' => 'ご滞在ごとにステータスポイントを加算', 'color' => $accent],
                ['icon' => 'fa-wallet', 'text' => 'Apple Wallet / Google ウォレットに会員証を追加', 'color' => $accent],
            ]],
            ['type' => 'data', 'source' => 'member_card', 'card_design' => 'emblem'],
            ['type' => 'data', 'source' => 'coupons', 'design' => 'cards'],
        ];
        $layouts['announcements'] = [
            ['type' => 'heading', 'design' => 'section', 'text' => '館内のご案内', 'size' => 'large'],
            ['type' => 'access', 'design' => 'card', 'title' => 'アクセス', 'address' => '東京都〇〇区 1-2-3', 'phone' => '03-0000-0000'],
            ['type' => 'data', 'source' => 'announcements', 'design' => 'cards'],
        ];

        return $layouts;
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    private static function clinicLayout(): array
    {
        $accent = '#0284c7';
        $layouts = self::baseTabs($accent);
        $layouts['home'] = [
            ['type' => 'hero', 'design' => 'minimal', 'eyebrow' => 'Clinic', 'title' => 'オンライン予約', 'subtitle' => '診療科目・日時をお選びください', 'button_label' => '予約する'],
            ['type' => 'member_summary', 'design' => 'minimal', 'greeting' => 'こんにちは'],
            ['type' => 'stats_row', 'section_label' => '受付状況', 'items' => [
                ['label' => '現在の待ち', 'value' => '12', 'hint' => '分'],
                ['label' => '次回予約', 'value' => '4/2', 'hint' => ''],
                ['label' => 'お知らせ', 'value' => '2', 'hint' => '件'],
            ]],
            ['type' => 'quick_actions', 'design' => 'grid', 'actions' => [
                ['icon' => 'fa-calendar-check', 'label' => '予約', 'action' => '#reservations'],
                ['icon' => 'fa-users', 'label' => '順番待ち', 'action' => '#queue'],
                ['icon' => 'fa-file-medical', 'label' => '診療案内', 'action' => '#announcements'],
                ['icon' => 'fa-id-card', 'label' => '診察券', 'action' => '#member'],
            ]],
            ['type' => 'list', 'style' => 'number', 'items' => ['診療科目を選択', '日時を選択', '内容を確認して確定']],
            ['type' => 'hours', 'design' => 'card', 'title' => '診療時間', 'rows' => [
                ['day' => '月〜金', 'time' => '9:00 - 18:00'],
                ['day' => '土', 'time' => '9:00 - 13:00'],
                ['day' => '日・祝', 'time' => '休診'],
            ]],
            ['type' => 'data', 'source' => 'announcements', 'design' => 'compact'],
            ['type' => 'button', 'design' => 'cta', 'text' => '診察を予約する', 'action' => '#reservations', 'color' => $accent, 'full_width' => true],
        ];
        $layouts['reservations'] = [
            ['type' => 'heading', 'design' => 'section', 'text' => 'ご予約', 'size' => 'large'],
            ['type' => 'banner', 'design' => 'soft', 'icon' => 'fa-calendar-check', 'text' => 'ネット受付・順番待ち確認が可能です', 'color' => $accent, 'text_color' => '#ffffff'],
            ['type' => 'list', 'style' => 'number', 'items' => ['診療科目を選択', '日時を選択', '内容を確認して確定']],
            ['type' => 'data', 'source' => 'reservations', 'design' => 'cards'],
        ];
        $layouts['queue'] = [
            ['type' => 'heading', 'design' => 'section', 'text' => '順番待ち', 'size' => 'large'],
            ['type' => 'banner', 'design' => 'soft', 'icon' => 'fa-users', 'text' => '受付状況をリアルタイムで確認', 'color' => $accent, 'text_color' => '#ffffff'],
            ['type' => 'icon_text', 'rows' => [
                ['icon' => 'fa-clipboard-list', 'text' => 'ネットで受付番号を取得', 'color' => $accent],
                ['icon' => 'fa-mobile-alt', 'text' => '順番が近づくと LINE で通知', 'color' => $accent],
            ]],
            ['type' => 'data', 'source' => 'queue', 'design' => 'cards'],
        ];
        $layouts['announcements'] = [
            ['type' => 'heading', 'design' => 'section', 'text' => '診療のご案内', 'size' => 'large'],
            ['type' => 'faq', 'design' => 'accordion', 'title' => 'よくある質問', 'items' => [
                ['q' => '初診の流れは？', 'a' => 'アプリで予約後、受付で診察券をご提示ください。'],
                ['q' => 'キャンセルはできますか？', 'a' => '予約詳細から変更・キャンセルが可能です。'],
            ]],
            ['type' => 'access', 'design' => 'card', 'title' => 'アクセス', 'address' => '東京都〇〇区 1-2-3', 'phone' => '03-0000-0000'],
            ['type' => 'data', 'source' => 'announcements', 'design' => 'cards'],
        ];

        return $layouts;
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    private static function luxuryLayout(): array
    {
        $accent = '#8b6f47';
        $layouts = self::baseTabs($accent);
        $layouts['home'] = [
            ['type' => 'member_summary', 'design' => 'emblem', 'greeting' => 'ようこそ'],
            ['type' => 'hero', 'design' => 'centered', 'eyebrow' => 'Collection', 'title' => '今季の新作コレクション', 'subtitle' => 'メンバー限定の先行販売', 'image_url' => 'https://images.unsplash.com/photo-1483985988350-763728e1935b?w=1400&q=80'],
            ['type' => 'card', 'design' => 'overlay', 'title' => '新作アイテム', 'description' => '今季の新作をいち早く', 'image_url' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=800', 'image_height' => 200, 'action' => '#products'],
            ['type' => 'collection', 'design' => 'editorial', 'section_label' => 'エディトリアル', 'layout' => 'scroll', 'item_image_height' => 150, 'items' => [
                ['title' => 'シルクブラウス', 'description' => '¥28,600', 'image_url' => 'https://images.unsplash.com/photo-1434389677669-e08b4cac3105?w=800', 'action' => '#products'],
                ['title' => 'レザーバッグ', 'description' => '¥42,000', 'badge' => '新着', 'image_url' => 'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=800', 'action' => '#products'],
            ]],
            ['type' => 'banner', 'design' => 'outline', 'icon' => 'fa-crown', 'text' => 'VIP会員 — プライベートセール開催中', 'color' => $accent, 'action' => '#coupons'],
            ['type' => 'button', 'design' => 'outline', 'text' => 'コレクションを見る', 'action' => '#products', 'color' => $accent, 'full_width' => true],
        ];
        $layouts['products'] = [
            ['type' => 'heading', 'design' => 'underline', 'text' => 'コレクション', 'size' => 'large'],
            ['type' => 'collection', 'design' => 'editorial', 'section_label' => '新作', 'layout' => 'grid', 'columns' => 2, 'item_image_height' => 160, 'items' => [
                ['title' => 'カシミアコート', 'description' => '¥68,000', 'image_url' => 'https://images.unsplash.com/photo-1483985988355-763728e1935b?w=600', 'action' => '#products'],
                ['title' => 'レザーバッグ', 'description' => '¥42,000', 'badge' => '新着', 'image_url' => 'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=600', 'action' => '#products'],
                ['title' => 'シルクスカーフ', 'description' => '¥18,000', 'image_url' => 'https://images.unsplash.com/photo-1434389677669-e08b4cac3105?w=600', 'action' => '#products'],
                ['title' => 'ジュエリー', 'description' => '¥88,000', 'image_url' => 'https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?w=600', 'action' => '#products'],
            ]],
            ['type' => 'data', 'source' => 'products', 'design' => 'cards'],
        ];
        $layouts['member'] = [
            ['type' => 'member_summary', 'design' => 'emblem', 'greeting' => 'VIP Member'],
            ['type' => 'banner', 'design' => 'outline', 'icon' => 'fa-crown', 'text' => '次のランクまであと 2,400 pt', 'field' => 'points_to_next', 'color' => $accent],
            ['type' => 'icon_text', 'rows' => [
                ['icon' => 'fa-gem', 'text' => 'VIP限定のプライベートイベントにご招待', 'color' => $accent],
                ['icon' => 'fa-wallet', 'text' => 'Apple Wallet / Google ウォレットに会員証を追加', 'color' => $accent],
            ]],
            ['type' => 'data', 'source' => 'member_card', 'card_design' => 'emblem'],
            ['type' => 'data', 'source' => 'coupons', 'design' => 'cards'],
        ];

        return $layouts;
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    private static function deliveryLayout(): array
    {
        $accent = '#e11d48';
        $layouts = self::baseTabs($accent);
        $layouts['home'] = [
            ['type' => 'member_summary', 'design' => 'compact'],
            ['type' => 'hero', 'design' => 'split', 'title' => '最短30分でお届け', 'subtitle' => '人気メニューを LINE からデリバリー', 'image_url' => 'https://images.unsplash.com/photo-1561758033-d066a3efea2f?w=1400&q=80'],
            ['type' => 'banner', 'design' => 'countdown', 'icon' => 'fa-motorcycle', 'text' => '今なら配達料無料キャンペーン', 'color' => $accent, 'text_color' => '#ffffff', 'action' => '#products'],
            ['type' => 'quick_actions', 'design' => 'compact', 'actions' => [
                ['icon' => 'fa-utensils', 'label' => '今すぐ注文', 'action' => '#products'],
                ['icon' => 'fa-motorcycle', 'label' => '配達状況', 'action' => '#orders'],
                ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
                ['icon' => 'fa-id-card', 'label' => '会員証', 'action' => '#member'],
            ]],
            ['type' => 'collection', 'design' => 'chip_scroll', 'section_label' => 'カテゴリ', 'items' => [
                ['title' => 'バーガー', 'action' => '#products'],
                ['title' => 'ピザ', 'action' => '#products'],
                ['title' => '丼もの', 'action' => '#products'],
                ['title' => 'スイーツ', 'action' => '#products'],
            ]],
            ['type' => 'collection', 'design' => 'product_shelf', 'section_label' => '人気メニュー', 'layout' => 'grid', 'columns' => 2, 'item_image_height' => 110, 'items' => [
                ['title' => 'クラシックバーガー', 'description' => '¥890', 'image_url' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=600', 'action' => '#products'],
                ['title' => 'マルゲリータ', 'description' => '¥1,280', 'image_url' => 'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=600', 'action' => '#products'],
                ['title' => '牛丼', 'description' => '¥680', 'image_url' => 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=600', 'action' => '#products'],
                ['title' => 'パンケーキ', 'description' => '¥720', 'image_url' => 'https://images.unsplash.com/photo-1488477181946-6428a0291777?w=600', 'action' => '#products'],
            ]],
            ['type' => 'button', 'design' => 'cta', 'text' => 'カートに進む', 'action' => '#products', 'color' => $accent, 'full_width' => true],
        ];
        $layouts['orders'] = [
            ['type' => 'member_summary', 'design' => 'compact'],
            ['type' => 'heading', 'design' => 'section', 'text' => '配達状況', 'size' => 'large'],
            ['type' => 'banner', 'design' => 'soft', 'icon' => 'fa-shipping-fast', 'text' => '配達状況・受け取り予定をリアルタイムで確認', 'color' => $accent],
            ['type' => 'icon_text', 'rows' => [
                ['icon' => 'fa-check-circle', 'text' => '注文受付', 'color' => $accent],
                ['icon' => 'fa-utensils', 'text' => '調理中', 'color' => $accent],
                ['icon' => 'fa-motorcycle', 'text' => '配達中', 'color' => $accent],
            ]],
            ['type' => 'data', 'source' => 'orders', 'design' => 'timeline'],
            ['type' => 'button', 'design' => 'cta', 'text' => 'もう一度注文する', 'action' => '#products', 'color' => $accent],
        ];
        $layouts['products'] = [
            ['type' => 'heading', 'design' => 'section', 'text' => 'メニュー', 'size' => 'large'],
            ['type' => 'collection', 'design' => 'chip_scroll', 'section_label' => 'カテゴリ', 'items' => [
                ['title' => '人気', 'action' => '#products'],
                ['title' => 'バーガー', 'action' => '#products'],
                ['title' => '麺類', 'action' => '#products'],
                ['title' => 'ドリンク', 'action' => '#products'],
            ]],
            ['type' => 'collection', 'design' => 'product_shelf', 'section_label' => 'おすすめセット', 'layout' => 'grid', 'columns' => 2, 'item_image_height' => 120, 'items' => [
                ['title' => 'バーガーセット', 'description' => '¥1,090', 'image_url' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=600', 'action' => '#products'],
                ['title' => 'ピザセット', 'description' => '¥1,580', 'image_url' => 'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=600', 'action' => '#products'],
            ]],
            ['type' => 'data', 'source' => 'products', 'design' => 'cards'],
        ];

        return $layouts;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function buildSingleScreenCatalog(): array
    {
        return array_merge(
            self::singleScreenDefaultsA(),
            self::singleScreenDefaultsB(),
            self::singleScreenExtraTemplates()
        );
    }

    /**
     * ShopSettingLiffDefaults 単画面（前半）。
     *
     * @return array<int, array<string, mixed>>
     */
    private static function singleScreenDefaultsA(): array
    {
        return [
            self::screenEntry('flagship-dashboard', 'フラッグシップ・ホーム', 'home', '会員ウォレット・ショートカット・おすすめ棚を備えた、〇〇店向け定番アプリホーム。', ['home'], ['accent_color' => '#1c1917'], [
                ['type' => 'member_summary', 'design' => 'wallet', 'greeting' => 'おかえりなさい、〇〇さん'],
                ['type' => 'stats_row', 'section_label' => 'マイステータス', 'items' => [
                    ['label' => 'ポイント', 'value' => '1,240', 'hint' => 'pt', 'field' => 'points'],
                    ['label' => 'ランク', 'value' => 'ゴールド', 'hint' => '', 'field' => 'rank'],
                    ['label' => 'クーポン', 'value' => '3', 'hint' => '枚'],
                ]],
                ['type' => 'quick_actions', 'design' => 'pill', 'actions' => [
                    ['icon' => 'fa-utensils', 'label' => '注文', 'action' => '#products'],
                    ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
                    ['icon' => 'fa-stamp', 'label' => 'スタンプ', 'action' => '#stamps'],
                    ['icon' => 'fa-calendar-alt', 'label' => '予約', 'action' => '#reservations'],
                    ['icon' => 'fa-id-card', 'label' => '会員証', 'action' => '#member'],
                ]],
                ['type' => 'banner', 'design' => 'countdown', 'icon' => 'fa-bolt', 'text' => '本日ポイント2倍 — 会員限定キャンペーン', 'color' => '#1c1917', 'text_color' => '#ffffff', 'action' => '#coupons'],
                ['type' => 'hero', 'design' => 'apple', 'eyebrow' => '本日', 'title' => 'シェフのおすすめ', 'subtitle' => '今宵の一皿と、あなた専用の特典をチェック', 'image_url' => 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=1400&q=80'],
                ['type' => 'collection', 'design' => 'picks', 'section_label' => 'あなたへのおすすめ', 'layout' => 'scroll', 'item_image_height' => 130, 'items' => [
                    ['title' => 'シグネチャープレート', 'description' => '¥1,280', 'badge' => 'おすすめ', 'image_url' => 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=800'],
                    ['title' => '季節のサラダ', 'description' => '¥880', 'badge' => '新着', 'image_url' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=800'],
                    ['title' => 'クラフトラテ', 'description' => '¥580', 'image_url' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=800'],
                ]],
                ['type' => 'data', 'source' => 'announcements', 'design' => 'compact'],
                ['type' => 'button', 'design' => 'cta', 'text' => 'メニューを見る', 'action' => '#products', 'color' => '#1c1917', 'full_width' => true],
            ]),
            self::screenEntry('welcome-home', 'ウェルカム・ホーム', 'home', '初回訪問向け。ヒーロー訴求と会員登録導線をバランスよく配置したウェルカム画面。', ['home'], ['accent_color' => '#007aff'], [
                ['type' => 'hero', 'design' => 'apple', 'eyebrow' => 'ようこそ', 'title' => 'ようこそ〇〇店へ', 'subtitle' => '注文・予約・会員特典まで、LINEひとつで完結', 'image_url' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=1400&q=80'],
                ['type' => 'member_summary', 'design' => 'wallet', 'greeting' => 'はじめまして'],
                ['type' => 'banner', 'design' => 'pill', 'icon' => 'fa-gift', 'text' => '初回登録でドリンククーポンをプレゼント', 'color' => '#007aff', 'text_color' => '#ffffff', 'action' => '#coupons'],
                ['type' => 'quick_actions', 'design' => 'grid', 'actions' => [
                    ['icon' => 'fa-utensils', 'label' => 'メニュー', 'action' => '#products'],
                    ['icon' => 'fa-calendar-check', 'label' => '予約', 'action' => '#reservations'],
                    ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
                    ['icon' => 'fa-user-plus', 'label' => '会員登録', 'action' => '#member'],
                ]],
                ['type' => 'collection', 'design' => 'picks', 'section_label' => '人気メニュー', 'layout' => 'scroll', 'item_image_height' => 120, 'items' => [
                    ['title' => '本日のランチ', 'description' => '¥1,200', 'image_url' => 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=800'],
                    ['title' => '季節のスープ', 'description' => '¥680', 'image_url' => 'https://images.unsplash.com/photo-1547592166-23acba133eaa?w=800'],
                ]],
                ['type' => 'button', 'design' => 'cta', 'text' => 'メニューを見る', 'action' => '#products', 'color' => '#007aff', 'full_width' => true],
                ['type' => 'button', 'design' => 'outline', 'text' => '会員になる', 'action' => '#member', 'color' => '#007aff', 'full_width' => true],
            ]),
            self::screenEntry('campaign-slider', 'キャンペーン・スライダー', 'campaign', 'フルブリードスライダーと特典リストで、販促を印象的に伝えるキャンペーン画面。', ['home', 'announcements'], ['accent_color' => '#9f1239'], [
                ['type' => 'slider', 'design' => 'hero', 'section_label' => 'キャンペーン', 'slide_height' => 220, 'slides' => [
                    ['image_url' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=1200', 'caption' => '春の新メニュー — 期間限定'],
                    ['image_url' => 'https://images.unsplash.com/photo-1466978913421-dad2ebd01d17?w=1200', 'caption' => 'テイクアウト10%OFF'],
                    ['image_url' => 'https://images.unsplash.com/photo-1498837167922-ddd27525d352?w=1200', 'caption' => '週末限定セット'],
                ]],
                ['type' => 'banner', 'design' => 'promo', 'icon' => 'fa-star', 'text' => '会員ランクに応じた追加特典あり', 'color' => '#9f1239', 'text_color' => '#ffffff'],
                ['type' => 'heading', 'design' => 'underline', 'text' => '今週のハイライト', 'size' => 'large'],
                ['type' => 'icon_text', 'rows' => [
                    ['icon' => 'fa-check-circle', 'text' => '会員はポイント2倍デーあり', 'color' => '#9f1239'],
                    ['icon' => 'fa-check-circle', 'text' => '予約限定特典クーポン', 'color' => '#9f1239'],
                    ['icon' => 'fa-check-circle', 'text' => '新商品は毎週更新', 'color' => '#9f1239'],
                ]],
                ['type' => 'data', 'source' => 'coupons', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'cta', 'text' => 'クーポンを確認', 'action' => '#coupons', 'color' => '#9f1239', 'full_width' => true],
            ]),
            self::screenEntry('quick-order', 'クイック注文', 'order', 'カテゴリ棚と強いCTAで、最短3タップの注文導線を構築します。', ['home', 'products'], ['accent_color' => '#ea580c', 'density' => 'compact', 'content_width' => 'edge'], [
                ['type' => 'heading', 'design' => 'section', 'text' => '今すぐ注文', 'size' => 'large'],
                ['type' => 'text', 'design' => 'lead', 'text' => '人気カテゴリから選んで、最短3タップで注文完了。'],
                ['type' => 'collection', 'design' => 'product_shelf', 'section_label' => 'クイックピック', 'layout' => 'grid', 'columns' => 2, 'item_image_height' => 130, 'items' => [
                    ['title' => '定番メニュー', 'description' => '人気No.1', 'badge' => '人気', 'image_url' => 'https://images.unsplash.com/photo-1550547660-d9450f859349?w=600'],
                    ['title' => '季節限定', 'description' => '期間限定', 'badge' => '新着', 'image_url' => 'https://images.unsplash.com/photo-1540189549336-e6e99c3679fe?w=600'],
                    ['title' => 'ドリンク', 'description' => 'テイクアウト可', 'image_url' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=600'],
                    ['title' => 'スイーツ', 'description' => '人気急上昇', 'image_url' => 'https://images.unsplash.com/photo-1488477181946-6428a0291777?w=600'],
                ]],
                ['type' => 'banner', 'design' => 'soft', 'icon' => 'fa-clock', 'text' => '受取時間を選んで、店頭でスムーズ受取', 'color' => '#ea580c', 'text_color' => '#ffffff'],
                ['type' => 'data', 'source' => 'products', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'cta', 'text' => '商品一覧へ進む', 'action' => '#products', 'color' => '#ea580c', 'full_width' => true],
            ]),
            self::screenEntry('member-benefits', '会員特典ラウンジ', 'member', '会員証・特典・ランクアップを前面に出した、ロイヤリティ強化画面。', ['member', 'home'], ['accent_color' => '#7c3aed'], [
                ['type' => 'member_summary', 'design' => 'emblem', 'greeting' => 'ゴールド会員'],
                ['type' => 'hero', 'design' => 'apple', 'eyebrow' => '会員', 'title' => '会員だけの特別な体験', 'subtitle' => '来店・注文・予約でポイントが貯まり、ランクアップで特典が広がります', 'image_url' => 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=1400&q=80'],
                ['type' => 'banner', 'design' => 'pill', 'icon' => 'fa-crown', 'text' => '今月の会員限定クーポンを配信中', 'color' => '#7c3aed', 'text_color' => '#ffffff', 'action' => '#coupons'],
                ['type' => 'icon_text', 'rows' => [
                    ['icon' => 'fa-stamp', 'text' => '来店ごとにスタンプを獲得', 'color' => '#7c3aed'],
                    ['icon' => 'fa-chart-line', 'text' => 'ランクアップで特典が増加', 'color' => '#7c3aed'],
                    ['icon' => 'fa-gift', 'text' => '会員限定クーポンを優先配信', 'color' => '#7c3aed'],
                    ['icon' => 'fa-wallet', 'text' => 'Apple Wallet / Google ウォレットに会員証を追加', 'color' => '#7c3aed'],
                ]],
                ['type' => 'data', 'source' => 'member_card', 'design' => 'premium', 'card_design' => 'emblem'],
                ['type' => 'button', 'design' => 'cta', 'text' => '会員証を開く', 'action' => '#member', 'color' => '#7c3aed', 'full_width' => true],
            ]),
            self::screenEntry('brand-story', 'ブランドストーリー', 'story', 'エディトリアルヒーローとカードで、店舗の世界観を伝えるブランドストーリー。', ['home', 'gallery'], ['accent_color' => '#c2410c', 'font_family' => 'serif-display'], [
                ['type' => 'hero', 'design' => 'apple', 'eyebrow' => 'ストーリー', 'title' => '私たちのこだわり', 'subtitle' => '素材・製法・接客への想いを、ひとつひとつ丁寧に', 'image_url' => 'https://images.unsplash.com/photo-1466978913421-dad2ebd01d17?w=1400&q=80'],
                ['type' => 'collection', 'design' => 'editorial', 'section_label' => 'こだわり', 'layout' => 'scroll', 'item_image_height' => 150, 'show_description' => true, 'items' => [
                    ['title' => '毎朝仕込み', 'description' => 'フレッシュ', 'image_url' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=1000'],
                    ['title' => '地元の食材', 'description' => '地産地消', 'image_url' => 'https://images.unsplash.com/photo-1542838132-92c53300491e?w=1000'],
                    ['title' => '職人の技', 'description' => 'クラフト', 'image_url' => 'https://images.unsplash.com/photo-1556910103-1c02745aae4d?w=1000'],
                ]],
                ['type' => 'data', 'source' => 'gallery', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'soft', 'text' => 'ギャラリーを見る', 'action' => '#gallery', 'color' => '#c2410c', 'full_width' => true],
            ]),
            self::screenEntry('reservation-flow', '予約フロー', 'reservation', '予約手順・空き状況・注意点を1画面で伝え、離脱を減らす予約導線。', ['reservations'], ['accent_color' => '#0284c7'], [
                ['type' => 'hero', 'design' => 'split', 'title' => '来店予約', 'subtitle' => '希望の日時を選んで、スムーズにご来店ください。', 'image_url' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=1400&q=80'],
                ['type' => 'heading', 'design' => 'section', 'text' => '予約の流れ', 'size' => 'large'],
                ['type' => 'list', 'style' => 'number', 'items' => ['希望日時を選択', '人数と連絡先を入力', '内容を確認して予約確定']],
                ['type' => 'banner', 'design' => 'soft', 'icon' => 'fa-info-circle', 'text' => '当日の変更はお電話がスムーズです', 'color' => '#0284c7', 'text_color' => '#ffffff'],
                ['type' => 'data', 'source' => 'reservations', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'cta', 'text' => '予約画面へ進む', 'action' => '#reservations', 'color' => '#0284c7', 'full_width' => true],
            ]),
            self::screenEntry('queue-guide', '順番待ちガイド', 'support', '混雑時の受付・通知・待ち組数確認をわかりやすくする順番待ちガイド。', ['queue', 'home'], ['accent_color' => '#0f766e'], [
                ['type' => 'heading', 'design' => 'section', 'text' => '順番待ち受付', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'pill', 'icon' => 'fa-users', 'text' => '現在の待ち組数をリアルタイム表示', 'color' => '#0f766e', 'text_color' => '#ffffff'],
                ['type' => 'icon_text', 'rows' => [
                    ['icon' => 'fa-user-friends', 'text' => '人数を入力して受付', 'color' => '#0f766e'],
                    ['icon' => 'fa-mobile-alt', 'text' => '通知で呼び出しをお知らせ', 'color' => '#0f766e'],
                    ['icon' => 'fa-check-circle', 'text' => '受付後は現在組数を確認可能', 'color' => '#0f766e'],
                ]],
                ['type' => 'data', 'source' => 'queue', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'cta', 'text' => '順番待ちを開始', 'action' => '#queue', 'color' => '#0f766e', 'full_width' => true],
            ]),
            self::screenEntry('announcement-compact', 'お知らせボード', 'campaign', '新着情報とキャンペーンを簡潔にまとめる、お知らせボード型レイアウト。', ['announcements', 'home'], ['accent_color' => '#7c3aed'], [
                ['type' => 'heading', 'design' => 'section', 'text' => '最新のお知らせ', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'soft', 'icon' => 'fa-bell', 'text' => '週替わりキャンペーン更新中', 'color' => '#7c3aed', 'text_color' => '#ffffff'],
                ['type' => 'collection', 'design' => 'magazine', 'section_label' => 'ハイライト', 'layout' => 'scroll', 'item_image_height' => 100, 'items' => [
                    ['title' => '週末限定セット', 'description' => '土日限定', 'badge' => '人気', 'image_url' => 'https://images.unsplash.com/photo-1498837167922-ddd27525d352?w=800'],
                    ['title' => '新メニュー登場', 'description' => '今週から', 'badge' => '新着', 'image_url' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=800'],
                ]],
                ['type' => 'data', 'source' => 'announcements', 'design' => 'cards'],
            ]),
            self::screenEntry('salon-home', 'サロン向けホーム', 'industry', '予約・スタイル訴求を中心にした、〇〇サロン向けダッシュボード。', ['home', 'reservations', 'member'], ['accent_color' => '#db2777', 'app_display_name' => '〇〇サロン'], [
                ['type' => 'member_summary', 'design' => 'wallet', 'greeting' => 'いらっしゃいませ'],
                ['type' => 'hero', 'design' => 'apple', 'eyebrow' => 'Beauty', 'title' => '次回予約でキレイを継続', 'subtitle' => '担当スタイリストの空き状況を、いつでもチェック', 'image_url' => 'https://images.unsplash.com/photo-1560066984-138dadb4c035?w=1400&q=80'],
                ['type' => 'slider', 'design' => 'cards', 'section_label' => 'スタイル', 'slide_height' => 190, 'slides' => [
                    ['image_url' => 'https://images.unsplash.com/photo-1560066984-138dadb4c035?w=1200', 'caption' => '今月のおすすめスタイル'],
                    ['image_url' => 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?w=1200', 'caption' => 'カラーキャンペーン実施中'],
                ]],
                ['type' => 'quick_actions', 'design' => 'pill', 'actions' => [
                    ['icon' => 'fa-calendar-check', 'label' => '予約', 'action' => '#reservations'],
                    ['icon' => 'fa-cut', 'label' => 'メニュー', 'action' => '#products'],
                    ['icon' => 'fa-images', 'label' => 'ギャラリー', 'action' => '#gallery'],
                    ['icon' => 'fa-id-card', 'label' => '会員証', 'action' => '#member'],
                ]],
                ['type' => 'button', 'design' => 'cta', 'text' => '今すぐ予約する', 'action' => '#reservations', 'color' => '#db2777', 'full_width' => true],
            ]),
            self::screenEntry('takeout-fast-lane', 'テイクアウト高速導線', 'industry', 'テイクアウト注文を最短化する、〇〇店向け注文ホーム。', ['home', 'products', 'orders'], ['accent_color' => '#ea580c', 'density' => 'compact'], [
                ['type' => 'heading', 'design' => 'section', 'text' => '受け取り時間を選んですぐ注文', 'size' => 'large'],
                ['type' => 'collection', 'design' => 'picks', 'section_label' => '受取枠から選ぶ', 'layout' => 'scroll', 'item_image_height' => 130, 'items' => [
                    ['title' => 'ランチBOX', 'description' => '12:00受取', 'image_url' => 'https://images.unsplash.com/photo-1543332164-6e82f355bad5?w=800'],
                    ['title' => 'パスタセット', 'description' => '12:30受取', 'image_url' => 'https://images.unsplash.com/photo-1621996346565-e3dbc353d2e5?w=800'],
                    ['title' => 'サラダボウル', 'description' => '13:00受取', 'image_url' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=800'],
                ]],
                ['type' => 'banner', 'design' => 'soft', 'icon' => 'fa-bell', 'text' => '受取準備完了時に LINE で通知します', 'color' => '#ea580c'],
                ['type' => 'data', 'source' => 'products', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'cta', 'text' => '注文を開始', 'action' => '#products', 'color' => '#ea580c', 'full_width' => true],
            ]),
            self::screenEntry('products-catalog', 'メニューカタログ', 'order', 'カテゴリ訴求と商品一覧を組み合わせた、見やすいメニューカタログ。', ['products'], ['accent_color' => '#ea580c'], [
                ['type' => 'heading', 'design' => 'section', 'text' => '商品を選ぶ', 'size' => 'large'],
                ['type' => 'text', 'design' => 'lead', 'text' => 'カテゴリから商品を選んでカートに追加してください。'],
                ['type' => 'collection', 'design' => 'chip_scroll', 'section_label' => 'カテゴリ', 'items' => [
                    ['title' => 'メイン', 'action' => '#products'],
                    ['title' => 'サイド', 'action' => '#products'],
                    ['title' => 'ドリンク', 'action' => '#products'],
                    ['title' => 'スイーツ', 'action' => '#products'],
                ]],
                ['type' => 'data', 'source' => 'products', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'cta', 'text' => '注文履歴を見る', 'action' => '#orders', 'color' => '#ea580c', 'full_width' => true],
            ]),
            self::screenEntry('orders-status-board', '注文ステータスボード', 'order', '現在注文と履歴を確認しやすい、進捗重視のステータスボード。', ['orders'], ['accent_color' => '#2563eb'], [
                ['type' => 'heading', 'design' => 'section', 'text' => '注文ステータス', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'soft', 'icon' => 'fa-clock', 'text' => '受取準備完了時に通知します', 'color' => '#2563eb', 'text_color' => '#ffffff'],
                ['type' => 'icon_text', 'rows' => [
                    ['icon' => 'fa-receipt', 'text' => '注文受付 → 調理中 → 受取可能', 'color' => '#2563eb'],
                    ['icon' => 'fa-bell', 'text' => 'ステータス変更を LINE でお知らせ', 'color' => '#2563eb'],
                ]],
                ['type' => 'data', 'source' => 'orders', 'design' => 'timeline'],
            ]),
            self::screenEntry('stamps-loyalty-board', 'スタンプロイヤリティ', 'member', 'スタンプ進捗と特典案内を中心にした、来店促進テンプレート。', ['stamps'], ['accent_color' => '#16a34a'], [
                ['type' => 'member_summary', 'design' => 'compact'],
                ['type' => 'heading', 'design' => 'section', 'text' => 'スタンプカード', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'pill', 'icon' => 'fa-gift', 'text' => '満了で特典クーポンをプレゼント', 'color' => '#16a34a', 'text_color' => '#ffffff'],
                ['type' => 'icon_text', 'rows' => [
                    ['icon' => 'fa-stamp', 'text' => '来店ごとにスタンプを付与', 'color' => '#16a34a'],
                    ['icon' => 'fa-gift', 'text' => '満了で特典クーポンを配布', 'color' => '#16a34a'],
                ]],
                ['type' => 'data', 'source' => 'stamps', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'cta', 'text' => 'クーポンを見る', 'action' => '#coupons', 'color' => '#16a34a', 'full_width' => true],
            ]),
            self::screenEntry('member-card-focus', '会員証ウォレット', 'member', '会員証・ポイント・ランクの確認に最適化したウォレット型画面。', ['member'], ['accent_color' => '#007aff'], [
                ['type' => 'member_summary', 'design' => 'wallet'],
                ['type' => 'heading', 'design' => 'section', 'text' => '会員ページ', 'size' => 'large'],
                ['type' => 'text', 'design' => 'lead', 'text' => '会員証・ポイント・ランクをいつでも確認できます。'],
                ['type' => 'quick_actions', 'design' => 'pill', 'actions' => [
                    ['icon' => 'fa-qrcode', 'label' => '提示', 'action' => '#member'],
                    ['icon' => 'fa-stamp', 'label' => 'スタンプ', 'action' => '#stamps'],
                    ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
                    ['icon' => 'fa-history', 'label' => '履歴', 'action' => '#orders'],
                ]],
                ['type' => 'icon_text', 'rows' => [
                    ['icon' => 'fa-wallet', 'text' => 'Apple Wallet / Google ウォレットに会員証を追加できます', 'color' => '#007aff'],
                ]],
                ['type' => 'data', 'source' => 'member_card', 'design' => 'premium', 'card_design' => 'emblem'],
            ]),
            self::screenEntry('reservations-quick-booking', 'かんたん予約', 'reservation', '予約手順を簡潔にし、離脱を減らす最短予約テンプレート。', ['reservations'], ['accent_color' => '#0284c7'], [
                ['type' => 'heading', 'design' => 'section', 'text' => '来店予約', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'soft', 'icon' => 'fa-calendar-check', 'text' => '空いている枠からすぐ予約できます', 'color' => '#0284c7', 'text_color' => '#ffffff'],
                ['type' => 'list', 'style' => 'number', 'items' => ['日時を選択', '人数を入力', '内容を確認して確定']],
                ['type' => 'data', 'source' => 'reservations', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'cta', 'text' => '予約をはじめる', 'action' => '#reservations', 'color' => '#0284c7', 'full_width' => true],
            ]),
            self::screenEntry('coupon-showcase', 'クーポンショーケース', 'campaign', 'クーポン利用を促す販促向けテンプレートです。', ['coupons'], ['accent_color' => '#7c3aed'], [
                ['type' => 'heading', 'design' => 'section', 'text' => '会員限定クーポン', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'pill', 'icon' => 'fa-ticket-alt', 'text' => '今すぐ使えるクーポンを優先表示', 'color' => '#7c3aed', 'text_color' => '#ffffff'],
                ['type' => 'icon_text', 'rows' => [
                    ['icon' => 'fa-ticket-alt', 'text' => '毎週新しいクーポンを配信', 'color' => '#7c3aed'],
                    ['icon' => 'fa-bolt', 'text' => '今すぐ使えるクーポンを優先表示', 'color' => '#7c3aed'],
                ]],
                ['type' => 'data', 'source' => 'coupons', 'design' => 'cards'],
            ]),
            self::screenEntry('gallery-story', 'ギャラリーストーリー', 'story', '写真と説明で雰囲気を伝えるテンプレートです。', ['gallery'], ['accent_color' => '#1c1917'], [
                ['type' => 'heading', 'design' => 'section', 'text' => 'ギャラリー', 'size' => 'large'],
                ['type' => 'text', 'design' => 'lead', 'text' => '店内や商品の写真をチェックできます。'],
                ['type' => 'slider', 'design' => 'hero', 'section_label' => '注目', 'slide_height' => 200, 'slides' => [
                    ['image_url' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=1200', 'caption' => '店内の雰囲気'],
                    ['image_url' => 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=1200', 'caption' => '本日のプレート'],
                ]],
                ['type' => 'data', 'source' => 'gallery', 'design' => 'cards'],
            ]),
            self::screenEntry('home-night-lounge', 'ナイトラウンジ', 'home', '上質な夜の店舗体験を演出する、〇〇ラウンジ向けホーム。', ['home'], ['accent_color' => '#b8860b', 'font_family' => 'serif-display', 'app_display_name' => '〇〇ラウンジ'], [
                ['type' => 'hero', 'design' => 'apple', 'eyebrow' => 'Tonight', 'title' => '今夜のご予約を', 'subtitle' => '特別なひとときを、あなただけの席で', 'image_url' => 'https://images.unsplash.com/photo-1514933651103-005eec06c04b?w=1400&q=80'],
                ['type' => 'member_summary', 'design' => 'emblem', 'greeting' => 'おかえりなさい'],
                ['type' => 'quick_actions', 'design' => 'pill', 'actions' => [
                    ['icon' => 'fa-calendar-alt', 'label' => '予約', 'action' => '#reservations'],
                    ['icon' => 'fa-utensils', 'label' => 'メニュー', 'action' => '#products'],
                    ['icon' => 'fa-ticket-alt', 'label' => '特典', 'action' => '#coupons'],
                    ['icon' => 'fa-id-card', 'label' => '会員', 'action' => '#member'],
                ]],
                ['type' => 'banner', 'design' => 'soft', 'icon' => 'fa-star', 'text' => '会員ランク限定 — 本日のシェフおまかせコース', 'color' => '#1c1917'],
                ['type' => 'button', 'design' => 'cta', 'text' => '席を予約する', 'action' => '#reservations', 'color' => '#b8860b', 'full_width' => true],
            ]),
            self::screenEntry('home-bakery-morning', 'ベーカリー・モーニング', 'home', '焼きたてパンとコーヒーの温かみを伝える、〇〇ベーカリー向けホーム。', ['home'], ['accent_color' => '#c2410c', 'app_display_name' => '〇〇ベーカリー'], [
                ['type' => 'hero', 'design' => 'apple', 'eyebrow' => 'Morning', 'title' => '焼きたての朝を', 'subtitle' => '毎朝7時から、店内で香り立つ自家製パン', 'image_url' => 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=1400&q=80'],
                ['type' => 'banner', 'design' => 'soft', 'icon' => 'fa-bread-slice', 'text' => '本日のおすすめ — 塩バターロール 残りわずか', 'color' => '#c2410c', 'text_color' => '#ffffff'],
                ['type' => 'collection', 'design' => 'product_shelf', 'section_label' => '本日の焼きたて', 'layout' => 'grid', 'columns' => 2, 'item_image_height' => 110, 'items' => [
                    ['title' => 'クロワッサン', 'description' => '¥280', 'image_url' => 'https://images.unsplash.com/photo-1555507036-ab1f4038808a?w=600'],
                    ['title' => '食パン', 'description' => '¥420', 'image_url' => 'https://images.unsplash.com/photo-1549931319-a545dcf3bc73?w=600'],
                    ['title' => 'デニッシュ', 'description' => '¥350', 'image_url' => 'https://images.unsplash.com/photo-1486427944299-d1955d23e34d?w=600'],
                    ['title' => 'マフィン', 'description' => '¥320', 'image_url' => 'https://images.unsplash.com/photo-1607925796997-5d59fbfbc4d6?w=600'],
                ]],
                ['type' => 'button', 'design' => 'cta', 'text' => 'メニューを見る', 'action' => '#products', 'color' => '#c2410c', 'full_width' => true],
            ]),
            self::screenEntry('home-fitness-energy', 'フィットネス・ホーム', 'home', 'レッスン予約と来館記録を前面に出した、〇〇スタジオ向けダッシュボード。', ['home'], ['accent_color' => '#16a34a', 'app_display_name' => '〇〇スタジオ'], [
                ['type' => 'member_summary', 'design' => 'tier', 'greeting' => '今日もお疲れさまです'],
                ['type' => 'hero', 'design' => 'apple', 'eyebrow' => 'Training', 'title' => '今日も一歩前へ', 'subtitle' => 'レッスン予約・来館チェックインがスマホで完結', 'image_url' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=1400&q=80'],
                ['type' => 'stats_row', 'section_label' => '今週の記録', 'items' => [
                    ['label' => '今週', 'value' => '4', 'hint' => '回'],
                    ['label' => '連続', 'value' => '12', 'hint' => '日'],
                    ['label' => 'ポイント', 'value' => '560', 'hint' => 'pt', 'field' => 'points'],
                ]],
                ['type' => 'quick_actions', 'design' => 'pill', 'actions' => [
                    ['icon' => 'fa-calendar-check', 'label' => '予約', 'action' => '#reservations'],
                    ['icon' => 'fa-dumbbell', 'label' => 'レッスン', 'action' => '#products'],
                    ['icon' => 'fa-stamp', 'label' => '来館記録', 'action' => '#stamps'],
                    ['icon' => 'fa-id-card', 'label' => '会員証', 'action' => '#member'],
                ]],
                ['type' => 'button', 'design' => 'cta', 'text' => 'レッスンを予約', 'action' => '#reservations', 'color' => '#16a34a', 'full_width' => true],
            ]),
        ];
    }

    /**
     * ShopSettingLiffDefaults 単画面（後半 + CRM）。
     *
     * @return array<int, array<string, mixed>>
     */
    private static function singleScreenDefaultsB(): array
    {
        return [
            self::screenEntry('products-premium-menu', 'プレミアムメニュー', 'order', 'カテゴリヒーローと商品一覧で、飲食店のメニュー体験を高めます。', ['products'], ['accent_color' => '#7f1d1d', 'font_family' => 'serif-display'], [
                ['type' => 'hero', 'design' => 'apple', 'eyebrow' => 'Menu', 'title' => '季節のメニュー', 'subtitle' => '旬の食材を使った今季のおすすめ', 'image_url' => 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=1400&q=80'],
                ['type' => 'heading', 'design' => 'section', 'text' => 'カテゴリから選ぶ', 'size' => 'large'],
                ['type' => 'collection', 'design' => 'chip_scroll', 'section_label' => 'カテゴリ', 'items' => [
                    ['title' => '前菜', 'action' => '#products'],
                    ['title' => 'メイン', 'action' => '#products'],
                    ['title' => 'ドリンク', 'action' => '#products'],
                ]],
                ['type' => 'data', 'source' => 'products', 'design' => 'cards'],
            ]),
            self::screenEntry('coupons-wallet-style', 'クーポンウォレット', 'campaign', 'クーポンを財布のように見せる、利用促進向けテンプレートです。', ['coupons', 'home'], ['accent_color' => '#7c3aed'], [
                ['type' => 'member_summary', 'design' => 'wallet'],
                ['type' => 'banner', 'design' => 'pill', 'icon' => 'fa-ticket-alt', 'text' => 'あなたに届いたクーポンをチェック', 'color' => '#7c3aed', 'text_color' => '#ffffff'],
                ['type' => 'heading', 'design' => 'section', 'text' => '利用可能なクーポン', 'size' => 'large'],
                ['type' => 'icon_text', 'rows' => [
                    ['icon' => 'fa-bolt', 'text' => 'タップするだけで店頭利用OK', 'color' => '#7c3aed'],
                    ['icon' => 'fa-clock', 'text' => '期限が近いクーポンを優先表示', 'color' => '#7c3aed'],
                ]],
                ['type' => 'data', 'source' => 'coupons', 'design' => 'cards'],
            ]),
            self::screenEntry('member-vip-lounge', 'VIPラウンジ', 'member', 'ランク・ポイント・特典を贅沢に見せる会員向けテンプレートです。', ['member'], ['accent_color' => '#b8860b', 'font_family' => 'serif-display'], [
                ['type' => 'hero', 'design' => 'apple', 'eyebrow' => 'VIP', 'title' => '会員ラウンジ', 'subtitle' => 'あなたのランクに応じた特典をお届けします', 'image_url' => 'https://images.unsplash.com/photo-1560066984-138dadb4c035?w=1400&q=80'],
                ['type' => 'member_summary', 'design' => 'emblem', 'greeting' => 'ゴールド会員'],
                ['type' => 'card', 'design' => 'featured', 'title' => 'ランク特典', 'description' => 'ゴールド会員以上は毎月限定クーポンを配信', 'image_url' => 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=800', 'action' => '#coupons'],
                ['type' => 'icon_text', 'rows' => [
                    ['icon' => 'fa-wallet', 'text' => 'Apple Wallet / Google ウォレットに会員証を追加', 'color' => '#b8860b'],
                ]],
                ['type' => 'data', 'source' => 'member_card', 'design' => 'premium', 'card_design' => 'emblem'],
            ]),
            self::screenEntry('stamp-gamified', 'スタンプ・コレクト', 'member', '来店スタンプを楽しく集める、ゲーミフィケーション向け構成です。', ['stamps', 'home'], ['accent_color' => '#ea580c'], [
                ['type' => 'banner', 'design' => 'countdown', 'icon' => 'fa-stamp', 'text' => 'あと3個で特典クーポン獲得！', 'color' => '#ea580c', 'text_color' => '#ffffff', 'action' => '#stamps'],
                ['type' => 'heading', 'design' => 'section', 'text' => 'スタンプカード', 'size' => 'large'],
                ['type' => 'text', 'design' => 'lead', 'text' => '来店のたびにスタンプを押して、特典をゲットしましょう。'],
                ['type' => 'stats_row', 'section_label' => '進捗', 'items' => [
                    ['label' => '現在', 'value' => '7', 'hint' => '/10'],
                    ['label' => '残り', 'value' => '3', 'hint' => '個'],
                    ['label' => '特典', 'value' => '無料', 'hint' => 'ドリンク'],
                ]],
                ['type' => 'data', 'source' => 'stamps', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'cta', 'text' => 'クーポンを見る', 'action' => '#coupons', 'color' => '#ea580c', 'full_width' => true],
            ]),
            self::screenEntry('queue-live-board', '順番待ちライブボード', 'support', '待ち状況をリアルタイム感で伝える順番待ち向けテンプレートです。', ['queue', 'home'], ['accent_color' => '#059669'], [
                ['type' => 'heading', 'design' => 'section', 'text' => '現在の待ち状況', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'soft', 'icon' => 'fa-users', 'text' => '待ち組数はリアルタイムで更新されます', 'color' => '#059669', 'text_color' => '#ffffff'],
                ['type' => 'stats_row', 'section_label' => '受付状況', 'items' => [
                    ['label' => '待ち組数', 'value' => '8', 'hint' => '組'],
                    ['label' => '目安', 'value' => '25', 'hint' => '分'],
                ]],
                ['type' => 'data', 'source' => 'queue', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'cta', 'text' => '順番待ちを受付ける', 'action' => '#queue', 'color' => '#059669', 'full_width' => true],
            ]),
            self::screenEntry('announcements-editorial', 'お知らせ・エディトリアル', 'story', '雑誌風のレイアウトでお知らせを印象的に届けます。', ['announcements', 'home'], ['accent_color' => '#3730a3', 'font_family' => 'serif-display'], [
                ['type' => 'hero', 'design' => 'apple', 'eyebrow' => 'News', 'title' => 'お知らせとストーリー', 'subtitle' => '店舗からの最新情報をお届けします', 'image_url' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=1400&q=80'],
                ['type' => 'heading', 'design' => 'section', 'text' => '最新のお知らせ', 'size' => 'large'],
                ['type' => 'collection', 'design' => 'magazine', 'section_label' => 'ハイライト', 'layout' => 'scroll', 'item_image_height' => 110, 'items' => [
                    ['title' => '季節限定メニュー', 'description' => '公開中', 'badge' => '新着', 'image_url' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=800'],
                    ['title' => '週末セット', 'description' => '土日限定', 'badge' => '人気', 'image_url' => 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=800'],
                ]],
                ['type' => 'data', 'source' => 'announcements', 'design' => 'cards'],
            ]),
            self::screenEntry('home-ramen-shop', 'ラーメン店ホーム', 'industry', '看板メニューと注文導線を強調した、〇〇ラーメン向けホーム。', ['home', 'products'], ['accent_color' => '#3f6212', 'app_display_name' => '〇〇ラーメン'], [
                ['type' => 'hero', 'design' => 'apple', 'eyebrow' => 'Ramen', 'title' => '本日のおすすめ', 'subtitle' => '旨味濃厚スープと自家製麺の一杯を', 'image_url' => 'https://images.unsplash.com/photo-1569718212165-3a8278d5f624?w=1400&q=80'],
                ['type' => 'member_summary', 'design' => 'compact'],
                ['type' => 'collection', 'design' => 'picks', 'section_label' => '人気メニュー', 'layout' => 'scroll', 'item_image_height' => 120, 'items' => [
                    ['title' => '醤油ラーメン', 'description' => '¥880', 'badge' => '人気', 'image_url' => 'https://images.unsplash.com/photo-1617093727343-374698b1d08c?w=800'],
                    ['title' => '味噌ラーメン', 'description' => '¥920', 'image_url' => 'https://images.unsplash.com/photo-1598866594230-a7c1c2f4c36b?w=800'],
                    ['title' => 'つけ麺', 'description' => '¥950', 'badge' => '新着', 'image_url' => 'https://images.unsplash.com/photo-1569718212165-3a8278d5f624?w=800'],
                ]],
                ['type' => 'quick_actions', 'design' => 'pill', 'actions' => [
                    ['icon' => 'fa-utensils', 'label' => '注文', 'action' => '#products'],
                    ['icon' => 'fa-users', 'label' => '順番待ち', 'action' => '#queue'],
                    ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
                    ['icon' => 'fa-id-card', 'label' => '会員', 'action' => '#member'],
                ]],
                ['type' => 'button', 'design' => 'cta', 'text' => '今すぐ注文', 'action' => '#products', 'color' => '#3f6212', 'full_width' => true],
            ]),
            self::screenEntry('home-retail-showcase', '物販ショーケース', 'industry', '新着・ランキング・会員特典を並べた、〇〇ショップ向けホーム。', ['home'], ['accent_color' => '#111111', 'app_display_name' => '〇〇ショップ', 'content_width' => 'edge'], [
                ['type' => 'member_summary', 'design' => 'wallet', 'greeting' => 'ようこそ〇〇ショップへ'],
                ['type' => 'slider', 'design' => 'cards', 'section_label' => '新着', 'slide_height' => 200, 'slides' => [
                    ['image_url' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=1200', 'caption' => '春の新作コレクション'],
                    ['image_url' => 'https://images.unsplash.com/photo-1483985988355-763728e1935b?w=1200', 'caption' => '会員限定セール'],
                ]],
                ['type' => 'collection', 'design' => 'picks', 'section_label' => 'ランキング', 'layout' => 'scroll', 'item_image_height' => 130, 'items' => [
                    ['title' => 'ベストセラー1位', 'description' => '¥4,980', 'badge' => 'No.1', 'image_url' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=800'],
                    ['title' => '限定カラー', 'description' => '¥3,280', 'image_url' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=800'],
                ]],
                ['type' => 'banner', 'design' => 'soft', 'icon' => 'fa-gift', 'text' => '会員ランクアップで送料無料', 'color' => '#111111', 'text_color' => '#ffffff'],
                ['type' => 'button', 'design' => 'cta', 'text' => '商品を見る', 'action' => '#products', 'color' => '#111111', 'full_width' => true],
            ]),
            self::screenEntry('home-izakaya-night', '居酒屋・夜のひととき', 'industry', 'コース料理とドリンク訴求の、〇〇居酒屋向けホーム。', ['home'], ['accent_color' => '#7f1d1d', 'app_display_name' => '〇〇居酒屋'], [
                ['type' => 'hero', 'design' => 'apple', 'eyebrow' => 'Izakaya', 'title' => '今夜は〇〇居酒屋で', 'subtitle' => 'コースとドリンクをスマホから予約・注文', 'image_url' => 'https://images.unsplash.com/photo-1514933651103-005eec06c04b?w=1400&q=80'],
                ['type' => 'member_summary', 'design' => 'wallet'],
                ['type' => 'quick_actions', 'design' => 'pill', 'actions' => [
                    ['icon' => 'fa-calendar-alt', 'label' => '予約', 'action' => '#reservations'],
                    ['icon' => 'fa-utensils', 'label' => 'コース', 'action' => '#products'],
                    ['icon' => 'fa-beer', 'label' => 'ドリンク', 'action' => '#products'],
                    ['icon' => 'fa-users', 'label' => '順番待ち', 'action' => '#queue'],
                ]],
                ['type' => 'collection', 'design' => 'picks', 'section_label' => 'おすすめ', 'layout' => 'scroll', 'item_image_height' => 120, 'items' => [
                    ['title' => '飲み放題コース', 'description' => '¥3,980', 'badge' => '人気', 'image_url' => 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=800'],
                    ['title' => '刺身盛り合わせ', 'description' => '¥1,680', 'image_url' => 'https://images.unsplash.com/photo-1579871494447-81fbaa5dbbe9?w=800'],
                ]],
                ['type' => 'button', 'design' => 'cta', 'text' => '席を予約する', 'action' => '#reservations', 'color' => '#7f1d1d', 'full_width' => true],
            ]),
            self::screenEntry('reservations-spa-relax', 'スパ・リラクゼーション予約', 'reservation', 'スパ・エステ向けの予約導線テンプレート。', ['reservations', 'home'], ['accent_color' => '#0f766e', 'app_display_name' => '〇〇スパ'], [
                ['type' => 'hero', 'design' => 'apple', 'eyebrow' => 'Spa', 'title' => 'リラックス予約', 'subtitle' => '空き枠からすぐご予約いただけます', 'image_url' => 'https://images.unsplash.com/photo-1540555700478-4be289fbecef?w=800'],
                ['type' => 'heading', 'design' => 'section', 'text' => 'ご予約の流れ', 'size' => 'large'],
                ['type' => 'list', 'style' => 'number', 'items' => ['メニューを選択', '日時を選択', '内容を確認して確定']],
                ['type' => 'data', 'source' => 'reservations', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'cta', 'text' => '予約をはじめる', 'action' => '#reservations', 'color' => '#0f766e', 'full_width' => true],
            ]),
            self::screenEntry('products-drink-bar', 'ドリンクバーメニュー', 'order', 'ドリンク中心のメニュー訴求テンプレート。', ['products'], ['accent_color' => '#0369a1'], [
                ['type' => 'heading', 'design' => 'section', 'text' => 'ドリンクメニュー', 'size' => 'large'],
                ['type' => 'collection', 'design' => 'chip_scroll', 'section_label' => 'カテゴリ', 'items' => [
                    ['title' => 'コーヒー', 'action' => '#products'],
                    ['title' => 'ティー', 'action' => '#products'],
                    ['title' => 'ソフトドリンク', 'action' => '#products'],
                    ['title' => 'アルコール', 'action' => '#products'],
                ]],
                ['type' => 'collection', 'design' => 'product_shelf', 'section_label' => '人気ドリンク', 'layout' => 'grid', 'columns' => 2, 'item_image_height' => 120, 'items' => [
                    ['title' => 'カフェラテ', 'description' => '¥480', 'image_url' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=600'],
                    ['title' => 'フルーツティー', 'description' => '¥520', 'image_url' => 'https://images.unsplash.com/photo-1488477181946-6428a0291777?w=600'],
                ]],
                ['type' => 'data', 'source' => 'products', 'design' => 'cards'],
            ]),
            self::screenEntry('crm-cafe-loyalty', 'CRM・カフェロイヤリティ', 'industry', 'スタンプとモバイルオーダーを前面にした、〇〇カフェ向け CRM ホーム。', ['home', 'products', 'stamps', 'member'], ['accent_color' => '#7B4F2E', 'app_display_name' => '〇〇カフェ'], [
                ['type' => 'hero', 'design' => 'apple', 'eyebrow' => 'Cafe', 'title' => '今日の一杯を、すぐオーダー', 'subtitle' => 'スタンプ特典と会員ランクで毎日うれしい', 'image_url' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=1400&q=80'],
                ['type' => 'member_summary', 'design' => 'tier', 'greeting' => 'おはようございます'],
                ['type' => 'stats_row', 'section_label' => 'マイステータス', 'items' => [
                    ['label' => 'スタンプ', 'value' => '7', 'hint' => '/10'],
                    ['label' => 'ポイント', 'value' => '640', 'hint' => 'pt', 'field' => 'points'],
                    ['label' => 'クーポン', 'value' => '2', 'hint' => '枚'],
                ]],
                ['type' => 'card', 'design' => 'featured', 'title' => '本日のシグネチャー', 'description' => '季節のラテ ¥620', 'image_url' => 'https://images.unsplash.com/photo-1511920170033-f8396924c348?w=800', 'action' => '#products'],
                ['type' => 'banner', 'design' => 'soft', 'icon' => 'fa-mug-hot', 'text' => 'スタンプあと3個でドリンク1杯無料', 'color' => '#7B4F2E', 'action' => '#stamps'],
                ['type' => 'button', 'design' => 'cta', 'text' => 'モバイルオーダーを始める', 'action' => '#products', 'color' => '#7B4F2E', 'full_width' => true],
            ]),
            self::screenEntry('crm-sushi-reserve', 'CRM・鮨予約', 'industry', '完全予約制の鮨・割烹向け。上品なミニマル構成と予約中心の導線。', ['home', 'reservations'], ['accent_color' => '#1c1917', 'font_family' => 'serif', 'app_display_name' => '〇〇鮨', 'content_width' => 'narrow'], [
                ['type' => 'hero', 'design' => 'apple', 'eyebrow' => 'Sushi', 'title' => '旬を、一貫ずつ', 'subtitle' => 'カウンター8席 — 完全予約制', 'image_url' => 'https://images.unsplash.com/photo-1579871494447-81fbaa5dbbe9?w=1400&q=80'],
                ['type' => 'member_summary', 'design' => 'emblem'],
                ['type' => 'card', 'design' => 'featured', 'title' => 'おまかせコース', 'description' => '¥12,000 — 季節の握り10貫', 'image_url' => 'https://images.unsplash.com/photo-1579871494447-81fbaa5dbbe9?w=800', 'action' => '#reservations'],
                ['type' => 'button', 'design' => 'outline', 'text' => '席を予約する', 'action' => '#reservations', 'color' => '#1c1917', 'full_width' => true],
            ]),
            self::screenEntry('crm-beauty-booking', 'CRM・ビューティー予約', 'industry', '美容・ネイル向け。予約とギャラリーを前面にした CRM ホーム。', ['home', 'reservations', 'gallery', 'member'], ['accent_color' => '#db2777', 'app_display_name' => '〇〇サロン'], [
                ['type' => 'hero', 'design' => 'apple', 'eyebrow' => 'Beauty', 'title' => '次回のご予約をかんたんに', 'subtitle' => '空き枠から最短3ステップで予約完了', 'image_url' => 'https://images.unsplash.com/photo-1560066984-138dadb4c035?w=1400&q=80'],
                ['type' => 'stats_row', 'section_label' => 'マイステータス', 'items' => [
                    ['label' => '次回予約', 'value' => '3/28', 'hint' => ''],
                    ['label' => 'ポイント', 'value' => '820', 'hint' => 'pt', 'field' => 'points'],
                    ['label' => '特典', 'value' => '2', 'hint' => '枚'],
                ]],
                ['type' => 'quick_actions', 'design' => 'grid', 'actions' => [
                    ['icon' => 'fa-calendar-plus', 'label' => '予約する', 'action' => '#reservations'],
                    ['icon' => 'fa-images', 'label' => 'スタイル', 'action' => '#gallery'],
                    ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
                    ['icon' => 'fa-id-card', 'label' => '会員証', 'action' => '#member'],
                ]],
                ['type' => 'button', 'design' => 'cta', 'text' => '空き枠を確認して予約', 'action' => '#reservations', 'color' => '#db2777', 'full_width' => true],
            ]),
            self::screenEntry('crm-retail-flash', 'CRM・リテールフラッシュ', 'industry', 'セール訴求と商品棚をネイティブ風に。会員限定クーポンで来店を後押し。', ['home', 'products', 'coupons'], ['accent_color' => '#111111', 'app_display_name' => '〇〇ショップ'], [
                ['type' => 'banner', 'design' => 'countdown', 'icon' => 'fa-fire', 'text' => '週末限定 — 最大40%OFF', 'color' => '#111111', 'text_color' => '#ffffff', 'action' => '#products'],
                ['type' => 'member_summary', 'design' => 'wallet'],
                ['type' => 'collection', 'design' => 'chip_scroll', 'section_label' => 'カテゴリ', 'items' => [
                    ['title' => '新着', 'action' => '#products'],
                    ['title' => 'セール', 'action' => '#coupons'],
                    ['title' => '定番', 'action' => '#products'],
                ]],
                ['type' => 'collection', 'design' => 'product_shelf', 'section_label' => '注目のアイテム', 'layout' => 'grid', 'columns' => 2, 'item_image_height' => 140, 'items' => [
                    ['title' => 'ライトコート', 'description' => '¥18,900', 'badge' => '新着', 'image_url' => 'https://images.unsplash.com/photo-1483985988355-763728e1935b?w=800'],
                    ['title' => 'レザーバッグ', 'description' => '¥24,800', 'badge' => '人気', 'image_url' => 'https://images.unsplash.com/photo-1548036328-387074abb645?w=800'],
                ]],
                ['type' => 'button', 'design' => 'cta', 'text' => '商品一覧へ', 'action' => '#products', 'color' => '#111111', 'full_width' => true],
            ]),
            self::screenEntry('crm-gym-missions', 'CRM・ジムミッション', 'industry', '来館ミッションとレッスン予約でモチベーションを高めるフィットネス CRM。', ['home', 'reservations', 'stamps', 'member'], ['accent_color' => '#16a34a', 'app_display_name' => '〇〇ジム'], [
                ['type' => 'member_summary', 'design' => 'tier', 'greeting' => '今日もお疲れさまです'],
                ['type' => 'stats_row', 'section_label' => '今週の記録', 'items' => [
                    ['label' => 'ワークアウト', 'value' => '4', 'hint' => '回'],
                    ['label' => '連続', 'value' => '12', 'hint' => '日'],
                    ['label' => 'ポイント', 'value' => '560', 'hint' => 'pt', 'field' => 'points'],
                ]],
                ['type' => 'banner', 'design' => 'soft', 'icon' => 'fa-medal', 'text' => '今月の来館8回 — あと2回で特典クーポン獲得', 'color' => '#16a34a', 'action' => '#stamps'],
                ['type' => 'quick_actions', 'design' => 'pill', 'actions' => [
                    ['icon' => 'fa-dumbbell', 'label' => 'レッスン予約', 'action' => '#reservations'],
                    ['icon' => 'fa-stamp', 'label' => 'チェックイン', 'action' => '#stamps'],
                    ['icon' => 'fa-id-card', 'label' => '会員証', 'action' => '#member'],
                ]],
                ['type' => 'button', 'design' => 'cta', 'text' => 'レッスンを予約', 'action' => '#reservations', 'color' => '#16a34a', 'full_width' => true],
            ]),
            self::screenEntry('crm-hotel-concierge', 'CRM・ホテルコンシェルジュ', 'industry', 'チェックイン・レストラン予約・館内案内を上品にまとめたホテル CRM。', ['home', 'reservations', 'announcements', 'member'], ['accent_color' => '#b8860b', 'font_family' => 'serif-display', 'app_display_name' => '〇〇ホテル'], [
                ['type' => 'hero', 'design' => 'apple', 'eyebrow' => 'Concierge', 'title' => 'ご滞在をより快適に', 'subtitle' => 'レストラン・スパ・ルームサービスをアプリから', 'image_url' => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1400&q=80'],
                ['type' => 'member_summary', 'design' => 'emblem', 'greeting' => 'ご宿泊ありがとうございます'],
                ['type' => 'quick_actions', 'design' => 'grid', 'actions' => [
                    ['icon' => 'fa-utensils', 'label' => 'レストラン', 'action' => '#reservations'],
                    ['icon' => 'fa-concierge-bell', 'label' => 'ルーム', 'action' => '#products'],
                    ['icon' => 'fa-spa', 'label' => 'スパ', 'action' => '#reservations'],
                    ['icon' => 'fa-id-card', 'label' => '会員', 'action' => '#member'],
                ]],
                ['type' => 'data', 'source' => 'announcements', 'design' => 'compact'],
                ['type' => 'button', 'design' => 'outline', 'text' => 'ご予約・お問い合わせ', 'action' => '#reservations', 'color' => '#b8860b', 'full_width' => true],
            ]),
            self::screenEntry('crm-clinic-trust', 'CRM・クリニック受付', 'industry', '信頼感のある情報設計で、ネット予約・順番待ち・診療案内をまとめた受付 CRM。', ['home', 'reservations', 'queue', 'announcements'], ['accent_color' => '#0284c7', 'app_display_name' => '〇〇クリニック'], [
                ['type' => 'heading', 'design' => 'section', 'text' => 'ご予約・受付', 'size' => 'large'],
                ['type' => 'text', 'design' => 'lead', 'text' => '当日の受付状況とご予約をアプリから確認できます。'],
                ['type' => 'stats_row', 'items' => [
                    ['label' => '現在の待ち', 'value' => '12', 'hint' => '分'],
                    ['label' => '次回予約', 'value' => '4/2', 'hint' => ''],
                    ['label' => 'お知らせ', 'value' => '2', 'hint' => '件'],
                ]],
                ['type' => 'quick_actions', 'design' => 'compact', 'actions' => [
                    ['icon' => 'fa-calendar-check', 'label' => '予約', 'action' => '#reservations'],
                    ['icon' => 'fa-users', 'label' => '順番待ち', 'action' => '#queue'],
                    ['icon' => 'fa-file-medical', 'label' => '診療案内', 'action' => '#announcements'],
                    ['icon' => 'fa-id-card', 'label' => '診察券', 'action' => '#member'],
                ]],
                ['type' => 'hours', 'design' => 'card', 'title' => '診療時間', 'rows' => [
                    ['day' => '月〜金', 'time' => '9:00 - 18:00'],
                    ['day' => '土', 'time' => '9:00 - 13:00'],
                    ['day' => '日・祝', 'time' => '休診'],
                ]],
                ['type' => 'button', 'design' => 'cta', 'text' => '診察を予約する', 'action' => '#reservations', 'color' => '#0284c7', 'full_width' => true],
            ]),
            self::screenEntry('crm-pet-care', 'CRM・ペットケア', 'industry', 'トリミング予約と会員特典をまとめた、〇〇ペットサロン向け CRM。', ['home', 'reservations', 'member'], ['accent_color' => '#0891b2', 'app_display_name' => '〇〇ペットサロン'], [
                ['type' => 'hero', 'design' => 'apple', 'eyebrow' => 'Pet', 'title' => '大切なペットのために', 'subtitle' => 'トリミング予約・会員特典をアプリから', 'image_url' => 'https://images.unsplash.com/photo-1587300003388-59208cc962cd?w=1400&q=80'],
                ['type' => 'member_summary', 'design' => 'wallet'],
                ['type' => 'quick_actions', 'design' => 'pill', 'actions' => [
                    ['icon' => 'fa-calendar-check', 'label' => '予約', 'action' => '#reservations'],
                    ['icon' => 'fa-paw', 'label' => 'メニュー', 'action' => '#products'],
                    ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
                    ['icon' => 'fa-id-card', 'label' => '会員証', 'action' => '#member'],
                ]],
                ['type' => 'button', 'design' => 'cta', 'text' => '予約する', 'action' => '#reservations', 'color' => '#0891b2', 'full_width' => true],
            ]),
            self::screenEntry('crm-fashion-vip', 'CRM・ファッションVIP', 'industry', 'VIP会員とルックブック訴求の、高級アパレル向け CRM。', ['home', 'products', 'member', 'coupons'], ['accent_color' => '#92400e', 'font_family' => 'serif-display', 'app_display_name' => '〇〇ブティック', 'content_width' => 'narrow'], [
                ['type' => 'member_summary', 'design' => 'emblem', 'greeting' => 'ようこそ'],
                ['type' => 'hero', 'design' => 'apple', 'eyebrow' => 'Collection', 'title' => '今季のコレクション', 'subtitle' => 'メンバー限定の先行販売', 'image_url' => 'https://images.unsplash.com/photo-1483985988355-763728e1935b?w=800'],
                ['type' => 'collection', 'design' => 'editorial', 'section_label' => 'エディトリアル', 'layout' => 'scroll', 'item_image_height' => 150, 'items' => [
                    ['title' => 'シルクブラウス', 'description' => '¥28,600', 'image_url' => 'https://images.unsplash.com/photo-1434389677669-e08b4cac3105?w=800'],
                    ['title' => 'レザーバッグ', 'description' => '¥42,000', 'badge' => '新着', 'image_url' => 'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=800'],
                ]],
                ['type' => 'banner', 'design' => 'outline', 'icon' => 'fa-crown', 'text' => 'VIP会員 — プライベートセール開催中', 'color' => '#92400e', 'action' => '#coupons'],
                ['type' => 'button', 'design' => 'outline', 'text' => 'コレクションを見る', 'action' => '#products', 'color' => '#92400e', 'full_width' => true],
            ]),
            self::screenEntry('crm-native-wallet-home', 'CRM・ネイティブウォレット', 'home', 'Starbucks 系。会員ウォレット＋ピルショートカットの定番アプリホーム。', ['home'], ['accent_color' => '#007aff'], [
                ['type' => 'member_summary', 'design' => 'wallet', 'greeting' => 'おかえりなさい'],
                ['type' => 'stats_row', 'section_label' => 'マイステータス', 'items' => [
                    ['label' => 'ポイント', 'value' => '1,240', 'hint' => 'pt', 'field' => 'points'],
                    ['label' => 'ランク', 'value' => 'ゴールド', 'hint' => '', 'field' => 'rank'],
                    ['label' => 'スタンプ', 'value' => '7', 'hint' => '/10'],
                ]],
                ['type' => 'quick_actions', 'design' => 'pill', 'actions' => [
                    ['icon' => 'fa-utensils', 'label' => 'メニュー', 'action' => '#products'],
                    ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
                    ['icon' => 'fa-stamp', 'label' => 'スタンプ', 'action' => '#stamps'],
                    ['icon' => 'fa-id-card', 'label' => '会員証', 'action' => '#member'],
                ]],
                ['type' => 'banner', 'design' => 'soft', 'icon' => 'fa-gift', 'text' => '本日限定 — ドリンク1杯無料クーポン配信中', 'color' => '#007aff', 'action' => '#coupons'],
                ['type' => 'collection', 'design' => 'picks', 'section_label' => 'あなたへのおすすめ', 'layout' => 'scroll', 'item_image_height' => 120, 'items' => [
                    ['title' => 'おすすめセット', 'description' => '¥980', 'badge' => '人気', 'image_url' => 'https://images.unsplash.com/photo-1550547660-d9450f859349?w=800'],
                    ['title' => '季節限定', 'description' => '新着', 'badge' => '新着', 'image_url' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=800'],
                ]],
                ['type' => 'button', 'design' => 'cta', 'text' => 'メニューを見る', 'action' => '#products', 'color' => '#007aff', 'full_width' => true],
            ]),
            self::screenEntry('crm-delivery-dash', 'CRM・デリバリーダッシュ', 'industry', '最短注文と配達進捗を前面にした、フードデリバリー向け CRM。', ['home', 'products', 'orders', 'coupons'], ['accent_color' => '#ea580c', 'density' => 'compact', 'content_width' => 'edge'], [
                ['type' => 'member_summary', 'design' => 'compact'],
                ['type' => 'banner', 'design' => 'countdown', 'icon' => 'fa-motorcycle', 'text' => '今なら配達料無料キャンペーン', 'color' => '#ea580c', 'text_color' => '#ffffff', 'action' => '#products'],
                ['type' => 'quick_actions', 'design' => 'compact', 'actions' => [
                    ['icon' => 'fa-hamburger', 'label' => '今すぐ注文', 'action' => '#products'],
                    ['icon' => 'fa-motorcycle', 'label' => '配達状況', 'action' => '#orders'],
                    ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
                ]],
                ['type' => 'collection', 'design' => 'product_shelf', 'section_label' => '人気メニュー', 'layout' => 'grid', 'columns' => 2, 'item_image_height' => 110, 'items' => [
                    ['title' => 'クラシックバーガー', 'description' => '¥890', 'image_url' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=600'],
                    ['title' => 'マルゲリータ', 'description' => '¥1,280', 'image_url' => 'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=600'],
                ]],
                ['type' => 'button', 'design' => 'cta', 'text' => '今すぐ注文', 'action' => '#products', 'color' => '#ea580c', 'full_width' => true],
            ]),
        ];
    }

    /**
     * LiffDesignerExtraTemplates::tabTemplates() 互換の単画面テンプレート。
     *
     * @return array<int, array<string, mixed>>
     */
    private static function singleScreenExtraTemplates(): array
    {
        return [
            self::screenEntry('native-wallet-dashboard', 'ネイティブ・ウォレットダッシュボード', 'native', 'Starbucks / コンビニ系。会員ウォレット＋ピルショートカットの定番アプリホーム。', ['home'], ['accent_color' => '#007aff'], [
                ['type' => 'member_summary', 'design' => 'wallet'],
                ['type' => 'quick_actions', 'design' => 'pill', 'actions' => [
                    ['icon' => 'fa-utensils', 'label' => 'メニュー', 'action' => '#products'],
                    ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
                    ['icon' => 'fa-stamp', 'label' => 'スタンプ', 'action' => '#stamps'],
                    ['icon' => 'fa-id-card', 'label' => '会員証', 'action' => '#member'],
                ]],
                ['type' => 'banner', 'design' => 'soft', 'icon' => 'fa-gift', 'text' => '本日限定 — ドリンク1杯無料クーポン配信中', 'color' => '#1c1917'],
                ['type' => 'collection', 'design' => 'editorial', 'section_label' => 'おすすめ', 'layout' => 'scroll', 'item_image_height' => 128, 'items' => [
                    ['title' => 'おすすめセット', 'description' => '¥980', 'badge' => '人気', 'image_url' => 'https://images.unsplash.com/photo-1550547660-d9450f859349?w=800'],
                    ['title' => '季節限定', 'description' => '新着', 'badge' => '新着', 'image_url' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=800'],
                ]],
                ['type' => 'button', 'design' => 'cta', 'text' => 'メニューを見る', 'action' => '#products', 'color' => '#007aff', 'full_width' => true],
            ]),
            self::screenEntry('native-delivery-hub', 'ネイティブ・デリバリーハブ', 'native', '最短注文と進捗確認を前面にしたフードデリバリー向け。', ['home', 'products', 'orders'], ['accent_color' => '#ea580c', 'density' => 'compact', 'content_width' => 'edge'], [
                ['type' => 'member_summary', 'design' => 'compact'],
                ['type' => 'hero', 'design' => 'split', 'title' => '30分以内にお届け', 'subtitle' => '人気店のテイクアウト・デリバリーを LINE から。', 'image_url' => 'https://images.unsplash.com/photo-1561758033-d066a3efea2f?w=1400&q=80'],
                ['type' => 'quick_actions', 'design' => 'compact', 'actions' => [
                    ['icon' => 'fa-hamburger', 'label' => '今すぐ注文', 'action' => '#products'],
                    ['icon' => 'fa-motorcycle', 'label' => '配達状況', 'action' => '#orders'],
                    ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
                ]],
                ['type' => 'collection', 'design' => 'product_shelf', 'section_label' => '人気カテゴリ', 'layout' => 'grid', 'columns' => 2, 'item_image_height' => 100, 'items' => [
                    ['title' => 'バーガー', 'image_url' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=600'],
                    ['title' => 'ピザ', 'image_url' => 'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=600'],
                    ['title' => '丼もの', 'image_url' => 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=600'],
                    ['title' => 'スイーツ', 'image_url' => 'https://images.unsplash.com/photo-1488477181946-6428a0291777?w=600'],
                ]],
                ['type' => 'button', 'design' => 'cta', 'text' => 'カートに進む', 'action' => '#products', 'color' => '#ea580c', 'full_width' => true],
            ]),
            self::screenEntry('luxury-boutique-home', 'ラグジュアリー・ブティック', 'luxury', '高級アパレル・ジュエリー向け。エンブレム会員＋ルックブック訴求の洗練ホーム。', ['home'], ['accent_color' => '#92400e', 'font_family' => 'serif-display', 'content_width' => 'narrow', 'density' => 'relaxed', 'app_display_name' => '〇〇ブティック'], [
                ['type' => 'member_summary', 'design' => 'emblem', 'greeting' => 'ようこそ'],
                ['type' => 'hero', 'design' => 'apple', 'eyebrow' => 'Collection', 'title' => '今季のコレクション', 'subtitle' => 'メンバー限定エディション', 'image_url' => 'https://images.unsplash.com/photo-1483985988355-763728e1935b?w=1400&q=80'],
                ['type' => 'card', 'design' => 'overlay', 'title' => '新作アイテム', 'description' => '今季の新作をいち早く', 'image_url' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=800', 'image_height' => 200, 'action' => '#products'],
                ['type' => 'banner', 'design' => 'outline', 'icon' => 'fa-crown', 'text' => 'VIP会員 — プライベートセール開催中', 'color' => '#92400e'],
                ['type' => 'button', 'design' => 'outline', 'text' => 'コレクションを見る', 'action' => '#products', 'color' => '#92400e', 'full_width' => true],
            ]),
            self::screenEntry('luxury-hotel-concierge', 'ラグジュアリー・ホテルコンシェルジュ', 'luxury', '5つ星ホテル向け。ミニマル会員表示と予約・案内の上品な構成。', ['home', 'reservations'], ['accent_color' => '#b8860b', 'font_family' => 'serif-display', 'app_display_name' => '〇〇ホテル', 'density' => 'relaxed'], [
                ['type' => 'member_summary', 'design' => 'emblem', 'greeting' => 'ご宿泊ありがとうございます'],
                ['type' => 'hero', 'design' => 'minimal', 'title' => 'コンシェルジュ', 'subtitle' => 'レストラン予約・ルームサービス・館内案内'],
                ['type' => 'quick_actions', 'design' => 'compact', 'actions' => [
                    ['icon' => 'fa-utensils', 'label' => 'レストラン予約', 'action' => '#reservations'],
                    ['icon' => 'fa-concierge-bell', 'label' => 'ルームサービス', 'action' => '#products'],
                    ['icon' => 'fa-map', 'label' => '館内マップ', 'action' => '#gallery'],
                    ['icon' => 'fa-id-card', 'label' => '会員ステータス', 'action' => '#member'],
                ]],
                ['type' => 'card', 'design' => 'horizontal', 'title' => '本日のディナー', 'description' => 'シェフおすすめコース — 19:00〜', 'image_url' => 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=800', 'image_height' => 100],
                ['type' => 'button', 'design' => 'soft', 'text' => '予約する', 'action' => '#reservations', 'color' => '#b8860b', 'full_width' => true],
            ]),
            self::screenEntry('native-subscription-club', 'ネイティブ・サブスククラブ', 'member', '会員サブスクリプション型。グラス会員カードと特典訴求のロイヤリティホーム。', ['home', 'member'], ['accent_color' => '#7c3aed'], [
                ['type' => 'member_summary', 'design' => 'glass', 'greeting' => 'クラブ会員'],
                ['type' => 'banner', 'design' => 'pill', 'icon' => 'fa-star', 'text' => 'ゴールド会員 — 今月の特典を確認', 'color' => '#7c3aed', 'text_color' => '#ffffff', 'action' => '#coupons'],
                ['type' => 'icon_text', 'rows' => [
                    ['icon' => 'fa-check-circle', 'text' => '毎月限定クーポンを自動配信', 'color' => '#7c3aed'],
                    ['icon' => 'fa-check-circle', 'text' => 'ポイント有効期限なし', 'color' => '#7c3aed'],
                    ['icon' => 'fa-wallet', 'text' => 'Apple Wallet / Google ウォレットに会員証を追加', 'color' => '#7c3aed'],
                ]],
                ['type' => 'data', 'source' => 'member_card', 'design' => 'premium', 'card_design' => 'emblem'],
                ['type' => 'button', 'design' => 'cta', 'text' => '特典クーポンを見る', 'action' => '#coupons', 'color' => '#7c3aed', 'full_width' => true],
            ]),
            self::screenEntry('home-clinic-trust', 'クリニック・信頼感ホーム', 'industry', '医療・クリニック向け。信頼感のある情報設計と予約導線。', ['home', 'reservations'], ['accent_color' => '#334155', 'app_display_name' => '〇〇クリニック'], [
                ['type' => 'hero', 'design' => 'minimal', 'title' => 'オンライン予約', 'subtitle' => '診療科目・日時を選んで、スムーズにご予約ください。'],
                ['type' => 'member_summary', 'design' => 'compact'],
                ['type' => 'quick_actions', 'design' => 'compact', 'actions' => [
                    ['icon' => 'fa-calendar-check', 'label' => '予約する', 'action' => '#reservations'],
                    ['icon' => 'fa-file-medical', 'label' => '診療案内', 'action' => '#announcements'],
                    ['icon' => 'fa-clock', 'label' => '診療時間', 'action' => '#announcements'],
                ]],
                ['type' => 'hours', 'design' => 'card', 'title' => '診療時間', 'rows' => [
                    ['day' => '月〜金', 'time' => '9:00 - 18:00'],
                    ['day' => '土', 'time' => '9:00 - 13:00'],
                    ['day' => '休診', 'time' => '日・祝'],
                ]],
                ['type' => 'button', 'design' => 'cta', 'text' => '初診予約', 'action' => '#reservations', 'color' => '#334155', 'full_width' => true],
            ]),
            self::screenEntry('home-sushi-omakase', '鮨・おまかせ', 'industry', '高級鮨・割烹向け。上品なミニマル構成と予約中心の導線。', ['home'], ['accent_color' => '#1c1917', 'font_family' => 'serif', 'content_width' => 'narrow', 'density' => 'relaxed', 'app_display_name' => '〇〇鮨'], [
                ['type' => 'hero', 'design' => 'centered', 'title' => '旬を、一貫ずつ。', 'subtitle' => 'カウンター8席 — 完全予約制', 'image_url' => 'https://images.unsplash.com/photo-1579871494447-81fbaa5dbbe9?w=1400&q=80'],
                ['type' => 'member_summary', 'design' => 'split'],
                ['type' => 'card', 'design' => 'overlay', 'title' => 'おまかせコース', 'description' => '¥12,000 — 季節の握り10貫', 'image_url' => 'https://images.unsplash.com/photo-1579871494447-81fbaa5dbbe9?w=800', 'image_height' => 180],
                ['type' => 'button', 'design' => 'outline', 'text' => '席を予約する', 'action' => '#reservations', 'color' => '#1c1917', 'full_width' => true],
            ]),
            self::screenEntry('campaign-flash-sale', 'フラッシュセール', 'campaign', 'セール訴求。スライダー＋緊急感バナー＋グリッド商品棚。', ['home', 'coupons'], ['accent_color' => '#9f1239', 'density' => 'compact', 'content_width' => 'edge'], [
                ['type' => 'banner', 'design' => 'pill', 'icon' => 'fa-bolt', 'text' => 'フラッシュセール — 残り24時間', 'color' => '#9f1239', 'text_color' => '#ffffff', 'action' => '#products'],
                ['type' => 'slider', 'design' => 'cards', 'section_label' => 'セール', 'slide_height' => 200, 'slides' => [
                    ['image_url' => 'https://images.unsplash.com/photo-1607082349566-187342175e2f?w=1200', 'caption' => '最大50%OFF'],
                    ['image_url' => 'https://images.unsplash.com/photo-1472851294608-062f824d29cc?w=1200', 'caption' => '会員限定クーポン'],
                ]],
                ['type' => 'collection', 'design' => 'product_shelf', 'section_label' => 'セール商品', 'layout' => 'grid', 'columns' => 2, 'item_image_height' => 120, 'items' => [
                    ['title' => 'セール品A', 'description' => '¥1,980', 'badge' => '50%', 'image_url' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=600'],
                    ['title' => 'セール品B', 'description' => '¥2,480', 'badge' => '30%', 'image_url' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=600'],
                ]],
                ['type' => 'data', 'source' => 'coupons', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'cta', 'text' => 'セール会場へ', 'action' => '#products', 'color' => '#9f1239', 'full_width' => true],
            ]),
            self::screenEntry('story-magazine-editorial', 'マガジン・エディトリアル', 'story', '雑誌・メディア系。横型カードとセンターヒーローでブランドストーリーを伝えます。', ['home', 'announcements'], ['accent_color' => '#1c1917', 'font_family' => 'serif-display', 'content_width' => 'narrow', 'density' => 'relaxed'], [
                ['type' => 'hero', 'design' => 'centered', 'title' => '私たちの物語', 'subtitle' => '素材へのこだわりと、職人の技。', 'image_url' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=1400&q=80'],
                ['type' => 'heading', 'design' => 'eyebrow', 'text' => '最新記事', 'size' => 'large'],
                ['type' => 'card', 'design' => 'horizontal', 'title' => '春の新作メニュー', 'description' => '旬の食材を使った期間限定コース', 'image_url' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=800', 'image_height' => 100, 'action' => '#announcements'],
                ['type' => 'card', 'design' => 'horizontal', 'title' => 'シェフインタビュー', 'description' => '料理への想いを語る', 'image_url' => 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=800', 'image_height' => 100, 'action' => '#announcements'],
                ['type' => 'data', 'source' => 'announcements', 'design' => 'cards'],
            ]),
            self::screenEntry('member-status-pro', '会員ステータス Pro', 'member', '航空会社マイル / ホテルステータス系。スプリット会員表示＋エンブレム会員証。', ['member', 'home'], ['accent_color' => '#b8860b'], [
                ['type' => 'member_summary', 'design' => 'split', 'greeting' => 'ステータス会員'],
                ['type' => 'banner', 'design' => 'soft', 'icon' => 'fa-plane', 'text' => '次のランクまであと 2,400 pt', 'field' => 'points_to_next', 'color' => '#b8860b'],
                ['type' => 'data', 'source' => 'member_card', 'design' => 'premium', 'card_design' => 'split_banner'],
                ['type' => 'quick_actions', 'design' => 'pill', 'actions' => [
                    ['icon' => 'fa-history', 'label' => '履歴', 'action' => '#orders'],
                    ['icon' => 'fa-ticket-alt', 'label' => '特典', 'action' => '#coupons'],
                    ['icon' => 'fa-cog', 'label' => '設定', 'action' => '#member'],
                ]],
                ['type' => 'icon_text', 'rows' => [
                    ['icon' => 'fa-wallet', 'text' => 'Apple Wallet / Google ウォレットに会員証を追加', 'color' => '#b8860b'],
                ]],
            ]),
            self::screenEntry('products-lookbook-grid', 'ルックブック・グリッド', 'order', 'アパレル系。2列グリッド＋商品棚のルックブック。', ['products', 'home'], ['accent_color' => '#111111', 'content_width' => 'edge', 'density' => 'compact'], [
                ['type' => 'heading', 'design' => 'underline', 'text' => 'ショップ', 'size' => 'large'],
                ['type' => 'collection', 'design' => 'product_shelf', 'section_label' => '新着', 'layout' => 'grid', 'columns' => 2, 'item_image_height' => 160, 'items' => [
                    ['title' => 'リネンシャツ', 'description' => '¥8,900', 'image_url' => 'https://images.unsplash.com/photo-1434389677669-e08b4cac3105?w=600'],
                    ['title' => 'ワイドパンツ', 'description' => '¥12,400', 'badge' => '新着', 'image_url' => 'https://images.unsplash.com/photo-1473966968600-fa801b869a1a?w=600'],
                    ['title' => 'ニットベスト', 'description' => '¥6,800', 'image_url' => 'https://images.unsplash.com/photo-1434389677669-e08b4cac3105?w=600'],
                    ['title' => 'トートバッグ', 'description' => '¥4,200', 'image_url' => 'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=600'],
                ]],
                ['type' => 'data', 'source' => 'products', 'design' => 'cards'],
            ]),
            self::screenEntry('home-beauty-salon-pro', 'ビューティーサロン Pro', 'industry', '美容室・ネイルサロン向け。予約・ギャラリー導線。', ['home'], ['accent_color' => '#be185d', 'app_display_name' => '〇〇サロン'], [
                ['type' => 'member_summary', 'design' => 'glass', 'greeting' => 'いらっしゃいませ'],
                ['type' => 'slider', 'design' => 'cards', 'section_label' => 'スタイル', 'slide_height' => 200, 'slides' => [
                    ['image_url' => 'https://images.unsplash.com/photo-1560066984-138dadb4c035?w=1200', 'caption' => '春カラー'],
                    ['image_url' => 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?w=1200', 'caption' => 'ボブスタイル'],
                ]],
                ['type' => 'quick_actions', 'design' => 'pill', 'actions' => [
                    ['icon' => 'fa-calendar-check', 'label' => '予約', 'action' => '#reservations'],
                    ['icon' => 'fa-cut', 'label' => 'メニュー', 'action' => '#products'],
                    ['icon' => 'fa-images', 'label' => 'スタイル', 'action' => '#gallery'],
                    ['icon' => 'fa-id-card', 'label' => '会員', 'action' => '#member'],
                ]],
                ['type' => 'button', 'design' => 'cta', 'text' => '今すぐ予約', 'action' => '#reservations', 'color' => '#be185d', 'full_width' => true],
            ]),
            self::screenEntry('orders-tracker-native', '注文トラッカー', 'order', 'モバイルオーダー系。注文進捗を前面にしたステータス画面。', ['orders', 'home'], ['accent_color' => '#ea580c', 'density' => 'compact'], [
                ['type' => 'member_summary', 'design' => 'compact'],
                ['type' => 'banner', 'design' => 'soft', 'icon' => 'fa-receipt', 'text' => 'ご注文の準備ができ次第、通知します', 'color' => '#ea580c'],
                ['type' => 'heading', 'design' => 'section', 'text' => '注文状況', 'size' => 'large'],
                ['type' => 'icon_text', 'rows' => [
                    ['icon' => 'fa-check-circle', 'text' => '注文受付', 'color' => '#ea580c'],
                    ['icon' => 'fa-fire', 'text' => '調理中', 'color' => '#ea580c'],
                    ['icon' => 'fa-bell', 'text' => '受取準備完了', 'color' => '#d6d3d1'],
                ]],
                ['type' => 'data', 'source' => 'orders', 'design' => 'timeline'],
                ['type' => 'button', 'design' => 'cta', 'text' => '新しい注文', 'action' => '#products', 'color' => '#ea580c', 'full_width' => true],
            ]),
            self::screenEntry('home-coworking-hub', 'コワーキング・ハブ', 'industry', 'コワーキングスペース向け。予約・会員・お知らせのプロフェッショナル構成。', ['home', 'reservations'], ['accent_color' => '#334155', 'app_display_name' => '〇〇ワークスペース'], [
                ['type' => 'hero', 'design' => 'split', 'title' => 'もっと快適に働く', 'subtitle' => '会議室・ブース・デイパスを LINE から予約', 'image_url' => 'https://images.unsplash.com/photo-1497366216548-37526070297c?w=1400&q=80'],
                ['type' => 'member_summary', 'design' => 'wallet'],
                ['type' => 'quick_actions', 'design' => 'compact', 'actions' => [
                    ['icon' => 'fa-door-open', 'label' => 'ブース予約', 'action' => '#reservations'],
                    ['icon' => 'fa-users', 'label' => '会議室', 'action' => '#reservations'],
                    ['icon' => 'fa-wifi', 'label' => 'Wi-Fi情報', 'action' => '#announcements'],
                ]],
                ['type' => 'access', 'design' => 'card', 'title' => 'アクセス', 'address' => '東京都〇〇区 1-2-3', 'phone' => '03-0000-0000'],
                ['type' => 'data', 'source' => 'announcements', 'design' => 'cards'],
            ]),
            self::screenEntry('native-booking-one-tap', 'ネイティブ・ワンタップ予約', 'native', 'LIFF向け予約導線。親指で届く主CTA＋最短3ステップの予約体験。', ['home', 'reservations'], ['accent_color' => '#0f766e'], [
                ['type' => 'member_summary', 'design' => 'compact', 'greeting' => 'こんにちは'],
                ['type' => 'hero', 'design' => 'minimal', 'title' => '次の予約', 'subtitle' => '空き枠からすぐ予約 — かんたん3ステップで完了'],
                ['type' => 'quick_actions', 'design' => 'pill', 'actions' => [
                    ['icon' => 'fa-calendar-check', 'label' => '予約する', 'action' => '#reservations'],
                    ['icon' => 'fa-clock', 'label' => '空き状況', 'action' => '#reservations'],
                    ['icon' => 'fa-history', 'label' => '履歴', 'action' => '#orders'],
                ]],
                ['type' => 'data', 'source' => 'reservations', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'cta', 'text' => '今すぐ予約する', 'action' => '#reservations', 'color' => '#0f766e', 'full_width' => true],
            ]),
            self::screenEntry('native-grocery-express', 'ネイティブ・グロサリー便', 'native', 'コンビニ・スーパー向け。再購入ショートカットとクーポン訴求の定番リテールUI。', ['home', 'products', 'coupons'], ['accent_color' => '#ea580c', 'density' => 'compact', 'content_width' => 'edge'], [
                ['type' => 'banner', 'design' => 'soft', 'icon' => 'fa-percent', 'text' => '本日限定 — お会計10%OFFクーポン配信中', 'color' => '#ea580c'],
                ['type' => 'member_summary', 'design' => 'wallet'],
                ['type' => 'quick_actions', 'design' => 'compact', 'actions' => [
                    ['icon' => 'fa-shopping-basket', 'label' => '買い物', 'action' => '#products'],
                    ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
                    ['icon' => 'fa-receipt', 'label' => '履歴', 'action' => '#orders'],
                    ['icon' => 'fa-id-card', 'label' => '会員証', 'action' => '#member'],
                ]],
                ['type' => 'collection', 'design' => 'picks', 'section_label' => '再購入', 'layout' => 'scroll', 'item_image_height' => 110, 'items' => [
                    ['title' => 'おにぎりセット', 'description' => '¥498', 'image_url' => 'https://images.unsplash.com/photo-1580822184713-fc5400e7fe10?w=600'],
                    ['title' => 'サラダボウル', 'description' => '¥680', 'badge' => '人気', 'image_url' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=600'],
                ]],
                ['type' => 'button', 'design' => 'cta', 'text' => '商品一覧を見る', 'action' => '#products', 'color' => '#ea580c', 'full_width' => true],
            ]),
            self::screenEntry('native-fitness-studio', 'ネイティブ・フィットネススタジオ', 'industry', 'ジム・ヨガ・パーソナル向け。次回予約と会員ステータスを前面にしたモチベーションUI。', ['home', 'reservations', 'member'], ['accent_color' => '#f97316', 'app_display_name' => '〇〇スタジオ'], [
                ['type' => 'member_summary', 'design' => 'glass', 'greeting' => '今日も頑張りましょう'],
                ['type' => 'hero', 'design' => 'split', 'title' => '次のセッション', 'subtitle' => 'パーソナル / グループレッスンを予約', 'image_url' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=1400&q=80'],
                ['type' => 'quick_actions', 'design' => 'pill', 'actions' => [
                    ['icon' => 'fa-dumbbell', 'label' => 'レッスン予約', 'action' => '#reservations'],
                    ['icon' => 'fa-chart-line', 'label' => '実績', 'action' => '#member'],
                    ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
                ]],
                ['type' => 'banner', 'design' => 'soft', 'icon' => 'fa-fire', 'text' => '今月の来店 8回 — あと2回で特典獲得！', 'color' => '#f97316'],
                ['type' => 'button', 'design' => 'cta', 'text' => 'レッスンを予約', 'action' => '#reservations', 'color' => '#f97316', 'full_width' => true],
            ]),
            self::screenEntry('native-salon-glow', 'ネイティブ・サロンビューティー', 'industry', '美容室・ネイル・エステ向け。ビフォーアフター訴求と予約CTAの洗練レイアウト。', ['home', 'reservations', 'gallery'], ['accent_color' => '#e11d48', 'app_display_name' => '〇〇サロン', 'content_width' => 'narrow', 'density' => 'relaxed'], [
                ['type' => 'hero', 'design' => 'centered', 'title' => 'あなたのスタイル', 'subtitle' => 'トレンドスタイルと空き枠をチェック', 'image_url' => 'https://images.unsplash.com/photo-1560066984-138dadb4c035?w=1400&q=80'],
                ['type' => 'member_summary', 'design' => 'minimal'],
                ['type' => 'slider', 'design' => 'cards', 'slides' => [
                    ['image_url' => 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?w=1200', 'title' => '春カラー'],
                    ['image_url' => 'https://images.unsplash.com/photo-1487412947517-5cebf100ffc2?w=1200', 'title' => 'ネイルアート'],
                ]],
                ['type' => 'quick_actions', 'design' => 'compact', 'actions' => [
                    ['icon' => 'fa-calendar-alt', 'label' => '予約', 'action' => '#reservations'],
                    ['icon' => 'fa-images', 'label' => 'ギャラリー', 'action' => '#gallery'],
                    ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
                ]],
                ['type' => 'button', 'design' => 'cta', 'text' => '空き枠を確認して予約', 'action' => '#reservations', 'color' => '#e11d48', 'full_width' => true],
            ]),
            self::screenEntry('app-member-pass-premium', 'アプリ画面・プレミアム会員証', 'member', '会員証を主役にしたウォレット型。ポイント・ランク・特典導線をスマホアプリ品質で表示。', ['member', 'home'], ['accent_color' => '#b8860b', 'content_width' => 'narrow', 'system_ui_style' => 'luxe', 'member_card_design' => 'emblem', 'member_screen_design' => 'premium'], [
                ['type' => 'member_summary', 'design' => 'emblem', 'greeting' => '会員パス'],
                ['type' => 'banner', 'design' => 'outline', 'icon' => 'fa-crown', 'text' => 'VIP特典・限定クーポンを確認できます', 'color' => '#b8860b'],
                ['type' => 'quick_actions', 'design' => 'pill', 'actions' => [
                    ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
                    ['icon' => 'fa-stamp', 'label' => 'スタンプ', 'action' => '#stamps'],
                    ['icon' => 'fa-receipt', 'label' => '履歴', 'action' => '#orders'],
                    ['icon' => 'fa-calendar-check', 'label' => '予約', 'action' => '#reservations'],
                ]],
                ['type' => 'icon_text', 'rows' => [
                    ['icon' => 'fa-wallet', 'text' => 'Apple Wallet / Google ウォレットに会員証を追加', 'color' => '#b8860b'],
                ]],
                ['type' => 'data', 'source' => 'member', 'design' => 'premium'],
            ]),
            self::screenEntry('app-coupon-ticket-gallery', 'アプリ画面・クーポンチケット', 'coupon', 'チケット・ギャラリー型のクーポン画面。販促を見やすく、使用ボタンを強く出す構成。', ['coupons', 'home'], ['accent_color' => '#ea580c', 'coupon_screen_design' => 'ticket', 'system_ui_style' => 'editorial', 'system_card_style' => 'elevated'], [
                ['type' => 'hero', 'design' => 'split', 'title' => 'お得なクーポン', 'subtitle' => '今日使えるクーポンをまとめて確認', 'image_url' => 'https://images.unsplash.com/photo-1607083206869-4c7672e72a8a?w=1400&q=80'],
                ['type' => 'quick_actions', 'design' => 'compact', 'actions' => [
                    ['icon' => 'fa-check-circle', 'label' => '利用可能', 'action' => '#coupons'],
                    ['icon' => 'fa-gift', 'label' => '新着', 'action' => '#announcements'],
                    ['icon' => 'fa-id-card', 'label' => '会員証', 'action' => '#member'],
                ]],
                ['type' => 'data', 'source' => 'coupons', 'design' => 'premium'],
            ]),
            self::screenEntry('app-reservation-concierge', 'アプリ画面・予約コンシェルジュ', 'reservation', '予約一覧・空き枠確認を上品に見せるコンシェルジュ型。サロン・ホテル・クリニック向け。', ['reservations', 'home'], ['accent_color' => '#3730a3', 'font_family' => 'serif-display', 'content_width' => 'narrow', 'system_ui_style' => 'luxe', 'reservation_screen_design' => 'concierge', 'system_header_style' => 'hero'], [
                ['type' => 'hero', 'design' => 'centered', 'title' => 'ご予約', 'subtitle' => 'ご予約状況と次回の予定を確認', 'image_url' => 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?w=1400&q=80'],
                ['type' => 'button', 'design' => 'cta', 'text' => '新しく予約する', 'action' => '#reservations', 'color' => '#3730a3', 'full_width' => true],
                ['type' => 'data', 'source' => 'reservations', 'design' => 'timeline'],
                ['type' => 'faq', 'design' => 'cards', 'title' => '予約前の確認', 'items' => [
                    ['q' => '変更やキャンセルはできますか？', 'a' => '予約詳細から変更・キャンセルできます。'],
                    ['q' => '当日の受付方法は？', 'a' => '会員証画面をスタッフへ提示してください。'],
                ]],
            ]),
            self::screenEntry('app-order-tracking', 'アプリ画面・注文トラッキング', 'order', '注文履歴・配送/受取状況をダッシュボード風に表示。飲食・小売・デリバリー向け。', ['orders', 'products', 'home'], ['accent_color' => '#0369a1', 'content_width' => 'edge', 'density' => 'compact', 'activity_screen_design' => 'dashboard', 'system_ui_style' => 'compact', 'system_header_style' => 'editorial'], [
                ['type' => 'member_summary', 'design' => 'compact'],
                ['type' => 'banner', 'design' => 'soft', 'icon' => 'fa-shipping-fast', 'text' => '注文状況・受け取り予定をリアルタイムで確認', 'color' => '#0369a1'],
                ['type' => 'quick_actions', 'design' => 'compact', 'actions' => [
                    ['icon' => 'fa-shopping-bag', 'label' => '注文する', 'action' => '#products'],
                    ['icon' => 'fa-receipt', 'label' => '履歴', 'action' => '#orders'],
                    ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
                ]],
                ['type' => 'data', 'source' => 'orders', 'design' => 'timeline'],
            ]),
        ];
    }
}

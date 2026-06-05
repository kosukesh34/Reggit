<?php

namespace App\Support;

class ShopSettingLiffDefaults
{
    public static function defaultLiffBuilderTemplates(): array
    {
        return [
            [
                'id' => 'welcome',
                'name' => 'ウェルカム',
                'blocks' => [
                    ['type' => 'hero', 'title' => 'ようこそ', 'body' => 'スマホで快適に注文・予約ができます。', 'image_url' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=1200', 'button_label' => 'メニューを見る', 'button_url' => '#products'],
                    ['type' => 'text', 'title' => 'お知らせ', 'body' => '初回登録でクーポン配布中。'],
                    ['type' => 'button', 'title' => '会員登録', 'button_label' => '会員証を作成', 'button_url' => '#member'],
                ],
            ],
            [
                'id' => 'campaign',
                'name' => 'キャンペーン',
                'blocks' => [
                    ['type' => 'hero', 'title' => '期間限定キャンペーン', 'body' => '今週だけの特別メニュー。', 'image_url' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=1200'],
                    ['type' => 'list', 'title' => 'おすすめ', 'list_items' => ['季節限定バーガー', 'スペシャルドリンク', '数量限定クーポン']],
                    ['type' => 'button', 'button_label' => 'クーポンを確認', 'button_url' => '#coupons'],
                ],
            ],
            [
                'id' => 'reservation',
                'name' => '予約案内',
                'blocks' => [
                    ['type' => 'hero', 'title' => '事前予約でスムーズに', 'body' => '予約枠を確認して、希望の時間を押さえましょう。', 'image_url' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=1200'],
                    ['type' => 'button', 'button_label' => '予約する', 'button_url' => '#reservations'],
                    ['type' => 'text', 'title' => '注意事項', 'body' => '当日キャンセルはお控えください。'],
                ],
            ],
            [
                'id' => 'gallery',
                'name' => 'ギャラリー',
                'blocks' => [
                    ['type' => 'text', 'title' => '店内・商品ギャラリー', 'body' => '最新の写真をチェック。'],
                    ['type' => 'gallery', 'source' => 'gallery'],
                ],
            ],
            [
                'id' => 'form',
                'name' => '問い合わせ',
                'blocks' => [
                    ['type' => 'text', 'title' => 'お問い合わせ', 'body' => 'ご意見・ご要望をお聞かせください。'],
                    ['type' => 'form', 'title' => '簡単フォーム', 'form_fields' => [
                        ['label' => 'お名前', 'type' => 'text', 'placeholder' => '例: 山田 太郎', 'required' => true],
                        ['label' => 'メール', 'type' => 'email', 'placeholder' => 'example@example.com', 'required' => true],
                        ['label' => '内容', 'type' => 'textarea', 'placeholder' => 'ご用件', 'required' => true],
                    ]],
                ],
            ],
        ];
    }

    public static function defaultLiffDesignerTemplates(): array
    {
        return [
            [
                'id' => 'welcome-home',
                'name' => 'ようこそトップ',
                'category' => 'home',
                'description' => '初回訪問向けの定番トップ構成です。',
                'target_tabs' => ['home'],
                'device_support' => ['line', 'ios', 'android'],
                'blocks' => [
                    ['type' => 'hero', 'title' => 'いらっしゃいませ', 'subtitle' => 'LINEから注文・予約・会員特典までまとめてご利用いただけます。', 'gradient_start' => '#0ea5e9', 'gradient_end' => '#22c55e'],
                    ['type' => 'banner', 'icon' => 'fa-gift', 'text' => '初回登録でドリンククーポンを配布中', 'color' => '#0f766e'],
                    ['type' => 'button', 'text' => 'メニューを見る', 'action' => '#products'],
                    ['type' => 'button', 'text' => '予約する', 'action' => '#reservations', 'color' => '#1d4ed8'],
                ],
            ],
            [
                'id' => 'campaign-slider',
                'name' => 'キャンペーン訴求',
                'category' => 'campaign',
                'description' => '写真を活かして販促情報を伝える構成です。',
                'target_tabs' => ['home', 'announcements'],
                'device_support' => ['line', 'ios', 'android'],
                'blocks' => [
                    ['type' => 'slider', 'slide_height' => 180, 'slides' => [
                        ['image_url' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=1200', 'caption' => '春の新メニュー公開中'],
                        ['image_url' => 'https://images.unsplash.com/photo-1466978913421-dad2ebd01d17?w=1200', 'caption' => 'テイクアウト10%OFF'],
                        ['image_url' => 'https://images.unsplash.com/photo-1498837167922-ddd27525d352?w=1200', 'caption' => '週末限定セット販売'],
                    ]],
                    ['type' => 'heading', 'text' => '注目キャンペーン', 'size' => 'large'],
                    ['type' => 'icon_text', 'rows' => [
                        ['icon' => 'fa-check-circle', 'text' => '会員はポイント2倍デーあり', 'color' => '#06C755'],
                        ['icon' => 'fa-check-circle', 'text' => '予約限定特典クーポン', 'color' => '#06C755'],
                        ['icon' => 'fa-check-circle', 'text' => '新商品は毎週更新', 'color' => '#06C755'],
                    ]],
                ],
            ],
            [
                'id' => 'quick-order',
                'name' => 'クイック注文導線',
                'category' => 'order',
                'description' => '最短導線で注文開始させるための構成です。',
                'target_tabs' => ['home', 'products'],
                'device_support' => ['line', 'ios', 'android'],
                'blocks' => [
                    ['type' => 'heading', 'text' => 'すぐに注文する', 'size' => 'large'],
                    ['type' => 'text', 'text' => '人気カテゴリから選んで最短で注文できます。'],
                    ['type' => 'collection', 'layout' => 'grid', 'columns' => 2, 'items' => [
                        ['title' => '定番メニュー', 'description' => '人気No.1', 'image_url' => 'https://images.unsplash.com/photo-1550547660-d9450f859349?w=600'],
                        ['title' => '季節限定', 'description' => '期間限定', 'image_url' => 'https://images.unsplash.com/photo-1540189549336-e6e99c3679fe?w=600'],
                        ['title' => 'ドリンク', 'description' => 'テイクアウト可', 'image_url' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=600'],
                        ['title' => 'スイーツ', 'description' => '人気急上昇', 'image_url' => 'https://images.unsplash.com/photo-1488477181946-6428a0291777?w=600'],
                    ]],
                    ['type' => 'button', 'text' => '商品一覧へ', 'action' => '#products'],
                ],
            ],
            [
                'id' => 'member-benefits',
                'name' => '会員特典',
                'category' => 'member',
                'description' => '会員登録・会員証導線を強化する構成です。',
                'target_tabs' => ['member', 'home'],
                'device_support' => ['line', 'ios', 'android'],
                'blocks' => [
                    ['type' => 'heading', 'text' => '会員特典のご案内', 'size' => 'large'],
                    ['type' => 'list', 'items' => ['来店ごとにスタンプを獲得', 'ランクアップで特典が増加', '会員限定クーポンを配信']],
                    ['type' => 'button', 'text' => '会員証を確認する', 'action' => '#member'],
                ],
            ],
            [
                'id' => 'brand-story',
                'name' => 'ブランドストーリー',
                'category' => 'story',
                'description' => '店舗紹介・こだわり訴求に向いた構成です。',
                'target_tabs' => ['home', 'gallery'],
                'device_support' => ['line', 'ios', 'android'],
                'blocks' => [
                    ['type' => 'hero', 'title' => '私たちのこだわり', 'subtitle' => '素材・製法・接客への想いをご紹介します。', 'image_url' => 'https://images.unsplash.com/photo-1466978913421-dad2ebd01d17?w=1200'],
                    ['type' => 'card', 'title' => '毎朝仕込み', 'description' => '店内で毎朝丁寧に仕込んだメニューを提供します。', 'image_url' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=1000'],
                    ['type' => 'button', 'text' => 'ギャラリーを見る', 'action' => '#gallery', 'color' => '#0f766e'],
                ],
            ],
            [
                'id' => 'reservation-flow',
                'name' => '予約フローテンプレ',
                'category' => 'reservation',
                'description' => '予約手順と注意点を1画面で伝える構成です。',
                'target_tabs' => ['reservations'],
                'device_support' => ['line', 'ios', 'android'],
                'blocks' => [
                    ['type' => 'heading', 'text' => '予約の流れ', 'size' => 'large'],
                    ['type' => 'list', 'style' => 'number', 'items' => ['希望日時を選択', '人数と連絡先を入力', '内容確認して予約確定']],
                    ['type' => 'banner', 'icon' => 'fa-info-circle', 'text' => '当日の変更はお電話がスムーズです。', 'color' => '#1d4ed8'],
                    ['type' => 'button', 'text' => '予約画面へ進む', 'action' => '#reservations'],
                ],
            ],
            [
                'id' => 'queue-guide',
                'name' => '順番待ちガイド',
                'category' => 'support',
                'description' => '混雑時の受付導線をわかりやすくする構成です。',
                'target_tabs' => ['queue', 'home'],
                'device_support' => ['line', 'ios', 'android'],
                'blocks' => [
                    ['type' => 'heading', 'text' => '順番待ち受付', 'size' => 'large'],
                    ['type' => 'icon_text', 'rows' => [
                        ['icon' => 'fa-user-friends', 'text' => '人数を入力して受付', 'color' => '#0f766e'],
                        ['icon' => 'fa-mobile-alt', 'text' => '通知で呼び出しをお知らせ', 'color' => '#0f766e'],
                        ['icon' => 'fa-check-circle', 'text' => '受付後は現在組数を確認可能', 'color' => '#0f766e'],
                    ]],
                    ['type' => 'button', 'text' => '順番待ちを開始', 'action' => '#queue'],
                ],
            ],
            [
                'id' => 'announcement-compact',
                'name' => 'お知らせ一覧（コンパクト）',
                'category' => 'campaign',
                'description' => '新着情報を簡潔にまとめる軽量レイアウトです。',
                'target_tabs' => ['announcements', 'home'],
                'device_support' => ['line', 'ios', 'android'],
                'blocks' => [
                    ['type' => 'heading', 'text' => '最新のお知らせ', 'size' => 'large'],
                    ['type' => 'banner', 'icon' => 'fa-bell', 'text' => '週替わりキャンペーン更新中', 'color' => '#7c3aed'],
                    ['type' => 'data', 'source' => 'announcements'],
                ],
            ],
            [
                'id' => 'salon-home',
                'name' => '業種別: サロン向けホーム',
                'category' => 'industry',
                'description' => '美容室・サロン向けの予約重視構成です。',
                'target_tabs' => ['home', 'reservations', 'member'],
                'device_support' => ['line', 'ios', 'android'],
                'blocks' => [
                    ['type' => 'hero', 'title' => '次回予約でキレイを継続', 'subtitle' => '担当スタイリストの空き状況を確認できます。', 'gradient_start' => '#f472b6', 'gradient_end' => '#fb7185'],
                    ['type' => 'icon_text', 'rows' => [
                        ['icon' => 'fa-cut', 'text' => '施術履歴から再予約', 'color' => '#db2777'],
                        ['icon' => 'fa-ticket-alt', 'text' => '会員限定クーポンを配信', 'color' => '#db2777'],
                    ]],
                    ['type' => 'button', 'text' => '予約する', 'action' => '#reservations', 'color' => '#db2777'],
                ],
            ],
            [
                'id' => 'takeout-fast-lane',
                'name' => '業種別: テイクアウト高速導線',
                'category' => 'industry',
                'description' => 'テイクアウト注文を最短化する構成です。',
                'target_tabs' => ['home', 'products', 'orders'],
                'device_support' => ['line', 'ios', 'android'],
                'blocks' => [
                    ['type' => 'heading', 'text' => '受け取り時間を選んですぐ注文', 'size' => 'large'],
                    ['type' => 'collection', 'layout' => 'scroll', 'item_width' => 148, 'items' => [
                        ['title' => 'ランチBOX', 'description' => '12:00受取', 'image_url' => 'https://images.unsplash.com/photo-1543332164-6e82f355bad5?w=800'],
                        ['title' => 'パスタセット', 'description' => '12:30受取', 'image_url' => 'https://images.unsplash.com/photo-1621996346565-e3dbc353d2e5?w=800'],
                        ['title' => 'サラダボウル', 'description' => '13:00受取', 'image_url' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=800'],
                    ]],
                    ['type' => 'button', 'text' => '注文を開始', 'action' => '#products', 'color' => '#0ea5e9'],
                ],
            ],
            [
                'id' => 'products-catalog',
                'name' => '商品カタログ（一覧重視）',
                'category' => 'order',
                'description' => '商品一覧を見やすく表示する標準テンプレートです。',
                'target_tabs' => ['products'],
                'device_support' => ['line', 'ios', 'android'],
                'blocks' => [
                    ['type' => 'heading', 'text' => '商品を選ぶ', 'size' => 'large'],
                    ['type' => 'text', 'text' => 'カテゴリから商品を選んでカートに追加してください。'],
                    ['type' => 'data', 'source' => 'products'],
                ],
            ],
            [
                'id' => 'orders-status-board',
                'name' => '注文ステータス（進捗重視）',
                'category' => 'order',
                'description' => '現在注文と履歴を確認しやすいテンプレートです。',
                'target_tabs' => ['orders'],
                'device_support' => ['line', 'ios', 'android'],
                'blocks' => [
                    ['type' => 'heading', 'text' => '注文ステータス', 'size' => 'large'],
                    ['type' => 'banner', 'icon' => 'fa-clock', 'text' => '受取準備完了時に通知します。', 'color' => '#2563eb'],
                    ['type' => 'data', 'source' => 'orders'],
                ],
            ],
            [
                'id' => 'stamps-loyalty-board',
                'name' => 'スタンプ特典（来店促進）',
                'category' => 'member',
                'description' => 'スタンプ進捗と特典案内を中心にしたテンプレートです。',
                'target_tabs' => ['stamps'],
                'device_support' => ['line', 'ios', 'android'],
                'blocks' => [
                    ['type' => 'heading', 'text' => 'スタンプカード', 'size' => 'large'],
                    ['type' => 'icon_text', 'rows' => [
                        ['icon' => 'fa-stamp', 'text' => '来店ごとにスタンプを付与', 'color' => '#06C755'],
                        ['icon' => 'fa-gift', 'text' => '満了で特典クーポンを配布', 'color' => '#f59e0b'],
                    ]],
                    ['type' => 'data', 'source' => 'stamps'],
                ],
            ],
            [
                'id' => 'member-card-focus',
                'name' => '会員証メイン（ポイント重視）',
                'category' => 'member',
                'description' => '会員証・ポイント・ランクの確認に最適化したテンプレートです。',
                'target_tabs' => ['member'],
                'device_support' => ['line', 'ios', 'android'],
                'blocks' => [
                    ['type' => 'heading', 'text' => '会員ページ', 'size' => 'large'],
                    ['type' => 'text', 'text' => '会員証・ポイント・ランクをいつでも確認できます。'],
                    ['type' => 'data', 'source' => 'member_card'],
                ],
            ],
            [
                'id' => 'reservations-quick-booking',
                'name' => '予約導線（最短予約）',
                'category' => 'reservation',
                'description' => '予約手順を簡潔にし、離脱を減らすテンプレートです。',
                'target_tabs' => ['reservations'],
                'device_support' => ['line', 'ios', 'android'],
                'blocks' => [
                    ['type' => 'heading', 'text' => '来店予約', 'size' => 'large'],
                    ['type' => 'list', 'style' => 'number', 'items' => ['日時を選択', '人数を入力', '内容を確認して確定']],
                    ['type' => 'data', 'source' => 'reservations'],
                ],
            ],
            [
                'id' => 'coupon-showcase',
                'name' => 'クーポン訴求（特典重視）',
                'category' => 'campaign',
                'description' => 'クーポン利用を促す販促向けテンプレートです。',
                'target_tabs' => ['coupons'],
                'device_support' => ['line', 'ios', 'android'],
                'blocks' => [
                    ['type' => 'heading', 'text' => '会員限定クーポン', 'size' => 'large'],
                    ['type' => 'icon_text', 'rows' => [
                        ['icon' => 'fa-ticket-alt', 'text' => '毎週新しいクーポンを配信', 'color' => '#7c3aed'],
                        ['icon' => 'fa-bolt', 'text' => '今すぐ使えるクーポンを優先表示', 'color' => '#7c3aed'],
                    ]],
                    ['type' => 'data', 'source' => 'coupons'],
                ],
            ],
            [
                'id' => 'gallery-story',
                'name' => 'ギャラリーストーリー',
                'category' => 'story',
                'description' => '写真と説明で雰囲気を伝えるテンプレートです。',
                'target_tabs' => ['gallery'],
                'device_support' => ['line', 'ios', 'android'],
                'blocks' => [
                    ['type' => 'heading', 'text' => 'ギャラリー', 'size' => 'large'],
                    ['type' => 'text', 'text' => '店内や商品の写真をチェックできます。'],
                    ['type' => 'data', 'source' => 'gallery'],
                ],
            ],
            [
                'id' => 'super-pattern-classic',
                'name' => 'スーパターン: クラシックレストラン',
                'scope' => 'all_tabs',
                'category' => 'super',
                'description' => '温かみのある配色で、予約と会員導線を強化した標準構成です。',
                'target_tabs' => ['home', 'products', 'orders', 'stamps', 'coupons', 'queue', 'reservations', 'member', 'gallery', 'announcements'],
                'device_support' => ['line', 'ios', 'android'],
                'layouts' => self::defaultLiffTabLayouts(),
                'tab_settings' => self::defaultLiffTabSettings(),
                'custom_css' => '',
            ],
            [
                'id' => 'super-pattern-cafe',
                'name' => 'スーパターン: ナチュラルカフェ',
                'scope' => 'all_tabs',
                'category' => 'super',
                'description' => '軽やかな配色と写真訴求を中心にしたカフェ向け構成です。',
                'target_tabs' => ['home', 'products', 'orders', 'stamps', 'coupons', 'queue', 'reservations', 'member', 'gallery', 'announcements'],
                'device_support' => ['line', 'ios', 'android'],
                'layouts' => self::defaultLiffCafePatternLayouts(),
                'tab_settings' => [
                    'home' => ['background_color' => '#f7f6f1'],
                    'products' => ['background_color' => '#f8faf7'],
                    'orders' => ['background_color' => '#f5f8f6'],
                    'stamps' => ['background_color' => '#f4fbf6'],
                    'coupons' => ['background_color' => '#f7faf4'],
                    'queue' => ['background_color' => '#f8f7f2'],
                    'reservations' => ['background_color' => '#f4f9f8'],
                    'member' => ['background_color' => '#f6f8f4'],
                    'gallery' => ['background_color' => '#f9f8f4'],
                    'announcements' => ['background_color' => '#f8f8f6'],
                ],
                'custom_css' => '',
            ],
            [
                'id' => 'super-pattern-fast',
                'name' => 'スーパターン: ファストカジュアル',
                'scope' => 'all_tabs',
                'category' => 'super',
                'description' => '注文速度を最優先にした、強いCTA中心の高速導線です。',
                'target_tabs' => ['home', 'products', 'orders', 'stamps', 'coupons', 'queue', 'reservations', 'member', 'gallery', 'announcements'],
                'device_support' => ['line', 'ios', 'android'],
                'layouts' => self::defaultLiffFastPatternLayouts(),
                'tab_settings' => [
                    'home' => ['background_color' => '#f3f6ff'],
                    'products' => ['background_color' => '#f5f7ff'],
                    'orders' => ['background_color' => '#f3f4f8'],
                    'stamps' => ['background_color' => '#f6f6ff'],
                    'coupons' => ['background_color' => '#fff7f2'],
                    'queue' => ['background_color' => '#f7f7fa'],
                    'reservations' => ['background_color' => '#f2f8ff'],
                    'member' => ['background_color' => '#f5f5f7'],
                    'gallery' => ['background_color' => '#f6f8ff'],
                    'announcements' => ['background_color' => '#f5f7ff'],
                ],
                'custom_css' => '',
            ],
            [
                'id' => 'super-pattern-salon',
                'name' => 'スーパターン: 美容サロン',
                'scope' => 'all_tabs',
                'category' => 'super',
                'description' => '予約導線と会員管理を重視した、サロン・美容室向け構成です。',
                'target_tabs' => ['home', 'products', 'orders', 'stamps', 'coupons', 'queue', 'reservations', 'member', 'gallery', 'announcements'],
                'device_support' => ['line', 'ios', 'android'],
                'layouts' => self::defaultLiffSalonPatternLayouts(),
                'tab_settings' => [
                    'home' => ['background_color' => '#fdf2f8'],
                    'products' => ['background_color' => '#fef7fb'],
                    'orders' => ['background_color' => '#faf5f9'],
                    'stamps' => ['background_color' => '#fdf4fa'],
                    'coupons' => ['background_color' => '#fef6fa'],
                    'queue' => ['background_color' => '#faf6f9'],
                    'reservations' => ['background_color' => '#fdf2f8'],
                    'member' => ['background_color' => '#fef7fb'],
                    'gallery' => ['background_color' => '#fdf5f9'],
                    'announcements' => ['background_color' => '#faf6fa'],
                ],
                'custom_css' => '',
            ],
            [
                'id' => 'super-pattern-retail',
                'name' => 'スーパターン: 小売・物販ショップ',
                'scope' => 'all_tabs',
                'category' => 'super',
                'description' => '商品訴求と会員ロイヤリティを軸にした、物販店向け構成です。',
                'target_tabs' => ['home', 'products', 'orders', 'stamps', 'coupons', 'queue', 'reservations', 'member', 'gallery', 'announcements'],
                'device_support' => ['line', 'ios', 'android'],
                'layouts' => self::defaultLiffRetailPatternLayouts(),
                'tab_settings' => [
                    'home' => ['background_color' => '#f5f3ff'],
                    'products' => ['background_color' => '#f7f5ff'],
                    'orders' => ['background_color' => '#f4f3f9'],
                    'stamps' => ['background_color' => '#f6f5ff'],
                    'coupons' => ['background_color' => '#faf5ff'],
                    'queue' => ['background_color' => '#f5f5f8'],
                    'reservations' => ['background_color' => '#f4f5ff'],
                    'member' => ['background_color' => '#f6f5fa'],
                    'gallery' => ['background_color' => '#f7f6ff'],
                    'announcements' => ['background_color' => '#f5f5fa'],
                ],
                'custom_css' => '',
            ],
            [
                'id' => 'super-pattern-fitness',
                'name' => 'スーパターン: フィットネス・ジム',
                'scope' => 'all_tabs',
                'category' => 'super',
                'description' => '予約管理と会員ステータスを中心にした、ジム・スタジオ向け構成です。',
                'target_tabs' => ['home', 'products', 'orders', 'stamps', 'coupons', 'queue', 'reservations', 'member', 'gallery', 'announcements'],
                'device_support' => ['line', 'ios', 'android'],
                'layouts' => self::defaultLiffFitnessPatternLayouts(),
                'tab_settings' => [
                    'home' => ['background_color' => '#ecfeff'],
                    'products' => ['background_color' => '#f0fdfa'],
                    'orders' => ['background_color' => '#f0f9ff'],
                    'stamps' => ['background_color' => '#ecfdf5'],
                    'coupons' => ['background_color' => '#f0fdfa'],
                    'queue' => ['background_color' => '#f0f9ff'],
                    'reservations' => ['background_color' => '#ecfeff'],
                    'member' => ['background_color' => '#f0fdfa'],
                    'gallery' => ['background_color' => '#ecfeff'],
                    'announcements' => ['background_color' => '#f0f9ff'],
                ],
                'custom_css' => '',
            ],
        ];
    }

    public static function defaultLiffTabLayouts(): array
    {
        return [
            'home' => [
                ['type' => 'hero', 'title' => config('app.name').'へようこそ', 'subtitle' => '注文・予約・会員特典をこの画面でまとめて利用できます。', 'gradient_start' => '#0ea5e9', 'gradient_end' => '#22c55e'],
                ['type' => 'banner', 'icon' => 'fa-bell', 'text' => '本日限定: LINE会員はポイント2倍', 'color' => '#0f766e'],
                ['type' => 'heading', 'text' => 'おすすめメニュー', 'size' => 'large'],
                ['type' => 'collection', 'layout' => 'scroll', 'item_width' => 150, 'item_image_height' => 110, 'items' => [
                    ['title' => 'シグネチャープレート', 'description' => '人気No.1', 'image_url' => 'https://images.unsplash.com/photo-1550547660-d9450f859349?w=800'],
                    ['title' => '季節のサラダ', 'description' => '旬の素材', 'image_url' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=800'],
                    ['title' => '本日のパスタ', 'description' => '限定メニュー', 'image_url' => 'https://images.unsplash.com/photo-1621996346565-e3dbc353d2e5?w=800'],
                ]],
                ['type' => 'button', 'text' => '今すぐ注文する', 'action' => '#products', 'color' => '#06C755'],
                ['type' => 'button', 'text' => '席を予約する', 'action' => '#reservations', 'color' => '#1d4ed8'],
                ['type' => 'data', 'source' => 'announcements'],
            ],
            'products' => [
                ['type' => 'heading', 'text' => '商品一覧', 'size' => 'large'],
                ['type' => 'text', 'text' => 'カテゴリから商品を選択し、カートに追加してください。'],
                ['type' => 'data', 'source' => 'products'],
            ],
            'orders' => [
                ['type' => 'heading', 'text' => '注文状況', 'size' => 'large'],
                ['type' => 'text', 'text' => '注文履歴と現在のステータスを確認できます。'],
                ['type' => 'banner', 'icon' => 'fa-clock', 'text' => '受け取り準備が完了すると通知します', 'color' => '#2563eb'],
                ['type' => 'data', 'source' => 'orders'],
            ],
            'stamps' => [
                ['type' => 'heading', 'text' => 'スタンプカード', 'size' => 'large'],
                ['type' => 'icon_text', 'rows' => [
                    ['icon' => 'fa-stamp', 'text' => '来店ごとにスタンプ獲得', 'color' => '#06C755'],
                    ['icon' => 'fa-gift', 'text' => '満了で特典を獲得', 'color' => '#f59e0b'],
                ]],
                ['type' => 'data', 'source' => 'stamps'],
            ],
            'coupons' => [
                ['type' => 'heading', 'text' => 'クーポン', 'size' => 'large'],
                ['type' => 'banner', 'icon' => 'fa-ticket-alt', 'text' => '会員限定クーポンを配信中', 'color' => '#7c3aed'],
                ['type' => 'text', 'text' => 'ご注文前に利用可能なクーポンをご確認ください。'],
                ['type' => 'data', 'source' => 'coupons'],
            ],
            'queue' => [
                ['type' => 'heading', 'text' => '順番待ち', 'size' => 'large'],
                ['type' => 'text', 'text' => '混雑時は順番待ち受付をご利用ください。'],
                ['type' => 'icon_text', 'rows' => [
                    ['icon' => 'fa-user-check', 'text' => '人数を入力して受付', 'color' => '#0f766e'],
                    ['icon' => 'fa-mobile-alt', 'text' => '呼び出し通知でお知らせ', 'color' => '#0f766e'],
                ]],
                ['type' => 'data', 'source' => 'queue'],
            ],
            'reservations' => [
                ['type' => 'heading', 'text' => '来店予約', 'size' => 'large'],
                ['type' => 'list', 'items' => ['日時を選択', '人数を指定', '内容を確認して確定']],
                ['type' => 'banner', 'icon' => 'fa-calendar-check', 'text' => '予約変更・キャンセルは注文履歴から可能です', 'color' => '#1d4ed8'],
                ['type' => 'data', 'source' => 'reservations'],
            ],
            'member' => [
                ['type' => 'heading', 'text' => '会員ページ', 'size' => 'large'],
                ['type' => 'text', 'text' => '会員証・ポイント・ランクを確認できます。'],
                ['type' => 'icon_text', 'rows' => [
                    ['icon' => 'fa-coins', 'text' => '購入でポイントが貯まります', 'color' => '#d97706'],
                    ['icon' => 'fa-crown', 'text' => 'ランクアップで特典が増えます', 'color' => '#d97706'],
                ]],
                ['type' => 'data', 'source' => 'member_card'],
            ],
            'gallery' => [
                ['type' => 'heading', 'text' => 'ギャラリー', 'size' => 'large'],
                ['type' => 'text', 'text' => '店内・メニュー写真を随時更新しています。'],
                ['type' => 'data', 'source' => 'gallery'],
            ],
            'announcements' => [
                ['type' => 'heading', 'text' => 'お知らせ', 'size' => 'large'],
                ['type' => 'banner', 'icon' => 'fa-bullhorn', 'text' => '営業時間や限定情報を配信しています', 'color' => '#0f766e'],
                ['type' => 'data', 'source' => 'announcements'],
            ],
        ];
    }

    /**
     * 保存済みレイアウトをタブ単位で正規化する。
     *
     * ルール:
     * - タブが空ならデフォルトレイアウトを採用
     * - 既存ブロックは順序を変えずに維持する
     *
     * @param  array<string, mixed>  $layouts
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function ensureLiffTabLayouts(array $layouts): array
    {
        $defaults = self::defaultLiffTabLayouts();

        $normalized = [];
        foreach ($defaults as $tab => $defaultBlocks) {
            $rows = $layouts[$tab] ?? null;
            $blocks = is_array($rows) ? array_values(array_filter($rows, fn ($v) => is_array($v))) : [];

            if ($blocks === []) {
                $normalized[$tab] = $defaultBlocks;

                continue;
            }

            $normalized[$tab] = $blocks;
        }

        return $normalized;
    }

    public static function defaultLiffTabSettings(): array
    {
        return [
            '_global' => [
                'content_padding' => 18,
                'section_gap' => 16,
                'radius_scale' => 'soft',
                'tab_style' => 'default',
            ],
            'home' => ['background_color' => '#f2f9f6'],
            'products' => ['background_color' => '#f7faf9'],
            'orders' => ['background_color' => '#f5f7fa'],
            'stamps' => ['background_color' => '#f8faf5'],
            'coupons' => ['background_color' => '#faf6ff'],
            'queue' => ['background_color' => '#f7f7f7'],
            'reservations' => ['background_color' => '#f4f8ff'],
            'member' => ['background_color' => '#f7f8f9'],
            'gallery' => ['background_color' => '#fafaf8'],
            'announcements' => ['background_color' => '#f6f9ff'],
        ];
    }

    private static function defaultLiffCafePatternLayouts(): array
    {
        $layouts = self::defaultLiffTabLayouts();
        $layouts['home'] = [
            ['type' => 'hero', 'title' => 'Natural Cafe', 'subtitle' => 'ゆったりした時間を、LINEからスマートに。', 'gradient_start' => '#84cc16', 'gradient_end' => '#14b8a6'],
            ['type' => 'heading', 'text' => '今週のおすすめ', 'size' => 'large'],
            ['type' => 'slider', 'slide_height' => 180, 'slides' => [
                ['image_url' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=1200', 'caption' => '季節のハンドドリップ'],
                ['image_url' => 'https://images.unsplash.com/photo-1511920170033-f8396924c348?w=1200', 'caption' => 'スイーツセット'],
            ]],
            ['type' => 'button', 'text' => 'メニューを見る', 'action' => '#products'],
            ['type' => 'data', 'source' => 'announcements'],
        ];
        $layouts['coupons'] = [
            ['type' => 'heading', 'text' => 'カフェクーポン', 'size' => 'large'],
            ['type' => 'banner', 'icon' => 'fa-mug-hot', 'text' => 'モーニング時間帯はドリンク50円OFF', 'color' => '#166534'],
            ['type' => 'data', 'source' => 'coupons'],
        ];

        return $layouts;
    }

    private static function defaultLiffFastPatternLayouts(): array
    {
        $layouts = self::defaultLiffTabLayouts();
        $layouts['home'] = [
            ['type' => 'hero', 'title' => '最短30秒で注文', 'subtitle' => 'ピーク時でも、事前注文で待ち時間を短縮。', 'gradient_start' => '#2563eb', 'gradient_end' => '#ea580c'],
            ['type' => 'button', 'text' => '商品を選ぶ', 'action' => '#products', 'color' => '#ea580c'],
            ['type' => 'button', 'text' => '現在の注文を見る', 'action' => '#orders', 'color' => '#2563eb'],
            ['type' => 'banner', 'icon' => 'fa-bolt', 'text' => '受取時間を選んで、店頭でスムーズ受取', 'color' => '#1d4ed8'],
            ['type' => 'data', 'source' => 'announcements'],
        ];
        $layouts['products'] = [
            ['type' => 'heading', 'text' => 'クイックメニュー', 'size' => 'large'],
            ['type' => 'text', 'text' => '人気順で表示しています。まずはおすすめからどうぞ。'],
            ['type' => 'data', 'source' => 'products'],
        ];
        $layouts['queue'] = [
            ['type' => 'heading', 'text' => '待ち状況', 'size' => 'large'],
            ['type' => 'banner', 'icon' => 'fa-users', 'text' => '呼び出し通知を受け取れます', 'color' => '#0f766e'],
            ['type' => 'data', 'source' => 'queue'],
        ];

        return $layouts;
    }

    private static function defaultLiffSalonPatternLayouts(): array
    {
        $layouts = self::defaultLiffTabLayouts();
        $layouts['home'] = [
            ['type' => 'hero', 'title' => 'あなたのキレイを叶える', 'subtitle' => 'LINEから予約・会員特典をまとめてご利用いただけます。', 'gradient_start' => '#ec4899', 'gradient_end' => '#f472b6'],
            ['type' => 'slider', 'slide_height' => 180, 'slides' => [
                ['image_url' => 'https://images.unsplash.com/photo-1560066984-138dadb4c035?w=1200', 'caption' => '今月のおすすめスタイル'],
                ['image_url' => 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?w=1200', 'caption' => 'カラーキャンペーン実施中'],
            ]],
            ['type' => 'icon_text', 'rows' => [
                ['icon' => 'fa-calendar-check', 'text' => 'LINEから24時間予約可能', 'color' => '#db2777'],
                ['icon' => 'fa-cut', 'text' => '施術履歴からリピート予約', 'color' => '#db2777'],
                ['icon' => 'fa-gift', 'text' => '会員限定の特典を多数ご用意', 'color' => '#db2777'],
            ]],
            ['type' => 'button', 'text' => '今すぐ予約する', 'action' => '#reservations', 'color' => '#db2777'],
            ['type' => 'button', 'text' => '会員証を確認', 'action' => '#member', 'color' => '#9333ea'],
            ['type' => 'data', 'source' => 'announcements'],
        ];
        $layouts['reservations'] = [
            ['type' => 'heading', 'text' => 'ご予約', 'size' => 'large'],
            ['type' => 'banner', 'icon' => 'fa-heart', 'text' => '担当スタイリストの空き状況を確認できます', 'color' => '#db2777'],
            ['type' => 'list', 'style' => 'number', 'items' => ['メニューを選択', '日時を選択', '担当者を指名（任意）', '内容を確認して確定']],
            ['type' => 'data', 'source' => 'reservations'],
        ];
        $layouts['member'] = [
            ['type' => 'heading', 'text' => 'マイページ', 'size' => 'large'],
            ['type' => 'icon_text', 'rows' => [
                ['icon' => 'fa-crown', 'text' => '来店回数に応じてランクアップ', 'color' => '#d97706'],
                ['icon' => 'fa-ticket-alt', 'text' => 'ランク別の限定クーポンを配信', 'color' => '#d97706'],
            ]],
            ['type' => 'data', 'source' => 'member_card'],
        ];
        $layouts['gallery'] = [
            ['type' => 'heading', 'text' => 'スタイルギャラリー', 'size' => 'large'],
            ['type' => 'text', 'text' => '施術例やスタッフのおすすめスタイルをご覧ください。'],
            ['type' => 'data', 'source' => 'gallery'],
        ];

        return $layouts;
    }

    private static function defaultLiffRetailPatternLayouts(): array
    {
        $layouts = self::defaultLiffTabLayouts();
        $layouts['home'] = [
            ['type' => 'hero', 'title' => 'ようこそショップへ', 'subtitle' => 'LINE会員限定のお得な情報をお届けします。', 'gradient_start' => '#7c3aed', 'gradient_end' => '#a855f7'],
            ['type' => 'heading', 'text' => '注目アイテム', 'size' => 'large'],
            ['type' => 'collection', 'layout' => 'scroll', 'item_width' => 150, 'item_image_height' => 130, 'items' => [
                ['title' => '新着アイテム', 'description' => 'NEW', 'image_url' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=800'],
                ['title' => '人気ランキング', 'description' => 'BEST', 'image_url' => 'https://images.unsplash.com/photo-1472851294608-062f824d29cc?w=800'],
                ['title' => 'セール', 'description' => 'SALE', 'image_url' => 'https://images.unsplash.com/photo-1607082349566-187342175e2f?w=800'],
            ]],
            ['type' => 'banner', 'icon' => 'fa-tags', 'text' => 'LINE会員限定セール開催中！', 'color' => '#7c3aed'],
            ['type' => 'button', 'text' => '商品を見る', 'action' => '#products', 'color' => '#7c3aed'],
            ['type' => 'data', 'source' => 'announcements'],
        ];
        $layouts['products'] = [
            ['type' => 'heading', 'text' => '商品カタログ', 'size' => 'large'],
            ['type' => 'text', 'text' => 'カテゴリから商品を探して、カートに追加できます。'],
            ['type' => 'data', 'source' => 'products'],
        ];
        $layouts['member'] = [
            ['type' => 'heading', 'text' => '会員ステータス', 'size' => 'large'],
            ['type' => 'icon_text', 'rows' => [
                ['icon' => 'fa-coins', 'text' => 'お買い物でポイントが貯まる', 'color' => '#7c3aed'],
                ['icon' => 'fa-crown', 'text' => 'ランクアップで割引率UP', 'color' => '#7c3aed'],
                ['icon' => 'fa-birthday-cake', 'text' => 'お誕生日に特別クーポン', 'color' => '#7c3aed'],
            ]],
            ['type' => 'data', 'source' => 'member_card'],
        ];
        $layouts['coupons'] = [
            ['type' => 'heading', 'text' => 'お得なクーポン', 'size' => 'large'],
            ['type' => 'banner', 'icon' => 'fa-percentage', 'text' => '会員ランクに応じたクーポンを毎月配信', 'color' => '#7c3aed'],
            ['type' => 'data', 'source' => 'coupons'],
        ];

        return $layouts;
    }

    private static function defaultLiffFitnessPatternLayouts(): array
    {
        $layouts = self::defaultLiffTabLayouts();
        $layouts['home'] = [
            ['type' => 'hero', 'title' => '今日もトレーニング日和', 'subtitle' => 'LINEからレッスン予約・会員情報をまとめて管理。', 'gradient_start' => '#0891b2', 'gradient_end' => '#06b6d4'],
            ['type' => 'icon_text', 'rows' => [
                ['icon' => 'fa-dumbbell', 'text' => 'レッスン予約は24時間OK', 'color' => '#0891b2'],
                ['icon' => 'fa-chart-line', 'text' => '来館回数をトラッキング', 'color' => '#0891b2'],
                ['icon' => 'fa-medal', 'text' => '来館ポイントで特典獲得', 'color' => '#0891b2'],
            ]],
            ['type' => 'button', 'text' => 'レッスンを予約する', 'action' => '#reservations', 'color' => '#0891b2'],
            ['type' => 'button', 'text' => '会員証を表示', 'action' => '#member', 'color' => '#059669'],
            ['type' => 'data', 'source' => 'announcements'],
        ];
        $layouts['reservations'] = [
            ['type' => 'heading', 'text' => 'レッスン予約', 'size' => 'large'],
            ['type' => 'text', 'text' => '空きのあるレッスンを選んで予約してください。'],
            ['type' => 'banner', 'icon' => 'fa-clock', 'text' => 'キャンセルは開始2時間前まで可能です', 'color' => '#0891b2'],
            ['type' => 'data', 'source' => 'reservations'],
        ];
        $layouts['member'] = [
            ['type' => 'heading', 'text' => '会員情報', 'size' => 'large'],
            ['type' => 'icon_text', 'rows' => [
                ['icon' => 'fa-id-card', 'text' => '会員証をスマホで提示', 'color' => '#0891b2'],
                ['icon' => 'fa-fire', 'text' => '今月の来館回数を確認', 'color' => '#0891b2'],
            ]],
            ['type' => 'data', 'source' => 'member_card'],
        ];
        $layouts['stamps'] = [
            ['type' => 'heading', 'text' => '来館ポイント', 'size' => 'large'],
            ['type' => 'icon_text', 'rows' => [
                ['icon' => 'fa-check-circle', 'text' => '来館ごとにスタンプ獲得', 'color' => '#059669'],
                ['icon' => 'fa-gift', 'text' => '満了でプロテインなどの特典', 'color' => '#059669'],
            ]],
            ['type' => 'data', 'source' => 'stamps'],
        ];

        return $layouts;
    }
}

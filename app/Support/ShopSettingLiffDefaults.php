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
                'id' => 'flagship-dashboard',
                'name' => 'フラッグシップ・ダッシュボード',
                'category' => 'home',
                'description' => '会員カード・ショートカット・ヒーロー・おすすめ棚を備えた、本格店舗アプリの定番ホーム構成です。',
                'target_tabs' => ['home'],
                'device_support' => ['line', 'ios', 'android'],
                'theme' => [
                    'theme_preset' => 'noir',
                    'background_style' => 'solid',
                    'bg_color' => '#f7f6f2',
                    'content_width' => 'comfy',
                    'card_style' => 'elevated',
                    'button_shape' => 'pill',
                    'density' => 'default',
                ],
                'blocks' => [
                    ['type' => 'member_summary', 'design' => 'wallet'],
                    ['type' => 'quick_actions', 'design' => 'pill', 'actions' => [
                        ['icon' => 'fa-utensils', 'label' => '注文', 'action' => '#products'],
                        ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
                        ['icon' => 'fa-calendar-alt', 'label' => '予約', 'action' => '#reservations'],
                        ['icon' => 'fa-id-card', 'label' => '会員証', 'action' => '#member'],
                    ]],
                    ['type' => 'hero', 'design' => 'editorial', 'title' => '本日のスペシャル', 'subtitle' => 'シェフ厳選の季節メニューを、いつでもスマートに。', 'image_url' => 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=1400&q=80'],
                    ['type' => 'banner', 'design' => 'pill', 'icon' => 'fa-bolt', 'text' => 'LINE会員限定 — 本日ポイント2倍デー', 'color' => '#1c1917', 'text_color' => '#ffffff'],
                    ['type' => 'collection', 'section_label' => 'Pick Up', 'layout' => 'scroll', 'item_image_height' => 120, 'items' => [
                        ['title' => 'シグネチャープレート', 'description' => '人気No.1', 'badge' => 'BEST', 'image_url' => 'https://images.unsplash.com/photo-1550547660-d9450f859349?w=800'],
                        ['title' => '季節のサラダ', 'description' => '旬の素材', 'badge' => 'NEW', 'image_url' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=800'],
                        ['title' => '本日のパスタ', 'description' => '限定', 'image_url' => 'https://images.unsplash.com/photo-1473093295043-cdd812d0e601?w=800'],
                    ]],
                    ['type' => 'button', 'text' => '今すぐ注文する', 'action' => '#products', 'design' => 'cta'],
                    ['type' => 'data', 'source' => 'announcements'],
                ],
            ],
            [
                'id' => 'welcome-home',
                'name' => 'ようこそトップ',
                'category' => 'home',
                'description' => '初回訪問向け。ヒーロー訴求と会員登録導線をバランスよく配置します。',
                'target_tabs' => ['home'],
                'device_support' => ['line', 'ios', 'android'],
                'theme' => [
                    'theme_preset' => 'ocean',
                    'background_style' => 'gradient',
                    'bg_gradient_start' => '#f0f9ff',
                    'bg_gradient_end' => '#ecfeff',
                    'content_width' => 'comfy',
                ],
                'blocks' => [
                    ['type' => 'hero', 'title' => 'いらっしゃいませ', 'subtitle' => 'LINEから注文・予約・会員特典まで、ひとつのアプリで。', 'image_url' => 'https://images.unsplash.com/photo-1559339352-11d035aa65de?w=1400&q=80'],
                    ['type' => 'member_summary'],
                    ['type' => 'banner', 'icon' => 'fa-gift', 'text' => '初回登録でドリンククーポンをプレゼント', 'color' => '#0c4a6e', 'text_color' => '#ffffff'],
                    ['type' => 'quick_actions', 'actions' => [
                        ['icon' => 'fa-utensils', 'label' => 'メニュー', 'action' => '#products'],
                        ['icon' => 'fa-calendar-check', 'label' => '予約', 'action' => '#reservations'],
                        ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
                        ['icon' => 'fa-user-plus', 'label' => '会員登録', 'action' => '#member'],
                    ]],
                    ['type' => 'button', 'text' => 'メニューを見る', 'action' => '#products'],
                    ['type' => 'button', 'text' => '会員になる', 'action' => '#member', 'color' => '#0369a1'],
                ],
            ],
            [
                'id' => 'campaign-slider',
                'name' => 'キャンペーン訴求',
                'category' => 'campaign',
                'description' => 'フルブリードスライダーと特典リストで、販促を印象的に伝えます。',
                'target_tabs' => ['home', 'announcements'],
                'device_support' => ['line', 'ios', 'android'],
                'theme' => [
                    'theme_preset' => 'bordeaux',
                    'background_style' => 'image',
                    'bg_image_url' => 'https://images.unsplash.com/photo-1466978913421-dad2ebd01d17?w=1600&q=80',
                    'bg_overlay_opacity' => 68,
                    'content_width' => 'comfy',
                    'card_style' => 'elevated',
                ],
                'blocks' => [
                    ['type' => 'slider', 'section_label' => 'Campaign', 'slide_height' => 210, 'slides' => [
                        ['image_url' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=1200', 'caption' => '春の新メニュー — 期間限定'],
                        ['image_url' => 'https://images.unsplash.com/photo-1466978913421-dad2ebd01d17?w=1200', 'caption' => 'テイクアウト10%OFF'],
                        ['image_url' => 'https://images.unsplash.com/photo-1498837167922-ddd27525d352?w=1200', 'caption' => '週末限定セット'],
                    ]],
                    ['type' => 'banner', 'icon' => 'fa-star', 'text' => '会員ランクに応じた追加特典あり', 'color' => '#9f1239', 'text_color' => '#ffffff'],
                    ['type' => 'heading', 'text' => '今週のハイライト', 'size' => 'large'],
                    ['type' => 'icon_text', 'rows' => [
                        ['icon' => 'fa-check-circle', 'text' => '会員はポイント2倍デーあり', 'color' => '#9f1239'],
                        ['icon' => 'fa-check-circle', 'text' => '予約限定特典クーポン', 'color' => '#9f1239'],
                        ['icon' => 'fa-check-circle', 'text' => '新商品は毎週更新', 'color' => '#9f1239'],
                    ]],
                    ['type' => 'button', 'text' => 'クーポンを確認', 'action' => '#coupons'],
                ],
            ],
            [
                'id' => 'quick-order',
                'name' => 'クイック注文導線',
                'category' => 'order',
                'description' => 'カテゴリ棚と強いCTAで、最短の注文導線を構築します。',
                'target_tabs' => ['home', 'products'],
                'device_support' => ['line', 'ios', 'android'],
                'theme' => [
                    'theme_preset' => 'citrus',
                    'background_style' => 'gradient',
                    'bg_gradient_start' => '#fff7ed',
                    'bg_gradient_end' => '#ffedd5',
                    'content_width' => 'edge',
                    'button_shape' => 'pill',
                ],
                'blocks' => [
                    ['type' => 'heading', 'text' => 'すぐに注文する', 'size' => 'large'],
                    ['type' => 'text', 'text' => '人気カテゴリから選んで、最短3タップで注文完了。'],
                    ['type' => 'collection', 'section_label' => 'Quick Pick', 'layout' => 'grid', 'columns' => 2, 'item_image_height' => 130, 'items' => [
                        ['title' => '定番メニュー', 'description' => '人気No.1', 'badge' => 'HOT', 'image_url' => 'https://images.unsplash.com/photo-1550547660-d9450f859349?w=600'],
                        ['title' => '季節限定', 'description' => '期間限定', 'badge' => 'NEW', 'image_url' => 'https://images.unsplash.com/photo-1540189549336-e6e99c3679fe?w=600'],
                        ['title' => 'ドリンク', 'description' => 'テイクアウト可', 'image_url' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=600'],
                        ['title' => 'スイーツ', 'description' => '人気急上昇', 'image_url' => 'https://images.unsplash.com/photo-1488477181946-6428a0291777?w=600'],
                    ]],
                    ['type' => 'banner', 'icon' => 'fa-clock', 'text' => '受取時間を選んで、店頭でスムーズ受取', 'color' => '#ea580c', 'text_color' => '#ffffff'],
                    ['type' => 'button', 'text' => '商品一覧へ進む', 'action' => '#products', 'color' => '#ea580c'],
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
                'description' => 'エディトリアルヒーローとカードで、店舗の世界観を伝えます。',
                'target_tabs' => ['home', 'gallery'],
                'device_support' => ['line', 'ios', 'android'],
                'theme' => [
                    'theme_preset' => 'terracotta',
                    'background_style' => 'solid',
                    'bg_color' => '#faf5ee',
                    'content_width' => 'comfy',
                    'font_family' => 'serif-display',
                    'card_style' => 'elevated',
                ],
                'blocks' => [
                    ['type' => 'hero', 'title' => '私たちのこだわり', 'subtitle' => '素材・製法・接客への想いを、ひとつひとつ丁寧に。', 'image_url' => 'https://images.unsplash.com/photo-1466978913421-dad2ebd01d17?w=1400&q=80'],
                    ['type' => 'collection', 'section_label' => 'Story', 'layout' => 'scroll', 'item_image_height' => 150, 'items' => [
                        ['title' => '毎朝仕込み', 'description' => 'Fresh daily', 'image_url' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=1000'],
                        ['title' => '地元の食材', 'description' => 'Local farm', 'image_url' => 'https://images.unsplash.com/photo-1542838132-92c53300491e?w=1000'],
                        ['title' => '職人の技', 'description' => 'Craft', 'image_url' => 'https://images.unsplash.com/photo-1556910103-1c02745aae4d?w=1000'],
                    ]],
                    ['type' => 'card', 'title' => 'シェフからのメッセージ', 'description' => 'お客様に「また来たい」と思っていただける体験を届けます。', 'image_url' => 'https://images.unsplash.com/photo-1577219491135-ce391730fb2c?w=1000', 'image_height' => 160],
                    ['type' => 'button', 'text' => 'ギャラリーを見る', 'action' => '#gallery', 'color' => '#c2410c'],
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
                'description' => '予約・スタイル訴求を中心にした、美容サロン向けダッシュボードです。',
                'target_tabs' => ['home', 'reservations', 'member'],
                'device_support' => ['line', 'ios', 'android'],
                'theme' => [
                    'theme_preset' => 'lavender',
                    'background_style' => 'gradient',
                    'bg_gradient_start' => '#fdf2f8',
                    'bg_gradient_end' => '#faf5ff',
                    'content_width' => 'comfy',
                ],
                'blocks' => [
                    ['type' => 'member_summary'],
                    ['type' => 'hero', 'title' => '次回予約でキレイを継続', 'subtitle' => '担当スタイリストの空き状況を、いつでもチェック。', 'image_url' => 'https://images.unsplash.com/photo-1560066984-138dadb4c035?w=1400&q=80'],
                    ['type' => 'slider', 'section_label' => 'Style', 'slide_height' => 190, 'slides' => [
                        ['image_url' => 'https://images.unsplash.com/photo-1560066984-138dadb4c035?w=1200', 'caption' => '今月のおすすめスタイル'],
                        ['image_url' => 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?w=1200', 'caption' => 'カラーキャンペーン実施中'],
                    ]],
                    ['type' => 'quick_actions', 'actions' => [
                        ['icon' => 'fa-calendar-check', 'label' => '予約', 'action' => '#reservations'],
                        ['icon' => 'fa-cut', 'label' => 'メニュー', 'action' => '#products'],
                        ['icon' => 'fa-images', 'label' => 'ギャラリー', 'action' => '#gallery'],
                        ['icon' => 'fa-id-card', 'label' => '会員証', 'action' => '#member'],
                    ]],
                    ['type' => 'button', 'text' => '今すぐ予約する', 'action' => '#reservations', 'color' => '#db2777'],
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
                'id' => 'home-night-lounge',
                'name' => 'ナイトラウンジ',
                'category' => 'home',
                'description' => 'ダークトーンとゴールドアクセントで上質な夜の店舗体験を演出します。',
                'target_tabs' => ['home'],
                'device_support' => ['line', 'ios', 'android'],
                'theme' => [
                    'theme_preset' => 'mono-night',
                    'background_style' => 'gradient',
                    'bg_gradient_start' => '#1c1917',
                    'bg_gradient_end' => '#292524',
                    'content_width' => 'comfy',
                    'card_style' => 'elevated',
                    'button_shape' => 'pill',
                ],
                'blocks' => [
                    ['type' => 'hero', 'title' => 'Tonight', 'subtitle' => '特別なひとときを、あなただけの席で。', 'image_url' => 'https://images.unsplash.com/photo-1514933651103-005eec06c04b?w=1400&q=80'],
                    ['type' => 'member_summary'],
                    ['type' => 'quick_actions', 'actions' => [
                        ['icon' => 'fa-calendar-alt', 'label' => '予約', 'action' => '#reservations'],
                        ['icon' => 'fa-utensils', 'label' => 'メニュー', 'action' => '#products'],
                        ['icon' => 'fa-ticket-alt', 'label' => '特典', 'action' => '#coupons'],
                        ['icon' => 'fa-id-card', 'label' => '会員', 'action' => '#member'],
                    ]],
                    ['type' => 'banner', 'icon' => 'fa-star', 'text' => '会員ランク限定 — 本日のシェフおまかせコース', 'color' => '#1c1917', 'text_color' => '#fbbf24'],
                    ['type' => 'collection', 'section_label' => 'Tonight\'s Pick', 'layout' => 'scroll', 'item_image_height' => 130, 'items' => [
                        ['title' => 'シグネチャーカクテル', 'description' => 'Bar Select', 'badge' => 'NEW', 'image_url' => 'https://images.unsplash.com/photo-1514362545857-3bc16c4c7d42?w=800'],
                        ['title' => 'おつまみプレート', 'description' => 'Chef\'s', 'image_url' => 'https://images.unsplash.com/photo-1559339352-11d035aa65de?w=800'],
                    ]],
                    ['type' => 'button', 'text' => '席を予約する', 'action' => '#reservations', 'color' => '#d97706'],
                ],
            ],
            [
                'id' => 'home-bakery-morning',
                'name' => 'ベーカリー・モーニング',
                'category' => 'home',
                'description' => '焼きたてパンとコーヒーの温かみを伝える、朝の店舗向けホームです。',
                'target_tabs' => ['home'],
                'device_support' => ['line', 'ios', 'android'],
                'theme' => [
                    'theme_preset' => 'terracotta',
                    'background_style' => 'solid',
                    'bg_color' => '#faf5ee',
                    'content_width' => 'comfy',
                    'card_style' => 'outline',
                    'button_shape' => 'rounded',
                ],
                'blocks' => [
                    ['type' => 'hero', 'title' => '焼きたての朝を', 'subtitle' => '毎朝7時から、店内で香り立つ自家製パン。', 'image_url' => 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=1400&q=80'],
                    ['type' => 'banner', 'icon' => 'fa-bread-slice', 'text' => '本日のおすすめ — 塩バターロール 残りわずか', 'color' => '#c2410c', 'text_color' => '#ffffff'],
                    ['type' => 'collection', 'section_label' => 'Fresh Today', 'layout' => 'grid', 'columns' => 2, 'item_image_height' => 110, 'items' => [
                        ['title' => 'クロワッサン', 'description' => '¥280', 'image_url' => 'https://images.unsplash.com/photo-1555507036-ab1f4038808a?w=600'],
                        ['title' => '食パン', 'description' => '¥420', 'image_url' => 'https://images.unsplash.com/photo-1549931319-a545dcf3bc73?w=600'],
                        ['title' => 'デニッシュ', 'description' => '¥350', 'image_url' => 'https://images.unsplash.com/photo-1486427944299-d1955d23e34d?w=600'],
                        ['title' => 'マフィン', 'description' => '¥320', 'image_url' => 'https://images.unsplash.com/photo-1607925796997-5d59fbfbc4d6?w=600'],
                    ]],
                    ['type' => 'quick_actions', 'actions' => [
                        ['icon' => 'fa-shopping-bag', 'label' => '注文', 'action' => '#products'],
                        ['icon' => 'fa-stamp', 'label' => 'スタンプ', 'action' => '#stamps'],
                        ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
                        ['icon' => 'fa-id-card', 'label' => '会員', 'action' => '#member'],
                    ]],
                    ['type' => 'button', 'text' => 'メニューを見る', 'action' => '#products', 'color' => '#c2410c'],
                ],
            ],
            [
                'id' => 'home-fitness-energy',
                'name' => 'フィットネス・エナジー',
                'category' => 'home',
                'description' => 'レッスン予約と来館記録を前面に出したジム向けダッシュボードです。',
                'target_tabs' => ['home'],
                'device_support' => ['line', 'ios', 'android'],
                'theme' => [
                    'theme_preset' => 'ocean',
                    'background_style' => 'gradient',
                    'bg_gradient_start' => '#ecfeff',
                    'bg_gradient_end' => '#f0fdfa',
                    'content_width' => 'edge',
                    'button_shape' => 'pill',
                ],
                'blocks' => [
                    ['type' => 'member_summary'],
                    ['type' => 'hero', 'title' => '今日も一歩前へ', 'subtitle' => 'レッスン予約・来館チェックインがスマホで完結。', 'image_url' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=1400&q=80'],
                    ['type' => 'quick_actions', 'actions' => [
                        ['icon' => 'fa-calendar-check', 'label' => '予約', 'action' => '#reservations'],
                        ['icon' => 'fa-dumbbell', 'label' => 'レッスン', 'action' => '#products'],
                        ['icon' => 'fa-stamp', 'label' => '来館記録', 'action' => '#stamps'],
                        ['icon' => 'fa-id-card', 'label' => '会員証', 'action' => '#member'],
                    ]],
                    ['type' => 'slider', 'section_label' => 'Programs', 'slide_height' => 180, 'slides' => [
                        ['image_url' => 'https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?w=1200', 'caption' => 'パーソナルトレーニング'],
                        ['image_url' => 'https://images.unsplash.com/photo-1518611012118-696072aa579a?w=1200', 'caption' => 'グループレッスン'],
                    ]],
                    ['type' => 'button', 'text' => 'レッスンを予約', 'action' => '#reservations', 'color' => '#0891b2'],
                ],
            ],
            [
                'id' => 'products-premium-menu',
                'name' => 'プレミアムメニュー',
                'category' => 'order',
                'description' => 'カテゴリヒーローと商品一覧で、飲食店のメニュー体験を高めます。',
                'target_tabs' => ['products'],
                'device_support' => ['line', 'ios', 'android'],
                'theme' => [
                    'theme_preset' => 'bordeaux',
                    'background_style' => 'solid',
                    'bg_color' => '#fafaf9',
                    'content_width' => 'comfy',
                ],
                'blocks' => [
                    ['type' => 'hero', 'title' => 'Season Menu', 'subtitle' => '旬の食材を使った今季のおすすめ。', 'image_url' => 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=1400&q=80'],
                    ['type' => 'heading', 'text' => 'カテゴリから選ぶ', 'size' => 'large'],
                    ['type' => 'collection', 'section_label' => 'Category', 'layout' => 'scroll', 'item_image_height' => 100, 'items' => [
                        ['title' => '前菜', 'description' => 'Appetizer', 'image_url' => 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=600'],
                        ['title' => 'メイン', 'description' => 'Main', 'image_url' => 'https://images.unsplash.com/photo-1550547660-d9450f859349?w=600'],
                        ['title' => 'ドリンク', 'description' => 'Drink', 'image_url' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=600'],
                    ]],
                    ['type' => 'data', 'source' => 'products'],
                ],
            ],
            [
                'id' => 'coupons-wallet-style',
                'name' => 'クーポンウォレット',
                'category' => 'campaign',
                'description' => 'クーポンを財布のように見せる、利用促進向けテンプレートです。',
                'target_tabs' => ['coupons', 'home'],
                'device_support' => ['line', 'ios', 'android'],
                'theme' => [
                    'theme_preset' => 'lavender',
                    'background_style' => 'gradient',
                    'bg_gradient_start' => '#faf5ff',
                    'bg_gradient_end' => '#fdf2f8',
                    'content_width' => 'comfy',
                    'card_style' => 'elevated',
                ],
                'blocks' => [
                    ['type' => 'member_summary'],
                    ['type' => 'banner', 'icon' => 'fa-ticket-alt', 'text' => 'あなたに届いたクーポンをチェック', 'color' => '#7c3aed', 'text_color' => '#ffffff'],
                    ['type' => 'heading', 'text' => '利用可能なクーポン', 'size' => 'large'],
                    ['type' => 'icon_text', 'rows' => [
                        ['icon' => 'fa-bolt', 'text' => 'タップするだけで店頭利用OK', 'color' => '#7c3aed'],
                        ['icon' => 'fa-clock', 'text' => '期限が近いクーポンを優先表示', 'color' => '#7c3aed'],
                    ]],
                    ['type' => 'data', 'source' => 'coupons'],
                ],
            ],
            [
                'id' => 'member-vip-lounge',
                'name' => 'VIPラウンジ',
                'category' => 'member',
                'description' => 'ランク・ポイント・特典を贅沢に見せる会員向けテンプレートです。',
                'target_tabs' => ['member'],
                'device_support' => ['line', 'ios', 'android'],
                'theme' => [
                    'theme_preset' => 'noir',
                    'background_style' => 'solid',
                    'bg_color' => '#f7f6f2',
                    'content_width' => 'narrow',
                    'card_style' => 'elevated',
                    'button_shape' => 'pill',
                ],
                'blocks' => [
                    ['type' => 'hero', 'title' => 'Member Lounge', 'subtitle' => 'あなたのランクに応じた特典をお届けします。', 'image_url' => 'https://images.unsplash.com/photo-1560066984-138dadb4c035?w=1400&q=80'],
                    ['type' => 'member_summary'],
                    ['type' => 'card', 'title' => 'ランク特典', 'description' => 'ゴールド会員以上は毎月限定クーポンを配信。', 'image_url' => 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=800', 'image_height' => 140],
                    ['type' => 'quick_actions', 'actions' => [
                        ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
                        ['icon' => 'fa-stamp', 'label' => 'スタンプ', 'action' => '#stamps'],
                        ['icon' => 'fa-receipt', 'label' => '注文履歴', 'action' => '#orders'],
                        ['icon' => 'fa-cog', 'label' => '設定', 'action' => '#member'],
                    ]],
                    ['type' => 'data', 'source' => 'member_card'],
                ],
            ],
            [
                'id' => 'stamp-gamified',
                'name' => 'スタンプ・コレクト',
                'category' => 'member',
                'description' => '来店スタンプを楽しく集める、ゲーミフィケーション向け構成です。',
                'target_tabs' => ['stamps', 'home'],
                'device_support' => ['line', 'ios', 'android'],
                'theme' => [
                    'theme_preset' => 'citrus',
                    'background_style' => 'gradient',
                    'bg_gradient_start' => '#fff7ed',
                    'bg_gradient_end' => '#ffedd5',
                    'content_width' => 'comfy',
                ],
                'blocks' => [
                    ['type' => 'banner', 'icon' => 'fa-stamp', 'text' => 'あと3個で特典クーポン獲得！', 'color' => '#ea580c', 'text_color' => '#ffffff'],
                    ['type' => 'heading', 'text' => 'スタンプカード', 'size' => 'large'],
                    ['type' => 'text', 'text' => '来店のたびにスタンプを押して、特典をゲットしましょう。'],
                    ['type' => 'data', 'source' => 'stamps'],
                    ['type' => 'button', 'text' => 'クーポンを見る', 'action' => '#coupons', 'color' => '#ea580c'],
                ],
            ],
            [
                'id' => 'queue-live-board',
                'name' => '順番待ちライブボード',
                'category' => 'support',
                'description' => '待ち状況をリアルタイム感で伝える順番待ち向けテンプレートです。',
                'target_tabs' => ['queue', 'home'],
                'device_support' => ['line', 'ios', 'android'],
                'theme' => [
                    'theme_preset' => 'emerald',
                    'background_style' => 'solid',
                    'bg_color' => '#f0fdf4',
                    'content_width' => 'comfy',
                ],
                'blocks' => [
                    ['type' => 'heading', 'text' => '現在の待ち状況', 'size' => 'large'],
                    ['type' => 'banner', 'icon' => 'fa-users', 'text' => '待ち組数はリアルタイムで更新されます', 'color' => '#059669', 'text_color' => '#ffffff'],
                    ['type' => 'icon_text', 'rows' => [
                        ['icon' => 'fa-mobile-alt', 'text' => '受付後は呼び出しをプッシュ通知', 'color' => '#059669'],
                        ['icon' => 'fa-clock', 'text' => '目安待ち時間を画面で確認', 'color' => '#059669'],
                    ]],
                    ['type' => 'data', 'source' => 'queue'],
                    ['type' => 'button', 'text' => '順番待ちを受付ける', 'action' => '#queue', 'color' => '#059669'],
                ],
            ],
            [
                'id' => 'announcements-editorial',
                'name' => 'お知らせ・エディトリアル',
                'category' => 'story',
                'description' => '雑誌風のレイアウトでお知らせを印象的に届けます。',
                'target_tabs' => ['announcements', 'home'],
                'device_support' => ['line', 'ios', 'android'],
                'theme' => [
                    'theme_preset' => 'royal',
                    'background_style' => 'solid',
                    'bg_color' => '#f8fafc',
                    'font_family' => 'serif-display',
                    'content_width' => 'narrow',
                ],
                'blocks' => [
                    ['type' => 'hero', 'title' => 'News & Stories', 'subtitle' => '店舗からの最新情報をお届けします。', 'image_url' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=1400&q=80'],
                    ['type' => 'heading', 'text' => '最新のお知らせ', 'size' => 'large'],
                    ['type' => 'data', 'source' => 'announcements'],
                ],
            ],
            [
                'id' => 'home-ramen-shop',
                'name' => 'ラーメン店・看板メニュー',
                'category' => 'industry',
                'description' => '看板メニューと注文導線を強調した飲食店向けホームです。',
                'target_tabs' => ['home', 'products'],
                'device_support' => ['line', 'ios', 'android'],
                'theme' => [
                    'theme_preset' => 'matcha',
                    'background_style' => 'solid',
                    'bg_color' => '#fafaf9',
                    'content_width' => 'comfy',
                    'button_shape' => 'pill',
                ],
                'blocks' => [
                    ['type' => 'hero', 'title' => '本日のおすすめ', 'subtitle' => '旨味濃厚スープと自家製麺の一杯を。', 'image_url' => 'https://images.unsplash.com/photo-1569718212165-3a8278d5f624?w=1400&q=80'],
                    ['type' => 'collection', 'section_label' => '人気メニュー', 'layout' => 'scroll', 'item_image_height' => 120, 'items' => [
                        ['title' => '醤油ラーメン', 'description' => '¥880', 'badge' => '人気', 'image_url' => 'https://images.unsplash.com/photo-1617093727343-374698b1d08c?w=800'],
                        ['title' => '味噌ラーメン', 'description' => '¥920', 'image_url' => 'https://images.unsplash.com/photo-1598866594230-a7c1c2f4c36b?w=800'],
                        ['title' => 'つけ麺', 'description' => '¥950', 'badge' => 'NEW', 'image_url' => 'https://images.unsplash.com/photo-1569718212165-3a8278d5f624?w=800'],
                    ]],
                    ['type' => 'quick_actions', 'actions' => [
                        ['icon' => 'fa-utensils', 'label' => '注文', 'action' => '#products'],
                        ['icon' => 'fa-users', 'label' => '順番待ち', 'action' => '#queue'],
                        ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
                        ['icon' => 'fa-id-card', 'label' => '会員', 'action' => '#member'],
                    ]],
                    ['type' => 'button', 'text' => '今すぐ注文', 'action' => '#products', 'color' => '#3f6212'],
                ],
            ],
            [
                'id' => 'home-retail-showcase',
                'name' => '物販ショーケース',
                'category' => 'industry',
                'description' => '新着・ランキング・会員特典を並べた小売向けホームです。',
                'target_tabs' => ['home'],
                'device_support' => ['line', 'ios', 'android'],
                'theme' => [
                    'theme_preset' => 'noir',
                    'background_style' => 'solid',
                    'bg_color' => '#f7f6f2',
                    'content_width' => 'edge',
                    'card_style' => 'elevated',
                ],
                'blocks' => [
                    ['type' => 'member_summary'],
                    ['type' => 'slider', 'section_label' => 'New Arrival', 'slide_height' => 200, 'slides' => [
                        ['image_url' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=1200', 'caption' => '春の新作コレクション'],
                        ['image_url' => 'https://images.unsplash.com/photo-1483985988350-763728e1935b?w=1200', 'caption' => '会員限定セール'],
                    ]],
                    ['type' => 'collection', 'section_label' => 'Ranking', 'layout' => 'scroll', 'item_image_height' => 130, 'items' => [
                        ['title' => 'ベストセラー1位', 'description' => '¥4,980', 'badge' => 'No.1', 'image_url' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=800'],
                        ['title' => '限定カラー', 'description' => '¥3,280', 'image_url' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=800'],
                    ]],
                    ['type' => 'banner', 'icon' => 'fa-gift', 'text' => '会員ランクアップで送料無料', 'color' => '#1c1917', 'text_color' => '#ffffff'],
                    ['type' => 'button', 'text' => '商品を見る', 'action' => '#products'],
                ],
            ],
            [
                'id' => 'home-izakaya-night',
                'name' => '居酒屋・夜のひととき',
                'category' => 'industry',
                'description' => 'コース料理とドリンク訴求の居酒屋向けホームです。',
                'target_tabs' => ['home'],
                'device_support' => ['line', 'ios', 'android'],
                'theme' => [
                    'theme_preset' => 'bordeaux',
                    'background_style' => 'gradient',
                    'bg_gradient_start' => '#1c1917',
                    'bg_gradient_end' => '#44403c',
                    'content_width' => 'comfy',
                ],
                'blocks' => [
                    ['type' => 'hero', 'title' => '今夜のおすすめ', 'subtitle' => '旬の刺身と焼き鳥、日本酒のペアリングを。', 'image_url' => 'https://images.unsplash.com/photo-1559339352-11d035aa65de?w=1400&q=80'],
                    ['type' => 'card', 'title' => '飲み放題プラン', 'description' => '2時間 ¥2,980 — 生ビール・ハイボール・サワー', 'image_url' => 'https://images.unsplash.com/photo-1514362545857-3bc16c4c7d42?w=800', 'image_height' => 140],
                    ['type' => 'quick_actions', 'actions' => [
                        ['icon' => 'fa-calendar-alt', 'label' => '予約', 'action' => '#reservations'],
                        ['icon' => 'fa-utensils', 'label' => 'メニュー', 'action' => '#products'],
                        ['icon' => 'fa-users', 'label' => '順番待ち', 'action' => '#queue'],
                        ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
                    ]],
                    ['type' => 'button', 'text' => '席を予約する', 'action' => '#reservations', 'color' => '#9f1239'],
                ],
            ],
            [
                'id' => 'reservations-spa-relax',
                'name' => 'スパ・リラクゼーション予約',
                'category' => 'reservation',
                'description' => '癒やし系サロン向け。コース紹介と予約導線を一体化します。',
                'target_tabs' => ['reservations', 'home'],
                'device_support' => ['line', 'ios', 'android'],
                'theme' => [
                    'theme_preset' => 'lavender',
                    'background_style' => 'gradient',
                    'bg_gradient_start' => '#fdf4ff',
                    'bg_gradient_end' => '#faf5ff',
                    'content_width' => 'narrow',
                ],
                'blocks' => [
                    ['type' => 'hero', 'title' => 'Relax & Reset', 'subtitle' => '心と体を整える、あなただけの時間。', 'image_url' => 'https://images.unsplash.com/photo-1540555700478-4be289fbecef?w=1400&q=80'],
                    ['type' => 'collection', 'section_label' => 'Menu', 'layout' => 'grid', 'columns' => 2, 'item_image_height' => 100, 'items' => [
                        ['title' => 'ボディケア 60分', 'description' => '¥8,800', 'image_url' => 'https://images.unsplash.com/photo-1544161515-4ab6ce6db874?w=600'],
                        ['title' => 'フェイシャル', 'description' => '¥6,600', 'image_url' => 'https://images.unsplash.com/photo-1570172619644-dfd03ed5d881?w=600'],
                    ]],
                    ['type' => 'button', 'text' => '予約する', 'action' => '#reservations', 'color' => '#a855f7'],
                    ['type' => 'data', 'source' => 'reservations'],
                ],
            ],
            [
                'id' => 'products-drink-bar',
                'name' => 'ドリンクバー・メニュー',
                'category' => 'order',
                'description' => 'ドリンクカテゴリをビジュアルで訴求する商品タブ向けテンプレートです。',
                'target_tabs' => ['products'],
                'device_support' => ['line', 'ios', 'android'],
                'theme' => [
                    'theme_preset' => 'ocean',
                    'background_style' => 'solid',
                    'bg_color' => '#f0fdfa',
                    'content_width' => 'comfy',
                ],
                'blocks' => [
                    ['type' => 'heading', 'text' => 'Drink Menu', 'size' => 'large'],
                    ['type' => 'collection', 'section_label' => 'Category', 'layout' => 'scroll', 'items' => [
                        ['title' => 'コーヒー', 'description' => 'Hot / Ice', 'image_url' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=600'],
                        ['title' => 'ティー', 'description' => 'Herbal', 'image_url' => 'https://images.unsplash.com/photo-1556678723-b7304681975b?w=600'],
                        ['title' => 'スムージー', 'description' => 'Fresh', 'image_url' => 'https://images.unsplash.com/photo-1505252585467-12662942d3ca?w=600'],
                    ]],
                    ['type' => 'data', 'source' => 'products'],
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
                'theme' => [
                    'theme_preset' => 'bordeaux',
                    'background_style' => 'image',
                    'bg_image_url' => 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=1600&q=80',
                    'bg_overlay_opacity' => 70,
                    'content_width' => 'comfy',
                ],
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
                'theme' => [
                    'theme_preset' => 'sand',
                    'background_style' => 'image',
                    'bg_image_url' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=1600&q=80',
                    'bg_overlay_opacity' => 65,
                    'content_width' => 'comfy',
                ],
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
                'theme' => [
                    'theme_preset' => 'citrus',
                    'background_style' => 'gradient',
                    'bg_gradient_start' => '#fff7ed',
                    'bg_gradient_end' => '#ffedd5',
                    'content_width' => 'edge',
                ],
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
                'theme' => [
                    'theme_preset' => 'lavender',
                    'background_style' => 'gradient',
                    'bg_gradient_start' => '#f5f3ff',
                    'bg_gradient_end' => '#faf5ff',
                    'content_width' => 'comfy',
                ],
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
                'theme' => [
                    'theme_preset' => 'royal',
                    'background_style' => 'gradient',
                    'bg_gradient_start' => '#eef2ff',
                    'bg_gradient_end' => '#f8fafc',
                    'content_width' => 'narrow',
                ],
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
                'theme' => [
                    'theme_preset' => 'ocean',
                    'background_style' => 'gradient',
                    'bg_gradient_start' => '#e0f2fe',
                    'bg_gradient_end' => '#f0f9ff',
                    'content_width' => 'edge',
                ],
            ],
            ...LiffDesignerExtraTemplates::tabTemplates(),
            ...LiffDesignerExtraTemplates::superPatterns(),
        ];
    }

    public static function defaultLiffTabLayouts(): array
    {
        return [
            'home' => [
                ['type' => 'member_summary', 'design' => 'wallet'],
                ['type' => 'quick_actions', 'design' => 'pill', 'actions' => [
                    ['icon' => 'fa-utensils', 'label' => 'メニュー', 'action' => '#products'],
                    ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
                    ['icon' => 'fa-stamp', 'label' => 'スタンプ', 'action' => '#stamps'],
                    ['icon' => 'fa-id-card', 'label' => '会員証', 'action' => '#member'],
                ]],
                ['type' => 'hero', 'design' => 'editorial', 'title' => '本日のスペシャル', 'subtitle' => 'シェフ厳選の季節メニューをお届けします。', 'image_url' => 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=1400&q=80'],
                ['type' => 'banner', 'design' => 'pill', 'icon' => 'fa-bell', 'text' => '本日限定: LINE会員はポイント2倍', 'color' => '#1c1917', 'text_color' => '#ffffff', 'action' => '#coupons'],
                ['type' => 'collection', 'section_label' => 'Recommended', 'layout' => 'scroll', 'item_image_height' => 120, 'items' => [
                    ['title' => 'シグネチャープレート', 'description' => '人気No.1', 'badge' => 'BEST', 'image_url' => 'https://images.unsplash.com/photo-1550547660-d9450f859349?w=800'],
                    ['title' => '季節のサラダ', 'description' => '旬の素材', 'badge' => 'NEW', 'image_url' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=800'],
                    ['title' => '本日のパスタ', 'description' => '限定メニュー', 'image_url' => 'https://images.unsplash.com/photo-1473093295043-cdd812d0e601?w=800'],
                ]],
                ['type' => 'button', 'design' => 'cta', 'text' => '今すぐ注文する', 'action' => '#products'],
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
                ['type' => 'banner', 'icon' => 'fa-clock', 'text' => '受け取り準備が完了すると通知します', 'color' => '#eef2ff', 'text_color' => '#3730a3'],
                ['type' => 'data', 'source' => 'orders'],
            ],
            'stamps' => [
                ['type' => 'heading', 'text' => 'スタンプカード', 'size' => 'large'],
                ['type' => 'icon_text', 'rows' => [
                    ['icon' => 'fa-stamp', 'text' => '来店ごとにスタンプ獲得', 'color' => '#4f46e5'],
                    ['icon' => 'fa-gift', 'text' => '満了で特典を獲得', 'color' => '#4f46e5'],
                ]],
                ['type' => 'data', 'source' => 'stamps'],
            ],
            'coupons' => [
                ['type' => 'heading', 'text' => 'クーポン', 'size' => 'large'],
                ['type' => 'banner', 'icon' => 'fa-ticket-alt', 'text' => '会員限定クーポンを配信中', 'color' => '#eef2ff', 'text_color' => '#3730a3'],
                ['type' => 'text', 'text' => 'ご注文前に利用可能なクーポンをご確認ください。'],
                ['type' => 'data', 'source' => 'coupons'],
            ],
            'queue' => [
                ['type' => 'heading', 'text' => '順番待ち', 'size' => 'large'],
                ['type' => 'text', 'text' => '混雑時は順番待ち受付をご利用ください。'],
                ['type' => 'icon_text', 'rows' => [
                    ['icon' => 'fa-user-check', 'text' => '人数を入力して受付', 'color' => '#4f46e5'],
                    ['icon' => 'fa-mobile-alt', 'text' => '呼び出し通知でお知らせ', 'color' => '#4f46e5'],
                ]],
                ['type' => 'data', 'source' => 'queue'],
            ],
            'reservations' => [
                ['type' => 'heading', 'text' => '来店予約', 'size' => 'large'],
                ['type' => 'list', 'items' => ['日時を選択', '人数を指定', '内容を確認して確定']],
                ['type' => 'banner', 'icon' => 'fa-calendar-check', 'text' => '予約変更・キャンセルは注文履歴から可能です', 'color' => '#eef2ff', 'text_color' => '#3730a3'],
                ['type' => 'data', 'source' => 'reservations'],
            ],
            'member' => [
                ['type' => 'heading', 'text' => '会員ページ', 'size' => 'large'],
                ['type' => 'text', 'text' => '会員証・ポイント・ランクを確認できます。'],
                ['type' => 'icon_text', 'rows' => [
                    ['icon' => 'fa-coins', 'text' => '購入でポイントが貯まります', 'color' => '#4f46e5'],
                    ['icon' => 'fa-crown', 'text' => 'ランクアップで特典が増えます', 'color' => '#4f46e5'],
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
                ['type' => 'banner', 'icon' => 'fa-bullhorn', 'text' => '営業時間や限定情報を配信しています', 'color' => '#eef2ff', 'text_color' => '#3730a3'],
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
            ['type' => 'member_summary'],
            ['type' => 'quick_actions', 'actions' => [
                ['icon' => 'fa-coffee', 'label' => 'メニュー', 'action' => '#products'],
                ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
                ['icon' => 'fa-calendar-alt', 'label' => '予約', 'action' => '#reservations'],
                ['icon' => 'fa-id-card', 'label' => '会員証', 'action' => '#member'],
            ]],
            ['type' => 'hero', 'title' => 'Natural Cafe', 'subtitle' => 'ゆったりした時間を、LINEからスマートに。', 'image_url' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=1400&q=80'],
            ['type' => 'slider', 'section_label' => 'Weekly', 'slide_height' => 190, 'slides' => [
                ['image_url' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=1200', 'caption' => '季節のハンドドリップ'],
                ['image_url' => 'https://images.unsplash.com/photo-1511920170033-f8396924c348?w=1200', 'caption' => 'スイーツセット'],
            ]],
            ['type' => 'collection', 'section_label' => 'Drinks', 'layout' => 'scroll', 'item_image_height' => 115, 'items' => [
                ['title' => 'ラテ', 'description' => '人気', 'image_url' => 'https://images.unsplash.com/photo-1461023058943-07fcbe16d735?w=800'],
                ['title' => 'コールドブリュー', 'description' => 'NEW', 'badge' => 'NEW', 'image_url' => 'https://images.unsplash.com/photo-1517701551472-7b4e3a7eeefd?w=800'],
            ]],
            ['type' => 'button', 'text' => 'メニューを見る', 'action' => '#products', 'color' => '#65a30d'],
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
            ['type' => 'member_summary'],
            ['type' => 'hero', 'title' => '最短30秒で注文', 'subtitle' => 'ピーク時でも、事前注文で待ち時間を短縮。', 'image_url' => 'https://images.unsplash.com/photo-1561758033-d066a3efea2f?w=1400&q=80'],
            ['type' => 'quick_actions', 'actions' => [
                ['icon' => 'fa-hamburger', 'label' => '注文', 'action' => '#products'],
                ['icon' => 'fa-receipt', 'label' => '注文状況', 'action' => '#orders'],
                ['icon' => 'fa-users', 'label' => '順番待ち', 'action' => '#queue'],
                ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
            ]],
            ['type' => 'button', 'text' => '商品を選ぶ', 'action' => '#products', 'color' => '#ea580c'],
            ['type' => 'banner', 'icon' => 'fa-bolt', 'text' => '受取時間を選んで、店頭でスムーズ受取', 'color' => '#1d4ed8', 'text_color' => '#ffffff'],
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

    public static function defaultLiffSalonPatternLayouts(): array
    {
        $layouts = self::defaultLiffTabLayouts();
        $layouts['home'] = [
            ['type' => 'member_summary'],
            ['type' => 'hero', 'title' => 'あなたのキレイを叶える', 'subtitle' => 'LINEから予約・会員特典をまとめてご利用いただけます。', 'image_url' => 'https://images.unsplash.com/photo-1560066984-138dadb4c035?w=1400&q=80'],
            ['type' => 'quick_actions', 'actions' => [
                ['icon' => 'fa-calendar-check', 'label' => '予約', 'action' => '#reservations'],
                ['icon' => 'fa-cut', 'label' => 'メニュー', 'action' => '#products'],
                ['icon' => 'fa-images', 'label' => 'ギャラリー', 'action' => '#gallery'],
                ['icon' => 'fa-id-card', 'label' => '会員証', 'action' => '#member'],
            ]],
            ['type' => 'slider', 'section_label' => 'Style', 'slide_height' => 190, 'slides' => [
                ['image_url' => 'https://images.unsplash.com/photo-1560066984-138dadb4c035?w=1200', 'caption' => '今月のおすすめスタイル'],
                ['image_url' => 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?w=1200', 'caption' => 'カラーキャンペーン実施中'],
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
            ['type' => 'member_summary'],
            ['type' => 'hero', 'title' => 'ようこそショップへ', 'subtitle' => 'LINE会員限定のお得な情報をお届けします。', 'image_url' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=1400&q=80'],
            ['type' => 'quick_actions', 'actions' => [
                ['icon' => 'fa-shopping-bag', 'label' => '商品', 'action' => '#products'],
                ['icon' => 'fa-percent', 'label' => 'セール', 'action' => '#coupons'],
                ['icon' => 'fa-crown', 'label' => '会員', 'action' => '#member'],
                ['icon' => 'fa-images', 'label' => 'ギャラリー', 'action' => '#gallery'],
            ]],
            ['type' => 'collection', 'section_label' => 'Trending', 'layout' => 'scroll', 'item_image_height' => 130, 'items' => [
                ['title' => '新着アイテム', 'description' => 'NEW', 'badge' => 'NEW', 'image_url' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=800'],
                ['title' => '人気ランキング', 'description' => 'BEST', 'badge' => 'HOT', 'image_url' => 'https://images.unsplash.com/photo-1472851294608-062f824d29cc?w=800'],
                ['title' => 'セール', 'description' => 'SALE', 'badge' => 'SALE', 'image_url' => 'https://images.unsplash.com/photo-1607082349566-187342175e2f?w=800'],
            ]],
            ['type' => 'banner', 'icon' => 'fa-tags', 'text' => 'LINE会員限定セール開催中！', 'color' => '#7c3aed', 'text_color' => '#ffffff'],
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

    public static function defaultLiffFitnessPatternLayouts(): array
    {
        $layouts = self::defaultLiffTabLayouts();
        $layouts['home'] = [
            ['type' => 'member_summary'],
            ['type' => 'hero', 'title' => '今日もトレーニング日和', 'subtitle' => 'LINEからレッスン予約・会員情報をまとめて管理。', 'image_url' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=1400&q=80'],
            ['type' => 'quick_actions', 'actions' => [
                ['icon' => 'fa-dumbbell', 'label' => 'レッスン', 'action' => '#reservations'],
                ['icon' => 'fa-fire', 'label' => 'スタンプ', 'action' => '#stamps'],
                ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
                ['icon' => 'fa-id-card', 'label' => '会員証', 'action' => '#member'],
            ]],
            ['type' => 'banner', 'icon' => 'fa-medal', 'text' => '今月の来館回数で特典クーポンをゲット', 'color' => '#0891b2', 'text_color' => '#ffffff'],
            ['type' => 'button', 'text' => 'レッスンを予約する', 'action' => '#reservations', 'color' => '#0891b2'],
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

    public static function defaultLiffHotelPatternLayouts(): array
    {
        $layouts = self::defaultLiffTabLayouts();
        $layouts['home'] = [
            ['type' => 'member_summary', 'design' => 'minimal', 'greeting' => 'Welcome'],
            ['type' => 'hero', 'design' => 'centered', 'title' => 'Concierge', 'subtitle' => 'ご滞在をより快適に。', 'image_url' => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1400&q=80'],
            ['type' => 'quick_actions', 'design' => 'compact', 'actions' => [
                ['icon' => 'fa-utensils', 'label' => 'レストラン', 'action' => '#reservations'],
                ['icon' => 'fa-concierge-bell', 'label' => 'ルームサービス', 'action' => '#products'],
                ['icon' => 'fa-spa', 'label' => 'スパ予約', 'action' => '#reservations'],
                ['icon' => 'fa-id-card', 'label' => '会員', 'action' => '#member'],
            ]],
            ['type' => 'card', 'design' => 'horizontal', 'title' => '本日のディナー', 'description' => 'シェフおすすめコース', 'image_url' => 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=800', 'image_height' => 100],
            ['type' => 'data', 'source' => 'announcements'],
        ];
        $layouts['reservations'] = [
            ['type' => 'heading', 'text' => 'ご予約', 'size' => 'large'],
            ['type' => 'banner', 'design' => 'soft', 'icon' => 'fa-calendar-check', 'text' => 'レストラン・スパのご予約', 'color' => '#b8860b'],
            ['type' => 'data', 'source' => 'reservations'],
        ];
        $layouts['member'] = [
            ['type' => 'member_summary', 'design' => 'emblem'],
            ['type' => 'data', 'source' => 'member_card', 'card_design' => 'emblem'],
        ];

        return $layouts;
    }

    public static function defaultLiffClinicPatternLayouts(): array
    {
        $layouts = self::defaultLiffTabLayouts();
        $layouts['home'] = [
            ['type' => 'hero', 'design' => 'minimal', 'title' => 'オンライン予約', 'subtitle' => '診療科目・日時をお選びください。'],
            ['type' => 'member_summary', 'design' => 'minimal'],
            ['type' => 'quick_actions', 'design' => 'compact', 'actions' => [
                ['icon' => 'fa-calendar-check', 'label' => '予約', 'action' => '#reservations'],
                ['icon' => 'fa-file-medical', 'label' => '診療案内', 'action' => '#announcements'],
                ['icon' => 'fa-phone', 'label' => 'お問い合わせ', 'action' => '#announcements'],
            ]],
            ['type' => 'hours', 'title' => '診療時間', 'rows' => [
                ['day' => '月〜金', 'time' => '9:00 - 18:00'],
                ['day' => '土', 'time' => '9:00 - 13:00'],
            ]],
            ['type' => 'data', 'source' => 'announcements'],
        ];
        $layouts['reservations'] = [
            ['type' => 'heading', 'text' => 'ご予約', 'size' => 'large'],
            ['type' => 'list', 'style' => 'number', 'items' => ['診療科目を選択', '日時を選択', '内容を確認して確定']],
            ['type' => 'data', 'source' => 'reservations'],
        ];

        return $layouts;
    }

    public static function defaultLiffLuxuryRetailPatternLayouts(): array
    {
        $layouts = self::defaultLiffTabLayouts();
        $layouts['home'] = [
            ['type' => 'member_summary', 'design' => 'emblem'],
            ['type' => 'hero', 'design' => 'centered', 'title' => 'New Season', 'subtitle' => 'Limited Collection 2026', 'image_url' => 'https://images.unsplash.com/photo-1483985988350-763728e1935b?w=1400&q=80'],
            ['type' => 'collection', 'section_label' => 'Featured', 'layout' => 'scroll', 'item_image_height' => 150, 'items' => [
                ['title' => 'Silk Collection', 'description' => '¥28,600〜', 'badge' => 'NEW', 'image_url' => 'https://images.unsplash.com/photo-1434389677669-e08b4cac3105?w=800'],
                ['title' => 'Leather Line', 'description' => '¥42,000〜', 'image_url' => 'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=800'],
            ]],
            ['type' => 'banner', 'design' => 'outline', 'icon' => 'fa-crown', 'text' => 'VIP会員限定プライベートセール', 'color' => '#b8860b'],
            ['type' => 'button', 'design' => 'outline', 'text' => 'コレクションを見る', 'action' => '#products', 'color' => '#b8860b'],
        ];
        $layouts['products'] = [
            ['type' => 'heading', 'text' => 'Shop', 'size' => 'large'],
            ['type' => 'collection', 'section_label' => 'All Items', 'layout' => 'grid', 'columns' => 2, 'item_image_height' => 140, 'items' => [
                ['title' => 'Item A', 'description' => '¥12,000', 'image_url' => 'https://images.unsplash.com/photo-1434389677669-e08b4cac3105?w=600'],
                ['title' => 'Item B', 'description' => '¥18,000', 'image_url' => 'https://images.unsplash.com/photo-1473966968600-fa801b869a1a?w=600'],
            ]],
            ['type' => 'data', 'source' => 'products'],
        ];
        $layouts['member'] = [
            ['type' => 'member_summary', 'design' => 'split'],
            ['type' => 'data', 'source' => 'member_card', 'card_design' => 'split_banner'],
        ];

        return $layouts;
    }

    public static function defaultLiffDeliveryPatternLayouts(): array
    {
        $layouts = self::defaultLiffFastPatternLayouts();
        $layouts['home'] = [
            ['type' => 'member_summary', 'design' => 'compact'],
            ['type' => 'hero', 'design' => 'split', 'title' => '30分お届け', 'subtitle' => '人気メニューを最短で。', 'image_url' => 'https://images.unsplash.com/photo-1561758033-d066a3efea2f?w=1400&q=80'],
            ['type' => 'quick_actions', 'design' => 'pill', 'actions' => [
                ['icon' => 'fa-hamburger', 'label' => '注文', 'action' => '#products'],
                ['icon' => 'fa-motorcycle', 'label' => '配達状況', 'action' => '#orders'],
                ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
            ]],
            ['type' => 'collection', 'section_label' => '人気', 'layout' => 'grid', 'columns' => 2, 'item_image_height' => 100, 'items' => [
                ['title' => 'セットA', 'description' => '¥980', 'badge' => 'HOT', 'image_url' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=600'],
                ['title' => 'セットB', 'description' => '¥1,280', 'image_url' => 'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=600'],
            ]],
            ['type' => 'button', 'design' => 'cta', 'text' => '今すぐ注文', 'action' => '#products', 'color' => '#ea580c'],
        ];
        $layouts['orders'] = [
            ['type' => 'heading', 'text' => '配達状況', 'size' => 'large'],
            ['type' => 'banner', 'design' => 'soft', 'icon' => 'fa-motorcycle', 'text' => '配達員が向かっています', 'color' => '#ea580c'],
            ['type' => 'data', 'source' => 'orders'],
        ];

        return $layouts;
    }
}

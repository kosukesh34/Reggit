<?php

namespace App\Support\LiffAppStudio\Layouts;

use App\Support\LiffAppStudio\Blocks;

/** App Studio の「landing」業種のタブ別レイアウト。 */
final class LandingLayouts
{
    /** Reggit LP — 大判写真→3つの主機能→再来店導線。LPと同じセージ/ティールのブランド設計 */
    public static function layouts(): array
    {
        $a = '#0E7C66';

        return [
            'home' => [
                [
                    'type' => 'hero',
                    'design' => 'editorial',
                    'eyebrow' => 'WELCOME',
                    'title' => '店舗に必要な機能を、簡単に',
                    'subtitle' => '注文・ポイント・スタンプ・順番待ちを、ひとつのアプリで。',
                    'button_label' => 'サービスを見る',
                    'image_url' => '/images/landing/lp-hero-cafe.jpg',
                ],
                ['type' => 'heading', 'design' => 'section', 'text' => '店舗体験を、ひとつにつなぐ', 'size' => 'large', 'text_align' => 'center'],
                ['type' => 'text', 'design' => 'lead', 'text' => '来店前の注文から、店内体験、再来店のきっかけまで。必要な機能へ迷わずアクセスできます。', 'text_align' => 'center'],
                ['type' => 'quick_actions', 'design' => 'grid', 'actions' => [
                    ['icon' => 'fa-bag-shopping', 'label' => 'オーダー', 'action' => '#products'],
                    ['icon' => 'fa-id-card', 'label' => '会員証', 'action' => '#member'],
                    ['icon' => 'fa-users', 'label' => '順番待ち', 'action' => '#queue'],
                    ['icon' => 'fa-calendar-check', 'label' => '予約', 'action' => '#reservations'],
                ]],
                [
                    'type' => 'card',
                    'design' => 'overlay',
                    'title' => 'モバイルオーダー',
                    'description' => 'メニュー閲覧から注文まで、スマホでスムーズに。',
                    'image_url' => '/images/landing/lp-feature-order.jpg',
                    'image_height' => 220,
                    'action' => '#products',
                ],
                [
                    'type' => 'card',
                    'design' => 'overlay',
                    'title' => '会員証・ポイント・スタンプ',
                    'description' => '毎日の利用が、次の来店の楽しみにつながります。',
                    'image_url' => '/images/landing/lp-feature-member.jpg',
                    'image_height' => 220,
                    'action' => '#member',
                ],
                [
                    'type' => 'card',
                    'design' => 'overlay',
                    'title' => '順番待ち・予約',
                    'description' => '受付から呼び出しまで、待ち時間をもっと自由に。',
                    'image_url' => '/images/landing/lp-feature-queue.jpg',
                    'image_height' => 220,
                    'action' => '#queue',
                ],
                ['type' => 'banner', 'design' => 'promo', 'icon' => 'fa-gift', 'text' => '会員登録で、次回使える特典をお届け', 'color' => $a, 'action' => '#member'],
                ['type' => 'button', 'design' => 'sticky', 'text' => '今すぐオーダー', 'action' => '#products', 'color' => $a, 'full_width' => true],
            ],
            'products' => [
                [
                    'type' => 'hero',
                    'design' => 'split',
                    'eyebrow' => 'MOBILE ORDER',
                    'title' => '並ばず、好きなタイミングで',
                    'subtitle' => '今日のおすすめをスマホから注文できます。',
                    'image_url' => '/images/landing/lp-feature-order.jpg',
                ],
                ['type' => 'quick_actions', 'design' => 'pill', 'actions' => [
                    ['icon' => 'fa-fire', 'label' => '人気', 'action' => '#products'],
                    ['icon' => 'fa-mug-hot', 'label' => 'ドリンク', 'action' => '#products'],
                    ['icon' => 'fa-bread-slice', 'label' => 'フード', 'action' => '#products'],
                ]],
                ['type' => 'data', 'source' => 'products', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'sticky', 'text' => 'カートを見る', 'action' => '#order/cart', 'color' => $a, 'full_width' => true],
            ],
            'orders' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'ご注文状況', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'pill', 'icon' => 'fa-bell', 'text' => '受け取り準備ができたらお知らせします', 'color' => $a, 'text_color' => '#ffffff'],
                ['type' => 'data', 'source' => 'orders', 'design' => 'timeline'],
                ['type' => 'button', 'design' => 'cta', 'text' => 'メニューへ戻る', 'action' => '#products', 'color' => $a],
            ],
            'stamps' => [
                ['type' => 'member_summary', 'design' => 'rewards', 'greeting' => 'いつもありがとうございます'],
                ['type' => 'heading', 'design' => 'section', 'text' => 'スタンプカード', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'promo', 'icon' => 'fa-gift', 'text' => '10個貯まると、お好きな特典をプレゼント', 'color' => $a],
                Blocks::stampData('grid_card', 'circle', 'image', $a),
            ],
            'coupons' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'あなたへの特典', 'size' => 'large'],
                ['type' => 'text', 'design' => 'lead', 'text' => '会員限定のクーポンと、期間限定のお知らせです。'],
                ['type' => 'banner', 'design' => 'pill', 'icon' => 'fa-ticket-alt', 'text' => '使えるクーポンをタップ', 'color' => $a, 'text_color' => '#ffffff'],
                ['type' => 'data', 'source' => 'coupons', 'design' => 'cards'],
            ],
            'queue' => [
                [
                    'type' => 'hero',
                    'design' => 'split',
                    'eyebrow' => 'QUEUE',
                    'title' => '待ち時間を、もっと自由に',
                    'subtitle' => '順番が近づいたらスマホへお知らせします。',
                    'image_url' => '/images/landing/lp-feature-queue.jpg',
                ],
                ['type' => 'stats_row', 'section_label' => 'ただいまの状況', 'items' => [
                    ['label' => '待ち組数', 'value' => '5', 'hint' => '組'],
                    ['label' => '待ち時間', 'value' => '25', 'hint' => '分'],
                ]],
                ['type' => 'data', 'source' => 'queue', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'sticky', 'text' => '順番待ちを受付する', 'action' => '#queue', 'color' => $a, 'full_width' => true],
            ],
            'reservations' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'ご来店予約', 'size' => 'large'],
                ['type' => 'text', 'design' => 'lead', 'text' => '空いている日時を選ぶだけ。予約の確認や変更もアプリから行えます。'],
                ['type' => 'list', 'style' => 'number', 'items' => ['日時を選ぶ', '人数と内容を入力', '予約を確定']],
                ['type' => 'data', 'source' => 'reservations', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'sticky', 'text' => '新しく予約する', 'action' => '#reservations', 'color' => $a, 'full_width' => true],
            ],
            'member' => [
                [
                    'type' => 'hero',
                    'design' => 'split',
                    'eyebrow' => 'MEMBERSHIP',
                    'title' => '使うたび、うれしい会員証',
                    'subtitle' => 'ポイント・ランク・購入履歴をひとつに。',
                    'image_url' => '/images/landing/lp-feature-member.jpg',
                ],
                ['type' => 'member_summary', 'design' => 'wallet', 'greeting' => '会員証'],
                ['type' => 'stats_row', 'items' => [
                    ['label' => 'ポイント', 'value' => '2,450', 'hint' => 'pt', 'field' => 'points'],
                    ['label' => '次の特典まで', 'value' => '550', 'hint' => 'pt', 'field' => 'points_to_next'],
                ]],
                ['type' => 'data', 'source' => 'member_card', 'card_design' => 'emblem'],
                ['type' => 'icon_text', 'rows' => [
                    ['icon' => 'fa-barcode', 'text' => 'レジで会員証をご提示ください', 'color' => $a],
                    ['icon' => 'fa-wallet', 'text' => 'Apple / Google Walletにも追加できます', 'color' => $a],
                ]],
                ['type' => 'button', 'design' => 'sticky', 'text' => '会員証を表示', 'action' => '#member', 'color' => $a, 'full_width' => true],
            ],
            'gallery' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'お店のギャラリー', 'size' => 'large'],
                ['type' => 'slider', 'source' => 'gallery', 'design' => 'hero', 'section_label' => '今月のおすすめ', 'slide_height' => 260],
                ['type' => 'data', 'source' => 'gallery', 'design' => 'cards'],
            ],
            'announcements' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'お知らせ', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'promo', 'icon' => 'fa-bullhorn', 'text' => '新商品・キャンペーン情報をお届け', 'color' => $a],
                ['type' => 'data', 'source' => 'announcements', 'design' => 'cards'],
                ['type' => 'hours', 'design' => 'card', 'title' => '営業時間', 'rows' => [
                    ['day' => '平日', 'time' => '8:00 - 20:00'],
                    ['day' => '土日祝', 'time' => '9:00 - 19:00'],
                ]],
                ['type' => 'access', 'design' => 'card', 'title' => '店舗情報', 'address' => '店舗住所を入力してください', 'note' => '最寄駅からの案内を編集できます'],
            ],
        ];
    }
}

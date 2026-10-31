<?php

namespace App\Support\LiffAppStudio\Layouts;

use App\Support\LiffAppStudio\Blocks;

/** App Studio の「cafe」業種のタブ別レイアウト。 */
final class CafeLayouts
{
    /** Starbucks — 挨拶→リワード→MOP主役→会員証/スタンプ。ホームに商品棚なし */
    public static function layouts(): array
    {
        $a = '#00754A';

        return [
            'home' => [
                ['type' => 'member_summary', 'design' => 'rewards', 'greeting' => 'おはようございます'],
                ['type' => 'stats_row', 'section_label' => 'マイリワード', 'items' => [
                    ['label' => 'ポイント', 'value' => '70', 'hint' => 'pt', 'field' => 'points'],
                    ['label' => '次の特典まで', 'value' => '20', 'hint' => 'pt', 'field' => 'points_to_next'],
                ]],
                ['type' => 'hero', 'design' => 'apple', 'eyebrow' => 'モバイルオーダー', 'title' => 'レジに並ばず、お店で受け取り', 'subtitle' => '今日の一杯を今すぐオーダー', 'image_url' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=1400&q=80'],
                ['type' => 'quick_actions', 'design' => 'pill', 'actions' => [
                    ['icon' => 'fa-bag-shopping', 'label' => 'オーダー', 'action' => '#products'],
                    ['icon' => 'fa-barcode', 'label' => '会員証', 'action' => '#member'],
                    ['icon' => 'fa-stamp', 'label' => 'スタンプ', 'action' => '#stamps'],
                    ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
                ]],
                ['type' => 'button', 'design' => 'sticky', 'text' => 'オーダーする', 'action' => '#products', 'color' => $a, 'full_width' => true],
            ],
            'products' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'ドリンク & フード', 'size' => 'large'],
                ['type' => 'quick_actions', 'design' => 'pill', 'actions' => [
                    ['icon' => 'fa-mug-hot', 'label' => 'ホット', 'action' => '#products'],
                    ['icon' => 'fa-snowflake', 'label' => 'アイス', 'action' => '#products'],
                    ['icon' => 'fa-bread-slice', 'label' => 'フード', 'action' => '#products'],
                ]],
                ['type' => 'data', 'source' => 'products', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'cta', 'text' => 'カートを見る', 'action' => '#order/cart', 'color' => $a],
            ],
            'orders' => [
                ['type' => 'heading', 'design' => 'section', 'text' => '注文状況', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'promo', 'icon' => 'fa-bell', 'text' => 'お呼び出しは LINE でお知らせ', 'color' => $a],
                ['type' => 'data', 'source' => 'orders', 'design' => 'timeline'],
                ['type' => 'button', 'design' => 'cta', 'text' => 'もう一杯注文', 'action' => '#products', 'color' => $a],
            ],
            'stamps' => [
                ['type' => 'member_summary', 'design' => 'rewards'],
                ['type' => 'heading', 'design' => 'section', 'text' => 'スターリワード', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'promo', 'icon' => 'fa-star', 'text' => '25スターでドリンク無料に交換', 'color' => $a],
                ['type' => 'stats_row', 'items' => [
                    ['label' => 'スター', 'value' => '70', 'hint' => '', 'field' => 'points'],
                    ['label' => '次の特典まで', 'value' => '20', 'hint' => '', 'field' => 'points_to_next'],
                ]],
                Blocks::stampData('circle_board', 'circle', 'image', $a),
            ],
            'coupons' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'クーポン・特典', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'pill', 'icon' => 'fa-ticket-alt', 'text' => '使えるクーポンをタップ', 'color' => $a, 'text_color' => '#ffffff'],
                ['type' => 'data', 'source' => 'coupons', 'design' => 'cards'],
            ],
            'queue' => [
                ['type' => 'heading', 'design' => 'section', 'text' => '順番待ち', 'size' => 'large'],
                ['type' => 'data', 'source' => 'queue', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'cta', 'text' => '受付する', 'action' => '#queue', 'color' => $a, 'full_width' => true],
            ],
            'reservations' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'ご予約', 'size' => 'large'],
                ['type' => 'text', 'design' => 'lead', 'text' => 'お席のご予約はこちらから。'],
                ['type' => 'data', 'source' => 'reservations', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'cta', 'text' => '席を予約', 'action' => '#reservations', 'color' => $a, 'full_width' => true],
            ],
            'member' => [
                ['type' => 'member_summary', 'design' => 'wallet', 'greeting' => 'カード'],
                ['type' => 'data', 'source' => 'member_card', 'card_design' => 'emblem'],
                ['type' => 'stats_row', 'items' => [
                    ['label' => '残高', 'value' => '¥0', 'hint' => ''],
                    ['label' => 'スター', 'value' => '70', 'hint' => '', 'field' => 'points'],
                ]],
                ['type' => 'icon_text', 'rows' => [
                    ['icon' => 'fa-barcode', 'text' => 'レジでバーコードをご提示ください', 'color' => $a],
                ]],
                ['type' => 'button', 'design' => 'sticky', 'text' => '会員証を提示', 'action' => '#member', 'color' => $a, 'full_width' => true],
            ],
            'gallery' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'ギャラリー', 'size' => 'large'],
                ['type' => 'slider', 'source' => 'gallery', 'design' => 'hero', 'slide_height' => 200],
                ['type' => 'data', 'source' => 'gallery', 'design' => 'cards'],
            ],
            'announcements' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'お知らせ', 'size' => 'large'],
                ['type' => 'data', 'source' => 'announcements', 'design' => 'cards'],
            ],
        ];
    }
}

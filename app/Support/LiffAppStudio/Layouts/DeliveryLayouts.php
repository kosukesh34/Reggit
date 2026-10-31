<?php

namespace App\Support\LiffAppStudio\Layouts;

use App\Support\LiffAppStudio\Blocks;

/** App Studio の「delivery」業種のタブ別レイアウト。 */
final class DeliveryLayouts
{
    /** デリバリー — 配達ステータス→再注文CTA。ホームに商品棚なし */
    public static function layouts(): array
    {
        $a = '#ff3b30';

        return [
            'home' => [
                ['type' => 'banner', 'design' => 'pill', 'icon' => 'fa-motorcycle', 'text' => '配達中 — あと約12分でお届け', 'color' => $a, 'text_color' => '#ffffff', 'action' => '#orders'],
                ['type' => 'card', 'design' => 'featured', 'title' => '注文 #4821', 'description' => 'ご注文店舗 → お届け先まで配送中', 'image_url' => 'https://images.unsplash.com/photo-1526367799907-021232d6f453?w=800', 'action' => '#orders'],
                ['type' => 'quick_actions', 'design' => 'grid', 'actions' => [
                    ['icon' => 'fa-utensils', 'label' => '注文する', 'action' => '#products'],
                    ['icon' => 'fa-truck', 'label' => '配達状況', 'action' => '#orders'],
                    ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
                    ['icon' => 'fa-history', 'label' => '再注文', 'action' => '#orders'],
                ]],
                ['type' => 'button', 'design' => 'sticky', 'text' => 'もう一度注文', 'action' => '#products', 'color' => $a, 'full_width' => true],
            ],
            'products' => [
                ['type' => 'heading', 'design' => 'section', 'text' => '人気の商品', 'size' => 'large'],
                ['type' => 'data', 'source' => 'products', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'sticky', 'text' => 'カートに進む', 'action' => '#order/cart', 'color' => $a, 'full_width' => true],
            ],
            'orders' => [
                ['type' => 'banner', 'design' => 'pill', 'icon' => 'fa-truck', 'text' => 'リアルタイムで配達状況を表示', 'color' => $a, 'text_color' => '#ffffff'],
                ['type' => 'list', 'style' => 'number', 'items' => ['注文受付', 'レストランで調理中', '配達員が受け取り', '配達中', 'お届け完了']],
                ['type' => 'icon_text', 'rows' => [
                    ['icon' => 'fa-bell', 'text' => '配達完了で LINE 通知', 'color' => $a],
                ]],
                ['type' => 'data', 'source' => 'orders', 'design' => 'timeline'],
                ['type' => 'button', 'design' => 'cta', 'text' => '再注文する', 'action' => '#products', 'color' => $a],
            ],
            'stamps' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'スタンプ', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'promo', 'icon' => 'fa-gift', 'text' => '注文5回で500円OFFクーポン', 'color' => $a],
                Blocks::stampData('wallet', 'rounded', 'solid', $a),
            ],
            'coupons' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'クーポン', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'countdown', 'icon' => 'fa-ticket-alt', 'text' => '期間限定クーポン配信中', 'color' => $a, 'text_color' => '#ffffff'],
                ['type' => 'data', 'source' => 'coupons', 'design' => 'cards'],
            ],
            'queue' => [
                ['type' => 'heading', 'design' => 'section', 'text' => '順番待ち', 'size' => 'large'],
                ['type' => 'data', 'source' => 'queue', 'design' => 'cards'],
            ],
            'reservations' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'ご予約', 'size' => 'large'],
                ['type' => 'text', 'design' => 'lead', 'text' => '事前予約で受け取り時間を指定できます。'],
                ['type' => 'data', 'source' => 'reservations', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'cta', 'text' => '予約注文', 'action' => '#reservations', 'color' => $a, 'full_width' => true],
            ],
            'member' => [
                ['type' => 'member_summary', 'design' => 'compact', 'greeting' => 'こんにちは'],
                ['type' => 'data', 'source' => 'member_card', 'card_design' => 'emblem'],
                ['type' => 'icon_text', 'rows' => [
                    ['icon' => 'fa-barcode', 'text' => 'レジでバーコードをご提示ください', 'color' => $a],
                ]],
                ['type' => 'button', 'design' => 'sticky', 'text' => 'バーコードを拡大', 'action' => '#member', 'color' => $a, 'full_width' => true],
            ],
            'gallery' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'ギャラリー', 'size' => 'large'],
                ['type' => 'data', 'source' => 'gallery', 'design' => 'cards'],
            ],
            'announcements' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'お知らせ', 'size' => 'large'],
                ['type' => 'data', 'source' => 'announcements', 'design' => 'cards'],
            ],
        ];
    }
}

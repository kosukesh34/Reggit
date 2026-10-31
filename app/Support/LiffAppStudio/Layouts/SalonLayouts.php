<?php

namespace App\Support\LiffAppStudio\Layouts;

use App\Support\LiffAppStudio\Blocks;

/** App Studio の「salon」業種のタブ別レイアウト。 */
final class SalonLayouts
{
    /** ルックブック主導 — 大判スタイル→クイック→予約CTA。ホームに商品棚なし */
    public static function layouts(): array
    {
        $a = '#ff2d55';

        return [
            'home' => [
                ['type' => 'slider', 'source' => 'gallery', 'design' => 'hero', 'section_label' => 'スタイルギャラリー', 'slide_height' => 320],
                ['type' => 'quick_actions', 'design' => 'pill', 'actions' => [
                    ['icon' => 'fa-calendar-plus', 'label' => '予約', 'action' => '#reservations'],
                    ['icon' => 'fa-images', 'label' => 'スタイル', 'action' => '#gallery'],
                    ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
                    ['icon' => 'fa-id-card', 'label' => '会員証', 'action' => '#member'],
                ]],
                ['type' => 'button', 'design' => 'sticky', 'text' => 'このスタイルで予約', 'action' => '#reservations', 'color' => $a, 'full_width' => true],
            ],
            'products' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'メニュー', 'size' => 'large'],
                ['type' => 'data', 'source' => 'products', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'sticky', 'text' => 'このメニューで予約', 'action' => '#reservations', 'color' => $a, 'full_width' => true],
            ],
            'orders' => [
                ['type' => 'heading', 'design' => 'section', 'text' => '施術履歴', 'size' => 'large'],
                ['type' => 'data', 'source' => 'orders', 'design' => 'timeline'],
                ['type' => 'button', 'design' => 'cta', 'text' => '同じメニューで再予約', 'action' => '#reservations', 'color' => $a],
            ],
            'stamps' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'スタンプ', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'promo', 'icon' => 'fa-gift', 'text' => '来店5回でトリートメント無料', 'color' => $a],
                Blocks::stampData('minimal', 'rounded', 'icon', $a),
            ],
            'coupons' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'クーポン', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'pill', 'icon' => 'fa-ticket-alt', 'text' => '会員限定クーポン', 'color' => $a, 'text_color' => '#ffffff'],
                ['type' => 'data', 'source' => 'coupons', 'design' => 'cards'],
            ],
            'queue' => [
                ['type' => 'heading', 'design' => 'section', 'text' => '順番待ち', 'size' => 'large'],
                ['type' => 'data', 'source' => 'queue', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'cta', 'text' => '受付する', 'action' => '#queue', 'color' => $a, 'full_width' => true],
            ],
            'reservations' => [
                ['type' => 'hero', 'design' => 'minimal', 'title' => '空き枠から予約', 'subtitle' => 'メニューと日時を選んで完了', 'image_url' => 'https://images.unsplash.com/photo-1560066984-138dadb4c035?w=1400&q=80'],
                ['type' => 'data', 'source' => 'reservations', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'sticky', 'text' => '新規予約', 'action' => '#reservations', 'color' => $a, 'full_width' => true],
            ],
            'member' => [
                ['type' => 'member_summary', 'design' => 'wallet', 'greeting' => 'こんにちは'],
                ['type' => 'data', 'source' => 'member_card', 'card_design' => 'emblem'],
                ['type' => 'icon_text', 'rows' => [
                    ['icon' => 'fa-barcode', 'text' => 'レジでバーコードをご提示ください', 'color' => $a],
                ]],
                ['type' => 'button', 'design' => 'sticky', 'text' => 'バーコードを拡大', 'action' => '#member', 'color' => $a, 'full_width' => true],
            ],
            'gallery' => [
                ['type' => 'slider', 'source' => 'gallery', 'design' => 'hero', 'section_label' => 'スタイルギャラリー', 'slide_height' => 300],
                ['type' => 'data', 'source' => 'gallery', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'sticky', 'text' => 'このスタイルで予約', 'action' => '#reservations', 'color' => $a, 'full_width' => true],
            ],
            'announcements' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'お知らせ', 'size' => 'large'],
                ['type' => 'data', 'source' => 'announcements', 'design' => 'cards'],
            ],
        ];
    }
}

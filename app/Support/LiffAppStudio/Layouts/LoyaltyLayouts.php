<?php

namespace App\Support\LiffAppStudio\Layouts;

use App\Support\LiffAppStudio\Blocks;

/** App Studio の「loyalty」業種のタブ別レイアウト。 */
final class LoyaltyLayouts
{
    /** Starbucks Rewards — 挨拶→残高/次特典→主役1つ→クイック→提示CTA。商品棚なし */
    public static function layouts(): array
    {
        $a = '#00754A';

        return [
            'home' => [
                ['type' => 'member_summary', 'design' => 'rewards', 'greeting' => 'おはようございます'],
                ['type' => 'stats_row', 'section_label' => 'マイリワード', 'items' => [
                    ['label' => 'ポイント', 'value' => '70', 'hint' => 'pt', 'field' => 'points'],
                    ['label' => 'ランク', 'value' => 'グリーン', 'hint' => '', 'field' => 'rank'],
                    ['label' => '次の特典まで', 'value' => '20', 'hint' => 'pt', 'field' => 'points_to_next'],
                ]],
                ['type' => 'card', 'design' => 'featured', 'title' => '特典に交換する', 'description' => 'スターがたまったら、ドリンクやフードと交換', 'image_url' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=800', 'action' => '#coupons'],
                ['type' => 'quick_actions', 'design' => 'pill', 'actions' => [
                    ['icon' => 'fa-barcode', 'label' => '会員証', 'action' => '#member'],
                    ['icon' => 'fa-gift', 'label' => '特典交換', 'action' => '#coupons'],
                    ['icon' => 'fa-stamp', 'label' => 'スタンプ', 'action' => '#stamps'],
                    ['icon' => 'fa-envelope', 'label' => 'お知らせ', 'action' => '#announcements'],
                ]],
                ['type' => 'button', 'design' => 'sticky', 'text' => '会員証を提示', 'action' => '#member', 'color' => $a, 'full_width' => true],
            ],
            'products' => [
                ['type' => 'heading', 'design' => 'section', 'text' => '交換メニュー', 'size' => 'large'],
                ['type' => 'text', 'design' => 'lead', 'text' => 'ポイントで交換できるアイテムはこちら。'],
                ['type' => 'data', 'source' => 'products', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'cta', 'text' => '特典で交換', 'action' => '#coupons', 'color' => $a],
            ],
            'orders' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'ご利用履歴', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'promo', 'icon' => 'fa-coins', 'text' => 'ご利用ごとにポイントが貯まります', 'color' => $a],
                ['type' => 'data', 'source' => 'orders', 'design' => 'timeline'],
            ],
            'stamps' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'スタンプ', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'promo', 'icon' => 'fa-gift', 'text' => '10個達成で特典クーポンをプレゼント', 'color' => $a],
                ['type' => 'icon_text', 'rows' => [
                    ['icon' => 'fa-stamp', 'text' => 'ご来店ごとにスタンプ1個', 'color' => $a],
                    ['icon' => 'fa-crown', 'text' => 'ランクアップで特典が増えます', 'color' => $a],
                ]],
                Blocks::stampData('grid_card', 'circle', 'image', $a),
            ],
            'coupons' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'クーポン・特典', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'pill', 'icon' => 'fa-ticket-alt', 'text' => '使えるクーポンをタップ', 'color' => $a, 'text_color' => '#ffffff'],
                ['type' => 'data', 'source' => 'coupons', 'design' => 'cards'],
            ],
            'queue' => [
                ['type' => 'heading', 'design' => 'section', 'text' => '順番待ち', 'size' => 'large'],
                ['type' => 'data', 'source' => 'queue', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'cta', 'text' => '順番待ちを開始', 'action' => '#queue', 'color' => $a, 'full_width' => true],
            ],
            'reservations' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'ご予約', 'size' => 'large'],
                ['type' => 'data', 'source' => 'reservations', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'cta', 'text' => '予約する', 'action' => '#reservations', 'color' => $a, 'full_width' => true],
            ],
            'member' => [
                ['type' => 'member_summary', 'design' => 'wallet', 'greeting' => '会員証'],
                ['type' => 'data', 'source' => 'member_card', 'card_design' => 'emblem'],
                ['type' => 'stats_row', 'items' => [
                    ['label' => 'ポイント', 'value' => '2,480', 'hint' => 'pt', 'field' => 'points'],
                    ['label' => '次の特典まで', 'value' => '20', 'hint' => 'pt', 'field' => 'points_to_next'],
                ]],
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

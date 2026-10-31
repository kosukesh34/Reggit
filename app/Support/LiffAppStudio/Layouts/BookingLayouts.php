<?php

namespace App\Support\LiffAppStudio\Layouts;

use App\Support\LiffAppStudio\Blocks;

/** App Studio の「booking」業種のタブ別レイアウト。 */
final class BookingLayouts
{
    /** ホットペッパー風 — 次回予約カード→空き枠ヒーロー→クイック。ホームに履歴一覧なし */
    public static function layouts(): array
    {
        $a = '#af52de';

        return [
            'home' => [
                ['type' => 'card', 'design' => 'featured', 'title' => '次回のご予約', 'description' => '7月25日（金）14:00〜 カット＋カラー / 担当 田中', 'image_url' => 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?w=800', 'action' => '#reservations'],
                ['type' => 'hero', 'design' => 'split', 'title' => '次はいつ来る？', 'subtitle' => '空き枠からワンタップで予約', 'image_url' => 'https://images.unsplash.com/photo-1560066984-138dadb4c035?w=1400&q=80'],
                ['type' => 'quick_actions', 'design' => 'grid', 'actions' => [
                    ['icon' => 'fa-calendar-plus', 'label' => '予約する', 'action' => '#reservations'],
                    ['icon' => 'fa-exchange-alt', 'label' => '変更', 'action' => '#reservations'],
                    ['icon' => 'fa-id-card', 'label' => '会員証', 'action' => '#member'],
                    ['icon' => 'fa-images', 'label' => 'スタイル', 'action' => '#gallery'],
                ]],
                ['type' => 'button', 'design' => 'sticky', 'text' => '空き枠を見る', 'action' => '#reservations', 'color' => $a, 'full_width' => true],
            ],
            'products' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'メニュー', 'size' => 'large'],
                ['type' => 'data', 'source' => 'products', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'sticky', 'text' => 'このメニューで予約', 'action' => '#reservations', 'color' => $a, 'full_width' => true],
            ],
            'orders' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'ご利用履歴', 'size' => 'large'],
                ['type' => 'data', 'source' => 'orders', 'design' => 'timeline'],
                ['type' => 'button', 'design' => 'cta', 'text' => '同じメニューで予約', 'action' => '#reservations', 'color' => $a],
            ],
            'stamps' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'スタンプ', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'promo', 'icon' => 'fa-gift', 'text' => '来店5回ごとにトリートメント無料', 'color' => $a],
                Blocks::stampData('circle_board', 'circle', 'icon', $a),
            ],
            'coupons' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'クーポン', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'pill', 'icon' => 'fa-ticket-alt', 'text' => '使えるクーポンをタップ', 'color' => $a, 'text_color' => '#ffffff'],
                ['type' => 'data', 'source' => 'coupons', 'design' => 'cards'],
            ],
            'queue' => [
                ['type' => 'heading', 'design' => 'section', 'text' => '順番待ち', 'size' => 'large'],
                ['type' => 'stats_row', 'items' => [
                    ['label' => '現在の待ち', 'value' => '2', 'hint' => '組'],
                    ['label' => '目安', 'value' => '15', 'hint' => '分'],
                ]],
                ['type' => 'data', 'source' => 'queue', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'cta', 'text' => '受付する', 'action' => '#queue', 'color' => $a, 'full_width' => true],
            ],
            'reservations' => [
                ['type' => 'hero', 'design' => 'split', 'title' => 'ご予約', 'subtitle' => 'メニューと日時を選ぶだけ', 'image_url' => 'https://images.unsplash.com/photo-1560066984-138dadb4c035?w=1400&q=80'],
                ['type' => 'list', 'style' => 'number', 'items' => ['メニューを選択', '日時を選ぶ', '予約を確定']],
                ['type' => 'data', 'source' => 'reservations', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'sticky', 'text' => '新規予約', 'action' => '#reservations', 'color' => $a, 'full_width' => true],
            ],
            'member' => [
                ['type' => 'member_summary', 'design' => 'tier', 'greeting' => 'こんにちは'],
                ['type' => 'data', 'source' => 'member_card', 'card_design' => 'emblem'],
                ['type' => 'icon_text', 'rows' => [
                    ['icon' => 'fa-barcode', 'text' => 'レジでバーコードをご提示ください', 'color' => $a],
                    ['icon' => 'fa-coins', 'text' => 'ご来店でポイントが貯まります', 'color' => $a],
                ]],
                ['type' => 'button', 'design' => 'sticky', 'text' => 'バーコードを拡大', 'action' => '#member', 'color' => $a, 'full_width' => true],
            ],
            'gallery' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'スタイルギャラリー', 'size' => 'large'],
                ['type' => 'slider', 'source' => 'gallery', 'design' => 'hero', 'slide_height' => 220],
                ['type' => 'data', 'source' => 'gallery', 'design' => 'cards'],
            ],
            'announcements' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'お知らせ', 'size' => 'large'],
                ['type' => 'data', 'source' => 'announcements', 'design' => 'cards'],
            ],
        ];
    }
}

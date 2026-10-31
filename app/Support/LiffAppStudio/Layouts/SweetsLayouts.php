<?php

namespace App\Support\LiffAppStudio\Layouts;

use App\Support\LiffAppStudio\Blocks;

/** App Studio の「sweets」業種のタブ別レイアウト。 */
final class SweetsLayouts
{
    /** シャトレーゼ — 会員ステータス→季節ヒーロー→会員証/クーポン。会計バーコード最優先 */
    public static function layouts(): array
    {
        $a = '#5C3A21';

        return [
            'home' => [
                ['type' => 'member_summary', 'design' => 'minimal', 'greeting' => 'こんにちは'],
                ['type' => 'stats_row', 'items' => [
                    ['label' => 'ポイント', 'value' => '1,200', 'hint' => 'pt', 'field' => 'points'],
                    ['label' => '次の特典まで', 'value' => '300', 'hint' => 'pt', 'field' => 'points_to_next'],
                ]],
                ['type' => 'slider', 'source' => 'gallery', 'design' => 'hero', 'section_label' => '季節のおすすめ', 'slide_height' => 240],
                ['type' => 'quick_actions', 'design' => 'grid', 'actions' => [
                    ['icon' => 'fa-barcode', 'label' => '会員証', 'action' => '#member'],
                    ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
                ]],
                ['type' => 'button', 'design' => 'sticky', 'text' => '会員証を提示', 'action' => '#member', 'color' => $a, 'full_width' => true],
            ],
            'products' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'スイーツを探す', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'promo', 'icon' => 'fa-shopping-bag', 'text' => 'ネット予約・店頭受け取りがスムーズ', 'color' => $a],
                ['type' => 'data', 'source' => 'products', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'sticky', 'text' => '予約・購入へ', 'action' => '#order/cart', 'color' => $a, 'full_width' => true],
            ],
            'orders' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'ご注文・受取', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'pill', 'icon' => 'fa-store', 'text' => '店頭受取の準備ができたらお知らせします', 'color' => $a, 'text_color' => '#ffffff'],
                ['type' => 'data', 'source' => 'orders', 'design' => 'timeline'],
            ],
            'stamps' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'スタンプ', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'promo', 'icon' => 'fa-gift', 'text' => '対象商品のご購入でスタンプが貯まります', 'color' => $a],
                Blocks::stampData('grid_card', 'rounded', 'solid', $a),
            ],
            'coupons' => [
                ['type' => 'heading', 'design' => 'section', 'text' => '限定クーポン', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'pill', 'icon' => 'fa-ticket-alt', 'text' => '使えるクーポンをタップ', 'color' => $a, 'text_color' => '#ffffff'],
                ['type' => 'data', 'source' => 'coupons', 'design' => 'cards'],
            ],
            'queue' => [
                ['type' => 'heading', 'design' => 'section', 'text' => '順番待ち', 'size' => 'large'],
                ['type' => 'data', 'source' => 'queue', 'design' => 'cards'],
            ],
            'reservations' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'ケーキ予約・店頭受取', 'size' => 'large'],
                ['type' => 'text', 'design' => 'lead', 'text' => '人気ギフトやデコレーションケーキを予約して店頭で受け取れます。'],
                ['type' => 'list', 'style' => 'number', 'items' => ['商品を選ぶ', '受取日時を指定', '店舗で受け取る']],
                ['type' => 'data', 'source' => 'reservations', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'cta', 'text' => '予約する', 'action' => '#reservations', 'color' => $a, 'full_width' => true],
            ],
            'member' => [
                ['type' => 'member_summary', 'design' => 'wallet', 'greeting' => '会員証'],
                ['type' => 'stats_row', 'items' => [
                    ['label' => 'ポイント', 'value' => '0', 'hint' => 'pt', 'field' => 'points'],
                    ['label' => '有効期限', 'value' => '2026/11/07', 'hint' => ''],
                ]],
                ['type' => 'data', 'source' => 'member_card', 'card_design' => 'emblem'],
                ['type' => 'icon_text', 'rows' => [
                    ['icon' => 'fa-barcode', 'text' => 'レジでバーコードをご提示ください', 'color' => $a],
                ]],
                ['type' => 'button', 'design' => 'sticky', 'text' => 'バーコードを拡大', 'action' => '#member', 'color' => $a, 'full_width' => true],
            ],
            'gallery' => [
                ['type' => 'heading', 'design' => 'section', 'text' => '季節のスイーツ', 'size' => 'large'],
                ['type' => 'slider', 'source' => 'gallery', 'design' => 'hero', 'slide_height' => 220],
                ['type' => 'data', 'source' => 'gallery', 'design' => 'cards'],
            ],
            'announcements' => [
                ['type' => 'heading', 'design' => 'section', 'text' => '最新情報', 'size' => 'large'],
                ['type' => 'data', 'source' => 'announcements', 'design' => 'cards'],
            ],
        ];
    }
}

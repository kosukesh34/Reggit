<?php

namespace App\Support\LiffAppStudio\Layouts;

use App\Support\LiffAppStudio\Blocks;

/** App Studio の「retail」業種のタブ別レイアウト。 */
final class RetailLayouts
{
    /** UNIQLO — 検索→カテゴリグリッド→主役1枚。商品一覧データはホームに置かない */
    public static function layouts(): array
    {
        $a = '#111111';

        return [
            'home' => [
                ['type' => 'banner', 'design' => 'promo', 'icon' => 'fa-search', 'text' => '何をお探しですか？', 'color' => $a, 'text_color' => '#ffffff', 'action' => '#products'],
                ['type' => 'quick_actions', 'design' => 'grid', 'actions' => [
                    ['icon' => 'fa-tshirt', 'label' => 'ウィメンズ', 'action' => '#products'],
                    ['icon' => 'fa-male', 'label' => 'メンズ', 'action' => '#products'],
                    ['icon' => 'fa-child', 'label' => 'キッズ', 'action' => '#products'],
                    ['icon' => 'fa-home', 'label' => 'ホーム', 'action' => '#products'],
                ]],
                ['type' => 'card', 'design' => 'overlay', 'title' => '新着', 'description' => '会員先行で公開中', 'image_url' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=800', 'action' => '#products'],
                ['type' => 'quick_actions', 'design' => 'pill', 'actions' => [
                    ['icon' => 'fa-barcode', 'label' => 'スキャン', 'action' => '#products'],
                    ['icon' => 'fa-id-card', 'label' => '会員証', 'action' => '#member'],
                    ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
                ]],
                ['type' => 'button', 'design' => 'sticky', 'text' => '会員証を提示', 'action' => '#member', 'color' => $a, 'full_width' => true],
            ],
            'products' => [
                ['type' => 'data', 'source' => 'products', 'design' => 'cards'],
            ],
            'orders' => [
                ['type' => 'heading', 'design' => 'section', 'text' => '注文履歴', 'size' => 'large'],
                ['type' => 'data', 'source' => 'orders', 'design' => 'timeline'],
                ['type' => 'button', 'design' => 'cta', 'text' => '商品を見る', 'action' => '#products', 'color' => $a],
            ],
            'stamps' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'スタンプ', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'promo', 'icon' => 'fa-gift', 'text' => 'お買い上げ5回で1,000ptプレゼント', 'color' => $a],
                Blocks::stampData('wallet', 'square', 'solid', $a),
            ],
            'coupons' => [
                ['type' => 'banner', 'design' => 'countdown', 'icon' => 'fa-ticket-alt', 'text' => '会員限定シークレットセール 残り12時間', 'color' => $a, 'text_color' => '#ffffff'],
                ['type' => 'data', 'source' => 'coupons', 'design' => 'cards'],
            ],
            'queue' => [
                ['type' => 'heading', 'design' => 'section', 'text' => '順番待ち', 'size' => 'large'],
                ['type' => 'data', 'source' => 'queue', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'cta', 'text' => '受付する', 'action' => '#queue', 'color' => $a, 'full_width' => true],
            ],
            'reservations' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'ご来店予約', 'size' => 'large'],
                ['type' => 'text', 'design' => 'lead', 'text' => '店頭でのパーソナルスタイリングを予約。'],
                ['type' => 'data', 'source' => 'reservations', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'cta', 'text' => '予約する', 'action' => '#reservations', 'color' => $a, 'full_width' => true],
            ],
            'member' => [
                ['type' => 'member_summary', 'design' => 'emblem', 'greeting' => '会員証'],
                ['type' => 'data', 'source' => 'member_card', 'card_design' => 'emblem'],
                ['type' => 'icon_text', 'rows' => [
                    ['icon' => 'fa-barcode', 'text' => 'レジでバーコードをご提示ください', 'color' => $a],
                    ['icon' => 'fa-crown', 'text' => 'プラチナ会員は先行セール参加可能', 'color' => $a],
                ]],
                ['type' => 'button', 'design' => 'sticky', 'text' => 'バーコードを拡大', 'action' => '#member', 'color' => $a, 'full_width' => true],
            ],
            'gallery' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'ルックブック', 'size' => 'large'],
                ['type' => 'slider', 'source' => 'gallery', 'design' => 'hero', 'slide_height' => 240],
                ['type' => 'data', 'source' => 'gallery', 'design' => 'cards'],
            ],
            'announcements' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'お知らせ', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'pill', 'icon' => 'fa-bullhorn', 'text' => 'セール・入荷情報をお届け', 'color' => $a, 'text_color' => '#ffffff'],
                ['type' => 'data', 'source' => 'announcements', 'design' => 'cards'],
            ],
        ];
    }
}

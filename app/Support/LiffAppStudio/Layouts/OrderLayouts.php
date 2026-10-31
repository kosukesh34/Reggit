<?php

namespace App\Support\LiffAppStudio\Layouts;

use App\Support\LiffAppStudio\Blocks;

/** App Studio の「order」業種のタブ別レイアウト。 */
final class OrderLayouts
{
    /** BK / マクド — 受取カード→プロモ→クーポン2列→注文CTA。ホームに商品棚なし */
    public static function layouts(): array
    {
        $a = '#A81612';
        $ink = '#3D2B1F';

        return [
            'home' => [
                ['type' => 'card', 'design' => 'featured', 'title' => '受取番号 #248', 'description' => '本日 12:00 受取 — ご注文は棚の2袋です。わからないときはスタッフへ', 'image_url' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=800', 'action' => '#orders'],
                ['type' => 'stats_row', 'section_label' => 'ポイント', 'items' => [
                    ['label' => '利用可能', 'value' => '1,250', 'hint' => 'pt', 'field' => 'points'],
                    ['label' => '次の特典まで', 'value' => '250', 'hint' => 'pt', 'field' => 'points_to_next'],
                ]],
                ['type' => 'heading', 'design' => 'section', 'text' => 'クーポン', 'size' => 'large'],
                ['type' => 'data', 'source' => 'coupons', 'design' => 'cards'],
                ['type' => 'quick_actions', 'design' => 'grid', 'actions' => [
                    ['icon' => 'fa-utensils', 'label' => 'メニュー', 'action' => '#products'],
                    ['icon' => 'fa-mobile-alt', 'label' => 'オーダー', 'action' => '#products'],
                    ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
                    ['icon' => 'fa-qrcode', 'label' => 'ポイント', 'action' => '#member'],
                ]],
                ['type' => 'button', 'design' => 'sticky', 'text' => 'モバイルオーダーする', 'action' => '#products', 'color' => $ink, 'full_width' => true],
            ],
            'products' => [
                ['type' => 'slider', 'source' => 'gallery', 'design' => 'hero', 'section_label' => 'おすすめ', 'slide_height' => 180],
                ['type' => 'quick_actions', 'design' => 'pill', 'actions' => [
                    ['icon' => 'fa-fire', 'label' => '人気', 'action' => '#products'],
                    ['icon' => 'fa-hamburger', 'label' => 'バーガー', 'action' => '#products'],
                    ['icon' => 'fa-coffee', 'label' => 'ドリンク', 'action' => '#products'],
                    ['icon' => 'fa-ice-cream', 'label' => 'デザート', 'action' => '#products'],
                ]],
                ['type' => 'data', 'source' => 'products', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'sticky', 'text' => 'カートに進む', 'action' => '#order/cart', 'color' => $a, 'full_width' => true],
            ],
            'orders' => [
                ['type' => 'banner', 'design' => 'pill', 'icon' => 'fa-bag-shopping', 'text' => '受取準備完了 — 棚の2袋', 'color' => $a, 'text_color' => '#ffffff'],
                ['type' => 'icon_text', 'rows' => [
                    ['icon' => 'fa-check-circle', 'text' => '受付 → 調理中 → 受取可能', 'color' => $a],
                    ['icon' => 'fa-clock', 'text' => '受取時刻になったら店舗へ', 'color' => $ink],
                ]],
                ['type' => 'data', 'source' => 'orders', 'design' => 'timeline'],
                ['type' => 'button', 'design' => 'cta', 'text' => '再注文する', 'action' => '#products', 'color' => $a],
            ],
            'stamps' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'スタンプ', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'promo', 'icon' => 'fa-gift', 'text' => '10個でお好きなセットメニュー無料', 'color' => $a],
                Blocks::stampData('wallet', 'rounded', 'solid', $a),
            ],
            'coupons' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'クーポン', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'pill', 'icon' => 'fa-ticket-alt', 'text' => '使えるクーポンをタップ', 'color' => $ink, 'text_color' => '#ffffff'],
                ['type' => 'data', 'source' => 'coupons', 'design' => 'cards'],
            ],
            'queue' => [
                ['type' => 'heading', 'design' => 'section', 'text' => '順番待ち', 'size' => 'large'],
                ['type' => 'stats_row', 'items' => [
                    ['label' => '現在の待ち', 'value' => '3', 'hint' => '組'],
                    ['label' => '目安時間', 'value' => '8', 'hint' => '分'],
                ]],
                ['type' => 'data', 'source' => 'queue', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'cta', 'text' => '受付する', 'action' => '#queue', 'color' => $a, 'full_width' => true],
            ],
            'reservations' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'ご予約', 'size' => 'large'],
                ['type' => 'text', 'design' => 'lead', 'text' => '来店予約で待ち時間なし。'],
                ['type' => 'data', 'source' => 'reservations', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'cta', 'text' => '予約する', 'action' => '#reservations', 'color' => $a, 'full_width' => true],
            ],
            'member' => [
                ['type' => 'banner', 'design' => 'promo', 'icon' => 'fa-crown', 'text' => 'ステージUPを目指しましょう！', 'color' => '#8B6914'],
                ['type' => 'member_summary', 'design' => 'tier', 'greeting' => 'マイ・メンバーシップ'],
                ['type' => 'data', 'source' => 'member_card', 'card_design' => 'emblem'],
                ['type' => 'stats_row', 'items' => [
                    ['label' => 'ポイント', 'value' => '1,250', 'hint' => 'pt', 'field' => 'points'],
                    ['label' => '次のステージまで', 'value' => '¥1,700', 'hint' => '', 'field' => 'points_to_next'],
                ]],
                ['type' => 'icon_text', 'rows' => [
                    ['icon' => 'fa-barcode', 'text' => 'レジでバーコードをご提示ください', 'color' => $a],
                ]],
                ['type' => 'button', 'design' => 'sticky', 'text' => 'バーコードを拡大', 'action' => '#member', 'color' => $ink, 'full_width' => true],
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

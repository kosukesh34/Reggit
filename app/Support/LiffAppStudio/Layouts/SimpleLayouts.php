<?php

namespace App\Support\LiffAppStudio\Layouts;

use App\Support\LiffAppStudio\Blocks;

/** App Studio の「simple」業種のタブ別レイアウト。 */
final class SimpleLayouts
{
    /** 会員証・クーポン・スタンプだけの最小構成 */
    public static function layouts(): array
    {
        $a = '#171717';

        return [
            'home' => [
                ['type' => 'member_summary', 'design' => 'minimal', 'greeting' => 'こんにちは'],
                ['type' => 'heading', 'design' => 'section', 'text' => '必要なものだけ、すぐ使える。', 'size' => 'large'],
                ['type' => 'text', 'design' => 'lead', 'text' => '会員証・クーポン・スタンプの3機能に絞ったシンプルな会員アプリです。'],
                ['type' => 'quick_actions', 'design' => 'list', 'actions' => [
                    ['icon' => 'fa-barcode', 'label' => '会員証', 'action' => '#member'],
                    ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
                    ['icon' => 'fa-stamp', 'label' => 'スタンプ', 'action' => '#stamps'],
                ]],
            ],
            'products' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'メニュー', 'size' => 'large'],
                ['type' => 'text', 'design' => 'body', 'text' => 'このシンプル構成では使用しません。'],
            ],
            'orders' => [
                ['type' => 'heading', 'design' => 'section', 'text' => '注文', 'size' => 'large'],
                ['type' => 'text', 'design' => 'body', 'text' => 'このシンプル構成では使用しません。'],
            ],
            'stamps' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'スタンプ', 'size' => 'large'],
                ['type' => 'text', 'design' => 'lead', 'text' => '10個貯まると特典をプレゼントします。'],
                Blocks::stampData('minimal', 'rounded', 'solid', $a),
            ],
            'coupons' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'クーポン', 'size' => 'large'],
                ['type' => 'text', 'design' => 'lead', 'text' => '使いたいクーポンを選んでください。'],
                ['type' => 'data', 'source' => 'coupons', 'design' => 'list'],
            ],
            'queue' => [
                ['type' => 'heading', 'design' => 'section', 'text' => '順番待ち', 'size' => 'large'],
                ['type' => 'text', 'design' => 'body', 'text' => 'このシンプル構成では使用しません。'],
            ],
            'reservations' => [
                ['type' => 'heading', 'design' => 'section', 'text' => '予約', 'size' => 'large'],
                ['type' => 'text', 'design' => 'body', 'text' => 'このシンプル構成では使用しません。'],
            ],
            'member' => [
                ['type' => 'member_summary', 'design' => 'minimal', 'greeting' => '会員証'],
                ['type' => 'data', 'source' => 'member_card', 'card_design' => 'barcode'],
                ['type' => 'icon_text', 'rows' => [
                    ['icon' => 'fa-barcode', 'text' => 'お会計の前にバーコードをご提示ください', 'color' => $a],
                ]],
                ['type' => 'button', 'design' => 'cta', 'text' => 'バーコードを大きく表示', 'action' => '#member', 'color' => $a, 'full_width' => true],
            ],
            'gallery' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'ギャラリー', 'size' => 'large'],
                ['type' => 'text', 'design' => 'body', 'text' => 'このシンプル構成では使用しません。'],
            ],
            'announcements' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'お知らせ', 'size' => 'large'],
                ['type' => 'text', 'design' => 'body', 'text' => 'このシンプル構成では使用しません。'],
            ],
        ];
    }
}

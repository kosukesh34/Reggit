<?php

namespace App\Support\LiffAppStudio\Layouts;

/** App Studio の「counter」業種のタブ別レイアウト。 */
final class CounterLayouts
{
    /** 店舗カウンター向け — 状況数値→注文/順番待ちの大ボタン→店頭ツール。 */
    public static function layouts(): array
    {
        $layouts = OrderLayouts::layouts();
        $a = '#151515';

        $layouts['home'] = [
            ['type' => 'stats_row', 'section_label' => '現在の店舗状況', 'items' => [
                ['label' => '最短受取', 'value' => '12', 'hint' => '分'],
                ['label' => '店内待ち', 'value' => '3', 'hint' => '組'],
                ['label' => '営業', 'value' => '21:00', 'hint' => 'まで'],
            ]],
            ['type' => 'quick_actions', 'design' => 'counter', 'actions' => [
                ['icon' => 'fa-bag-shopping', 'label' => '持ち帰りを注文', 'action' => '#products'],
                ['icon' => 'fa-users', 'label' => '順番待ちを受付', 'action' => '#queue'],
            ]],
            ['type' => 'heading', 'design' => 'section', 'text' => '店舗で使う', 'size' => 'large'],
            ['type' => 'quick_actions', 'design' => 'list', 'actions' => [
                ['icon' => 'fa-id-card', 'label' => '会員証を表示', 'action' => '#member'],
                ['icon' => 'fa-ticket-alt', 'label' => 'クーポンを選ぶ', 'action' => '#coupons'],
                ['icon' => 'fa-receipt', 'label' => '注文・受取状況', 'action' => '#orders'],
            ]],
            ['type' => 'button', 'design' => 'sticky', 'text' => '持ち帰りを注文する', 'action' => '#products', 'color' => $a, 'full_width' => true],
        ];

        return $layouts;
    }
}

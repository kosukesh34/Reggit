<?php

namespace App\Support\LiffAppStudio\Layouts;

use App\Support\LiffAppStudio\Blocks;

/** App Studio の「clinic」業種のタブ別レイアウト。 */
final class ClinicLayouts
{
    /** クリニック — 待ち状況→予約CTA。FAQ/アクセスは案内タブ相当、ホームは短く */
    public static function layouts(): array
    {
        $a = '#0284c7';

        return [
            'home' => [
                ['type' => 'stats_row', 'section_label' => '本日の状況', 'items' => [
                    ['label' => '待ち時間', 'value' => '12', 'hint' => '分'],
                    ['label' => '待ち人数', 'value' => '5', 'hint' => '人'],
                    ['label' => '次回予約', 'value' => '8/3', 'hint' => ''],
                ]],
                ['type' => 'banner', 'design' => 'promo', 'icon' => 'fa-info-circle', 'text' => '本日の診察は通常通り受付中です', 'color' => $a],
                ['type' => 'quick_actions', 'design' => 'grid', 'actions' => [
                    ['icon' => 'fa-calendar-check', 'label' => '予約', 'action' => '#reservations'],
                    ['icon' => 'fa-users', 'label' => '順番待ち', 'action' => '#queue'],
                    ['icon' => 'fa-id-card', 'label' => '診察券', 'action' => '#member'],
                    ['icon' => 'fa-bullhorn', 'label' => 'お知らせ', 'action' => '#announcements'],
                ]],
                ['type' => 'button', 'design' => 'sticky', 'text' => '予約する', 'action' => '#reservations', 'color' => $a, 'full_width' => true],
            ],
            'products' => [
                ['type' => 'heading', 'design' => 'section', 'text' => '診療メニュー', 'size' => 'large'],
                ['type' => 'data', 'source' => 'products', 'design' => 'cards'],
            ],
            'orders' => [
                ['type' => 'heading', 'design' => 'section', 'text' => '受診履歴', 'size' => 'large'],
                ['type' => 'data', 'source' => 'orders', 'design' => 'timeline'],
            ],
            'stamps' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'スタンプ', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'promo', 'icon' => 'fa-gift', 'text' => '定期受診でポイントが貯まります', 'color' => $a],
                Blocks::stampData('minimal', 'circle', 'solid', $a),
            ],
            'coupons' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'クーポン', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'pill', 'icon' => 'fa-ticket-alt', 'text' => '使えるクーポンをタップ', 'color' => $a, 'text_color' => '#ffffff'],
                ['type' => 'data', 'source' => 'coupons', 'design' => 'cards'],
            ],
            'queue' => [
                ['type' => 'heading', 'design' => 'section', 'text' => '順番待ち受付', 'size' => 'large'],
                ['type' => 'stats_row', 'items' => [
                    ['label' => '待ち人数', 'value' => '5', 'hint' => '人'],
                    ['label' => '目安', 'value' => '20', 'hint' => '分'],
                ]],
                ['type' => 'icon_text', 'rows' => [
                    ['icon' => 'fa-mobile-alt', 'text' => '順番が近づいたら LINE で通知', 'color' => $a],
                    ['icon' => 'fa-walking', 'text' => '呼び出しまで外出いただけます', 'color' => $a],
                ]],
                ['type' => 'data', 'source' => 'queue', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'cta', 'text' => '順番待ちを開始', 'action' => '#queue', 'color' => $a, 'full_width' => true],
            ],
            'reservations' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'ご予約', 'size' => 'large'],
                ['type' => 'text', 'design' => 'lead', 'text' => 'ご希望の診療科と日時を選択してください。'],
                ['type' => 'list', 'style' => 'number', 'items' => ['診療科を選択', '希望日時を選ぶ', '予約を確定']],
                ['type' => 'data', 'source' => 'reservations', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'cta', 'text' => '予約する', 'action' => '#reservations', 'color' => $a, 'full_width' => true],
            ],
            'member' => [
                ['type' => 'member_summary', 'design' => 'wallet', 'greeting' => 'こんにちは'],
                ['type' => 'heading', 'design' => 'section', 'text' => '診察券', 'size' => 'large'],
                ['type' => 'data', 'source' => 'member_card', 'card_design' => 'emblem'],
                ['type' => 'icon_text', 'rows' => [
                    ['icon' => 'fa-barcode', 'text' => 'レジでバーコードをご提示ください', 'color' => $a],
                ]],
                ['type' => 'button', 'design' => 'sticky', 'text' => 'バーコードを拡大', 'action' => '#member', 'color' => $a, 'full_width' => true],
            ],
            'gallery' => [
                ['type' => 'heading', 'design' => 'section', 'text' => '院内のご案内', 'size' => 'large'],
                ['type' => 'slider', 'source' => 'gallery', 'design' => 'hero', 'slide_height' => 200],
                ['type' => 'data', 'source' => 'gallery', 'design' => 'cards'],
            ],
            'announcements' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'お知らせ', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'pill', 'icon' => 'fa-bullhorn', 'text' => '休診日・予防接種のご案内', 'color' => $a, 'text_color' => '#ffffff'],
                ['type' => 'hours', 'design' => 'card', 'title' => '診療時間', 'rows' => [
                    ['day' => '月〜金', 'time' => '9:00 - 18:00'],
                    ['day' => '土', 'time' => '9:00 - 13:00'],
                    ['day' => '日・祝', 'time' => '休診'],
                ]],
                ['type' => 'access', 'design' => 'card', 'title' => 'アクセス', 'address' => '店舗住所（編集してください）', 'note' => '最寄駅からの案内を編集'],
                ['type' => 'faq', 'design' => 'accordion', 'title' => 'よくある質問', 'items' => [
                    ['q' => '初診の持ち物は？', 'a' => '保険証・お薬手帳をお持ちください。'],
                    ['q' => 'キャンセルできますか？', 'a' => '予約詳細から変更・キャンセルが可能です。'],
                ]],
                ['type' => 'data', 'source' => 'announcements', 'design' => 'cards'],
            ],
        ];
    }
}

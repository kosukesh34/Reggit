<?php

namespace App\Support\LiffAppStudio\Layouts;

/** App Studio の「appointment」業種のタブ別レイアウト。 */
final class AppointmentLayouts
{
    /** 来店予約向け — 次回予定→空き枠→予約CTA→来店ツール。 */
    public static function layouts(): array
    {
        $layouts = BookingLayouts::layouts();
        $a = '#203F3A';

        $layouts['home'] = [
            ['type' => 'card', 'design' => 'next_appointment', 'title' => '次のご予約', 'description' => '8月21日（金）14:00〜 カット＋カラー / 担当 田中', 'image_url' => '/assets/salon-feature.webp', 'action' => '#reservations'],
            ['type' => 'heading', 'design' => 'section', 'text' => '空き時間から選ぶ', 'size' => 'large'],
            ['type' => 'quick_actions', 'design' => 'slots', 'actions' => [
                ['icon' => 'fa-clock', 'label' => '本日 17:30', 'action' => '#reservations'],
                ['icon' => 'fa-clock', 'label' => '明日 11:00', 'action' => '#reservations'],
                ['icon' => 'fa-clock', 'label' => '8/14 15:30', 'action' => '#reservations'],
            ]],
            ['type' => 'button', 'design' => 'cta', 'text' => '新しい予約を取る', 'action' => '#reservations', 'color' => $a, 'full_width' => true],
            ['type' => 'quick_actions', 'design' => 'grid', 'actions' => [
                ['icon' => 'fa-id-card', 'label' => '会員証', 'action' => '#member'],
                ['icon' => 'fa-ticket-alt', 'label' => 'ご優待', 'action' => '#coupons'],
                ['icon' => 'fa-images', 'label' => 'スタイル', 'action' => '#gallery'],
            ]],
        ];

        return $layouts;
    }
}

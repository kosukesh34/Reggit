<?php

namespace App\Support\LiffAppStudio;

/** 業種レイアウト・単体画面で共通に使うブロック。 */
final class Blocks
{
    /**
     * スタンプカードブロック。業種ごとに見た目が揃わないよう design/slot/filled を明示する。
     *
     * @return array<string, mixed>
     */
    public static function stampData(
        string $design = 'grid_card',
        string $slot = 'circle',
        string $filled = 'image',
        string $accent = ''
    ): array {
        $row = [
            'type' => 'data',
            'source' => 'stamps',
            'design' => 'cards',
            'stamp_design' => $design,
            'stamp_slot_style' => $slot,
            'stamp_filled_style' => $filled,
        ];
        if ($accent !== '') {
            $row['stamp_accent_color'] = $accent;
        }

        return $row;
    }
}

<?php

namespace App\Support;

/**
 * LIFF（顧客向け）画面の本格SaaSテーマプリセット。
 *
 * テーマは _global 設定（tab_settings JSON 内）に保存される以下のキーで構成される:
 *   - theme_preset : プリセットID（下記 PRESETS のいずれか / 'custom'）
 *   - accent_color : アクセント（ブランド）カラー hex（プリセットを上書き）
 *   - font_family  : フォント（sans / rounded / serif / serif-display）
 *   - surface_tone : 背景トーン（paper / warm / cool / mist / charcoal）
 *
 * cssVariablesFor() でこれらを解決し、CSS カスタムプロパティのマップを返す。
 */
class LiffThemePresets
{
    /** フォント選択肢（key => [label, base, display]） */
    public static function fontStacks(): array
    {
        return [
            'sans' => [
                'label' => 'モダン（ゴシック）',
                'base' => 'var(--font-sans)',
                'display' => 'var(--font-sans)',
            ],
            'rounded' => [
                'label' => '丸ゴシック（やわらか）',
                'base' => 'var(--font-rounded)',
                'display' => 'var(--font-rounded)',
            ],
            'serif' => [
                'label' => '明朝（上質）',
                'base' => 'var(--font-serif)',
                'display' => 'var(--font-serif)',
            ],
            'serif-display' => [
                'label' => '見出しのみ明朝',
                'base' => 'var(--font-sans)',
                'display' => 'var(--font-serif)',
            ],
        ];
    }

    /** 背景トーン（key => [label, tokens...]） */
    public static function surfaceTones(): array
    {
        return [
            'paper' => [
                'label' => 'ペーパー（標準）',
                'tokens' => [
                    '--bg-canvas' => '#f7f6f2',
                    '--bg-light' => '#fffefb',
                    '--bg-subtle' => '#faf9f5',
                    '--surface' => '#fffefb',
                    '--surface-2' => '#f9f8f3',
                    '--surface-muted' => '#f1efe8',
                    '--text-primary' => '#1c1917',
                    '--text-secondary' => '#57534e',
                    '--text-tertiary' => '#a8a29e',
                    '--border-color' => '#e8e5dd',
                    '--border-strong' => '#d8d4c8',
                    '--hairline' => 'rgba(28, 25, 23, 0.06)',
                ],
            ],
            'warm' => [
                'label' => 'ウォーム（あたたかい）',
                'tokens' => [
                    '--bg-canvas' => '#faf5ee',
                    '--bg-light' => '#fffdf8',
                    '--bg-subtle' => '#fcf8f1',
                    '--surface' => '#fffdf8',
                    '--surface-2' => '#fbf6ed',
                    '--surface-muted' => '#f4ece0',
                    '--text-primary' => '#26201a',
                    '--text-secondary' => '#6b5d4f',
                    '--text-tertiary' => '#b3a591',
                    '--border-color' => '#ece2d3',
                    '--border-strong' => '#ddd0bb',
                    '--hairline' => 'rgba(38, 32, 26, 0.06)',
                ],
            ],
            'cool' => [
                'label' => 'クール（すっきり）',
                'tokens' => [
                    '--bg-canvas' => '#f2f5f8',
                    '--bg-light' => '#ffffff',
                    '--bg-subtle' => '#f7f9fb',
                    '--surface' => '#ffffff',
                    '--surface-2' => '#f5f7fa',
                    '--surface-muted' => '#eef1f5',
                    '--text-primary' => '#0f172a',
                    '--text-secondary' => '#475569',
                    '--text-tertiary' => '#94a3b8',
                    '--border-color' => '#e2e8f0',
                    '--border-strong' => '#cbd5e1',
                    '--hairline' => 'rgba(15, 23, 42, 0.06)',
                ],
            ],
            'mist' => [
                'label' => 'ミスト（ニュートラル）',
                'tokens' => [
                    '--bg-canvas' => '#f5f5f4',
                    '--bg-light' => '#ffffff',
                    '--bg-subtle' => '#fafafa',
                    '--surface' => '#ffffff',
                    '--surface-2' => '#f7f7f6',
                    '--surface-muted' => '#efeeec',
                    '--text-primary' => '#1c1917',
                    '--text-secondary' => '#57534e',
                    '--text-tertiary' => '#a3a3a3',
                    '--border-color' => '#e7e5e4',
                    '--border-strong' => '#d6d3d1',
                    '--hairline' => 'rgba(28, 25, 23, 0.05)',
                ],
            ],
            'charcoal' => [
                'label' => 'チャコール（ダーク）',
                'tokens' => [
                    '--bg-canvas' => '#141312',
                    '--bg-light' => '#1f1d1b',
                    '--bg-subtle' => '#1a1817',
                    '--surface' => '#211f1d',
                    '--surface-2' => '#272422',
                    '--surface-muted' => '#2e2b28',
                    '--text-primary' => '#f5f3f0',
                    '--text-secondary' => '#c4bfb8',
                    '--text-tertiary' => '#8a847c',
                    '--border-color' => 'rgba(245, 243, 240, 0.12)',
                    '--border-strong' => 'rgba(245, 243, 240, 0.2)',
                    '--hairline' => 'rgba(245, 243, 240, 0.08)',
                ],
            ],
        ];
    }

    /**
     * プリセット一覧（UI のギャラリー表示・適用に使う）。
     *
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            [
                'id' => 'noir',
                'name' => 'ノワール',
                'description' => 'モノトーンで洗練された、上質ミニマル。',
                'accent_color' => '#1c1917',
                'font_family' => 'sans',
                'surface_tone' => 'paper',
                'radius_scale' => 'soft',
                'tab_style' => 'default',
                'section_gap' => 16,
            ],
            [
                'id' => 'royal',
                'name' => 'ロイヤル',
                'description' => '深い藍と明朝見出しで知的な高級感。',
                'accent_color' => '#3730a3',
                'font_family' => 'serif-display',
                'surface_tone' => 'cool',
                'radius_scale' => 'default',
                'tab_style' => 'pill',
                'section_gap' => 16,
            ],
            [
                'id' => 'emerald',
                'name' => 'エメラルド',
                'description' => '落ち着いた深緑で信頼感のある印象。',
                'accent_color' => '#0f766e',
                'font_family' => 'sans',
                'surface_tone' => 'paper',
                'radius_scale' => 'soft',
                'tab_style' => 'floating',
                'section_gap' => 18,
            ],
            [
                'id' => 'bordeaux',
                'name' => 'ボルドー',
                'description' => 'ワインレッド×明朝で重厚なプレミアム。',
                'accent_color' => '#9f1239',
                'font_family' => 'serif',
                'surface_tone' => 'warm',
                'radius_scale' => 'default',
                'tab_style' => 'default',
                'section_gap' => 16,
            ],
            [
                'id' => 'sand',
                'name' => 'サンド',
                'description' => 'キャメル×丸みでやさしいナチュラル。',
                'accent_color' => '#a8763e',
                'font_family' => 'rounded',
                'surface_tone' => 'warm',
                'radius_scale' => 'round',
                'tab_style' => 'pill',
                'section_gap' => 18,
            ],
            [
                'id' => 'ocean',
                'name' => 'オーシャン',
                'description' => '澄んだ青で爽快感のあるモダン。',
                'accent_color' => '#0369a1',
                'font_family' => 'sans',
                'surface_tone' => 'cool',
                'radius_scale' => 'soft',
                'tab_style' => 'floating',
                'section_gap' => 16,
            ],
            [
                'id' => 'sakura',
                'name' => 'サクラ',
                'description' => 'やわらかなローズで親しみやすく華やか。',
                'accent_color' => '#be185d',
                'font_family' => 'rounded',
                'surface_tone' => 'warm',
                'radius_scale' => 'round',
                'tab_style' => 'pill',
                'section_gap' => 18,
            ],
            [
                'id' => 'mono-night',
                'name' => 'ミッドナイト',
                'description' => 'ダーク基調で先進的・プレミアムな夜の表情。',
                'accent_color' => '#d4af37',
                'font_family' => 'sans',
                'surface_tone' => 'charcoal',
                'radius_scale' => 'soft',
                'tab_style' => 'floating',
                'section_gap' => 16,
            ],
        ];
    }

    /** プリセットIDから定義を取得 */
    public static function find(?string $id): ?array
    {
        if ($id === null || $id === '') {
            return null;
        }
        foreach (self::all() as $preset) {
            if (($preset['id'] ?? null) === $id) {
                return $preset;
            }
        }

        return null;
    }

    /**
     * _global 設定からテーマを解決し、出力すべき CSS カスタムプロパティのマップを返す。
     *
     * @param  array<string, mixed>  $global
     * @return array<string, string>
     */
    public static function cssVariablesFor(?array $global): array
    {
        $global = is_array($global) ? $global : [];
        $vars = [];

        $preset = self::find($global['theme_preset'] ?? null);

        // アクセント（ブランド）カラー: 個別指定 > プリセット
        $accent = self::normalizeHex($global['accent_color'] ?? null)
            ?? ($preset['accent_color'] ?? null);
        if ($accent !== null) {
            $vars['--primary-color'] = $accent;
            $vars['--primary-rgb'] = self::hexToRgb($accent);
            $vars['--primary-dark'] = self::darken($accent, 0.16);
        }

        // フォント: 個別指定 > プリセット
        $fontKey = (string) ($global['font_family'] ?? ($preset['font_family'] ?? ''));
        $fonts = self::fontStacks();
        if (isset($fonts[$fontKey])) {
            $vars['--font-base'] = $fonts[$fontKey]['base'];
            $vars['--font-display'] = $fonts[$fontKey]['display'];
        }

        // 背景トーン: 個別指定 > プリセット
        $toneKey = (string) ($global['surface_tone'] ?? ($preset['surface_tone'] ?? ''));
        $tones = self::surfaceTones();
        if (isset($tones[$toneKey])) {
            foreach ($tones[$toneKey]['tokens'] as $name => $value) {
                $vars[$name] = $value;
            }
        }

        return $vars;
    }

    private static function normalizeHex(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }
        $value = trim($value);
        if (preg_match('/^#[0-9a-fA-F]{6}$/', $value) !== 1) {
            return null;
        }

        return strtolower($value);
    }

    private static function hexToRgb(string $hex): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) {
            return '0, 0, 0';
        }
        $r = (int) hexdec(substr($hex, 0, 2));
        $g = (int) hexdec(substr($hex, 2, 2));
        $b = (int) hexdec(substr($hex, 4, 2));

        return "{$r}, {$g}, {$b}";
    }

    private static function darken(string $hex, float $amount): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) {
            return '#'.$hex;
        }
        $amount = max(0.0, min(1.0, $amount));
        $channels = [];
        foreach ([0, 2, 4] as $offset) {
            $value = (int) hexdec(substr($hex, $offset, 2));
            $value = (int) round($value * (1 - $amount));
            $channels[] = str_pad(dechex(max(0, min(255, $value))), 2, '0', STR_PAD_LEFT);
        }

        return '#'.implode('', $channels);
    }
}

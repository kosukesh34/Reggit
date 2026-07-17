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
 *   - card_style   : カードの質感（flat / elevated / outline）
 *   - button_shape : ボタン形状（rounded / pill / square）
 *   - density           : 余白の密度（compact / default / relaxed）
 *   - background_style  : ページ背景（solid / gradient / image）
 *   - bg_color          : 背景色 hex
 *   - bg_gradient_start : グラデーション開始色
 *   - bg_gradient_end   : グラデーション終了色
 *   - bg_image_url      : 背景画像 URL（https）
 *   - bg_overlay_opacity: 画像オーバーレイ不透明度 0〜80
 *   - content_width     : コンテンツ幅（edge / comfy / narrow）
 *
 * cssVariablesFor() で CSS カスタムプロパティのマップを、
 * bodyClassesFor() で body に付与するテーマクラスの配列を返す。
 */
class LiffThemePresets
{
    /** コンテンツ幅（key => [label, css vars, body class]） */
    public static function contentWidths(): array
    {
        return [
            'edge' => [
                'label' => 'フル幅（端まで）',
                'tokens' => ['--content-max-width' => '100%'],
            ],
            'comfy' => [
                'label' => 'ゆったり（推奨）',
                'tokens' => ['--content-max-width' => 'min(100%, 440px)'],
            ],
            'narrow' => [
                'label' => 'ナロー（読みやすさ重視）',
                'tokens' => ['--content-max-width' => 'min(100%, 380px)'],
            ],
        ];
    }

    /** 背景スタイル選択肢 */
    public static function backgroundStyles(): array
    {
        return [
            'solid' => '単色',
            'gradient' => 'グラデーション',
            'image' => '背景画像',
        ];
    }

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
            'ios' => [
                'label' => 'iOS（システム）',
                'tokens' => [
                    '--bg-canvas' => '#f2f2f7',
                    '--bg-light' => '#ffffff',
                    '--bg-subtle' => '#e5e5ea',
                    '--surface' => '#ffffff',
                    '--surface-2' => '#f9f9fb',
                    '--surface-muted' => '#ebebf0',
                    '--surface-glass' => 'rgba(255, 255, 255, 0.72)',
                    '--text-primary' => '#1c1c1e',
                    '--text-secondary' => 'rgba(60, 60, 67, 0.68)',
                    '--text-tertiary' => 'rgba(60, 60, 67, 0.36)',
                    '--border-color' => 'rgba(60, 60, 67, 0.18)',
                    '--border-strong' => 'rgba(60, 60, 67, 0.29)',
                    '--hairline' => 'rgba(60, 60, 67, 0.12)',
                    '--chrome' => '#f2f2f7',
                    '--chrome-dark' => '#e5e5ea',
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
                'id' => 'apple_system',
                'name' => 'Apple System',
                'description' => 'iOS Liquid Glass 風。ライトクローム・フローティングタブの標準アプリ体験。',
                'accent_color' => '#007aff',
                'font_family' => 'sans',
                'surface_tone' => 'ios',
                'radius_scale' => 'soft',
                'tab_style' => 'floating',
                'section_gap' => 20,
                'card_style' => 'elevated',
                'button_shape' => 'pill',
                'density' => 'default',
                'background_style' => 'solid',
                'bg_color' => '#f2f2f7',
                'content_width' => 'comfy',
                'system_ui_style' => 'refined',
                'system_header_style' => 'minimal',
                'member_screen_design' => 'apple',
                'coupon_screen_design' => 'apple',
                'reservation_screen_design' => 'apple',
                'activity_screen_design' => 'cards',
            ],
            [
                'id' => 'noir',
                'name' => 'ノワール',
                'description' => 'モノトーンの上質ミニマル。アパレル・雑貨に。',
                'accent_color' => '#1c1917',
                'font_family' => 'sans',
                'surface_tone' => 'paper',
                'radius_scale' => 'soft',
                'tab_style' => 'default',
                'section_gap' => 16,
                'card_style' => 'outline',
                'button_shape' => 'square',
                'density' => 'default',
                'background_style' => 'solid',
                'bg_color' => '#f7f6f2',
                'content_width' => 'comfy',
            ],
            [
                'id' => 'royal',
                'name' => 'ロイヤル',
                'description' => '深い藍と明朝見出しで知的な高級感。ホテル・料亭に。',
                'accent_color' => '#3730a3',
                'font_family' => 'serif-display',
                'surface_tone' => 'cool',
                'radius_scale' => 'default',
                'tab_style' => 'pill',
                'section_gap' => 16,
                'card_style' => 'elevated',
                'button_shape' => 'rounded',
                'density' => 'relaxed',
                'background_style' => 'gradient',
                'bg_gradient_start' => '#eef2ff',
                'bg_gradient_end' => '#f8fafc',
                'content_width' => 'comfy',
            ],
            [
                'id' => 'emerald',
                'name' => 'エメラルド',
                'description' => '落ち着いた深緑で信頼感。クリニック・整体に。',
                'accent_color' => '#0f766e',
                'font_family' => 'sans',
                'surface_tone' => 'paper',
                'radius_scale' => 'soft',
                'tab_style' => 'floating',
                'section_gap' => 18,
                'card_style' => 'flat',
                'button_shape' => 'rounded',
                'density' => 'default',
                'background_style' => 'solid',
                'bg_color' => '#f0fdfa',
                'content_width' => 'comfy',
            ],
            [
                'id' => 'bordeaux',
                'name' => 'ボルドー',
                'description' => 'ワインレッド×明朝の重厚プレミアム。レストラン・バーに。',
                'accent_color' => '#9f1239',
                'font_family' => 'serif',
                'surface_tone' => 'warm',
                'radius_scale' => 'default',
                'tab_style' => 'default',
                'section_gap' => 16,
                'card_style' => 'elevated',
                'button_shape' => 'rounded',
                'density' => 'relaxed',
                'background_style' => 'image',
                'bg_image_url' => 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=1600&q=80',
                'bg_overlay_opacity' => 72,
                'bg_color' => '#1c1917',
                'content_width' => 'comfy',
            ],
            [
                'id' => 'sand',
                'name' => 'サンド',
                'description' => 'キャメル×丸みのやさしいナチュラル。カフェに。',
                'accent_color' => '#a8763e',
                'font_family' => 'rounded',
                'surface_tone' => 'warm',
                'radius_scale' => 'round',
                'tab_style' => 'pill',
                'section_gap' => 18,
                'card_style' => 'flat',
                'button_shape' => 'pill',
                'density' => 'default',
                'background_style' => 'image',
                'bg_image_url' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=1600&q=80',
                'bg_overlay_opacity' => 68,
                'bg_color' => '#faf5ee',
                'content_width' => 'comfy',
            ],
            [
                'id' => 'ocean',
                'name' => 'オーシャン',
                'description' => '澄んだ青の爽快モダン。ジム・スポーツに。',
                'accent_color' => '#0369a1',
                'font_family' => 'sans',
                'surface_tone' => 'cool',
                'radius_scale' => 'soft',
                'tab_style' => 'floating',
                'section_gap' => 16,
                'card_style' => 'elevated',
                'button_shape' => 'pill',
                'density' => 'compact',
                'background_style' => 'gradient',
                'bg_gradient_start' => '#e0f2fe',
                'bg_gradient_end' => '#f0f9ff',
                'content_width' => 'edge',
            ],
            [
                'id' => 'sakura',
                'name' => 'サクラ',
                'description' => 'やわらかなローズで華やか。ネイル・スイーツに。',
                'accent_color' => '#be185d',
                'font_family' => 'rounded',
                'surface_tone' => 'warm',
                'radius_scale' => 'round',
                'tab_style' => 'pill',
                'section_gap' => 18,
                'card_style' => 'flat',
                'button_shape' => 'pill',
                'density' => 'relaxed',
                'background_style' => 'gradient',
                'bg_gradient_start' => '#fdf2f8',
                'bg_gradient_end' => '#fff1f2',
                'content_width' => 'comfy',
            ],
            [
                'id' => 'mono-night',
                'name' => 'ミッドナイト',
                'description' => 'ダーク×ゴールドの夜の表情。バー・ラウンジに。',
                'accent_color' => '#d4af37',
                'font_family' => 'sans',
                'surface_tone' => 'charcoal',
                'radius_scale' => 'soft',
                'tab_style' => 'floating',
                'section_gap' => 16,
                'card_style' => 'outline',
                'button_shape' => 'rounded',
                'density' => 'relaxed',
                'background_style' => 'solid',
                'bg_color' => '#141312',
                'content_width' => 'comfy',
            ],
            [
                'id' => 'matcha',
                'name' => 'マッチャ',
                'description' => '抹茶グリーン×明朝の和モダン。和食・居酒屋・日本茶に。',
                'accent_color' => '#4d7c0f',
                'font_family' => 'serif-display',
                'surface_tone' => 'paper',
                'radius_scale' => 'compact',
                'tab_style' => 'default',
                'section_gap' => 16,
                'card_style' => 'outline',
                'button_shape' => 'square',
                'density' => 'default',
                'background_style' => 'image',
                'bg_image_url' => 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=1600&q=80',
                'bg_overlay_opacity' => 70,
                'bg_color' => '#1c1917',
                'content_width' => 'narrow',
            ],
            [
                'id' => 'terracotta',
                'name' => 'テラコッタ',
                'description' => '焼きたての温もり。ベーカリー・イタリアンに。',
                'accent_color' => '#c2410c',
                'font_family' => 'rounded',
                'surface_tone' => 'warm',
                'radius_scale' => 'soft',
                'tab_style' => 'pill',
                'section_gap' => 18,
                'card_style' => 'flat',
                'button_shape' => 'rounded',
                'density' => 'default',
                'background_style' => 'image',
                'bg_image_url' => 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=1600&q=80',
                'bg_overlay_opacity' => 65,
                'bg_color' => '#faf5ee',
                'content_width' => 'comfy',
            ],
            [
                'id' => 'lavender',
                'name' => 'ラベンダー',
                'description' => '上品なパープルで癒やしの空気感。美容室・エステに。',
                'accent_color' => '#7e22ce',
                'font_family' => 'sans',
                'surface_tone' => 'mist',
                'radius_scale' => 'round',
                'tab_style' => 'floating',
                'section_gap' => 18,
                'card_style' => 'elevated',
                'button_shape' => 'pill',
                'density' => 'relaxed',
                'background_style' => 'gradient',
                'bg_gradient_start' => '#f5f3ff',
                'bg_gradient_end' => '#faf5ff',
                'content_width' => 'comfy',
            ],
            [
                'id' => 'citrus',
                'name' => 'シトラス',
                'description' => '元気なオレンジで注文をあと押し。テイクアウト・ファストに。',
                'accent_color' => '#ea580c',
                'font_family' => 'sans',
                'surface_tone' => 'mist',
                'radius_scale' => 'default',
                'tab_style' => 'default',
                'section_gap' => 14,
                'card_style' => 'flat',
                'button_shape' => 'rounded',
                'density' => 'compact',
                'background_style' => 'gradient',
                'bg_gradient_start' => '#fff7ed',
                'bg_gradient_end' => '#ffedd5',
                'content_width' => 'edge',
            ],
            [
                'id' => 'gold',
                'name' => 'ゴールド',
                'description' => 'シャンパンゴールドのラグジュアリー。高級店・ホテルに。',
                'accent_color' => '#b8860b',
                'font_family' => 'serif-display',
                'surface_tone' => 'warm',
                'radius_scale' => 'soft',
                'tab_style' => 'pill',
                'section_gap' => 18,
                'card_style' => 'elevated',
                'button_shape' => 'pill',
                'density' => 'relaxed',
                'background_style' => 'gradient',
                'bg_gradient_start' => '#faf8f3',
                'bg_gradient_end' => '#f5f0e6',
                'content_width' => 'narrow',
            ],
            [
                'id' => 'slate',
                'name' => 'スレート',
                'description' => 'クールグレーのプロフェッショナル。B2B・クリニックに。',
                'accent_color' => '#334155',
                'font_family' => 'sans',
                'surface_tone' => 'cool',
                'radius_scale' => 'default',
                'tab_style' => 'floating',
                'section_gap' => 16,
                'card_style' => 'outline',
                'button_shape' => 'rounded',
                'density' => 'default',
                'background_style' => 'solid',
                'bg_color' => '#f8fafc',
                'content_width' => 'comfy',
            ],
            [
                'id' => 'rose',
                'name' => 'ロゼ',
                'description' => 'ローズピンクのエレガンス。美容・ウェルネスに。',
                'accent_color' => '#be185d',
                'font_family' => 'rounded',
                'surface_tone' => 'warm',
                'radius_scale' => 'round',
                'tab_style' => 'pill',
                'section_gap' => 18,
                'card_style' => 'elevated',
                'button_shape' => 'pill',
                'density' => 'relaxed',
                'background_style' => 'gradient',
                'bg_gradient_start' => '#fff1f2',
                'bg_gradient_end' => '#fdf2f8',
                'content_width' => 'comfy',
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

        // コンテンツ幅
        $widthKey = (string) ($global['content_width'] ?? ($preset['content_width'] ?? ''));
        $widths = self::contentWidths();
        if (isset($widths[$widthKey])) {
            foreach ($widths[$widthKey]['tokens'] as $name => $value) {
                $vars[$name] = $value;
            }
        }

        // 背景レイヤー用 CSS 変数
        $bgVars = self::backgroundCssVariables($global, $preset);
        foreach ($bgVars as $name => $value) {
            $vars[$name] = $value;
        }

        return $vars;
    }

    /**
     * ページ背景用の CSS カスタムプロパティを返す。
     *
     * @param  array<string, mixed>  $global
     * @param  array<string, mixed>|null  $preset
     * @return array<string, string>
     */
    public static function backgroundCssVariables(array $global, ?array $preset): array
    {
        $vars = [];
        $style = (string) ($global['background_style'] ?? ($preset['background_style'] ?? 'solid'));
        if (! array_key_exists($style, self::backgroundStyles())) {
            $style = 'solid';
        }

        $bgColor = self::normalizeHex($global['bg_color'] ?? null)
            ?? self::normalizeHex($preset['bg_color'] ?? null)
            ?? '#f7f6f2';
        $vars['--app-bg-color'] = $bgColor;

        $overlay = isset($global['bg_overlay_opacity']) && is_numeric($global['bg_overlay_opacity'])
            ? max(0, min(80, (int) $global['bg_overlay_opacity']))
            : (isset($preset['bg_overlay_opacity']) ? max(0, min(80, (int) $preset['bg_overlay_opacity'])) : 60);
        $vars['--app-bg-overlay'] = number_format($overlay / 100, 2, '.', '');

        if ($style === 'gradient') {
            $start = self::normalizeHex($global['bg_gradient_start'] ?? null)
                ?? self::normalizeHex($preset['bg_gradient_start'] ?? null)
                ?? $bgColor;
            $end = self::normalizeHex($global['bg_gradient_end'] ?? null)
                ?? self::normalizeHex($preset['bg_gradient_end'] ?? null)
                ?? '#ffffff';
            $vars['--app-bg-gradient'] = "linear-gradient(165deg, {$start} 0%, {$end} 55%, {$bgColor} 100%)";
        }

        if ($style === 'image') {
            $imageUrl = self::normalizeBgImageUrl($global['bg_image_url'] ?? null)
                ?? self::normalizeBgImageUrl($preset['bg_image_url'] ?? null);
            if ($imageUrl !== null) {
                $vars['--app-bg-image'] = "url('{$imageUrl}')";
            }
        }

        return $vars;
    }

    /**
     * @param  array<string, mixed>  $global
     * @return array<int, string>
     */
    public static function bodyClassesFor(?array $global): array
    {
        $global = is_array($global) ? $global : [];
        $preset = self::find($global['theme_preset'] ?? null);
        $classes = [];

        $resolve = static function (string $key, array $allowed, string $default) use ($global, $preset): ?string {
            $value = (string) ($global[$key] ?? ($preset[$key] ?? ''));
            if ($value === '' || $value === $default || ! in_array($value, $allowed, true)) {
                return null;
            }

            return $value;
        };

        $tabStyle = $resolve('tab_style', ['pill', 'floating'], 'default');
        if ($tabStyle !== null) {
            $classes[] = 'tab-style-'.$tabStyle;
        }
        $cardStyle = $resolve('card_style', ['flat', 'elevated', 'outline'], '');
        if ($cardStyle !== null) {
            $classes[] = 'card-style-'.$cardStyle;
        }
        $buttonShape = $resolve('button_shape', ['rounded', 'pill', 'square'], '');
        if ($buttonShape !== null) {
            $classes[] = 'button-shape-'.$buttonShape;
        }
        $density = $resolve('density', ['compact', 'relaxed'], 'default');
        if ($density !== null) {
            $classes[] = 'density-'.$density;
        }

        $systemUiStyle = $resolve('system_ui_style', ['editorial', 'compact', 'luxe'], 'refined');
        if ($systemUiStyle !== null) {
            $classes[] = 'system-ui-'.$systemUiStyle;
        } else {
            $classes[] = 'system-ui-refined';
        }
        $systemCardStyle = $resolve('system_card_style', ['elevated', 'outline', 'flat'], 'soft');
        if ($systemCardStyle !== null) {
            $classes[] = 'system-card-'.$systemCardStyle;
        } else {
            $classes[] = 'system-card-soft';
        }
        $systemMediaShape = $resolve('system_media_shape', ['square', 'arch'], 'rounded');
        if ($systemMediaShape !== null) {
            $classes[] = 'system-media-'.$systemMediaShape;
        } else {
            $classes[] = 'system-media-rounded';
        }
        $systemHeaderStyle = $resolve('system_header_style', ['editorial', 'hero'], 'minimal');
        if ($systemHeaderStyle !== null) {
            $classes[] = 'system-header-'.$systemHeaderStyle;
        } else {
            $classes[] = 'system-header-minimal';
        }

        $presetId = (string) ($global['theme_preset'] ?? ($preset['id'] ?? ''));
        if ($presetId === 'apple_system') {
            $classes[] = 'theme-apple_system';
            $classes[] = 'chrome-glass';
        }

        $memberScreen = (string) ($global['member_screen_design'] ?? ($preset['member_screen_design'] ?? 'wallet'));
        $classes[] = 'member-screen-'.(in_array($memberScreen, ['wallet', 'premium', 'minimal', 'compact', 'apple', 'glass'], true) ? $memberScreen : 'wallet');

        $couponScreen = (string) ($global['coupon_screen_design'] ?? ($preset['coupon_screen_design'] ?? 'ticket'));
        $classes[] = 'coupon-screen-'.(in_array($couponScreen, ['ticket', 'gallery', 'compact', 'luxury', 'apple', 'glass'], true) ? $couponScreen : 'ticket');

        $reservationScreen = (string) ($global['reservation_screen_design'] ?? ($preset['reservation_screen_design'] ?? 'cards'));
        $classes[] = 'reservation-screen-'.(in_array($reservationScreen, ['cards', 'timeline', 'compact', 'concierge', 'apple'], true) ? $reservationScreen : 'cards');

        $activityScreen = (string) ($global['activity_screen_design'] ?? ($preset['activity_screen_design'] ?? 'cards'));
        $classes[] = 'activity-screen-'.(in_array($activityScreen, ['cards', 'timeline', 'compact', 'dashboard'], true) ? $activityScreen : 'cards');

        $bgStyle = (string) ($global['background_style'] ?? ($preset['background_style'] ?? 'solid'));
        if (array_key_exists($bgStyle, self::backgroundStyles()) && $bgStyle !== 'solid') {
            $classes[] = 'app-bg-'.$bgStyle;
        } elseif ($bgStyle === 'solid' && (
            self::normalizeHex($global['bg_color'] ?? null) !== null
            || self::normalizeHex($preset['bg_color'] ?? null) !== null
        )) {
            $classes[] = 'app-bg-solid';
        }

        $widthKey = (string) ($global['content_width'] ?? ($preset['content_width'] ?? ''));
        if ($widthKey !== '' && array_key_exists($widthKey, self::contentWidths())) {
            $classes[] = 'content-width-'.$widthKey;
        }

        return $classes;
    }

    private static function normalizeBgImageUrl(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }
        $value = trim($value);
        if ($value === '' || ! preg_match('#^https://[a-zA-Z0-9\-._~:/?#\[\]@!$&\'()*+,;=%]+$#', $value)) {
            return null;
        }

        return $value;
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

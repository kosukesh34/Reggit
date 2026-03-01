<?php

namespace App\Support;

use InvalidArgumentException;

class LiffDesignerSanitizer
{
    private const ALLOWED_TABS = [
        'home', 'products', 'orders', 'stamps', 'coupons',
        'queue', 'reservations', 'member', 'gallery', 'announcements',
    ];

    private const ALLOWED_BLOCK_TYPES = [
        'hero', 'heading', 'text', 'image', 'button', 'card', 'collection',
        'slider', 'icon_text', 'list', 'banner', 'spacer', 'divider', 'data',
    ];

    private const ALLOWED_DATA_SOURCES = [
        'products', 'orders', 'coupons', 'stamps', 'announcements',
        'queue', 'reservations', 'gallery', 'member_card', 'member',
    ];

    public static function sanitizeLayouts(array $layouts): array
    {
        $safe = [];
        foreach (self::ALLOWED_TABS as $tab) {
            $rows = $layouts[$tab] ?? [];
            $safe[$tab] = [];
            if (!is_array($rows)) {
                continue;
            }
            $rows = array_slice($rows, 0, 100);
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $block = self::sanitizeBlock($row);
                if ($block !== null) {
                    $safe[$tab][] = $block;
                }
            }
        }

        return $safe;
    }

    public static function sanitizeTabSettings(?array $tabSettings): ?array
    {
        if ($tabSettings === null) {
            return null;
        }

        $safe = [];
        foreach (self::ALLOWED_TABS as $tab) {
            $row = $tabSettings[$tab] ?? null;
            if (!is_array($row)) {
                continue;
            }
            $item = [];
            $bgColor = self::color($row['background_color'] ?? null);
            if ($bgColor !== null) {
                $item['background_color'] = $bgColor;
            }
            $bgImage = self::safeUrl($row['background_image'] ?? null, false);
            if ($bgImage !== '') {
                $item['background_image'] = $bgImage;
            }
            if ($item !== []) {
                $safe[$tab] = $item;
            }
        }

        return $safe;
    }

    public static function sanitizeCustomCss(string $css): string
    {
        $css = str_replace("\0", '', $css);
        $css = mb_substr($css, 0, 20000);
        $css = preg_replace('/<\/style/i', '<\\/style', $css) ?? '';

        $denyPatterns = [
            '/@import/i',
            '/expression\s*\(/i',
            '/javascript\s*:/i',
            '/vbscript\s*:/i',
            '/-moz-binding/i',
            '/behavior\s*:/i',
        ];
        foreach ($denyPatterns as $pattern) {
            if (preg_match($pattern, $css) === 1) {
                throw new InvalidArgumentException('カスタムCSSに使用できない記述が含まれています。');
            }
        }

        return trim($css);
    }

    private static function sanitizeBlock(array $row): ?array
    {
        $type = (string) ($row['type'] ?? 'text');
        if ($type === 'label') {
            $type = 'heading';
        }
        if ($type === 'gallery') {
            $type = 'data';
            $row['source'] = 'gallery';
        }
        if ($type === 'form') {
            $type = 'text';
            $row['text'] = $row['body'] ?? ($row['text'] ?? '');
        }

        if (!in_array($type, self::ALLOWED_BLOCK_TYPES, true)) {
            return null;
        }

        $b = ['type' => $type];
        if (isset($row['id']) && is_string($row['id']) && preg_match('/^[a-zA-Z0-9_-]{1,64}$/', $row['id']) === 1) {
            $b['id'] = $row['id'];
        }

        $b['border_radius'] = self::num($row['border_radius'] ?? 10, 0, 24, 10);
        $b['padding'] = self::num($row['padding'] ?? 0, 0, 48, 0);
        $b['margin_bottom'] = self::num($row['margin_bottom'] ?? 0, 0, 48, 0);
        $b['opacity'] = self::num($row['opacity'] ?? 100, 0, 100, 100);
        $b['shadow'] = (bool) ($row['shadow'] ?? false);
        $align = (string) ($row['text_align'] ?? 'left');
        $b['text_align'] = in_array($align, ['left', 'center', 'right'], true) ? $align : 'left';
        $bg = self::color($row['background_color'] ?? null);
        if ($bg !== null) {
            $b['background_color'] = $bg;
        }

        switch ($type) {
            case 'hero':
                $b['title'] = self::text($row['title'] ?? 'ようこそ！', 80);
                $b['subtitle'] = self::text($row['subtitle'] ?? ($row['text'] ?? ''), 160);
                $b['image_url'] = self::safeUrl($row['image_url'] ?? null, false);
                $b['gradient_start'] = self::color($row['gradient_start'] ?? '#667eea') ?? '#667eea';
                $b['gradient_end'] = self::color($row['gradient_end'] ?? '#764ba2') ?? '#764ba2';
                break;
            case 'heading':
                $b['text'] = self::text($row['text'] ?? ($row['title'] ?? '見出し'), 120);
                $size = (string) ($row['size'] ?? 'medium');
                $b['size'] = in_array($size, ['small', 'medium', 'large'], true) ? $size : 'medium';
                $b['text_color'] = self::color($row['text_color'] ?? '#1f2937') ?? '#1f2937';
                break;
            case 'text':
                $b['text'] = self::text($row['text'] ?? ($row['body'] ?? ''), 2000);
                $b['font_size'] = self::num($row['font_size'] ?? 13, 10, 28, 13);
                $b['text_color'] = self::color($row['text_color'] ?? '#4b5563') ?? '#4b5563';
                break;
            case 'image':
                $b['image_url'] = self::safeUrl($row['image_url'] ?? null, false);
                $b['alt'] = self::text($row['alt'] ?? '', 120);
                $fit = (string) ($row['fit'] ?? 'cover');
                $b['fit'] = in_array($fit, ['cover', 'contain', 'fill'], true) ? $fit : 'cover';
                break;
            case 'button':
                $b['text'] = self::text($row['text'] ?? ($row['button_label'] ?? 'ボタン'), 60);
                $b['color'] = self::color($row['color'] ?? '#06C755') ?? '#06C755';
                $b['text_color'] = self::color($row['text_color'] ?? '#ffffff') ?? '#ffffff';
                $b['action'] = self::safeUrl($row['action'] ?? ($row['button_url'] ?? ''), true);
                $b['full_width'] = !isset($row['full_width']) || (bool) $row['full_width'];
                break;
            case 'card':
                $b['title'] = self::text($row['title'] ?? '本日のおすすめ', 80);
                $b['description'] = self::text($row['description'] ?? '', 240);
                $b['image_url'] = self::safeUrl($row['image_url'] ?? null, false);
                $b['image_height'] = self::num($row['image_height'] ?? 140, 60, 360, 140);
                break;
            case 'collection':
                $b['layout'] = ((string) ($row['layout'] ?? 'scroll')) === 'grid' ? 'grid' : 'scroll';
                $b['columns'] = self::num($row['columns'] ?? 2, 2, 4, 2);
                $b['max_rows'] = self::num($row['max_rows'] ?? 0, 0, 10, 0);
                $b['item_width'] = self::num($row['item_width'] ?? 140, 80, 280, 140);
                $b['item_image_height'] = self::num($row['item_image_height'] ?? 100, 40, 240, 100);
                $b['gap'] = self::num($row['gap'] ?? 8, 0, 32, 8);
                $b['show_description'] = !isset($row['show_description']) || (bool) $row['show_description'];
                $items = is_array($row['items'] ?? null) ? array_slice($row['items'], 0, 24) : [];
                $b['items'] = array_values(array_filter(array_map(function ($it) {
                    if (!is_array($it)) {
                        return null;
                    }
                    return [
                        'title' => self::text($it['title'] ?? '', 60),
                        'image_url' => self::safeUrl($it['image_url'] ?? null, false),
                        'description' => self::text($it['description'] ?? '', 120),
                    ];
                }, $items)));
                break;
            case 'slider':
                $b['slide_height'] = self::num($row['slide_height'] ?? 160, 80, 360, 160);
                $b['auto_play'] = !isset($row['auto_play']) || (bool) $row['auto_play'];
                $b['show_dots'] = !isset($row['show_dots']) || (bool) $row['show_dots'];
                $slides = is_array($row['slides'] ?? null) ? array_slice($row['slides'], 0, 12) : [];
                $b['slides'] = array_values(array_filter(array_map(function ($it) {
                    if (!is_array($it)) {
                        return null;
                    }
                    return [
                        'image_url' => self::safeUrl($it['image_url'] ?? null, false),
                        'caption' => self::text($it['caption'] ?? '', 100),
                    ];
                }, $slides)));
                break;
            case 'icon_text':
                $rows = is_array($row['rows'] ?? null) ? array_slice($row['rows'], 0, 12) : [];
                $b['rows'] = array_values(array_filter(array_map(function ($it) {
                    if (!is_array($it)) {
                        return null;
                    }
                    $icon = (string) ($it['icon'] ?? 'fa-check-circle');
                    if (preg_match('/^fa-[a-z0-9-]{1,40}$/i', $icon) !== 1) {
                        $icon = 'fa-check-circle';
                    }
                    return [
                        'icon' => $icon,
                        'text' => self::text($it['text'] ?? '', 80),
                        'color' => self::color($it['color'] ?? '#06C755') ?? '#06C755',
                    ];
                }, $rows)));
                break;
            case 'list':
                $style = (string) ($row['style'] ?? 'bullet');
                $b['style'] = in_array($style, ['bullet', 'number'], true) ? $style : 'bullet';
                $b['text_color'] = self::color($row['text_color'] ?? '#374151') ?? '#374151';
                $items = $row['items'] ?? ($row['list_items'] ?? []);
                $items = is_array($items) ? array_slice($items, 0, 30) : [];
                $b['items'] = array_map(fn ($v) => self::text($v, 120), $items);
                break;
            case 'banner':
                $icon = (string) ($row['icon'] ?? 'fa-info-circle');
                if (preg_match('/^fa-[a-z0-9-]{1,40}$/i', $icon) !== 1) {
                    $icon = 'fa-info-circle';
                }
                $b['icon'] = $icon;
                $b['text'] = self::text($row['text'] ?? '', 120);
                $b['color'] = self::color($row['color'] ?? '#3b82f6') ?? '#3b82f6';
                $b['text_color'] = self::color($row['text_color'] ?? '#ffffff') ?? '#ffffff';
                break;
            case 'spacer':
                $b['height'] = self::num($row['height'] ?? 16, 4, 120, 16);
                break;
            case 'divider':
                $b['divider_color'] = self::color($row['divider_color'] ?? '#e5e7eb') ?? '#e5e7eb';
                $line = (string) ($row['divider_style'] ?? 'solid');
                $b['divider_style'] = in_array($line, ['solid', 'dashed', 'dotted'], true) ? $line : 'solid';
                break;
            case 'data':
                $src = (string) ($row['source'] ?? 'products');
                if ($src === 'member') {
                    $src = 'member_card';
                }
                $b['source'] = in_array($src, self::ALLOWED_DATA_SOURCES, true) ? $src : 'products';
                break;
        }

        return $b;
    }

    private static function num($v, int $min, int $max, int $default): int
    {
        if (is_numeric($v) === false) {
            return $default;
        }
        $n = (int) $v;
        if ($n < $min) {
            return $min;
        }
        if ($n > $max) {
            return $max;
        }
        return $n;
    }

    private static function text($v, int $max = 255): string
    {
        $s = is_scalar($v) ? (string) $v : '';
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $s) ?? '';
        return trim(mb_substr($s, 0, $max));
    }

    private static function color($v): ?string
    {
        $s = self::text($v, 7);
        if ($s === '') {
            return null;
        }
        if (preg_match('/^#[0-9a-fA-F]{6}$/', $s) !== 1) {
            return null;
        }
        return strtolower($s);
    }

    private static function safeUrl($url, bool $allowAnchor): string
    {
        $u = self::text($url, 1000);
        if ($u === '') {
            return '';
        }
        if ($allowAnchor && str_starts_with($u, '#')) {
            return $u;
        }
        if (str_starts_with($u, '/')) {
            return $u;
        }
        if (preg_match('/^(https?:\/\/|mailto:|tel:)/i', $u) === 1) {
            return $u;
        }
        return '';
    }
}


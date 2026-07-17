import { useMemo } from 'react';
import type { GlobalSettings, ThemePreset } from '../catalog/types';

const RADIUS_SCALES: Record<string, { md: string }> = {
    compact: { md: '12px' },
    default: { md: '16px' },
    soft: { md: '18px' },
    round: { md: '24px' },
};

const CONTENT_WIDTHS: Record<string, string> = {
    edge: '100%',
    comfy: 'min(100%, 440px)',
    narrow: 'min(100%, 380px)',
};

function findPreset(presets: ThemePreset[] | undefined, id: unknown): ThemePreset | undefined {
    if (typeof id !== 'string' || id === '') return undefined;
    return (presets || []).find((p) => p.id === id);
}

function hexToRgb(hex: string): string {
    const raw = hex.replace('#', '').trim();
    if (raw.length === 3) {
        return [
            parseInt(raw[0] + raw[0], 16),
            parseInt(raw[1] + raw[1], 16),
            parseInt(raw[2] + raw[2], 16),
        ].join(', ');
    }
    if (raw.length === 6) {
        const r = parseInt(raw.slice(0, 2), 16);
        const g = parseInt(raw.slice(2, 4), 16);
        const b = parseInt(raw.slice(4, 6), 16);
        if ([r, g, b].every((n) => !Number.isNaN(n))) return `${r}, ${g}, ${b}`;
    }
    return '28, 25, 23';
}

export function buildGlobalDesignCss(globalSettings: GlobalSettings, presets?: ThemePreset[]): string {
    const g = globalSettings;
    const preset = findPreset(presets, g.theme_preset);
    const radiusKey = String(g.radius_scale || (preset?.radius_scale as string) || 'soft');
    const scale = RADIUS_SCALES[radiusKey] || RADIUS_SCALES.soft;
    const cardStyle = String(g.card_style || (preset?.card_style as string) || '');
    const buttonShape = String(g.button_shape || (preset?.button_shape as string) || '');
    const density = String(g.density || (preset?.density as string) || 'default');
    const widthKey = String(g.content_width || (preset?.content_width as string) || 'comfy');
    const accent = String(g.accent_color || preset?.accent_color || preset?.accent || '#1c1917');
    const accentRgb = hexToRgb(accent);
    const pad = Number(g.content_padding ?? 18);
    const gap = Number(g.section_gap ?? 16);

    const cardDecl =
        cardStyle === 'flat'
            ? 'box-shadow: none;'
            : cardStyle === 'elevated'
              ? 'box-shadow: 0 6px 20px rgba(15,23,42,0.1); border-color: transparent;'
              : cardStyle === 'outline'
                ? 'box-shadow: none; border: 1px solid rgba(15,23,42,0.14);'
                : '';
    const btnRadius =
        buttonShape === 'rounded' ? '10px' : buttonShape === 'pill' ? '999px' : buttonShape === 'square' ? '5px' : '';
    const blockPad = density === 'compact' ? '12px' : density === 'relaxed' ? '20px' : '';

    return [
        `.ld.ld-react .ld-phone-device.is-device, .ld.ld-react .ld-phone-device__screen, .ld.ld-react .ld-phone__frame.is-line {`,
        `  --primary-color: ${accent};`,
        `  --primary-dark: ${accent};`,
        `  --primary-rgb: ${accentRgb};`,
        `  --ld-accent: ${accent};`,
        `  --chrome: #111827;`,
        `  --chrome-dark: #030712;`,
        `  --content-padding: ${pad}px;`,
        `  --section-gap: ${gap}px;`,
        `  --text-tertiary: #9ca3af;`,
        `  --border-color: rgba(28,25,23,0.08);`,
        `  --hairline: rgba(28,25,23,0.08);`,
        `  --surface-glass: rgba(255,255,255,0.96);`,
        `  --radius: ${scale.md};`,
        `  --radius-pill: 999px;`,
        `  --radius-xl: 22px;`,
        `}`,
        `.ld.ld-react .ld-phone__content { --content-padding: ${pad}px; --section-gap: ${gap}px; max-width: ${CONTENT_WIDTHS[widthKey] || CONTENT_WIDTHS.comfy}; }`,
        `.ld.ld-react .ld-phone__content .ld-block { border-radius: ${scale.md}; ${cardDecl} }`,
        btnRadius
            ? `.ld.ld-react .ld-phone__content .ld-app-btn, .ld.ld-react .ld-phone__content .ld-app-btn-group .ld-app-btn { border-radius: ${btnRadius} !important; }`
            : '',
        blockPad ? `.ld.ld-react .ld-phone__content .ld-block { padding: ${blockPad}; }` : '',
        `.ld.ld-react .ld-phone__screen .main-tab-btn.active { color: ${accent}; background: rgba(${accentRgb}, 0.1); }`,
        `.ld.ld-react .ld-phone__screen.tab-style-pill .main-tab-btn.active { background: ${accent}; color: #fff; }`,
        `.ld.ld-react .ld-phone__screen .cart-count { background: ${accent}; }`,
    ]
        .filter((line) => line !== '')
        .join('\n');
}

export function useGlobalDesignCss(globalSettings: GlobalSettings, presets?: ThemePreset[]): string {
    return useMemo(() => buildGlobalDesignCss(globalSettings, presets), [globalSettings, presets]);
}

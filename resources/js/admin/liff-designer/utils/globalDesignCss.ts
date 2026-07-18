import { useMemo } from 'react';
import type { DesignerConfig, GlobalSettings, ThemePreset } from '../catalog/types';

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

type DesignCssOptions = {
    fontStacks?: DesignerConfig['fontStacks'];
    surfaceTones?: DesignerConfig['surfaceTones'];
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

function darkenHex(hex: string, amount = 0.16): string {
    const rgb = hexToRgb(hex).split(',').map((part) => Number(part.trim()));
    if (rgb.length !== 3 || rgb.some((n) => !Number.isFinite(n))) return hex;
    const toHex = (n: number) => Math.max(0, Math.min(255, Math.round(n * (1 - amount)))).toString(16).padStart(2, '0');
    return `#${toHex(rgb[0])}${toHex(rgb[1])}${toHex(rgb[2])}`;
}

function resolveFontKey(g: GlobalSettings, preset: ThemePreset | undefined): string {
    const fromGlobal = String(g.font_family || '');
    if (fromGlobal !== '') return fromGlobal;
    return String(preset?.font_family || '');
}

function resolveToneKey(g: GlobalSettings, preset: ThemePreset | undefined): string {
    const fromGlobal = String(g.surface_tone || '');
    if (fromGlobal !== '') return fromGlobal;
    return String(preset?.surface_tone || '');
}

export function buildGlobalDesignCss(
    globalSettings: GlobalSettings,
    presets?: ThemePreset[],
    options?: DesignCssOptions,
): string {
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

    const fontKey = resolveFontKey(g, preset);
    const fontStack = fontKey !== '' ? options?.fontStacks?.[fontKey] : undefined;
    const toneKey = resolveToneKey(g, preset);
    const toneTokens =
        toneKey !== '' && options?.surfaceTones?.[toneKey]?.tokens !== undefined
            ? options.surfaceTones[toneKey].tokens || {}
            : {};

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

    const primaryDark = darkenHex(accent, 0.18);
    const extraVars: string[] = [
        `--primary-color: ${accent};`,
        `--primary-dark: ${primaryDark};`,
        `--primary-rgb: ${accentRgb};`,
        `--ld-accent: ${accent};`,
        `--content-padding: ${pad}px;`,
        `--section-gap: ${gap}px;`,
        `--radius: ${scale.md};`,
        `--radius-pill: 999px;`,
        `--radius-xl: 22px;`,
    ];

    if (fontStack !== undefined && typeof fontStack.base === 'string' && fontStack.base !== '') {
        extraVars.push(`--font-base: ${fontStack.base};`);
        extraVars.push(
            `--font-display: ${typeof fontStack.display === 'string' && fontStack.display !== '' ? fontStack.display : fontStack.base};`,
        );
    }

    Object.entries(toneTokens).forEach(([name, value]) => {
        if (typeof value === 'string' && value !== '') {
            extraVars.push(`${name}: ${value};`);
        }
    });

    if (toneTokens['--chrome'] === undefined) {
        extraVars.push('--chrome: #111827;');
    }
    if (toneTokens['--chrome-dark'] === undefined) {
        extraVars.push('--chrome-dark: #030712;');
    }
    if (toneTokens['--text-tertiary'] === undefined) {
        extraVars.push('--text-tertiary: #9ca3af;');
    }
    if (toneTokens['--border-color'] === undefined) {
        extraVars.push('--border-color: rgba(28,25,23,0.08);');
    }
    if (toneTokens['--hairline'] === undefined) {
        extraVars.push('--hairline: rgba(28,25,23,0.08);');
    }
    if (toneTokens['--surface-glass'] === undefined) {
        extraVars.push('--surface-glass: rgba(255,255,255,0.96);');
    }

    return [
        `.ld.ld-react .ld-phone-device.is-device, .ld.ld-react .ld-phone-device__screen, .ld.ld-react .ld-phone__frame.is-line {`,
        `  ${extraVars.join(' ')}`,
        `}`,
        `.ld.ld-react .ld-phone__content { --content-padding: ${pad}px; --section-gap: ${gap}px; max-width: ${CONTENT_WIDTHS[widthKey] || CONTENT_WIDTHS.comfy}; font-family: var(--font-base, inherit); }`,
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

export function useGlobalDesignCss(
    globalSettings: GlobalSettings,
    presets?: ThemePreset[],
    options?: DesignCssOptions,
): string {
    const fontStacks = options?.fontStacks;
    const surfaceTones = options?.surfaceTones;
    return useMemo(
        () => buildGlobalDesignCss(globalSettings, presets, { fontStacks, surfaceTones }),
        [globalSettings, presets, fontStacks, surfaceTones],
    );
}

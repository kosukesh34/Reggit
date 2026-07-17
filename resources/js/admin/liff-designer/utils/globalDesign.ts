import type { CSSProperties } from 'react';
import type { GlobalSettings, ThemePreset } from '../catalog/types';

const CONTENT_WIDTHS: Record<string, string> = {
    edge: '100%',
    comfy: 'min(100%, 440px)',
    narrow: 'min(100%, 380px)',
};

const PRESET_KEYS = [
    'font_family',
    'surface_tone',
    'card_style',
    'button_shape',
    'density',
    'background_style',
    'bg_color',
    'bg_gradient_start',
    'bg_gradient_end',
    'bg_image_url',
    'bg_overlay_opacity',
    'content_width',
    'radius_scale',
    'tab_style',
    'section_gap',
    'content_padding',
    'system_ui_style',
    'system_card_style',
    'system_media_shape',
    'system_header_style',
    'member_screen_design',
    'coupon_screen_design',
    'reservation_screen_design',
    'activity_screen_design',
] as const;

export function expandThemeIntoGlobal(
    current: GlobalSettings,
    theme: Record<string, unknown> | undefined,
    presets: ThemePreset[] | undefined,
): GlobalSettings {
    if (theme === undefined || Object.keys(theme).length === 0) {
        return { ...current };
    }

    const next: GlobalSettings = { ...current };
    const presetId = typeof theme.theme_preset === 'string' ? theme.theme_preset : '';
    const preset = presetId !== '' ? (presets || []).find((p) => p.id === presetId) : undefined;

    if (preset !== undefined) {
        next.theme_preset = preset.id;
        next.accent_color = preset.accent_color || preset.accent || next.accent_color;
        PRESET_KEYS.forEach((key) => {
            if (preset[key] !== undefined) next[key] = preset[key];
        });
    }

    Object.keys(theme).forEach((key) => {
        next[key] = theme[key];
    });
    if (typeof theme.theme_preset === 'string' && theme.theme_preset !== '') {
        next.theme_preset = theme.theme_preset;
    }

    return next;
}

export function resolvePhoneBodyStyle(
    tabSettings: Record<string, unknown>,
    globalSettings: GlobalSettings,
): CSSProperties {
    const tabBg = typeof tabSettings.background_color === 'string' ? tabSettings.background_color : '';
    const tabImage = typeof tabSettings.background_image === 'string' ? tabSettings.background_image : '';
    const bgStyle = String(globalSettings.background_style || 'solid');
    const globalBg = String(globalSettings.bg_color || '#fafaf9');

    // タブ固有の背景があるときだけ上書き（空ならアプリ全体の背景を使う）
    if (tabImage !== '') {
        return {
            backgroundColor: tabBg !== '' ? tabBg : globalBg,
            backgroundImage: `url(${tabImage})`,
            backgroundSize: String(tabSettings.background_size || 'cover'),
            backgroundPosition: 'center',
        };
    }

    if (tabBg !== '') {
        return { backgroundColor: tabBg };
    }

    if (bgStyle === 'gradient') {
        const start = String(globalSettings.bg_gradient_start || globalBg);
        const end = String(globalSettings.bg_gradient_end || '#ffffff');
        return {
            background: `linear-gradient(165deg, ${start} 0%, ${end} 55%, ${globalBg} 100%)`,
        };
    }

    if (bgStyle === 'image') {
        const url = String(globalSettings.bg_image_url || '');
        if (url.startsWith('https://') || url.startsWith('/')) {
            const opacity = Math.max(0, Math.min(80, Number(globalSettings.bg_overlay_opacity ?? 60))) / 100;
            return {
                backgroundColor: globalBg,
                backgroundImage: `linear-gradient(rgba(15,23,42,${opacity}), rgba(15,23,42,${opacity})), url(${url})`,
                backgroundSize: 'cover',
                backgroundPosition: 'center',
            };
        }
    }

    return { backgroundColor: globalBg };
}

export function contentPadding(tabSettings: Record<string, unknown>, globalSettings: GlobalSettings): number {
    if (tabSettings.content_padding !== undefined && tabSettings.content_padding !== null && tabSettings.content_padding !== '') {
        return Number(tabSettings.content_padding);
    }
    return Number(globalSettings.content_padding ?? 18);
}

export function sectionGap(globalSettings: GlobalSettings): number {
    return Number(globalSettings.section_gap || 16);
}

export function navStyleClass(globalSettings: GlobalSettings): string {
    const style = String(globalSettings.tab_style || 'default');
    if (style === 'pill') return ' is-pill';
    if (style === 'floating') return ' is-floating';
    return '';
}

export function screenTabStyleClass(globalSettings: GlobalSettings): string {
    const style = String(globalSettings.tab_style || 'default');
    if (style === 'pill') return 'tab-style-pill';
    if (style === 'floating') return 'tab-style-floating';
    return '';
}

export function frameSystemClasses(globalSettings: GlobalSettings): string {
    return [
        `system-ui-${String(globalSettings.system_ui_style || 'refined')}`,
        `system-card-${String(globalSettings.system_card_style || 'soft')}`,
        `system-media-${String(globalSettings.system_media_shape || 'rounded')}`,
        `system-header-${String(globalSettings.system_header_style || 'minimal')}`,
        `member-screen-${String(globalSettings.member_screen_design || 'wallet')}`,
        `coupon-screen-${String(globalSettings.coupon_screen_design || 'ticket')}`,
        `reservation-screen-${String(globalSettings.reservation_screen_design || 'cards')}`,
        `activity-screen-${String(globalSettings.activity_screen_design || 'cards')}`,
    ].join(' ');
}

export function contentMaxWidth(globalSettings: GlobalSettings): string {
    const key = String(globalSettings.content_width || 'comfy');
    return CONTENT_WIDTHS[key] || CONTENT_WIDTHS.comfy;
}

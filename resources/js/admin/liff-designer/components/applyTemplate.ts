import { normalizeBlock, TAB_LABELS, TAB_ICONS, defaultTabSettings } from '../catalog/blockCatalog';
import type { DesignerState, LiffBlock, MenuItem, ThemePreset } from '../catalog/types';
import { expandThemeIntoGlobal } from '../utils/globalDesign';
import { isAllScreensTemplate, paletteLabelFor, type TemplateItem } from './templateGalleryMeta';

export const TEMPLATE_THEME_KEEP = '__template__';

const PRESET_THEME_KEYS = [
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

export type TemplateThemeSelection = {
    previewTemplate: TemplateItem;
    themeOverride?: Record<string, unknown>;
    label: string;
};

export type TemplateSetupOptions = {
    shopName: string;
    memberCardDesign: string;
    memberCardDisplayType: string;
    memberCardCodeType: string;
    heroTitle: string;
    fontFamily: string;
    contentWidth: string;
    buttonShape: string;
    cardStyle: string;
};

export const DEFAULT_TEMPLATE_SETUP: TemplateSetupOptions = {
    shopName: '',
    memberCardDesign: 'default',
    memberCardDisplayType: 'card',
    memberCardCodeType: 'barcode',
    heroTitle: '',
    fontFamily: '',
    contentWidth: '',
    buttonShape: '',
    cardStyle: '',
};

export function resolveTemplateThemeSelection(
    tpl: TemplateItem,
    selectionId: string,
    presets: ThemePreset[] | undefined,
): TemplateThemeSelection {
    if (selectionId === TEMPLATE_THEME_KEEP || selectionId === '') {
        return {
            previewTemplate: tpl,
            themeOverride: undefined,
            label: paletteLabelFor(tpl),
        };
    }
    const preset = (presets || []).find((p) => p.id === selectionId);
    if (preset === undefined) {
        return {
            previewTemplate: tpl,
            themeOverride: undefined,
            label: paletteLabelFor(tpl),
        };
    }

    const accent = String(preset.accent_color || preset.accent || '').trim();
    const themeOverride: Record<string, unknown> = {
        theme_preset: preset.id,
    };
    if (accent !== '') {
        themeOverride.accent_color = accent;
    }
    PRESET_THEME_KEYS.forEach((key) => {
        if (preset[key] !== undefined) {
            themeOverride[key] = preset[key];
        }
    });

    const theme: Record<string, unknown> = {
        ...(tpl.theme || {}),
        ...themeOverride,
    };

    return {
        previewTemplate: {
            ...tpl,
            theme,
            palette: String(preset.label || preset.name || preset.id),
        },
        themeOverride,
        label: String(preset.label || preset.name || preset.id),
    };
}

export type TemplateApplyResult = {
    layouts: Record<string, LiffBlock[]>;
    globalSettings: Record<string, unknown>;
    tabSettings: Record<string, Record<string, unknown>>;
    menuItems?: MenuItem[];
    activeTab: string;
    customCss?: string;
    message: string;
    applied: boolean;
};

function isPlainObject(value: unknown): value is Record<string, unknown> {
    return value !== null && typeof value === 'object' && !Array.isArray(value);
}

function collectHashTabsFromBlocks(rows: unknown[]): string[] {
    const tabs: string[] = [];
    const visit = (value: unknown) => {
        if (typeof value === 'string' && value.startsWith('#') && value.length > 1) {
            const tab = value.slice(1).split(/[?/]/)[0];
            if (TAB_LABELS[tab] !== undefined && !tabs.includes(tab)) {
                tabs.push(tab);
            }
            return;
        }
        if (Array.isArray(value)) {
            value.forEach(visit);
            return;
        }
        if (isPlainObject(value)) {
            Object.values(value).forEach(visit);
        }
    };
    rows.forEach(visit);
    return tabs;
}

export function resolveTemplateEnabledTabs(tpl: TemplateItem): string[] {
    const ordered: string[] = [];
    const push = (tab: string) => {
        if (TAB_LABELS[tab] === undefined || ordered.includes(tab)) return;
        ordered.push(tab);
    };

    if (Array.isArray(tpl.menu)) {
        tpl.menu.forEach((row) => {
            if (isPlainObject(row) && typeof row.tab === 'string' && row.enabled !== false) {
                push(row.tab);
            }
        });
    }

    (Array.isArray(tpl.target_tabs) ? tpl.target_tabs : []).forEach((tab) => {
        if (typeof tab === 'string') push(tab);
    });

    if (isPlainObject(tpl.layouts)) {
        Object.keys(tpl.layouts).forEach((tab) => {
            const rows = tpl.layouts?.[tab];
            if (Array.isArray(rows) && rows.length > 0) {
                push(tab);
            }
        });
        Object.values(tpl.layouts).forEach((rows) => {
            if (Array.isArray(rows)) {
                collectHashTabsFromBlocks(rows).forEach(push);
            }
        });
    }

    if (Array.isArray(tpl.blocks)) {
        collectHashTabsFromBlocks(tpl.blocks).forEach(push);
    }

    push('home');
    push('member');
    return ordered;
}

export function buildMenuItemsForTemplate(tpl: TemplateItem, current: MenuItem[]): MenuItem[] {
    const enabledTabs = resolveTemplateEnabledTabs(tpl);
    const enabledSet = new Set(enabledTabs);
    const byTab = Object.fromEntries(current.map((item) => [item.tab, item]));
    const ordered: MenuItem[] = [];

    enabledTabs.forEach((tab, index) => {
        const existing = byTab[tab];
        ordered.push({
            tab,
            label: existing?.label || TAB_LABELS[tab] || tab,
            icon: existing?.icon || TAB_ICONS[tab] || 'fa-circle',
            order: index,
            enabled: true,
        });
    });

    current.forEach((item) => {
        if (enabledSet.has(item.tab)) return;
        ordered.push({
            ...item,
            enabled: false,
            order: ordered.length,
        });
    });

    return ordered;
}

export function resolvePreferredTab(tpl: TemplateItem, state: DesignerState): string {
    const targetTabs = Array.isArray(tpl.target_tabs) ? tpl.target_tabs.filter((t) => typeof t === 'string') : [];
    const enabled = resolveTemplateEnabledTabs(tpl);
    return (
        targetTabs.find((t) => enabled.includes(t) || state.menuItems.some((m) => m.tab === t))
        || enabled[0]
        || state.activeTab
    );
}

function splitTabSettings(raw: Record<string, unknown> | undefined): {
    globalFromTabs: Record<string, unknown>;
    tabOnly: Record<string, Record<string, unknown>>;
} {
    if (raw === undefined) {
        return { globalFromTabs: {}, tabOnly: {} };
    }
    const tabOnly: Record<string, Record<string, unknown>> = {};
    let globalFromTabs: Record<string, unknown> = {};
    Object.keys(raw).forEach((key) => {
        const value = raw[key];
        if (key === '_global' && isPlainObject(value)) {
            globalFromTabs = value;
            return;
        }
        if (isPlainObject(value)) {
            tabOnly[key] = value;
        }
    });
    return { globalFromTabs, tabOnly };
}

function applyIncomingTabSettings(
    current: Record<string, Record<string, unknown>>,
    incoming: Record<string, Record<string, unknown>>,
): Record<string, Record<string, unknown>> {
    const next: Record<string, Record<string, unknown>> = { ...current };
    Object.keys(incoming).forEach((tab) => {
        next[tab] = { ...(current[tab] || defaultTabSettings()), ...incoming[tab] };
    });
    return next;
}

function tabLabel(tab: string): string {
    return TAB_LABELS[tab] || tab;
}

function accentFromTheme(theme: Record<string, unknown> | undefined): string {
    const accent = String(theme?.accent_color || theme?.accent || '').trim();
    return accent !== '' ? accent : '#0f172a';
}

function personalizeBlock(
    block: LiffBlock,
    setup: TemplateSetupOptions,
    accent: string,
): LiffBlock {
    const next: LiffBlock = { ...block };
    const shop = setup.shopName.trim();

    if (next.type === 'hero') {
        if (setup.heroTitle.trim() !== '') {
            next.title = setup.heroTitle.trim();
        } else if (shop !== '') {
            next.title = shop;
        }
    }

    if (next.type === 'member_summary' && shop !== '') {
        next.greeting = `おかえりなさい、${shop}のお客様`;
    }

    if (next.type === 'access' && shop !== '') {
        next.title = shop;
        if (String(next.address || '').trim() === '') {
            next.address = '店舗住所を入力してください';
        }
    }

    if (next.type === 'data' && (next.source === 'member_card' || next.source === 'member')) {
        next.card_design = setup.memberCardDesign;
    }

    if (next.type === 'button' && (next.design === 'cta' || next.design === undefined || next.design === '')) {
        next.color = accent;
    }

    if (next.type === 'banner' && (next.color === undefined || next.color === '')) {
        next.color = accent;
        next.text_color = '#ffffff';
    }

    return next;
}

function personalizeLayouts(
    layouts: Record<string, LiffBlock[]>,
    setup: TemplateSetupOptions | undefined,
    theme: Record<string, unknown> | undefined,
): Record<string, LiffBlock[]> {
    if (setup === undefined) return layouts;
    const accent = accentFromTheme(theme);
    const next: Record<string, LiffBlock[]> = {};
    Object.keys(layouts).forEach((tab) => {
        next[tab] = layouts[tab].map((block) => personalizeBlock(block, setup, accent));
    });
    return next;
}

function setupGlobalPatch(setup: TemplateSetupOptions | undefined): Record<string, unknown> {
    if (setup === undefined) return {};
    const shop = setup.shopName.trim();
    const patch: Record<string, unknown> = {
        app_display_name: shop,
        member_card_design: setup.memberCardDesign,
        member_card_display_type: setup.memberCardDisplayType,
        member_card_code_type: setup.memberCardCodeType,
    };
    if (setup.fontFamily !== '') patch.font_family = setup.fontFamily;
    if (setup.contentWidth !== '') patch.content_width = setup.contentWidth;
    if (setup.buttonShape !== '') patch.button_shape = setup.buttonShape;
    if (setup.cardStyle !== '') patch.card_style = setup.cardStyle;
    return patch;
}

export function buildPersonalizedPreviewTemplate(
    tpl: TemplateItem,
    setup: TemplateSetupOptions,
): TemplateItem {
    const shop = setup.shopName.trim();
    const theme: Record<string, unknown> = {
        ...(tpl.theme || {}),
        ...setupGlobalPatch(setup),
        app_display_name: shop,
    };
    const accent = accentFromTheme(theme);

    const mapBlocks = (rows: unknown[]): Record<string, unknown>[] =>
        rows.map((item) => {
            const block = personalizeBlock(
                normalizeBlock((item || {}) as Record<string, unknown>, { n: Date.now() % 100000 }),
                setup,
                accent,
            );
            return block as unknown as Record<string, unknown>;
        });

    if (isAllScreensTemplate(tpl) && isPlainObject(tpl.layouts)) {
        const layouts: Record<string, unknown[]> = {};
        Object.keys(tpl.layouts).forEach((tab) => {
            const rows = Array.isArray(tpl.layouts?.[tab]) ? tpl.layouts![tab] : [];
            layouts[tab] = mapBlocks(rows as unknown[]);
        });
        return { ...tpl, theme, layouts };
    }

    const blocks = Array.isArray(tpl.blocks) ? mapBlocks(tpl.blocks) : [];
    return { ...tpl, theme, blocks };
}

export function buildTemplateApplyResult(
    tpl: TemplateItem,
    state: DesignerState,
    themePresets?: ThemePreset[],
    themeOverride?: Record<string, unknown>,
    setup?: TemplateSetupOptions,
): TemplateApplyResult {
    const idCounter = { n: Date.now() % 100000 };
    const { globalFromTabs, tabOnly } = splitTabSettings(
        isPlainObject(tpl.tab_settings) ? tpl.tab_settings : undefined,
    );
    const theme = isPlainObject(tpl.theme) ? tpl.theme : undefined;
    const override = isPlainObject(themeOverride) ? themeOverride : {};
    const mergedTheme = {
        ...globalFromTabs,
        ...(theme || {}),
        ...override,
        ...setupGlobalPatch(setup),
    };
    const globalSettings = expandThemeIntoGlobal(state.globalSettings, mergedTheme, themePresets);
    const hasLayouts = isPlainObject(tpl.layouts);
    const isAll = isAllScreensTemplate(tpl) && hasLayouts;
    const tabSettings = applyIncomingTabSettings(state.tabSettings, tabOnly);
    const customCss =
        typeof tpl.custom_css === 'string' && tpl.custom_css.trim() !== '' ? tpl.custom_css : undefined;

    const menuItems = buildMenuItemsForTemplate(tpl, state.menuItems);

    if (isAllScreensTemplate(tpl) && !hasLayouts) {
        return {
            layouts: state.layouts,
            globalSettings: state.globalSettings,
            tabSettings: state.tabSettings,
            activeTab: state.activeTab,
            message: '全画面テンプレートに layouts がないため適用をスキップしました',
            applied: false,
        };
    }

    if (isAll) {
        const layouts: Record<string, LiffBlock[]> = { ...state.layouts };
        Object.keys(TAB_LABELS).forEach((tab) => {
            if (Array.isArray(tpl.layouts?.[tab])) {
                layouts[tab] = (tpl.layouts![tab] as unknown[]).map((item) =>
                    normalizeBlock((item || {}) as Record<string, unknown>, idCounter),
                );
            }
        });
        Object.keys(tpl.layouts!).forEach((tab) => {
            if (TAB_LABELS[tab] === undefined && Array.isArray(tpl.layouts?.[tab])) {
                layouts[tab] = (tpl.layouts![tab] as unknown[]).map((item) =>
                    normalizeBlock((item || {}) as Record<string, unknown>, idCounter),
                );
            }
        });
        const activeTab = menuItems.some((m) => m.tab === 'home' && m.enabled)
            ? 'home'
            : (menuItems.find((m) => m.enabled)?.tab || state.activeTab);
        return {
            layouts: personalizeLayouts(layouts, setup, mergedTheme),
            globalSettings,
            tabSettings,
            menuItems,
            activeTab,
            customCss,
            message: `テンプレート「${tpl.label || tpl.name || '未命名'}」を全画面に適用しました`,
            applied: true,
        };
    }

    const rows = Array.isArray(tpl.blocks) ? tpl.blocks : [];
    const blocks = rows.map((item) => normalizeBlock((item || {}) as Record<string, unknown>, idCounter));
    const preferredTab = resolvePreferredTab(tpl, state);
    const layouts = personalizeLayouts({ ...state.layouts, [preferredTab]: blocks }, setup, mergedTheme);
    const nextTabSettings = { ...tabSettings };

    if (Object.keys(tabOnly).length === 0 && isPlainObject(tpl.tab_settings)) {
        const flat = tpl.tab_settings;
        const hasNestedTab = Object.keys(flat).some((k) => k === '_global' || TAB_LABELS[k] !== undefined);
        if (!hasNestedTab) {
            const scalarPatch: Record<string, unknown> = {};
            Object.keys(flat).forEach((key) => {
                const value = flat[key];
                if (value === null || typeof value !== 'object') {
                    scalarPatch[key] = value;
                }
            });
            if (Object.keys(scalarPatch).length > 0) {
                nextTabSettings[preferredTab] = {
                    ...(nextTabSettings[preferredTab] || defaultTabSettings()),
                    ...scalarPatch,
                };
            }
        }
    }

    return {
        layouts,
        globalSettings,
        tabSettings: nextTabSettings,
        menuItems,
        activeTab: preferredTab,
        customCss,
        message: `テンプレートを「${tabLabel(preferredTab)}」に適用しました`,
        applied: true,
    };
}

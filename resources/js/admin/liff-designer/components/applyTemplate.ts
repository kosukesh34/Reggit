import { normalizeBlock, TAB_LABELS, defaultTabSettings } from '../catalog/blockCatalog';
import type { DesignerState, LiffBlock, ThemePreset } from '../catalog/types';
import { expandThemeIntoGlobal } from '../utils/globalDesign';
import { isAllScreensTemplate, type TemplateItem } from './templateGalleryMeta';

export type TemplateApplyResult = {
    layouts: Record<string, LiffBlock[]>;
    globalSettings: Record<string, unknown>;
    tabSettings: Record<string, Record<string, unknown>>;
    activeTab: string;
    customCss?: string;
    message: string;
    applied: boolean;
};

function isPlainObject(value: unknown): value is Record<string, unknown> {
    return value !== null && typeof value === 'object' && !Array.isArray(value);
}

export function resolvePreferredTab(tpl: TemplateItem, state: DesignerState): string {
    const targetTabs = Array.isArray(tpl.target_tabs) ? tpl.target_tabs.filter((t) => typeof t === 'string') : [];
    return targetTabs.find((t) => state.menuItems.some((m) => m.tab === t)) || state.activeTab;
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

export function buildTemplateApplyResult(
    tpl: TemplateItem,
    state: DesignerState,
    themePresets?: ThemePreset[],
): TemplateApplyResult {
    const idCounter = { n: Date.now() % 100000 };
    const { globalFromTabs, tabOnly } = splitTabSettings(
        isPlainObject(tpl.tab_settings) ? tpl.tab_settings : undefined,
    );
    const theme = isPlainObject(tpl.theme) ? tpl.theme : undefined;
    const mergedTheme = { ...globalFromTabs, ...(theme || {}) };
    const globalSettings = expandThemeIntoGlobal(state.globalSettings, mergedTheme, themePresets);
    const hasLayouts = isPlainObject(tpl.layouts);
    const isAll = isAllScreensTemplate(tpl) && hasLayouts;
    const tabSettings = applyIncomingTabSettings(state.tabSettings, tabOnly);
    const customCss =
        typeof tpl.custom_css === 'string' && tpl.custom_css.trim() !== '' ? tpl.custom_css : undefined;

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
        return {
            layouts,
            globalSettings,
            tabSettings,
            activeTab: state.menuItems.some((m) => m.tab === 'home') ? 'home' : state.activeTab,
            customCss,
            message: `テンプレート「${tpl.label || tpl.name || '未命名'}」を全画面に適用しました`,
            applied: true,
        };
    }

    const rows = Array.isArray(tpl.blocks) ? tpl.blocks : [];
    const blocks = rows.map((item) => normalizeBlock((item || {}) as Record<string, unknown>, idCounter));
    const preferredTab = resolvePreferredTab(tpl, state);
    const layouts = { ...state.layouts, [preferredTab]: blocks };
    const nextTabSettings = { ...tabSettings };

    // タブテンプレで tab_settings がタブキー無しの場合は対象タブへ適用
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
        activeTab: preferredTab,
        customCss,
        message: `テンプレートを「${tabLabel(preferredTab)}」に適用しました`,
        applied: true,
    };
}

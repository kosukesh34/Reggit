import type { DesignerState } from '../catalog/types';
import { DETAIL_PAGES, MENU_TABS } from '../catalog/blockCatalog';

export function serializeTabSettings(state: DesignerState): Record<string, unknown> {
    return {
        ...state.tabSettings,
        _global: state.globalSettings,
    };
}

function splitLayouts(state: DesignerState): {
    layouts: Record<string, unknown[]>;
    detailLayouts: Record<string, unknown[]>;
} {
    const layouts: Record<string, unknown[]> = {};
    const detailLayouts: Record<string, unknown[]> = {};
    MENU_TABS.forEach((key) => {
        layouts[key] = state.layouts[key] || [];
    });
    DETAIL_PAGES.forEach((key) => {
        detailLayouts[key] = state.layouts[key] || [];
    });
    return { layouts, detailLayouts };
}

export function buildSavePayload(state: DesignerState): {
    layouts_json: string;
    detail_layouts_json: string;
    tab_settings_json: string;
    menu_items_json: string;
    custom_css: string;
} {
    const { layouts, detailLayouts } = splitLayouts(state);
    return {
        layouts_json: JSON.stringify(layouts),
        detail_layouts_json: JSON.stringify(detailLayouts),
        tab_settings_json: JSON.stringify(serializeTabSettings(state)),
        menu_items_json: JSON.stringify(state.menuItems),
        custom_css: state.customCss,
    };
}

export function exportDesignerJson(state: DesignerState): void {
    const { layouts, detailLayouts } = splitLayouts(state);
    const payload = {
        exported_at: new Date().toISOString(),
        active_tab: state.activeTab,
        layouts,
        detail_layouts: detailLayouts,
        tab_settings: serializeTabSettings(state),
        menu_items: state.menuItems,
        custom_css: state.customCss,
    };
    const blob = new Blob([JSON.stringify(payload, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `liff-designer-${new Date().toISOString().slice(0, 10)}.json`;
    document.body.appendChild(a);
    a.click();
    a.remove();
    setTimeout(() => URL.revokeObjectURL(url), 500);
}

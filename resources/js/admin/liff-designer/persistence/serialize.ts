import type { DesignerState } from '../catalog/types';

export function serializeTabSettings(state: DesignerState): Record<string, unknown> {
    return {
        ...state.tabSettings,
        _global: state.globalSettings,
    };
}

export function buildSavePayload(state: DesignerState): {
    layouts_json: string;
    tab_settings_json: string;
    menu_items_json: string;
    custom_css: string;
} {
    return {
        layouts_json: JSON.stringify(state.layouts),
        tab_settings_json: JSON.stringify(serializeTabSettings(state)),
        menu_items_json: JSON.stringify(state.menuItems),
        custom_css: state.customCss,
    };
}

export function exportDesignerJson(state: DesignerState): void {
    const payload = {
        exported_at: new Date().toISOString(),
        active_tab: state.activeTab,
        layouts: state.layouts,
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

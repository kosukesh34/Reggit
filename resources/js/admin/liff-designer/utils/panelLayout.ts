export type PanelPresetId = 'compact' | 'standard' | 'wide' | 'focus';

export type PanelWidths = { left: number; right: number };

export const PANEL_PRESETS: Record<PanelPresetId, { label: string; widths: PanelWidths }> = {
    compact: { label: 'コンパクト', widths: { left: 200, right: 300 } },
    standard: { label: '標準', widths: { left: 248, right: 388 } },
    wide: { label: 'ワイド', widths: { left: 300, right: 460 } },
    focus: { label: '編集重視', widths: { left: 200, right: 520 } },
};

const STORAGE_KEY = 'ld-panel-widths-v1';

export function applyPanelWidths(root: HTMLElement, widths: PanelWidths): void {
    root.style.setProperty('--ld-screens-width', `${widths.left}px`);
    root.style.setProperty('--ld-sidebar-width', `${widths.right}px`);
}

export function applyPanelPreset(root: HTMLElement, presetId: PanelPresetId): void {
    const preset = PANEL_PRESETS[presetId];
    if (preset === undefined) return;
    applyPanelWidths(root, preset.widths);
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(preset.widths));
    } catch {
        /* ignore */
    }
}

export function loadSavedPanelWidths(): PanelWidths {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (raw === null) return PANEL_PRESETS.standard.widths;
        const parsed = JSON.parse(raw) as Partial<PanelWidths>;
        return {
            left: Number.isFinite(parsed.left) ? Number(parsed.left) : PANEL_PRESETS.standard.widths.left,
            right: Number.isFinite(parsed.right) ? Number(parsed.right) : PANEL_PRESETS.standard.widths.right,
        };
    } catch {
        return PANEL_PRESETS.standard.widths;
    }
}

export function detectActivePreset(root: HTMLElement): PanelPresetId {
    const left = parseInt(getComputedStyle(root).getPropertyValue('--ld-screens-width'), 10);
    const right = parseInt(getComputedStyle(root).getPropertyValue('--ld-sidebar-width'), 10);
    const match = (Object.keys(PANEL_PRESETS) as PanelPresetId[]).find((id) => {
        const w = PANEL_PRESETS[id].widths;
        return w.left === left && w.right === right;
    });
    return match ?? 'standard';
}

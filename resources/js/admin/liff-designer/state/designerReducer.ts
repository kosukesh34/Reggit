import {
    collectLegacyNotes,
    createEmptyLayouts,
    defaultGlobalSettings,
    defaultTabSettings,
    DETAIL_PAGES,
    LINK_MENU_TABS,
    MENU_TABS,
    normalizeBlock,
    normalizeMenuItems,
} from '../catalog/blockCatalog';
import type {
    DesignerConfig,
    DesignerSnapshot,
    DesignerState,
    EditorMode,
    LeftStudioTab,
    LiffBlock,
    RightMode,
    RightStudioTab,
} from '../catalog/types';

const MAX_HISTORY = 50;

function cloneSnapshot(state: DesignerSnapshot): DesignerSnapshot {
    return JSON.parse(JSON.stringify({
        layouts: state.layouts,
        tabSettings: state.tabSettings,
        globalSettings: state.globalSettings,
        menuItems: state.menuItems,
        customCss: state.customCss,
    })) as DesignerSnapshot;
}

function applySnapshot(state: DesignerState, snap: DesignerSnapshot): DesignerState {
    return {
        ...state,
        layouts: snap.layouts,
        tabSettings: snap.tabSettings,
        globalSettings: snap.globalSettings,
        menuItems: snap.menuItems,
        customCss: snap.customCss,
        selectedId: null,
        quickEditBlockId: null,
        dirty: true,
    };
}

function withHistory(state: DesignerState, next: DesignerState): DesignerState {
    const snap = cloneSnapshot(next);
    const base = state.history.slice(0, state.historyIndex + 1);
    base.push(snap);
    if (base.length > MAX_HISTORY) {
        base.shift();
    }
    return {
        ...next,
        dirty: true,
        history: base,
        historyIndex: base.length - 1,
    };
}

export function hydrateDesignerState(config: DesignerConfig): DesignerState {
    const idCounter = { n: 0 };
    const rawLayouts = config.layouts || {};
    const rawDetailLayouts = config.detailLayouts || {};
    const rawTabSettings = config.tabSettings || {};
    const layouts = createEmptyLayouts();
    const tabSettings: Record<string, Record<string, unknown>> = {};

    MENU_TABS.forEach((key) => {
        tabSettings[key] = { ...defaultTabSettings(), ...(rawTabSettings[key] || {}) };
        const rows = Array.isArray(rawLayouts[key]) ? rawLayouts[key] : [];
        layouts[key] = rows.map((item) => normalizeBlock((item || {}) as Record<string, unknown>, idCounter));
    });

    DETAIL_PAGES.forEach((key) => {
        tabSettings[key] = { ...defaultTabSettings(), ...(rawTabSettings[key] || {}) };
        const rows = Array.isArray(rawDetailLayouts[key]) ? rawDetailLayouts[key] : [];
        layouts[key] = rows.map((item) => normalizeBlock((item || {}) as Record<string, unknown>, idCounter));
    });

    const globalSettings = {
        ...defaultGlobalSettings(),
        ...((rawTabSettings._global as Record<string, unknown>) || {}),
    };

    const snapshot: DesignerSnapshot = {
        layouts,
        tabSettings,
        globalSettings,
        menuItems: normalizeMenuItems(config.menuItems),
        customCss: typeof config.customCss === 'string' ? config.customCss : '',
    };

    return {
        ...snapshot,
        editorMode: 'simple',
        activeTab: 'home',
        selectedId: null,
        quickEditBlockId: null,
        rightMode: 'add',
        leftStudioTab: 'screens',
        rightStudioTab: 'edit',
        previewAsGuest: false,
        zoom: 70,
        paletteSearch: '',
        paletteCategory: 'all',
        dirty: false,
        saving: false,
        history: [cloneSnapshot(snapshot)],
        historyIndex: 0,
        legacyNotes: collectLegacyNotes(rawLayouts as Record<string, unknown[]>),
        toast: null,
        sidebarOpen: false,
        screensOpen: true,
        moreOpen: false,
        templateOpen: false,
        themeOpen: false,
        previewOpen: false,
        helpOpen: false,
        pendingTemplateId: null,
    };
}

export type DesignerAction =
    | { type: 'SET_EDITOR_MODE'; mode: EditorMode }
    | { type: 'SET_ACTIVE_TAB'; tab: string }
    | { type: 'SELECT_BLOCK'; id: string | null; openEdit?: boolean }
    | { type: 'OPEN_QUICK_EDIT'; id: string }
    | { type: 'CLOSE_QUICK_EDIT' }
    | { type: 'SET_RIGHT_MODE'; mode: RightMode }
    | { type: 'SET_LEFT_STUDIO_TAB'; tab: LeftStudioTab }
    | { type: 'SET_RIGHT_STUDIO_TAB'; tab: RightStudioTab }
    | { type: 'SET_PREVIEW_AS_GUEST'; value: boolean }
    | { type: 'SET_ZOOM'; zoom: number }
    | { type: 'SET_PALETTE_SEARCH'; value: string }
    | { type: 'SET_PALETTE_CATEGORY'; value: string }
    | { type: 'UPDATE_BLOCK'; id: string; patch: Partial<LiffBlock> }
    | { type: 'REPLACE_BLOCK'; id: string; block: LiffBlock }
    | { type: 'ADD_BLOCK'; block: LiffBlock; index?: number }
    | { type: 'DELETE_BLOCK'; id: string }
    | { type: 'DUPLICATE_BLOCK'; id: string }
    | { type: 'MOVE_BLOCK'; from: number; to: number }
    | { type: 'REORDER_BLOCKS'; blocks: LiffBlock[] }
    | { type: 'UPDATE_TAB_SETTINGS'; patch: Record<string, unknown> }
    | { type: 'UPDATE_GLOBAL'; patch: Record<string, unknown> }
    | { type: 'APPLY_GLOBAL_BACKGROUND' }
    | { type: 'UPDATE_MENU_ITEM'; tab: string; patch: Partial<{ label: string; icon: string; enabled: boolean; url: string; open: string }> }
    | { type: 'MOVE_MENU_ITEM'; tab: string; delta: number }
    | { type: 'ADD_LINK_MENU_ITEM' }
    | { type: 'REMOVE_MENU_ITEM'; tab: string }
    | { type: 'SET_CUSTOM_CSS'; value: string }
    | { type: 'IMPORT_SNAPSHOT'; snapshot: DesignerSnapshot }
    | {
          type: 'APPLY_LAYOUTS';
          layouts: Record<string, LiffBlock[]>;
          tabSettings?: Record<string, Record<string, unknown>>;
          globalSettings?: Record<string, unknown>;
          menuItems?: DesignerState['menuItems'];
          customCss?: string;
          activeTab?: string;
      }
    | { type: 'UNDO' }
    | { type: 'REDO' }
    | { type: 'MARK_SAVED' }
    | { type: 'SET_SAVING'; saving: boolean }
    | { type: 'SHOW_TOAST'; message: string; isError?: boolean }
    | { type: 'CLEAR_TOAST' }
    | { type: 'CLEAR_LEGACY_NOTES' }
    | { type: 'SET_UI'; patch: Partial<Pick<DesignerState, 'sidebarOpen' | 'screensOpen' | 'moreOpen' | 'templateOpen' | 'themeOpen' | 'previewOpen' | 'helpOpen' | 'pendingTemplateId' | 'leftStudioTab' | 'rightStudioTab'>> };

function activeBlocks(state: DesignerState): LiffBlock[] {
    return state.layouts[state.activeTab] || [];
}

export function designerReducer(state: DesignerState, action: DesignerAction): DesignerState {
    switch (action.type) {
        case 'SET_EDITOR_MODE':
            return {
                ...state,
                editorMode: action.mode,
                selectedId: null,
                quickEditBlockId: null,
                rightMode: 'add',
                rightStudioTab: 'edit',
                sidebarOpen: false,
            };
        case 'SET_LEFT_STUDIO_TAB':
            return { ...state, leftStudioTab: action.tab, screensOpen: true };
        case 'SET_RIGHT_STUDIO_TAB':
            return { ...state, rightStudioTab: action.tab, sidebarOpen: true };
        case 'SET_PREVIEW_AS_GUEST':
            return { ...state, previewAsGuest: action.value };
        case 'SET_ACTIVE_TAB':
            return {
                ...state,
                activeTab: action.tab,
                selectedId: null,
                quickEditBlockId: null,
                rightMode: state.editorMode === 'advanced' ? 'add' : state.rightMode,
            };
        case 'OPEN_QUICK_EDIT':
            return {
                ...state,
                quickEditBlockId: action.id,
                selectedId: action.id,
                rightStudioTab: 'edit',
                sidebarOpen: true,
            };
        case 'CLOSE_QUICK_EDIT':
            return {
                ...state,
                quickEditBlockId: null,
                selectedId: null,
            };
        case 'SELECT_BLOCK': {
            if (state.editorMode === 'simple') {
                if (action.id === null) {
                    return { ...state, selectedId: null, quickEditBlockId: null };
                }
                return {
                    ...state,
                    selectedId: action.id,
                    quickEditBlockId: action.id,
                    sidebarOpen: true,
                };
            }
            const openEdit = action.openEdit !== false && action.id !== null;
            return {
                ...state,
                selectedId: action.id,
                rightMode: openEdit ? 'edit' : state.rightMode,
                rightStudioTab: openEdit ? 'edit' : state.rightStudioTab,
                sidebarOpen: openEdit ? true : state.sidebarOpen,
            };
        }
        case 'SET_RIGHT_MODE':
            return {
                ...state,
                rightMode: action.mode,
                rightStudioTab: action.mode === 'edit' ? 'edit' : state.rightStudioTab,
                leftStudioTab: action.mode === 'add' ? 'blocks' : state.leftStudioTab,
                screensOpen: action.mode === 'add' ? true : state.screensOpen,
                sidebarOpen: true,
            };
        case 'SET_ZOOM':
            return { ...state, zoom: Math.min(125, Math.max(60, action.zoom)) };
        case 'SET_PALETTE_SEARCH':
            return { ...state, paletteSearch: action.value };
        case 'SET_PALETTE_CATEGORY':
            return { ...state, paletteCategory: action.value };
        case 'UPDATE_BLOCK': {
            const blocks = activeBlocks(state).map((b) => (b.id === action.id ? { ...b, ...action.patch, id: b.id, type: b.type } : b));
            return withHistory(state, {
                ...state,
                layouts: { ...state.layouts, [state.activeTab]: blocks },
            });
        }
        case 'REPLACE_BLOCK': {
            const blocks = activeBlocks(state).map((b) => (b.id === action.id ? action.block : b));
            return withHistory(state, {
                ...state,
                layouts: { ...state.layouts, [state.activeTab]: blocks },
            });
        }
        case 'ADD_BLOCK': {
            const blocks = [...activeBlocks(state)];
            const index = typeof action.index === 'number' ? action.index : blocks.length;
            blocks.splice(index, 0, action.block);
            return withHistory(state, {
                ...state,
                layouts: { ...state.layouts, [state.activeTab]: blocks },
                selectedId: action.block.id,
                rightMode: 'edit',
                rightStudioTab: 'edit',
                sidebarOpen: true,
            });
        }
        case 'DELETE_BLOCK': {
            const blocks = activeBlocks(state).filter((b) => b.id !== action.id);
            return withHistory(state, {
                ...state,
                layouts: { ...state.layouts, [state.activeTab]: blocks },
                selectedId: state.selectedId === action.id ? null : state.selectedId,
                quickEditBlockId: state.quickEditBlockId === action.id ? null : state.quickEditBlockId,
                rightMode: state.selectedId === action.id ? 'add' : state.rightMode,
            });
        }
        case 'DUPLICATE_BLOCK': {
            const blocks = [...activeBlocks(state)];
            const i = blocks.findIndex((b) => b.id === action.id);
            if (i < 0) return state;
            const copy = { ...JSON.parse(JSON.stringify(blocks[i])), id: `b${Date.now()}` } as LiffBlock;
            blocks.splice(i + 1, 0, copy);
            return withHistory(state, {
                ...state,
                layouts: { ...state.layouts, [state.activeTab]: blocks },
                selectedId: copy.id,
                rightMode: 'edit',
                rightStudioTab: 'edit',
            });
        }
        case 'MOVE_BLOCK': {
            const blocks = [...activeBlocks(state)];
            if (action.from < 0 || action.to < 0 || action.from >= blocks.length || action.to >= blocks.length) return state;
            const [item] = blocks.splice(action.from, 1);
            blocks.splice(action.to, 0, item);
            return withHistory(state, {
                ...state,
                layouts: { ...state.layouts, [state.activeTab]: blocks },
            });
        }
        case 'REORDER_BLOCKS':
            return withHistory(state, {
                ...state,
                layouts: { ...state.layouts, [state.activeTab]: action.blocks },
            });
        case 'UPDATE_TAB_SETTINGS':
            return withHistory(state, {
                ...state,
                tabSettings: {
                    ...state.tabSettings,
                    [state.activeTab]: { ...state.tabSettings[state.activeTab], ...action.patch },
                },
            });
        case 'UPDATE_GLOBAL':
            return withHistory(state, {
                ...state,
                globalSettings: { ...state.globalSettings, ...action.patch },
            });
        case 'APPLY_GLOBAL_BACKGROUND': {
            const nextTabSettings: Record<string, Record<string, unknown>> = {};
            Object.keys(state.tabSettings).forEach((key) => {
                if (key === '_global') {
                    nextTabSettings[key] = state.tabSettings[key];
                    return;
                }
                nextTabSettings[key] = {
                    ...state.tabSettings[key],
                    background_color: '',
                    background_image: '',
                };
            });
            Object.keys(state.layouts).forEach((key) => {
                if (nextTabSettings[key] !== undefined) return;
                nextTabSettings[key] = {
                    ...defaultTabSettings(),
                    background_color: '',
                    background_image: '',
                };
            });
            return withHistory(state, {
                ...state,
                tabSettings: nextTabSettings,
            });
        }
        case 'UPDATE_MENU_ITEM': {
            const menuItems = state.menuItems.map((m) => (m.tab === action.tab ? { ...m, ...action.patch } : m));
            return withHistory(state, { ...state, menuItems });
        }
        case 'MOVE_MENU_ITEM': {
            const items = [...state.menuItems];
            const i = items.findIndex((m) => m.tab === action.tab);
            const j = i + action.delta;
            if (i < 0 || j < 0 || j >= items.length) return state;
            const [m] = items.splice(i, 1);
            items.splice(j, 0, m);
            items.forEach((item, k) => {
                item.order = k;
            });
            return withHistory(state, { ...state, menuItems: items });
        }
        case 'ADD_LINK_MENU_ITEM': {
            const used = new Set(state.menuItems.map((m) => m.tab));
            const nextKey = LINK_MENU_TABS.find((key) => !used.has(key));
            if (nextKey === undefined) return state;
            const menuItems = [
                ...state.menuItems,
                {
                    tab: nextKey,
                    label: 'リンク',
                    icon: 'fa-globe',
                    order: state.menuItems.length,
                    enabled: true,
                    url: '',
                    open: 'embed',
                },
            ];
            return withHistory(state, { ...state, menuItems });
        }
        case 'REMOVE_MENU_ITEM': {
            const menuItems = state.menuItems.filter((m) => m.tab !== action.tab);
            menuItems.forEach((item, k) => {
                item.order = k;
            });
            const activeTab = state.activeTab === action.tab ? 'home' : state.activeTab;
            return withHistory(state, { ...state, menuItems, activeTab });
        }
        case 'SET_CUSTOM_CSS':
            return withHistory(state, { ...state, customCss: action.value });
        case 'IMPORT_SNAPSHOT':
            return withHistory(state, applySnapshot(state, action.snapshot));
        case 'APPLY_LAYOUTS':
            return withHistory(state, {
                ...state,
                layouts: action.layouts,
                tabSettings: action.tabSettings || state.tabSettings,
                globalSettings: action.globalSettings || state.globalSettings,
                menuItems: action.menuItems !== undefined ? action.menuItems : state.menuItems,
                customCss: action.customCss !== undefined ? action.customCss : state.customCss,
                activeTab: action.activeTab !== undefined ? action.activeTab : state.activeTab,
                selectedId: null,
                quickEditBlockId: null,
            });
        case 'UNDO': {
            if (state.historyIndex <= 0) return state;
            const historyIndex = state.historyIndex - 1;
            return applySnapshot({ ...state, historyIndex, history: state.history }, state.history[historyIndex]);
        }
        case 'REDO': {
            if (state.historyIndex >= state.history.length - 1) return state;
            const historyIndex = state.historyIndex + 1;
            return applySnapshot({ ...state, historyIndex, history: state.history }, state.history[historyIndex]);
        }
        case 'MARK_SAVED':
            return { ...state, dirty: false, saving: false };
        case 'SET_SAVING':
            return { ...state, saving: action.saving };
        case 'SHOW_TOAST':
            return { ...state, toast: { message: action.message, isError: action.isError === true } };
        case 'CLEAR_TOAST':
            return { ...state, toast: null };
        case 'CLEAR_LEGACY_NOTES':
            return { ...state, legacyNotes: [] };
        case 'SET_UI':
            return { ...state, ...action.patch };
        default:
            return state;
    }
}

export function canUndo(state: DesignerState): boolean {
    return state.historyIndex > 0;
}

export function canRedo(state: DesignerState): boolean {
    return state.historyIndex < state.history.length - 1;
}

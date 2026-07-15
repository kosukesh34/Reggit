export type EditorMode = 'simple' | 'advanced';

export type RightMode = 'add' | 'edit';

export type MenuItem = {
    tab: string;
    label: string;
    icon: string;
    order: number;
    enabled: boolean;
};

export type LiffBlock = Record<string, unknown> & {
    id: string;
    type: string;
};

export type TabSettings = Record<string, unknown>;

export type GlobalSettings = Record<string, unknown>;

export type ThemePreset = {
    id: string;
    label?: string;
    name?: string;
    description?: string;
    accent?: string;
    accent_color?: string;
    [key: string]: unknown;
};

export type DesignerConfig = {
    layouts?: Record<string, unknown[]>;
    tabSettings?: Record<string, TabSettings>;
    templates?: unknown[];
    customCss?: string;
    featureFlags?: Record<string, boolean>;
    menuItems?: unknown[];
    themePresets?: ThemePreset[];
    fontStacks?: Record<string, { label: string; base?: string; display?: string }>;
    surfaceTones?: Record<string, { label: string }>;
    formAction?: string;
    csrfToken?: string;
    backUrl?: string;
    lineFriendAddUrl?: string;
    contentWidths?: Record<string, { label: string }>;
    backgroundStyles?: Record<string, string>;
};

export type DesignerSnapshot = {
    layouts: Record<string, LiffBlock[]>;
    tabSettings: Record<string, TabSettings>;
    globalSettings: GlobalSettings;
    menuItems: MenuItem[];
    customCss: string;
};

export type DesignerState = DesignerSnapshot & {
    editorMode: EditorMode;
    activeTab: string;
    selectedId: string | null;
    quickEditBlockId: string | null;
    rightMode: RightMode;
    zoom: number;
    paletteSearch: string;
    paletteCategory: string;
    dirty: boolean;
    saving: boolean;
    history: DesignerSnapshot[];
    historyIndex: number;
    legacyNotes: string[];
    toast: { message: string; isError: boolean } | null;
    sidebarOpen: boolean;
    screensOpen: boolean;
    moreOpen: boolean;
    templateOpen: boolean;
    themeOpen: boolean;
    previewOpen: boolean;
    helpOpen: boolean;
};

export type EditorMode = 'simple' | 'advanced';

export type RightMode = 'add' | 'edit';

export type LeftStudioTab = 'screens' | 'templates' | 'blocks';

export type RightStudioTab = 'edit' | 'theme' | 'layers';

export type MenuItem = {
    tab: string;
    label: string;
    icon: string;
    order: number;
    enabled: boolean;
    /** 外部Webページタブのみ: https の埋め込み/リンク先URL */
    url?: string;
    /** 外部Webページタブのみ: 'embed'（アプリ内表示） | 'external'（外部ブラウザ） */
    open?: string;
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
    detailLayouts?: Record<string, unknown[]>;
    tabSettings?: Record<string, TabSettings>;
    templates?: unknown[];
    customCss?: string;
    featureFlags?: Record<string, boolean>;
    menuItems?: unknown[];
    themePresets?: ThemePreset[];
    fontStacks?: Record<string, { label: string; base?: string; display?: string }>;
    surfaceTones?: Record<string, { label: string; tokens?: Record<string, string> }>;
    formAction?: string;
    csrfToken?: string;
    backUrl?: string;
    lineFriendAddUrl?: string;
    contentWidths?: Record<string, { label: string }>;
    backgroundStyles?: Record<string, string>;
    previewData?: {
        products?: Array<{
            id: string | number;
            name: string;
            price: string | number;
            category: string;
            image_url?: string | null;
        }>;
        coupons?: Array<{
            id: string | number;
            title: string;
            meta: string;
            image_url?: string | null;
            available: boolean;
        }>;
        stamps?: Array<{
            id: string | number;
            name: string;
            required: number;
            reward: string;
            image_url?: string | null;
        }>;
        member?: Record<string, unknown> | null;
    };
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
    leftStudioTab: LeftStudioTab;
    rightStudioTab: RightStudioTab;
    previewAsGuest: boolean;
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
    pendingTemplateId: string | null;
};

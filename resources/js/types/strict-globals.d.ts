export {};

declare global {
    interface Window {
        __API_BASE__?: string;
        __LIFF_TAB_SETTINGS__?: Record<string, unknown>;
        app?: {
            currentTab?: string;
            switchTab?: (tab: string) => void | Promise<void>;
        };
    }
}

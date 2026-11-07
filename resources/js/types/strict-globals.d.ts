export {};

declare global {
    interface Window {
        __API_BASE__?: string;
        __GOOGLE_WALLET_ENABLED__?: boolean;
        __IS_LIFF__?: boolean;
        __LIFF_OS__?: 'ios' | 'android' | string | null;
        __MOBILE_APP_MODE__?: boolean;
        __MOBILE_DEVICE_INFO__?: { device_identifier?: string; [key: string]: unknown };
        __PROJECT_KEY__?: string;
        __WALLET_PASS_ENABLED__?: boolean;
        __nativeAddAppleWalletPass__?: (url: string) => boolean;
        __nativeOpenExternalUrl__?: (url: string) => boolean;
        __LIFF_TAB_SETTINGS__?: Record<string, unknown>;
        app?: {
            currentTab?: string;
            switchTab?: (tab: string) => void | Promise<void>;
        };
    }
}

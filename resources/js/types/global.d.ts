export {};

declare global {
    interface Window {
        __LEGACY_FRONTEND_SCRIPTS_LOADED__?: boolean;
        __LEGACY_ADMIN_SCRIPTS_LOADED__?: boolean;
        __PROJECT_KEY__?: string;
        __API_BASE__?: string;
        __ORDER_MODE__?: string;
        __MOBILE_APP_MODE__?: boolean;
        __IS_LIFF__?: boolean;
        __LINE_USER_ID__?: string | null;
        __FEATURE_FLAGS__?: Record<string, boolean>;
        __ldToastTimer?: number;
        __ldDesigner?: unknown;
        __ldShowToast?: (message: string, isError?: boolean, durationMs?: number) => void;
        LiffDesigner?: new (config: Record<string, unknown>) => unknown;
        CouponBulkIssueConfig?: Record<string, unknown>;
    }
}

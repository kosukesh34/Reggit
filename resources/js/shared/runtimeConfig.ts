export type FeatureFlags = Record<string, boolean>;

export type RuntimeConfig = {
    projectKey: string;
    apiBase: string;
    orderMode: string;
    isMobileAppMode: boolean;
    isLiff: boolean;
    lineUserId: string | null;
    featureFlags: FeatureFlags;
};

function readMetaContent(name: string): string {
    const element = document.querySelector(`meta[name="${name}"]`);
    if (element === null) {
        return '';
    }
    return element.getAttribute('content')?.trim() ?? '';
}

export function readRuntimeConfig(): RuntimeConfig {
    return {
        projectKey: window.__PROJECT_KEY__ || readMetaContent('project-key') || '',
        apiBase: window.__API_BASE__ || readMetaContent('api-base') || '/api',
        orderMode: window.__ORDER_MODE__ ?? 'both',
        isMobileAppMode: window.__MOBILE_APP_MODE__ === true,
        isLiff: window.__IS_LIFF__ === true,
        lineUserId: window.__LINE_USER_ID__ ?? null,
        featureFlags: window.__FEATURE_FLAGS__ ?? {},
    };
}

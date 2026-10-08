type LegacyApp = {
    onLiffReady?: () => void;
};

type BootstrapWindow = Window & typeof globalThis & {
    app?: LegacyApp | null;
};

export function bootstrapLegacyApp(
    createInstance: () => LegacyApp,
    membershipRequired: () => boolean,
): void {
    if (typeof window === 'undefined' || window.__LIFF_APP_BOOTSTRAP__) return;
    const browser = window as BootstrapWindow;
    window.__LIFF_APP_BOOTSTRAP__ = true;
    window.__clientDebugLog?.('app_bootstrap_set', {});

    const hideLoading = () => {
        if (document.querySelector('meta[name="line-login-required"]') !== null && (!window.__LIFF_READY__ || !window.__LINE_USER_ID__)) return;
        document.getElementById('liff-loading')?.classList.add('liff-loading--hidden');
    };
    const restoreIdentity = () => {
        if (window.__LINE_USER_ID__ || window.__IS_LIFF__) return;
        try {
            const projectKey = window.__PROJECT_KEY__ || '';
            const scoped = String(localStorage.getItem(`mobile_order_line_user_id_${projectKey}`) || '').trim();
            const legacy = String(localStorage.getItem('mobile_order_line_user_id') || '').trim();
            const cached = /^U[0-9a-f]{32}$/i.test(scoped) ? scoped : (/^U[0-9a-f]{32}$/i.test(legacy) ? legacy : '');
            if (cached) window.__LINE_USER_ID__ = cached;
        } catch { /* Storage can be unavailable in embedded browsers. */ }
    };
    const createApp = () => {
        if (browser.app) return;
        if (document.querySelector('meta[name="line-login-required"]') !== null && (!window.__LIFF_READY__ || !window.__LINE_USER_ID__)) return;
        restoreIdentity();
        browser.app = createInstance();
        window.__clientDebugLog?.('app_created', {
            require_membership: membershipRequired(),
            project_key: window.__PROJECT_KEY__ || null,
        });
        hideLoading();
    };

    document.addEventListener('liff-ready', function onLiffReady() {
        if (!browser.app) createApp();
        if (browser.app?.onLiffReady) {
            window.__clientDebugLog?.('app_on_liff_ready_called', {});
            browser.app.onLiffReady();
        }
    }, { once: true });

    window.setTimeout(() => {
        if (!browser.app && (!window.__IS_LIFF__ || window.__LIFF_ID_MISSING__)) createApp();
        hideLoading();
    }, 5000);
    window.setTimeout(() => {
        if (!browser.app) createApp();
    }, 15000);
}

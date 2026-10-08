import { pointQrTokenFromUrl } from '../../frontend/member/pointQrToken';
import { AppConstants } from '../constants';
import {
    extractQueueTokenFromEncodedParam,
    extractQueueTokenFromLocation,
    extractQueueTokenFromString,
    getPersistedQueueToken,
    persistQueueToken,
} from './queueToken';
import {
    extractStampGrantCodeFromEncodedParam,
    extractStampGrantCodeFromString,
    persistStampGrantCode,
} from './stampGrantToken';

declare const liff: any;

function bootstrapLiffInit(): void {
    if (typeof window === 'undefined' || window.__LIFF_INIT_BOOTSTRAPPED__ === true) {
        return;
    }
    window.__LIFF_INIT_BOOTSTRAPPED__ = true;

    const uaString = typeof navigator !== 'undefined' && navigator.userAgent ? navigator.userAgent : '';
    const debug = (eventName: string, payload?: Record<string, unknown>): void => {
        if (typeof window.__clientDebugLog === 'function') {
            window.__clientDebugLog(eventName, payload || {});
        }
    };

    function readNativeAppMeta(name: string, fallback: string): string {
        if (typeof document === 'undefined') {
            return fallback;
        }
        const el = document.querySelector(`meta[name="${name}"]`);
        const value = el && el.getAttribute('content') ? el.getAttribute('content')?.trim() : '';
        return value || fallback;
    }

    function isNativeMobileAppWebView(): boolean {
        const token = AppConstants.NATIVE_APP_USER_AGENT_TOKEN || readNativeAppMeta('native-app-user-agent-token', 'Reggit');
        const escaped = token.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        return new RegExp(`${escaped}\\/\\d+`, 'i').test(uaString);
    }

    function applyNativeEmbedClass(): void {
        if (typeof document === 'undefined' || !isNativeMobileAppWebView()) {
            return;
        }
        window.__MOBILE_APP_MODE__ = true;
        if (typeof window.__mobileOrderLog === 'function') {
            window.__mobileOrderLog('liff-init: native embed', { href: window.location.href });
        }
        document.documentElement?.classList.add('native-app-embed');
        const applyBodyClass = (): void => {
            document.body?.classList.add('native-app-embed');
        };
        applyBodyClass();
        if (!document.body) {
            document.addEventListener('DOMContentLoaded', applyBodyClass, { once: true });
        }
    }

    function persistQueueTokenIfPresent(): void {
        const token = extractQueueTokenFromLocation(window.location);
        if (token !== null) {
            persistQueueToken(token);
        }
    }

    function setLineUserId(userId: string | null, source: string): void {
        const normalized = userId ? String(userId).trim() : '';
        const valid = normalized && /^U[0-9a-f]{32}$/i.test(normalized);
        const previous = window.__LINE_USER_ID__ ? String(window.__LINE_USER_ID__).trim() : '';
        if (valid && previous && previous !== normalized) {
            debug('liff_line_user_id_changed', {
                source: source || 'unknown',
                previous_tail: previous.slice(-4),
                next_tail: normalized.slice(-4),
            });
        }
        window.__LINE_USER_ID__ = valid ? normalized : null;
        if (valid) {
            try {
                const projectKey = window.__PROJECT_KEY__ || '';
                window.localStorage?.setItem(`mobile_order_line_user_id_${projectKey}`, normalized);
                window.localStorage?.removeItem('mobile_order_line_user_id');
            } catch (_e) {
                // ignore
            }
            debug('liff_user_set', { source: source || 'unknown', tail: normalized.slice(-4) });
            return;
        }
        if (previous) {
            debug('liff_user_cleared', { source: source || 'unknown', previous_tail: previous.slice(-4) });
            try {
                const projectKey = window.__PROJECT_KEY__ || '';
                window.localStorage?.removeItem(`mobile_order_line_user_id_${projectKey}`);
                window.localStorage?.removeItem('mobile_order_line_user_id');
            } catch (_e) {
                // ignore
            }
        }
    }

    function setLineProfile(profile: unknown): void {
        window.__LINE_PROFILE__ = profile && typeof profile === 'object' ? profile as { displayName?: string; [key: string]: unknown } : null;
    }

    function dispatchProfileReady(): void {
        document.dispatchEvent(new CustomEvent('liff-profile-ready', {
            detail: {
                lineUserId: window.__LINE_USER_ID__,
                lineProfile: window.__LINE_PROFILE__,
                isLiff: !!window.__IS_LIFF__,
            },
        }));
    }

    function setLoadingText(text: string): void {
        const el = document.querySelector('.liff-loading-text');
        if (el) {
            el.textContent = text;
        }
    }

    function showLoginRequiredFallback(): void {
        const wrapper = document.getElementById('liff-loading');
        const inner = wrapper?.querySelector('.liff-loading-inner');
        if (!inner) {
            return;
        }
        inner.innerHTML = ''
            + '<p class="liff-loading-text" style="margin-bottom:16px;">認証が必要です。</p>'
            + '<p style="font-size:13px;color:rgba(255,255,255,0.9);margin-bottom:20px;">認証画面が表示されない場合は、下のボタンからもう一度お試しください。</p>'
            + '<button type="button" id="liff-login-retry-btn" style="border:none;border-radius:8px;padding:14px 24px;background:#06C755;color:#fff;font-size:15px;font-weight:700;cursor:pointer;width:100%;max-width:280px;">LINEでログインする</button>';
        const btn = document.getElementById('liff-login-retry-btn');
        btn?.addEventListener('click', () => {
            const id = getLiffId();
            if (typeof liff !== 'undefined' && id) {
                setLoadingText('読み込み中...');
                runInit(id);
            } else {
                window.location.reload();
            }
        });
        debug('liff_login_fallback_shown', {});
    }

    function scheduleLoginFallback(): void {
        const fallbackTimer = setTimeout(() => {
            if (window.__LINE_USER_ID__) {
                return;
            }
            showLoginRequiredFallback();
        }, 8000);
        window.addEventListener('beforeunload', () => {
            clearTimeout(fallbackTimer);
        }, { once: true });
    }

    function applyEmbedModeClass(isLiff: boolean): void {
        const root = document.documentElement;
        const body = document.body;
        const add = isLiff ? 'line-liff-embed' : 'line-web-embed';
        const remove = isLiff ? 'line-web-embed' : 'line-liff-embed';
        root?.classList.remove(remove);
        root?.classList.add(add);
        body?.classList.remove(remove);
        body?.classList.add(add);
    }

    function isInLineApp(): boolean {
        return /Line/i.test(uaString) || /LIFF/i.test(uaString);
    }

    function getLineUserIdFromToken(): string | null {
        if (typeof liff === 'undefined' || !liff.isLoggedIn()) {
            return null;
        }
        try {
            const decoded = liff.getDecodedIDToken();
            return decoded && decoded.sub ? decoded.sub : null;
        } catch (_e) {
            return null;
        }
    }

    function getLiffId(): string {
        const meta = document.querySelector('meta[name="line-liff-id"]');
        return meta ? (meta.getAttribute('content') || '').trim() : '';
    }

    function getProfileWithPermissionHandling(): Promise<unknown> {
        return new Promise((resolve, reject) => {
            if (typeof liff === 'undefined' || !liff.isLoggedIn()) {
                resolve(null);
                return;
            }
            liff.permission.query('profile').then((permissionStatus: { state: string }) => {
                if (permissionStatus.state === 'granted') {
                    return liff.getProfile().then(resolve).catch(reject);
                }
                return liff.permission.requestAll()
                    .then(() => liff.permission.query('profile'))
                    .then((newStatus: { state: string }) => {
                        if (newStatus.state === 'granted') {
                            return liff.getProfile().then(resolve).catch(reject);
                        }
                        reject(new Error('PERMISSION_DENIED'));
                    })
                    .catch(reject);
            }).catch(reject);
        });
    }

    function shortenUrl(): void {
        if (!history.replaceState || !window.location.search) {
            return;
        }
        try {
            const current = new URL(window.location.href);
            const keepParams = ['queue_id', 'queue_token', 'project_key', 'store_id', 'g', 'point_code', 'payment_order_id', 'payment_status'];
            const kept = new URLSearchParams();
            keepParams.forEach((key) => {
                const value = current.searchParams.get(key);
                if (value !== null && value !== '') {
                    kept.set(key, value);
                }
            });
            // LIFF can wrap return parameters in liff.state on its primary redirect.
            const liffState = current.searchParams.get('liff.state');
            if (liffState) {
                const stateUrl = new URL(liffState, current.origin);
                for (const key of ['payment_order_id', 'payment_status']) {
                    const value = stateUrl.searchParams.get(key);
                    if (!kept.has(key) && value) kept.set(key, value);
                }
            }
            const pointCode = pointQrTokenFromUrl(current.href);
            if (pointCode) kept.set('point_code', pointCode);
            const hashQueue = extractQueueTokenFromString(current.hash || '');
            if (hashQueue && !kept.get('queue_token') && !kept.get('queue_id')) {
                kept.set('queue_token', hashQueue);
            }
            if (!kept.get('queue_token') && !kept.get('queue_id')) {
                const resolved = extractQueueTokenFromEncodedParam(current.searchParams.get('liff.state'))
                    || extractQueueTokenFromEncodedParam(current.searchParams.get('state'))
                    || extractQueueTokenFromEncodedParam(current.searchParams.get('liffRedirectUri'))
                    || getPersistedQueueToken();
                if (resolved) {
                    kept.set('queue_token', resolved);
                }
            }
            if (!kept.get('g')) {
                const grant = extractStampGrantCodeFromString(current.hash || '')
                    || extractStampGrantCodeFromEncodedParam(current.searchParams.get('liff.state'))
                    || extractStampGrantCodeFromEncodedParam(current.searchParams.get('state'));
                if (grant) {
                    kept.set('g', grant);
                }
            }
            const grantKept = kept.get('g');
            if (grantKept) {
                persistStampGrantCode(grantKept);
            }
            const search = kept.toString();
            const nextUrl = search
                ? `${window.location.origin}${window.location.pathname}?${search}`
                : `${window.location.origin}${window.location.pathname}`;
            history.replaceState(null, '', `${nextUrl}${window.location.hash || ''}`);
        } catch (_e) {
            // ignore
        }
    }

    function hideLiffLoading(): void {
        const el = document.getElementById('liff-loading');
        if (el !== null) {
            el.classList.add('liff-loading--hidden');
        }
    }

    function dispatchReady(): void {
        if (typeof window.__mobileOrderLog === 'function') {
            window.__mobileOrderLog('liff-ready', { hasLineUserId: !!window.__LINE_USER_ID__ });
        }
        window.__LIFF_READY__ = true;
        debug('liff_dispatch_ready', { hasLineUserId: !!window.__LINE_USER_ID__, isLiff: !!window.__IS_LIFF__ });
        shortenUrl();
        hideLiffLoading();
        document.dispatchEvent(new CustomEvent('liff-ready', {
            detail: {
                lineUserId: window.__LINE_USER_ID__,
                lineProfile: window.__LINE_PROFILE__,
                isLiff: !!window.__IS_LIFF__,
            },
        }));
    }

    function showPermissionDeniedScreen(): void {
        window.__LIFF_PERMISSION_DENIED__ = true;
        debug('liff_permission_denied_screen', {});
    }

    function removePermissionPrompt(): void {
        const existing = document.getElementById('liff-permission-prompt');
        existing?.parentNode?.removeChild(existing);
    }

    function showPermissionPrompt(message: string): void {
        removePermissionPrompt();
        const wrapper = document.createElement('div');
        wrapper.id = 'liff-permission-prompt';
        wrapper.style.position = 'fixed';
        wrapper.style.left = '0';
        wrapper.style.right = '0';
        wrapper.style.bottom = '0';
        wrapper.style.zIndex = '10001';
        wrapper.style.padding = '12px 16px calc(12px + env(safe-area-inset-bottom, 0px))';
        wrapper.style.background = 'rgba(0,0,0,0.86)';
        wrapper.style.color = '#fff';
        wrapper.style.fontSize = '14px';
        wrapper.style.lineHeight = '1.5';
        wrapper.style.boxSizing = 'border-box';
        const text = document.createElement('div');
        text.textContent = message || 'LINEのアクセス許可が必要です。許可してください。';
        text.style.marginBottom = '10px';
        const button = document.createElement('button');
        button.type = 'button';
        button.textContent = 'アクセスを許可する';
        button.style.border = 'none';
        button.style.borderRadius = '8px';
        button.style.padding = '10px 12px';
        button.style.background = '#06C755';
        button.style.color = '#fff';
        button.style.fontWeight = '700';
        button.style.cursor = 'pointer';
        button.style.width = '100%';
        button.addEventListener('click', () => {
            if (typeof liff === 'undefined' || !liff.isLoggedIn()) {
                text.textContent = 'LINEログインが必要です。再度開き直してください。';
                return;
            }
            button.disabled = true;
            button.textContent = '確認中...';
            liff.permission.requestAll()
                .then(() => liff.permission.query('profile'))
                .then((status: { state: string }) => {
                    if (status && status.state === 'granted') {
                        return liff.getProfile().then((profile: unknown) => {
                            setLineProfile(profile || null);
                            window.__LIFF_PERMISSION_DENIED__ = false;
                            dispatchProfileReady();
                            removePermissionPrompt();
                        });
                    }
                    throw new Error('PERMISSION_DENIED');
                })
                .catch(() => {
                    text.textContent = 'アクセス許可が必要です。LINE側で許可後に再度お試しください。';
                })
                .finally(() => {
                    button.disabled = false;
                    button.textContent = 'アクセスを許可する';
                });
        });
        wrapper.appendChild(text);
        wrapper.appendChild(button);
        document.body.appendChild(wrapper);
    }

    function applyLiffDesignTokens(): void {
        const settings = window.__LIFF_TAB_SETTINGS__ || {};
        const global = (settings._global || {}) as Record<string, unknown>;
        const root = document.documentElement;
        const radiusMap: Record<string, Record<string, string>> = {
            compact: { xs: '6px', sm: '10px', md: '12px', lg: '14px', xl: '18px' },
            default: { xs: '8px', sm: '12px', md: '16px', lg: '20px', xl: '24px' },
            soft: { xs: '10px', sm: '14px', md: '18px', lg: '22px', xl: '28px' },
            round: { xs: '12px', sm: '18px', md: '24px', lg: '28px', xl: '32px' },
        };
        if (global.content_padding) {
            root.style.setProperty('--content-padding', `${global.content_padding}px`);
        }
        if (global.section_gap) {
            root.style.setProperty('--section-gap', `${global.section_gap}px`);
        }
        const radiusScale = typeof global.radius_scale === 'string' ? global.radius_scale : 'soft';
        const scale = radiusMap[radiusScale] || radiusMap.soft;
        root.style.setProperty('--radius-xs', scale.xs);
        root.style.setProperty('--radius-sm', scale.sm);
        root.style.setProperty('--radius', scale.md);
        root.style.setProperty('--radius-lg', scale.lg);
        root.style.setProperty('--radius-xl', scale.xl);
        const applyBodyClass = (): void => {
            if (!document.body || !global.tab_style || global.tab_style === 'default') {
                return;
            }
            document.body.classList.add(`tab-style-${global.tab_style}`);
        };
        applyBodyClass();
        if (!document.body) {
            document.addEventListener('DOMContentLoaded', applyBodyClass, { once: true });
        }
    }

    function waitForLiff(callback: () => void, maxWaitMs = 3000): void {
        const start = Date.now();
        const check = (): void => {
            if (typeof window.liff !== 'undefined') {
                callback();
                return;
            }
            if (Date.now() - start >= maxWaitMs) {
                setLineUserId(null, 'liff_id_missing');
                showLoginRequiredFallback();
                return;
            }
            setTimeout(check, 50);
        };
        check();
    }

    function runInit(liffId: string): void {
        // SDK initialization may never settle on a poor connection.
        const initTimer = window.setTimeout(() => {
            if (!window.__LIFF_READY__) showLoginRequiredFallback();
        }, 15000);
        liff.init({
            liffId,
            withLoginOnExternalBrowser: true,
        }).then(() => {
            window.clearTimeout(initTimer);
            debug('liff_init_success', {});
            if (typeof liff.getOS === 'function') {
                const liffOs = liff.getOS();
                if (liffOs === 'ios' || liffOs === 'android') {
                    window.__LIFF_OS__ = liffOs;
                    debug('liff_os_detected', { os: liffOs });
                }
            }
            const userId = liff.isLoggedIn() ? getLineUserIdFromToken() : null;
            setLineUserId(userId, 'liff_token');
            debug('liff_user_resolved', { hasUserId: !!userId, tail: userId ? String(userId).slice(-4) : '' });
            if (!userId) {
                debug('liff_not_logged_in_login_redirect', {});
                liff.login();
                scheduleLoginFallback();
                return;
            }
            dispatchReady();
            getProfileWithPermissionHandling()
                .then((profile) => {
                    setLineProfile(profile || null);
                    debug('liff_profile_success', { hasProfile: !!profile });
                    dispatchProfileReady();
                })
                .catch((err: { message?: string }) => {
                    const errorMessage = err && err.message ? err.message : 'unknown';
                    debug('liff_profile_failed', { message: errorMessage });
                    if (errorMessage === 'The access token expired') {
                        try {
                            if (typeof liff.logout === 'function') {
                                liff.logout();
                            }
                            liff.login();
                            scheduleLoginFallback();
                            return;
                        } catch (_e) {
                            // ignore
                        }
                    }
                    if (err && err.message === 'PERMISSION_DENIED') {
                        showPermissionDeniedScreen();
                        showPermissionPrompt('プロフィール連携の許可が必要です。');
                    }
                });
        }).catch((err: { message?: string }) => {
            window.clearTimeout(initTimer);
            debug('liff_init_failed', { message: err && err.message ? err.message : 'unknown' });
            setLineUserId(null, 'liff_init_failed');
            showLoginRequiredFallback();
        });
    }

    applyNativeEmbedClass();
    applyLiffDesignTokens();
    persistQueueTokenIfPresent();
    window.__IS_LIFF__ = isInLineApp();
    applyEmbedModeClass(window.__IS_LIFF__);
    const liffId = getLiffId();
    debug('liff_boot_start', { isLiff: !!window.__IS_LIFF__, hasLiffId: !!liffId });

    // Native screens use device authentication. The LINE web route always requires LINE login.
    const loginRequired = document.querySelector('meta[name="line-login-required"]') !== null;
    if (!loginRequired) {
        setLineUserId(null, 'native');
        setLineProfile(null);
        setTimeout(dispatchReady, 50);
        return;
    }
    if (!liffId) {
        window.__LIFF_ID_MISSING__ = true;
        setLineUserId(null, 'liff_id_missing');
        setLineProfile(null);
        setLoadingText('LINE連携が設定されていません。店舗にお問い合わせください。');
        return;
    }
    setLoadingText('読み込み中...');
    waitForLiff(() => runInit(liffId));
}

bootstrapLiffInit();

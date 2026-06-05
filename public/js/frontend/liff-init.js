/**
 * LIFF 初期化
 *
 * - LINE アプリ内（LIFF URL から開いた場合）: LIFF SDK を初期化し、未ログインなら認証画面を表示
 * - 外部ブラウザ・ネイティブアプリ: LIFF 初期化はスキップし、認証なしでアプリを表示
 *
 * window.__LINE_USER_ID__  = LINEユーザーID (null = 未ログイン/外部ブラウザ)
 * window.__LINE_PROFILE__  = LINEプロフィール情報 (null = 未取得)
 * window.__IS_LIFF__       = LINEアプリ内かどうか（UA に Line/LIFF を含む場合 true）
 */
(function () {
    const QUEUE_TOKEN_STORAGE_KEY = 'mobile_order_pending_queue_token';
    const uaString = (typeof navigator !== 'undefined' && navigator.userAgent) ? navigator.userAgent : '';
    const debug = function (eventName, payload) {
        if (typeof window !== 'undefined' && typeof window.__clientDebugLog === 'function') {
            window.__clientDebugLog(eventName, payload || {});
        }
    };

    function isNativeMobileAppWebView() {
        const token = (typeof AppConstants !== 'undefined' && AppConstants.NATIVE_APP_USER_AGENT_TOKEN)
            ? AppConstants.NATIVE_APP_USER_AGENT_TOKEN
            : readNativeAppMeta('native-app-user-agent-token', 'Reggit');
        const escaped = token.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        return new RegExp(`${escaped}\\/\\d+`, 'i').test(uaString);
    }

    function readNativeAppMeta(name, fallback) {
        if (typeof document === 'undefined') return fallback;
        const el = document.querySelector(`meta[name="${name}"]`);
        const value = el && el.getAttribute('content') ? el.getAttribute('content').trim() : '';
        return value || fallback;
    }

    function applyNativeEmbedClass() {
        if (typeof document === 'undefined' || typeof window === 'undefined') return;
        if (!isNativeMobileAppWebView()) return;
        window.__MOBILE_APP_MODE__ = true;
        if (typeof window.__mobileOrderLog === 'function') window.__mobileOrderLog('liff-init: native embed', { href: window.location.href });
        if (document.documentElement) {
            document.documentElement.classList.add('native-app-embed');
        }
        const applyBodyClass = function () {
            if (document.body) document.body.classList.add('native-app-embed');
        };
        applyBodyClass();
        if (!document.body) {
            document.addEventListener('DOMContentLoaded', applyBodyClass, { once: true });
        }
    }

    function persistQueueTokenIfPresent() {
        if (typeof window === 'undefined' || typeof window.sessionStorage === 'undefined') return;
        const token = extractQueueTokenFromLocation(window.location);
        if (token) {
            try {
                window.sessionStorage.setItem(QUEUE_TOKEN_STORAGE_KEY, token);
            } catch (e) { /* ignore */ }
        }
    }

    function getStoredQueueToken() {
        if (typeof window === 'undefined' || typeof window.sessionStorage === 'undefined') return null;
        try {
            return window.sessionStorage.getItem(QUEUE_TOKEN_STORAGE_KEY);
        } catch (e) {
            return null;
        }
    }

    function extractQueueTokenFromLocation(locationLike) {
        if (!locationLike) return null;
        const search = locationLike.search || '';
        const hash = locationLike.hash || '';
        const params = new URLSearchParams(search);
        const direct = params.get('queue_token') || params.get('queue_id');
        if (direct) return direct;

        const fromHash = extractQueueFromRaw(hash);
        if (fromHash) return fromHash;

        const fromLiffState = extractQueueFromRaw(params.get('liff.state'));
        if (fromLiffState) return fromLiffState;

        const fromState = extractQueueFromRaw(params.get('state'));
        if (fromState) return fromState;

        return extractQueueFromRaw(params.get('liffRedirectUri'));
    }

    function extractQueueFromRaw(raw) {
        if (!raw) return null;

        function parseFromString(s) {
            const normalized = (s || '').startsWith('#') ? (s || '').slice(1) : (s || '');
            try {
                const u = new URL(normalized);
                return u.searchParams.get('queue_token') || u.searchParams.get('queue_id');
            } catch (e) { /* ignore */ }
            const q = normalized.indexOf('?') >= 0 ? normalized.split('?')[1] : normalized;
            const p = new URLSearchParams(q);
            return p.get('queue_token') || p.get('queue_id');
        }

        try {
            const decoded = decodeURIComponent(raw);
            const fromDecoded = parseFromString(decoded);
            if (fromDecoded) return fromDecoded;
        } catch (e) { /* ignore */ }

        return parseFromString(raw);
    }

    function getLiffId() {
        const meta = document.querySelector('meta[name="line-liff-id"]');
        return meta ? (meta.getAttribute('content') || '').trim() : '';
    }

    function setLineUserId(userId, source) {
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
                if (typeof window !== 'undefined' && window.localStorage) {
                    const projectKey = (typeof window.__PROJECT_KEY__ !== 'undefined' && window.__PROJECT_KEY__)
                        ? window.__PROJECT_KEY__
                        : 'default';
                    window.localStorage.setItem('mobile_order_line_user_id_' + projectKey, normalized);
                    window.localStorage.removeItem('mobile_order_line_user_id');
                }
            } catch (e) { /* ignore */ }
            debug('liff_user_set', { source: source || 'unknown', tail: normalized.slice(-4) });
        } else if (previous) {
            debug('liff_user_cleared', { source: source || 'unknown', previous_tail: previous.slice(-4) });
            try {
                if (typeof window !== 'undefined' && window.localStorage) {
                    const projectKey = (typeof window.__PROJECT_KEY__ !== 'undefined' && window.__PROJECT_KEY__)
                        ? window.__PROJECT_KEY__
                        : 'default';
                    window.localStorage.removeItem('mobile_order_line_user_id_' + projectKey);
                    window.localStorage.removeItem('mobile_order_line_user_id');
                }
            } catch (e) { /* ignore */ }
        }
    }

    function setLineProfile(profile) {
        window.__LINE_PROFILE__ = profile || null;
    }

    function dispatchProfileReady() {
        document.dispatchEvent(new CustomEvent('liff-profile-ready', {
            detail: {
                lineUserId: window.__LINE_USER_ID__,
                lineProfile: window.__LINE_PROFILE__,
                isLiff: !!window.__IS_LIFF__
            }
        }));
    }

    function setLoadingText(text) {
        const el = document.querySelector('.liff-loading-text');
        if (el) el.textContent = text;
    }

    /**
     * 認証画面が表示されなかったときに表示するフォールバック。
     * 「認証が必要です」と「LINEでログインする」ボタンを表示し、タップで liff.login() を再実行できるようにする。
     */
    function showLoginRequiredFallback() {
        if (typeof document === 'undefined') return;
        const wrapper = document.getElementById('liff-loading');
        if (!wrapper) return;
        const inner = wrapper.querySelector('.liff-loading-inner');
        if (!inner) return;
        inner.innerHTML = ''
            + '<p class="liff-loading-text" style="margin-bottom:16px;">認証が必要です。</p>'
            + '<p style="font-size:13px;color:rgba(255,255,255,0.9);margin-bottom:20px;">認証画面が表示されない場合は、下のボタンからもう一度お試しください。</p>'
            + '<button type="button" id="liff-login-retry-btn" style="border:none;border-radius:8px;padding:14px 24px;background:#06C755;color:#fff;font-size:15px;font-weight:700;cursor:pointer;width:100%;max-width:280px;">LINEでログインする</button>';
        const btn = document.getElementById('liff-login-retry-btn');
        if (btn) {
            btn.addEventListener('click', function () {
                if (typeof liff !== 'undefined' && typeof liff.login === 'function') {
                    try {
                        liff.login();
                    } catch (e) {
                        setLoadingText('エラーが発生しました。画面を再読み込みしてください。');
                    }
                } else {
                    setLoadingText('読み込み中です。しばらくお待ちください…');
                    setTimeout(function () {
                        if (typeof liff !== 'undefined' && typeof liff.login === 'function') {
                            try { liff.login(); } catch (err) { /* ignore */ }
                        } else {
                            showLoginRequiredFallback();
                        }
                    }, 2000);
                }
            });
        }
        debug('liff_login_fallback_shown', {});
    }

    /** liff.login() 呼び出し後、リダイレクトされなかった場合にフォールバックを表示するタイマーを開始 */
    function scheduleLoginFallback() {
        var fallbackTimer = setTimeout(function () {
            if (typeof window.__LINE_USER_ID__ !== 'undefined' && window.__LINE_USER_ID__) return;
            showLoginRequiredFallback();
        }, 8000);
        if (typeof window !== 'undefined') {
            window.addEventListener('beforeunload', function () {
                clearTimeout(fallbackTimer);
            }, { once: true });
        }
    }

    function applyEmbedModeClass(isLiff) {
        if (typeof document === 'undefined') return;
        const root = document.documentElement;
        const body = document.body;
        const add = isLiff ? 'line-liff-embed' : 'line-web-embed';
        const remove = isLiff ? 'line-web-embed' : 'line-liff-embed';
        if (root) {
            root.classList.remove(remove);
            root.classList.add(add);
        }
        if (body) {
            body.classList.remove(remove);
            body.classList.add(add);
        }
    }

    /** LINEアプリ内かどうかを判定（LIFF SDK読み込み前でも使用可能） */
    function isInLineApp() {
        return /Line/i.test(uaString) || /LIFF/i.test(uaString);
    }

    function getLineUserIdFromToken() {
        if (typeof liff === 'undefined' || !liff.isLoggedIn()) return null;
        try {
            const decoded = liff.getDecodedIDToken();
            return decoded && decoded.sub ? decoded.sub : null;
        } catch (e) {
            console.warn('LIFF getDecodedIDToken error:', e);
            return null;
        }
    }

    function getProfileWithPermissionHandling() {
        return new Promise(function (resolve, reject) {
            if (typeof liff === 'undefined' || !liff.isLoggedIn()) {
                resolve(null);
                return;
            }
            liff.permission.query('profile').then(function (permissionStatus) {
                if (permissionStatus.state === 'granted') {
                    return liff.getProfile().then(resolve).catch(reject);
                }

                // prompt / denied を問わず、未許可時は必ず再リクエストする
                return liff.permission.requestAll().then(function () {
                    return liff.permission.query('profile');
                }).then(function (newStatus) {
                    if (newStatus.state === 'granted') {
                        return liff.getProfile().then(resolve).catch(reject);
                    }
                    reject(new Error('PERMISSION_DENIED'));
                }).catch(function (err) {
                    reject(err);
                });
            }).catch(reject);
        });
    }

    function shortenUrl() {
        if (typeof history !== 'undefined' && history.replaceState && window.location.search) {
            try {
                const current = new URL(window.location.href);
                const keepParams = ['queue_id', 'queue_token', 'project_key'];
                const kept = new URLSearchParams();
                keepParams.forEach(function (key) {
                    const value = current.searchParams.get(key);
                    if (value !== null && value !== '') {
                        kept.set(key, value);
                    }
                });
                const hashQueue = extractQueueFromRaw(current.hash || '');
                if (hashQueue && !kept.get('queue_token') && !kept.get('queue_id')) {
                    kept.set('queue_token', hashQueue);
                }

                // LIFF が state / liffRedirectUri に元クエリを入れるケースに対応
                if (!kept.get('queue_token') && !kept.get('queue_id')) {
                    const fromLiffState = extractQueueFromRaw(current.searchParams.get('liff.state'));
                    const fromState = extractQueueFromRaw(current.searchParams.get('state'));
                    const fromRedirect = extractQueueFromRaw(current.searchParams.get('liffRedirectUri'));
                    const resolved = fromLiffState || fromState || fromRedirect;
                    if (resolved) {
                        kept.set('queue_token', resolved);
                    }
                }
                if (!kept.get('queue_token') && !kept.get('queue_id')) {
                    const stored = getStoredQueueToken();
                    if (stored) {
                        kept.set('queue_token', stored);
                    }
                }

                const search = kept.toString();
                const nextUrl = search
                    ? (window.location.origin + window.location.pathname + '?' + search)
                    : (window.location.origin + window.location.pathname);
                history.replaceState(null, '', nextUrl);
            } catch (e) { /* ignore */ }
        }
    }

    function dispatchReady() {
        if (typeof window.__mobileOrderLog === 'function') window.__mobileOrderLog('liff-ready', { hasLineUserId: !!window.__LINE_USER_ID__ });
        debug('liff_dispatch_ready', {
            hasLineUserId: !!window.__LINE_USER_ID__,
            isLiff: !!window.__IS_LIFF__
        });
        shortenUrl();
        document.dispatchEvent(new CustomEvent('liff-ready', {
            detail: {
                lineUserId: window.__LINE_USER_ID__,
                lineProfile: window.__LINE_PROFILE__,
                isLiff: !!window.__IS_LIFF__
            }
        }));
    }

    function showPermissionDeniedScreen() {
        window.__LIFF_PERMISSION_DENIED__ = true;
        debug('liff_permission_denied_screen', {});
    }

    function removePermissionPrompt() {
        const existing = document.getElementById('liff-permission-prompt');
        if (existing && existing.parentNode) {
            existing.parentNode.removeChild(existing);
        }
    }

    function showPermissionPrompt(message) {
        if (typeof document === 'undefined') return;
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

        button.addEventListener('click', function () {
            if (typeof liff === 'undefined' || !liff.isLoggedIn()) {
                text.textContent = 'LINEログインが必要です。再度開き直してください。';
                return;
            }
            button.disabled = true;
            button.textContent = '確認中...';

            liff.permission.requestAll()
                .then(function () {
                    return liff.permission.query('profile');
                })
                .then(function (status) {
                    if (status && status.state === 'granted') {
                        return liff.getProfile().then(function (profile) {
                            setLineProfile(profile || null);
                            window.__LIFF_PERMISSION_DENIED__ = false;
                            dispatchProfileReady();
                            removePermissionPrompt();
                        });
                    }
                    throw new Error('PERMISSION_DENIED');
                })
                .catch(function () {
                    text.textContent = 'アクセス許可が必要です。LINE側で許可後に再度お試しください。';
                })
                .finally(function () {
                    button.disabled = false;
                    button.textContent = 'アクセスを許可する';
                });
        });

        wrapper.appendChild(text);
        wrapper.appendChild(button);
        document.body.appendChild(wrapper);
    }

    // ─── メイン初期化ロジック ───
    function applyLiffDesignTokens() {
        if (typeof document === 'undefined' || typeof window === 'undefined') return;
        const settings = window.__LIFF_TAB_SETTINGS__ || {};
        const global = settings._global || {};
        const root = document.documentElement;
        const radiusMap = {
            compact: { xs: '6px', sm: '10px', md: '12px', lg: '14px', xl: '18px' },
            default: { xs: '8px', sm: '12px', md: '16px', lg: '20px', xl: '24px' },
            soft: { xs: '10px', sm: '14px', md: '18px', lg: '22px', xl: '28px' },
            round: { xs: '12px', sm: '18px', md: '24px', lg: '28px', xl: '32px' },
        };
        if (global.content_padding) root.style.setProperty('--content-padding', global.content_padding + 'px');
        if (global.section_gap) root.style.setProperty('--section-gap', global.section_gap + 'px');
        const scale = radiusMap[global.radius_scale] || radiusMap.soft;
        root.style.setProperty('--radius-xs', scale.xs);
        root.style.setProperty('--radius-sm', scale.sm);
        root.style.setProperty('--radius', scale.md);
        root.style.setProperty('--radius-lg', scale.lg);
        root.style.setProperty('--radius-xl', scale.xl);
        const applyBodyClass = function () {
            if (!document.body || !global.tab_style || global.tab_style === 'default') return;
            document.body.classList.add('tab-style-' + global.tab_style);
        };
        applyBodyClass();
        if (!document.body) {
            document.addEventListener('DOMContentLoaded', applyBodyClass, { once: true });
        }
    }

    applyNativeEmbedClass();
    applyLiffDesignTokens();
    persistQueueTokenIfPresent();

    window.__IS_LIFF__ = isInLineApp();
    applyEmbedModeClass(window.__IS_LIFF__);

    const liffId = getLiffId();
    debug('liff_boot_start', { isLiff: !!window.__IS_LIFF__, hasLiffId: !!liffId });

    // (A) LIFF ID 未設定
    if (!liffId) {
        window.__LIFF_ID_MISSING__ = true;
        setLineUserId(null, 'liff_id_missing');
        setLineProfile(null);
        setTimeout(dispatchReady, 50);
        return;
    }

    // (B) 外部ブラウザ or ネイティブアプリ → LIFF 初期化せず、認証なしでアプリ表示
    if (!window.__IS_LIFF__) {
        setLineUserId(null, 'external_browser');
        setLineProfile(null);
        setTimeout(dispatchReady, 50);
        return;
    }

    // (C) LINEアプリ内（LIFF URL から開いた場合）→ LIFF 初期化し、未ログインなら認証画面を表示
    setLoadingText('読み込み中...');

    function runInit() {
        liff.init({
            liffId: liffId,
            withLoginOnExternalBrowser: false   // 外部ブラウザでは認証させない（(B)でスキップ済み）
        }).then(function () {
            debug('liff_init_success', {});
            const userId = liff.isLoggedIn() ? getLineUserIdFromToken() : null;
            setLineUserId(userId, 'liff_token');
            debug('liff_user_resolved', { hasUserId: !!userId, tail: userId ? String(userId).slice(-4) : '' });
            if (userId) {
                dispatchReady();
                getProfileWithPermissionHandling()
                    .then(function (profile) {
                        setLineProfile(profile || null);
                        debug('liff_profile_success', { hasProfile: !!profile });
                        dispatchProfileReady();
                    })
                    .catch(function (err) {
                        const errorMessage = err && err.message ? err.message : 'unknown';
                        debug('liff_profile_failed', { message: errorMessage });
                        if (errorMessage === 'The access token expired') {
                            debug('liff_profile_token_expired_relogin', {});
                            try {
                                if (typeof liff.logout === 'function') {
                                    liff.logout();
                                }
                                liff.login();
                                scheduleLoginFallback();
                                return;
                            } catch (loginErr) {
                                debug('liff_profile_token_expired_relogin_failed', {
                                    message: loginErr && loginErr.message ? loginErr.message : 'unknown'
                                });
                            }
                        }
                        if (err && err.message === 'PERMISSION_DENIED') {
                            showPermissionDeniedScreen();
                            showPermissionPrompt('プロフィール連携の許可が必要です。');
                        }
                    });
            } else {
                // LINEアプリ内で未ログイン → 認証画面へ（表示されない場合に備えフォールバックを予約）
                debug('liff_not_logged_in_login_redirect', {});
                liff.login();
                scheduleLoginFallback();
            }
        }).catch(function (err) {
            console.warn('LIFF init failed:', err);
            debug('liff_init_failed', { message: err && err.message ? err.message : 'unknown' });
            if (window.__IS_LIFF__) {
                try {
                    liff.login();
                    scheduleLoginFallback();
                } catch (e) { /* ignore */ }
                return;
            }
            setLineUserId(null, 'liff_id_missing');
            dispatchReady();
        });
    }

    function waitForLiff(callback, maxWaitMs) {
        maxWaitMs = maxWaitMs || 3000;
        const start = Date.now();
        function check() {
            if (typeof window.liff !== 'undefined') {
                callback();
                return;
            }
            if (Date.now() - start >= maxWaitMs) {
                console.warn('LIFF SDK load timeout');
                setLineUserId(null, 'liff_id_missing');
                if (window.__IS_LIFF__) {
                    setLoadingText('認証が必要です。');
                    showLoginRequiredFallback();
                } else {
                    dispatchReady();
                }
                return;
            }
            setTimeout(check, 50);
        }
        check();
    }

    waitForLiff(runInit);
})();

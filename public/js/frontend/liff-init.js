/**
 * LIFF 初期化
 *
 * - LINE アプリ内（LIFF ブラウザ）: LIFF SDK を初期化し、ログイン済みならユーザーIDを取得
 * - 外部ブラウザ: LIFF 初期化はスキップし、即座にアプリを表示（ログイン不要）
 *
 * window.__LINE_USER_ID__  = LINEユーザーID (null = 未ログイン/外部ブラウザ)
 * window.__LINE_PROFILE__  = LINEプロフィール情報 (null = 未取得)
 * window.__IS_LIFF__       = LINEアプリ内かどうか
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
        return /MobileOrderApp\/\d+/i.test(uaString);
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

    function setLineUserId(userId) {
        window.__LINE_USER_ID__ = userId || null;
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
        var existing = document.getElementById('liff-permission-prompt');
        if (existing && existing.parentNode) {
            existing.parentNode.removeChild(existing);
        }
    }

    function showPermissionPrompt(message) {
        if (typeof document === 'undefined') return;
        removePermissionPrompt();

        var wrapper = document.createElement('div');
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

        var text = document.createElement('div');
        text.textContent = message || 'LINEのアクセス許可が必要です。許可してください。';
        text.style.marginBottom = '10px';

        var button = document.createElement('button');
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
    applyNativeEmbedClass();
    persistQueueTokenIfPresent();

    window.__IS_LIFF__ = isInLineApp();
    applyEmbedModeClass(window.__IS_LIFF__);
    const liffId = getLiffId();
    debug('liff_boot_start', { isLiff: !!window.__IS_LIFF__, hasLiffId: !!liffId });

    // (A) LIFF ID 未設定 or 外部ブラウザ
    if (!liffId || !window.__IS_LIFF__) {
        if (!liffId) {
            window.__LIFF_ID_MISSING__ = true;
        }
        // LINEアプリ内なのに LIFF ID が無い場合はログインできないため、起動を止めて明示
        if (window.__IS_LIFF__ && !liffId) {
            setLoadingText('LIFF ID が未設定です。管理画面で設定してください。');
            return;
        }
        // 外部ブラウザではLINEログイン不要。即座にアプリを表示
        setLineUserId(null);
        setLineProfile(null);
        // 最小限の遅延で ready を発火（DOM読み込み待ち）
        setTimeout(dispatchReady, 50);
        return;
    }

    // (B) LINEアプリ内 → LIFF初期化
    setLoadingText('読み込み中...');

    function runInit() {
        liff.init({
            liffId: liffId,
            withLoginOnExternalBrowser: false   // 外部ブラウザでの自動ログインリダイレクトを無効化
        }).then(function () {
            debug('liff_init_success', {});
            const userId = liff.isLoggedIn() ? getLineUserIdFromToken() : null;
            setLineUserId(userId);
            debug('liff_user_resolved', { hasUserId: !!userId });
            if (userId) {
                // 初期表示を優先: プロフィール取得完了を待たずにアプリを起動
                dispatchReady();
                getProfileWithPermissionHandling()
                    .then(function (profile) {
                        setLineProfile(profile || null);
                        debug('liff_profile_success', { hasProfile: !!profile });
                        dispatchProfileReady();
                    })
                    .catch(function (err) {
                        debug('liff_profile_failed', { message: err && err.message ? err.message : 'unknown' });
                        if (err && err.message === 'PERMISSION_DENIED') {
                            showPermissionDeniedScreen();
                            showPermissionPrompt('プロフィール連携の許可が必要です。');
                        }
                    });
            } else {
                // LINEアプリ内だがログインしていない → 常にログインへ
                if (window.__IS_LIFF__) {
                    debug('liff_not_logged_in_login_redirect', {});
                    liff.login();
                    return;
                }
                dispatchReady();
            }
        }).catch(function (err) {
            console.warn('LIFF init failed:', err);
            debug('liff_init_failed', { message: err && err.message ? err.message : 'unknown' });
            // LIFF初期化失敗 → LINEアプリ内ならログインへ、外部ならアプリ起動
            if (window.__IS_LIFF__) {
                try { liff.login(); } catch (e) { /* ignore */ }
                return;
            }
            setLineUserId(null);
            dispatchReady();
        });
    }

    // LIFF SDKの読み込みを待機（最大3秒）
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
                setLineUserId(null);
                dispatchReady();
                return;
            }
            setTimeout(check, 50);
        }
        check();
    }

    waitForLiff(runInit);
})();

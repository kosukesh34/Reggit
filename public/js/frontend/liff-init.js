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

    function setLoadingText(text) {
        const el = document.querySelector('.liff-loading-text');
        if (el) el.textContent = text;
    }

    /** LINEアプリ内かどうかを判定（LIFF SDK読み込み前でも使用可能） */
    function isInLineApp() {
        const ua = navigator.userAgent || '';
        return /Line/i.test(ua) || /LIFF/i.test(ua);
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
                if (permissionStatus.state === 'prompt') {
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
                }
                reject(new Error('PERMISSION_DENIED'));
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
    }

    // ─── メイン初期化ロジック ───
    persistQueueTokenIfPresent();

    window.__IS_LIFF__ = isInLineApp();
    const liffId = getLiffId();

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
            const userId = liff.isLoggedIn() ? getLineUserIdFromToken() : null;
            setLineUserId(userId);
            if (userId) {
                getProfileWithPermissionHandling()
                    .then(function (profile) {
                        setLineProfile(profile || null);
                        dispatchReady();
                    })
                    .catch(function (err) {
                        if (err && err.message === 'PERMISSION_DENIED') {
                            showPermissionDeniedScreen();
                        }
                        dispatchReady();
                    });
            } else {
                // LINEアプリ内だがログインしていない → 常にログインへ
                if (window.__IS_LIFF__) {
                    liff.login();
                    return;
                }
                dispatchReady();
            }
        }).catch(function (err) {
            console.warn('LIFF init failed:', err);
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

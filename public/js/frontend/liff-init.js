/**
 * LIFF 初期化: LINE 認証画面を経由してユーザーIDを取得する。
 * チャネル同意の簡略化対応: 権限拒否時のエラーハンドリングを含む。
 */
(function () {
    function getLiffId() {
        var meta = document.querySelector('meta[name="line-liff-id"]');
        return meta ? (meta.getAttribute('content') || '').trim() : '';
    }

    function setLineUserId(userId) {
        window.__LINE_USER_ID__ = userId || null;
    }

    function setLineProfile(profile) {
        window.__LINE_PROFILE__ = profile || null;
    }

    function setLoadingText(text) {
        var el = document.querySelector('.liff-loading-text');
        if (el) el.textContent = text;
    }

    /**
     * openid は簡略化同意で取得可能。getDecodedIDToken() の sub が LINE ユーザーID。
     */
    function getLineUserIdFromToken() {
        if (typeof liff === 'undefined' || !liff.isLoggedIn()) return null;
        try {
            var decoded = liff.getDecodedIDToken();
            return decoded && decoded.sub ? decoded.sub : null;
        } catch (e) {
            console.warn('LIFF getDecodedIDToken error:', e);
            return null;
        }
    }

    /**
     * プロフィール取得（profile スコープ）。権限がない場合は許可画面が表示される。
     * 権限拒否時は PERMISSION_DENIED を throw。
     */
    function getProfileWithPermissionHandling() {
        return new Promise(function (resolve, reject) {
            if (typeof liff === 'undefined' || !liff.isLoggedIn()) {
                resolve(null);
                return;
            }
            // 権限状態を確認
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
                        if (err && err.message === 'PERMISSION_DENIED') reject(err);
                        else reject(err);
                    });
                }
                reject(new Error('PERMISSION_DENIED'));
            }).catch(reject);
        });
    }

    function dispatchReady() {
        document.dispatchEvent(new CustomEvent('liff-ready', {
            detail: {
                lineUserId: window.__LINE_USER_ID__,
                lineProfile: window.__LINE_PROFILE__
            }
        }));
    }

    function showPermissionDeniedScreen() {
        window.__LIFF_PERMISSION_DENIED__ = true;
    }

    function runInit() {
        var liffId = getLiffId();
        if (!liffId) {
            setLoadingText('LIFF IDが設定されていません');
            window.__LIFF_ID_MISSING__ = true;
            setTimeout(dispatchReady, 1500);
            return;
        }
        setLoadingText('LINEで認証しています...');
        liff.init({
            liffId: liffId,
            withLoginOnExternalBrowser: true
        }).then(function () {
            if (!liff.isLoggedIn()) {
                setLoadingText('LINEで認証しています...');
                liff.login();
                return;
            }
            var userId = getLineUserIdFromToken();
            if (liff.isInClient && liff.isInClient() && !userId) {
                setLoadingText('LINEで認証しています...');
                liff.login();
                return;
            }
            setLineUserId(userId);
            setLoadingText('読み込み中...');
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
                dispatchReady();
            }
        }).catch(function (err) {
            console.warn('LIFF init failed:', err);
            setLoadingText('読み込みに失敗しました');
            setTimeout(dispatchReady, 1500);
        });
    }

    function waitForLiff(callback, maxWaitMs) {
        maxWaitMs = maxWaitMs || 5000;
        var start = Date.now();
        function check() {
            if (typeof window.liff !== 'undefined') {
                callback();
                return;
            }
            if (Date.now() - start >= maxWaitMs) {
                setLoadingText('読み込みに失敗しました');
                setTimeout(dispatchReady, 1500);
                return;
            }
            setTimeout(check, 80);
        }
        check();
    }

    var liffId = getLiffId();
    if (!liffId) {
        setLoadingText('LIFF IDが設定されていません');
        window.__LIFF_ID_MISSING__ = true;
        setTimeout(dispatchReady, 1500);
        return;
    }
    waitForLiff(runInit);
})();

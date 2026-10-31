import { AppConstants } from '../../shared/constants';
import { ToastManager } from '../../shared/utils/toast';
import {
    canUseNativeAppleWalletPass,
    canUseNativeExternalWallet,
    getWalletReturnParams,
    isGoogleWalletEnabled,
    isIOS,
    isMobileAppMode,
    isWalletAddUrl,
    normalizeExternalUrl,
    resolveApiUrl,
    resolveAppOrigin,
    resolveGoogleWalletOpenUrl,
} from './walletEnvironment';
import { formatWalletErrorMessage } from './walletUrlPolicy';

declare const liff: any;

/** 会員証の Apple / Google Wallet 追加ボタン。発行 URL の先読みと、LIFF・ネイティブアプリ・ブラウザごとの開き方を扱う。 */
export class MemberWalletActions {
    /** WalletPassUrlIssueUseCase::TOKEN_TTL_MINUTES(3) より短めに余裕を持たせる */
    static WALLET_PASS_PREFETCH_TTL_MS = 150000;
    static GOOGLE_WALLET_PREFETCH_TTL_MS = 150000;

    _googleWalletOpenUrl: string | null = null;
    _googleWalletOpenUrlAt = 0;

    constructor(
        private apiClient: any,
        private ensureIdentityReady: (maxWaitMs?: number) => Promise<unknown>,
        private _debugLog: (eventName: string, payload?: unknown) => void,
    ) {}

    setup() {
        const projectKey = (typeof window !== 'undefined' && window.__PROJECT_KEY__) ? window.__PROJECT_KEY__ : '';
        const appleBtn = document.querySelector('.member-card-add-to-wallet-btn[data-wallet-pass-url]');
        if (appleBtn && appleBtn.getAttribute('data-wallet-bound') !== '1') {
            appleBtn.setAttribute('data-wallet-bound', '1');
            appleBtn.addEventListener('click', (e) => this._onAddToAppleWallet(e, appleBtn, projectKey));
            if (this._shouldPrefetchAppleWalletUrl()) {
                this._setAppleWalletButtonPreparing(appleBtn, true);
                this._prefetchAppleWalletOpenUrl(projectKey);
            }
        }
        const googleBtn = document.querySelector('.member-card-add-to-wallet-btn[data-google-wallet]');
        if (googleBtn && googleBtn.getAttribute('data-wallet-bound') !== '1') {
            googleBtn.setAttribute('data-wallet-bound', '1');
            googleBtn.addEventListener('click', (e) => this._onAddToGoogleWallet(e, googleBtn, projectKey));
            if (this._shouldPrefetchGoogleWalletUrl()) {
                this._setGoogleWalletButtonPreparing(googleBtn, true);
                this._prefetchGoogleWalletOpenUrl(projectKey).catch((err) => {
                    this._debugLog('google_wallet_prefetch_failed', {
                        message: err && err.message ? String(err.message) : 'unknown',
                        phase: 'initial',
                    });
                });
            }
        }
    }

    _shouldPrefetchGoogleWalletUrl() {
        if (typeof window === 'undefined') return false;
        if (!isGoogleWalletEnabled()) return false;
        if (window.__IS_LIFF__ === true) return true;
        return isMobileAppMode() && typeof window.__nativeOpenExternalUrl__ === 'function';
    }

    _setGoogleWalletButtonPreparing(btn, preparing) {
        if (!btn) return;
        const label = btn.querySelector('.member-card-add-to-wallet-text');
        if (preparing) {
            btn.disabled = true;
            this._googleWalletOpenUrl = null;
            this._googleWalletOpenUrlAt = 0;
            if (label) {
                label.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> 準備中...';
            }
            return;
        }
        btn.disabled = false;
        if (label) {
            label.innerHTML = '<i class="fab fa-google" aria-hidden="true"></i> Google Walletに追加';
        }
    }

    _storeGoogleWalletOpenUrl(openUrl) {
        if (!openUrl) {
            this._googleWalletOpenUrl = null;
            this._googleWalletOpenUrlAt = 0;
            return null;
        }
        this._googleWalletOpenUrl = openUrl;
        this._googleWalletOpenUrlAt = Date.now();
        return openUrl;
    }

    _getFreshGoogleWalletOpenUrl() {
        if (!this._googleWalletOpenUrl || !this._googleWalletOpenUrlAt) return null;
        const age = Date.now() - this._googleWalletOpenUrlAt;
        if (!Number.isFinite(age) || age < 0 || age >= MemberWalletActions.GOOGLE_WALLET_PREFETCH_TTL_MS) {
            this._googleWalletOpenUrl = null;
            this._googleWalletOpenUrlAt = 0;
            return null;
        }
        return this._googleWalletOpenUrl;
    }

    _prefetchGoogleWalletOpenUrl(projectKey) {
        return this._fetchGoogleWalletIssueUrl(projectKey)
            .then((issueUrl) => {
                const openUrl = resolveGoogleWalletOpenUrl(issueUrl);
                const btn = document.querySelector('.member-card-add-to-wallet-btn[data-google-wallet]');
                this._storeGoogleWalletOpenUrl(openUrl);
                if (btn) {
                    this._setGoogleWalletButtonPreparing(btn, false);
                }
                this._debugLog('google_wallet_prefetch_ok', {
                    has_url: !!openUrl,
                    url_length: openUrl ? openUrl.length : 0,
                });
                return openUrl;
            })
            .catch((err) => {
                this._debugLog('google_wallet_prefetch_failed', {
                    message: err && err.message ? String(err.message) : 'unknown',
                });
                const btn = document.querySelector('.member-card-add-to-wallet-btn[data-google-wallet]');
                if (btn) this._setGoogleWalletButtonPreparing(btn, false);
                throw err;
            });
    }

    async _fetchGoogleWalletIssueUrl(projectKey) {
        await this.ensureIdentityReady(2500);
        const endpoint = AppConstants.API_ENDPOINTS.MEMBER_GOOGLE_WALLET_URL
            + '?project_key=' + encodeURIComponent(projectKey);
        const result = await this.apiClient.get(endpoint);
        if (!result.success || !result.data || !result.data.url) {
            const apiError = result.error
                || (result.data && result.data.error)
                || (result.data && Array.isArray(result.data.reasons) && result.data.reasons.length
                    ? result.data.reasons.join(' / ')
                    : null)
                || 'unknown';
            this._debugLog('google_wallet_url_failed', { error: apiError });
            throw new Error(apiError || 'URLの取得に失敗しました');
        }
        return result.data.url;
    }

    _shouldPrefetchAppleWalletUrl() {
        if (typeof window === 'undefined') return false;
        if (window.__IS_LIFF__ === true) return true;
        return isMobileAppMode()
            && (canUseNativeExternalWallet() || canUseNativeAppleWalletPass());
    }

    _setAppleWalletButtonPreparing(btn, preparing) {
        if (!btn) return;
        const label = btn.querySelector('.member-card-add-to-wallet-text');
        if (preparing) {
            btn.disabled = true;
            btn.removeAttribute('data-wallet-open-url');
            btn.removeAttribute('data-wallet-open-url-at');
            if (label) {
                label.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> 準備中...';
            }
            return;
        }
        btn.disabled = false;
        if (label) {
            label.innerHTML = '<i class="fab fa-apple" aria-hidden="true"></i> Apple Walletに追加';
        }
    }

    /** API 発行 URL から .pkpass 直ダウンロード URL（ワンタイム token）を組み立てる */
    _buildAppleWalletPassDownloadUrl(projectKey, issueUrl) {
        const normalized = normalizeExternalUrl(issueUrl);
        if (!normalized) return null;
        try {
            const parsed = new URL(normalized, typeof window !== 'undefined' ? window.location.href : undefined);
            const params = new URLSearchParams(parsed.search);
            const passToken = params.get('pass_token');
            if (!passToken) return null;
            const pk = params.get('project_key') || projectKey || '';
            const origin = resolveAppOrigin();
            if (!origin) return null;
            const downloadParams = new URLSearchParams();
            downloadParams.set('token', passToken);
            downloadParams.set('project_key', pk);
            const relative = resolveApiUrl(AppConstants.API_ENDPOINTS.MEMBER_WALLET_PASS)
                + '?' + downloadParams.toString();
            return normalizeExternalUrl(relative) || relative;
        } catch (e) {
            return null;
        }
    }

    _getFreshAppleWalletPassDownloadUrl(btn) {
        if (!btn) return null;
        const downloadUrl = btn.getAttribute('data-wallet-pass-download-url');
        const prefetchedAt = btn.getAttribute('data-wallet-open-url-at');
        if (!downloadUrl || !prefetchedAt) return null;
        const age = Date.now() - parseInt(prefetchedAt, 10);
        if (!Number.isFinite(age) || age < 0 || age >= MemberWalletActions.WALLET_PASS_PREFETCH_TTL_MS) {
            return null;
        }
        return downloadUrl;
    }

    _getFreshAppleWalletOpenUrl(btn) {
        if (!btn || !this._isAppleWalletOpenUrlFresh(btn)) return null;
        return btn.getAttribute('data-wallet-open-url');
    }

    _isAppleWalletOpenUrlFresh(btn) {
        if (!btn) return false;
        const openUrl = btn.getAttribute('data-wallet-open-url');
        const prefetchedAt = btn.getAttribute('data-wallet-open-url-at');
        if (!openUrl || !prefetchedAt) return false;
        const age = Date.now() - parseInt(prefetchedAt, 10);
        return Number.isFinite(age) && age >= 0 && age < MemberWalletActions.WALLET_PASS_PREFETCH_TTL_MS;
    }

    async _handleNativeAppleWalletClick(btn, projectKey) {
        let passDownloadUrl = this._getFreshAppleWalletPassDownloadUrl(btn);

        if (canUseNativeAppleWalletPass() && !passDownloadUrl) {
            this._setAppleWalletButtonPreparing(btn, true);
            try {
                const issueUrl = await this._fetchWalletPassIssueUrl(projectKey);
                passDownloadUrl = this._buildAppleWalletPassDownloadUrl(projectKey, issueUrl);
                if (passDownloadUrl && btn) {
                    btn.setAttribute('data-wallet-pass-download-url', passDownloadUrl);
                    btn.setAttribute('data-wallet-open-url-at', String(Date.now()));
                }
            } catch (err) {
                this._setAppleWalletButtonPreparing(btn, false);
                if (typeof ToastManager !== 'undefined') {
                    ToastManager.error(formatWalletErrorMessage(err, 'Wallet Pass の URL 取得に失敗しました'));
                }
                return true;
            }
            this._setAppleWalletButtonPreparing(btn, false);
        }

        if (canUseNativeAppleWalletPass() && passDownloadUrl) {
            if (window.__nativeAddAppleWalletPass__(passDownloadUrl)) {
                this._debugLog('apple_wallet_native_passkit_ok', { url_length: passDownloadUrl.length });
                if (typeof ToastManager !== 'undefined') {
                    ToastManager.success('Apple Wallet に追加します');
                }
                return true;
            }
        }

        const openUrl = this._getFreshAppleWalletOpenUrl(btn);
        if (canUseNativeExternalWallet() && openUrl) {
            if (window.__nativeOpenExternalUrl__(openUrl)) {
                this._debugLog('apple_wallet_native_safari_ok', { url_length: openUrl.length });
                if (typeof ToastManager !== 'undefined') {
                    ToastManager.success('Safari で Apple Wallet を開きます。「追加」をタップしてください。');
                }
                return true;
            }
            if (typeof ToastManager !== 'undefined') {
                ToastManager.error('外部ブラウザを開けませんでした。');
            }
            return true;
        }

        if (typeof ToastManager !== 'undefined') {
            ToastManager.info(openUrl || passDownloadUrl
                ? 'リンクの有効期限が切れました。更新中です…'
                : '準備中です。しばらくお待ちください。');
        }
        this._setAppleWalletButtonPreparing(btn, true);
        this._prefetchAppleWalletOpenUrl(projectKey).then((url) => {
            if (url && typeof ToastManager !== 'undefined') {
                ToastManager.info('準備ができました。もう一度タップしてください。');
            }
        });
        return true;
    }

    /**
     * LIFF では openWindow がユーザータップと同一スタックで呼ばれる必要があるため、
     * 会員証表示時に Safari 用 URL を先に取得してボタンに保持する。
     */
    _prefetchAppleWalletOpenUrl(projectKey) {
        return this._fetchWalletPassIssueUrl(projectKey)
            .then((issueUrl) => {
                const openUrl = this._resolveAppleWalletSafariUrl(issueUrl);
                const passDownloadUrl = this._buildAppleWalletPassDownloadUrl(projectKey, issueUrl);
                const btn = document.querySelector('.member-card-add-to-wallet-btn[data-wallet-pass-url]');
                if (btn) {
                    const prefetchedAt = String(Date.now());
                    if (openUrl) {
                        btn.setAttribute('data-wallet-open-url', openUrl);
                    } else {
                        btn.removeAttribute('data-wallet-open-url');
                    }
                    if (passDownloadUrl) {
                        btn.setAttribute('data-wallet-pass-download-url', passDownloadUrl);
                    } else {
                        btn.removeAttribute('data-wallet-pass-download-url');
                    }
                    if (openUrl || passDownloadUrl) {
                        btn.setAttribute('data-wallet-open-url-at', prefetchedAt);
                    } else {
                        btn.removeAttribute('data-wallet-open-url-at');
                    }
                    this._setAppleWalletButtonPreparing(btn, false);
                }
                return openUrl;
            })
            .catch((err) => {
                console.warn('Apple Wallet URL prefetch failed:', err);
                const btn = document.querySelector('.member-card-add-to-wallet-btn[data-wallet-pass-url]');
                if (btn) this._setAppleWalletButtonPreparing(btn, false);
                return null;
            });
    }

    /**
     * LINE 内ブラウザ → Safari で開く URL（wallet-add 中間ページ経由）。
     * .pkpass 直リンクは LIFF の openWindow で拒否されることがあるため中間ページを使う。
     */
    _resolveAppleWalletSafariUrl(issueUrl) {
        return this._resolveWalletAddOpenUrl(issueUrl, 'apple');
    }

    /**
     * LIFF: ユーザータップ直後に同期的に呼ぶ（await 後は iOS で無視される）。
     */
    _openLiffExternalBrowserSync(url: any, debugContext: any = {}) {
        const normalized = normalizeExternalUrl(url);
        if (!normalized) {
            this._debugLog('liff_open_window_failed', Object.assign({}, debugContext || {}, {
                reason: 'invalid_url',
            }));
            return false;
        }
        if (typeof window === 'undefined') return false;
        if (!window.__IS_LIFF__ || typeof liff === 'undefined' || typeof liff.openWindow !== 'function') {
            this._debugLog('liff_open_window_failed', Object.assign({}, debugContext || {}, {
                reason: 'liff_unavailable',
            }));
            return false;
        }
        try {
            const result = liff.openWindow({ url: normalized, external: true });
            if (result && typeof result.then === 'function') {
                const self = this;
                result.then(function () {
                    self._debugLog('liff_open_window_ok', Object.assign({}, debugContext || {}, {
                        url_length: normalized.length,
                    }));
                }).catch(function (err) {
                    self._debugLog('liff_open_window_failed', Object.assign({}, debugContext || {}, {
                        reason: 'promise_rejected',
                        message: err && err.message ? String(err.message) : 'unknown',
                    }));
                });
            } else {
                this._debugLog('liff_open_window_ok', Object.assign({}, debugContext || {}, {
                    url_length: normalized.length,
                }));
            }
            return true;
        } catch (e) {
            this._debugLog('liff_open_window_failed', Object.assign({}, debugContext || {}, {
                reason: 'sync_throw',
                message: e && e.message ? String(e.message) : 'unknown',
            }));
            return false;
        }
    }

    async _fetchWalletPassIssueUrl(projectKey) {
        await this.ensureIdentityReady(2500);
        const endpoint = AppConstants.API_ENDPOINTS.MEMBER_WALLET_PASS_URL
            + '?project_key=' + encodeURIComponent(projectKey);
        const result = await this.apiClient.get(endpoint);
        if (!result.success || !result.data || !result.data.url) {
            this._debugLog('apple_wallet_url_failed', {
                error: result.error || (result.data && result.data.error) || 'unknown',
            });
            throw new Error(result.error || (result.data && result.data.error) || 'URLの取得に失敗しました');
        }
        this._debugLog('apple_wallet_url_fetched', { has_url: true });
        return result.data.url;
    }

    /**
     * API 発行 URL を現在のオリジン・return_url 付きの wallet-add URL に正規化する。
     * サーバー側 APP_URL と LIFF の表示オリジンが異なる場合でも開けるようにする。
     */
    _resolveWalletAddOpenUrl(issueUrl, type) {
        if ((type || 'apple') === 'google') {
            return resolveGoogleWalletOpenUrl(issueUrl);
        }

        const normalized = normalizeExternalUrl(issueUrl);
        if (!normalized) return null;

        const origin = resolveAppOrigin();
        if (!origin) return normalized;

        try {
            const parsed = new URL(normalized, typeof window !== 'undefined' ? window.location.href : undefined);
            const params = new URLSearchParams(parsed.search);
            const passToken = params.get('pass_token');
            const projectKey = params.get('project_key')
                || (typeof window !== 'undefined' && window.__PROJECT_KEY__ ? window.__PROJECT_KEY__ : '');
            const walletType = type || params.get('type') || 'apple';
            const ret = getWalletReturnParams();

            if (passToken) {
                const clientParams = new URLSearchParams();
                clientParams.set('pass_token', passToken);
                clientParams.set('project_key', projectKey);
                clientParams.set('type', walletType);
                if (ret.returnUrl) {
                    clientParams.set('return_url', ret.returnUrl);
                }
                clientParams.set('return_target', ret.returnTarget || 'web');
                return origin + '/wallet-add?' + clientParams.toString();
            }

            if (isWalletAddUrl(normalized)) {
                const clientParams = new URLSearchParams(parsed.search);
                if (ret.returnUrl && !clientParams.get('return_url')) {
                    clientParams.set('return_url', ret.returnUrl);
                }
                if (!clientParams.get('return_target')) {
                    clientParams.set('return_target', ret.returnTarget || 'web');
                }
                if (!clientParams.get('type')) {
                    clientParams.set('type', walletType);
                }
                return origin + '/wallet-add?' + clientParams.toString();
            }

            return this._buildWalletAddUrl(normalized, walletType) || normalized;
        } catch (e) {
            return normalized;
        }
    }

    async _openExternalUrl(url) {
        const normalized = normalizeExternalUrl(url);
        if (!normalized) {
            console.warn('invalid external url', url);
            return false;
        }
        if (typeof window === 'undefined') return false;

        if (window.__IS_LIFF__ && typeof liff !== 'undefined' && typeof liff.openWindow === 'function') {
            if (this._openLiffExternalBrowserSync(normalized, {})) {
                return true;
            }
            try {
                await liff.openWindow({ url: normalized, external: true });
                return true;
            } catch (e) {
                console.warn('liff.openWindow external async failed', e);
            }
        }

        try {
            const w = window.open(normalized, '_blank');
            if (!w || w.closed) {
                window.location.href = normalized;
            }
            return true;
        } catch (e) {
            try {
                window.location.href = normalized;
                return true;
            } catch (err) {
                console.warn('external url open failed', err);
            }
        }

        return false;
    }

    _buildWalletAddUrl(walletUrl, type) {
        const ret = getWalletReturnParams();
        const origin = resolveAppOrigin();
        if (!origin) {
            return null;
        }
        const params = new URLSearchParams();
        params.set('wallet_url', walletUrl);
        if (ret.returnUrl) {
            params.set('return_url', ret.returnUrl);
        }
        params.set('return_target', ret.returnTarget || 'web');
        params.set('type', type || 'apple');
        return origin + '/wallet-add?' + params.toString();
    }

    _onAddToAppleWallet(e, btn, projectKey) {
        e.preventDefault();
        this._debugLog('wallet_add_click', { type: 'apple', is_liff: !!(typeof window !== 'undefined' && window.__IS_LIFF__) });

        if (isMobileAppMode()) {
            void this._handleNativeAppleWalletClick(btn, projectKey);
            return;
        }

        if (typeof window !== 'undefined' && window.__IS_LIFF__ && typeof liff !== 'undefined' && liff.openWindow) {
            const openUrl = btn.getAttribute('data-wallet-open-url');
            if (!openUrl || !this._isAppleWalletOpenUrlFresh(btn)) {
                btn.removeAttribute('data-wallet-open-url');
                btn.removeAttribute('data-wallet-open-url-at');
                if (typeof ToastManager !== 'undefined') {
                    ToastManager.info(openUrl
                        ? 'リンクの有効期限が切れました。更新中です…'
                        : '準備中です。しばらくお待ちください。');
                }
                this._setAppleWalletButtonPreparing(btn, true);
                this._prefetchAppleWalletOpenUrl(projectKey).then(function (url) {
                    if (url && typeof ToastManager !== 'undefined') {
                        ToastManager.info('準備ができました。もう一度タップしてください。');
                    }
                });
                return;
            }
            if (this._openLiffExternalBrowserSync(openUrl, {})) {
                if (typeof ToastManager !== 'undefined') {
                    ToastManager.success('Safari で Apple Wallet を開きます。「追加」をタップしてください。');
                }
                return;
            }
            if (typeof ToastManager !== 'undefined') {
                ToastManager.error('Safari を開けませんでした。LINEの設定で外部ブラウザを許可するか、しばらくしてから再度お試しください。');
            }
            return;
        }

        this._runAppleWalletDownloadFlow(btn, projectKey);
    }

    async _runAppleWalletDownloadFlow(btn, projectKey) {
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="member-card-add-to-wallet-loading"><i class="fas fa-spinner fa-spin"></i> 取得中...</span>';

        try {
            const useExternalBrowser = canUseNativeExternalWallet()
                && !canUseNativeAppleWalletPass();

            if (useExternalBrowser) {
                const issueUrl = await this._fetchWalletPassIssueUrl(projectKey);
                const openUrl = this._resolveWalletAddOpenUrl(issueUrl, 'apple');
                if (!openUrl) {
                    throw new Error('Wallet Pass の URL が不正です');
                }
                if (!window.__nativeOpenExternalUrl__(openUrl)) {
                    throw new Error('外部ブラウザを開けませんでした');
                }
                if (typeof ToastManager !== 'undefined') {
                    ToastManager.success('Safariで開きます。表示されたら「追加」をタップしてください。');
                }
                return;
            }

            const url = btn.getAttribute('data-wallet-pass-url');
            if (!url) return;

            const fullUrl = url.startsWith('http') ? url : resolveApiUrl(url);
            if (isMobileAppMode() || isIOS()) {
                window.location.href = fullUrl;
                return;
            }

            const res = await fetch(fullUrl, { credentials: 'include', method: 'GET' });
            if (!res.ok) {
                const text = await res.text();
                const msg = text && text.length < 200 ? text.trim() : (res.status === 503 ? 'Wallet Pass を利用できません' : '取得に失敗しました');
                throw new Error(msg);
            }
            const ct = res.headers.get('Content-Type') || '';
            if (ct.indexOf('application/vnd.apple.pkpass') === -1 && ct.indexOf('application/zip') === -1) {
                const text = await res.text();
                throw new Error(text && text.length < 200 ? text.trim() : '不正なレスポンスです');
            }
            const blob = await res.blob();
            const blobUrl = URL.createObjectURL(blob);
            if (isIOS()) {
                const w = window.open(blobUrl, '_blank');
                if (!w || w.closed) window.location.href = blobUrl;
                setTimeout(function () { URL.revokeObjectURL(blobUrl); }, 2000);
            } else {
                const a = document.createElement('a');
                a.href = blobUrl;
                a.download = 'membership.pkpass';
                a.style.display = 'none';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                setTimeout(function () { URL.revokeObjectURL(blobUrl); }, 500);
            }
            if (typeof ToastManager !== 'undefined') {
                ToastManager.success('パスを開きました。表示されたら「追加」をタップしてください。');
            }
        } catch (err) {
            if (typeof ToastManager !== 'undefined') {
                ToastManager.error(formatWalletErrorMessage(err, 'Wallet Pass の取得に失敗しました'));
            }
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    }

    async _onAddToGoogleWallet(e, btn, projectKey) {
        e.preventDefault();
        this._debugLog('wallet_add_click', { type: 'google', is_liff: !!(typeof window !== 'undefined' && window.__IS_LIFF__) });

        const useExternalBrowser = (typeof window !== 'undefined' && window.__IS_LIFF__ && typeof liff !== 'undefined' && liff.openWindow)
            || (isMobileAppMode() && typeof window.__nativeOpenExternalUrl__ === 'function');

        if (useExternalBrowser) {
            const openUrl = this._getFreshGoogleWalletOpenUrl();
            if (!openUrl) {
                if (typeof ToastManager !== 'undefined') {
                    ToastManager.info('準備中です。しばらくお待ちください。');
                }
                this._setGoogleWalletButtonPreparing(btn, true);
                this._prefetchGoogleWalletOpenUrl(projectKey).then((url) => {
                    if (url && typeof ToastManager !== 'undefined') {
                        ToastManager.info('準備ができました。もう一度タップしてください。');
                    }
                }).catch((err) => {
                    if (typeof ToastManager !== 'undefined') {
                        const msg = err && err.message ? String(err.message) : 'Google Wallet の準備に失敗しました。';
                        ToastManager.error(msg);
                    }
                });
                return;
            }
            if (typeof window !== 'undefined' && window.__IS_LIFF__ && typeof liff !== 'undefined' && liff.openWindow) {
                if (this._openLiffExternalBrowserSync(openUrl, { type: 'google', phase: 'cached_open' })) {
                    if (typeof ToastManager !== 'undefined') {
                        ToastManager.success('Google Wallet を開きます。追加を完了してください。');
                    }
                    return;
                }
            } else if (typeof window.__nativeOpenExternalUrl__ === 'function' && window.__nativeOpenExternalUrl__(openUrl)) {
                this._debugLog('google_wallet_native_open_ok', { url_length: openUrl.length });
                if (typeof ToastManager !== 'undefined') {
                    ToastManager.success('Google Wallet を開きます。追加を完了してください。');
                }
                return;
            }
            if (typeof ToastManager !== 'undefined') {
                ToastManager.error('外部ブラウザを開けませんでした。');
            }
            return;
        }

        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="member-card-add-to-wallet-loading"><i class="fas fa-spinner fa-spin"></i> 取得中...</span>';

        try {
            const issueUrl = await this._fetchGoogleWalletIssueUrl(projectKey);
            const openUrl = resolveGoogleWalletOpenUrl(issueUrl);
            this._debugLog('google_wallet_url_fetched', { has_url: !!openUrl });
            if (!openUrl) {
                throw new Error('Google Wallet の URL が不正です');
            }
            window.location.href = openUrl;
        } catch (err) {
            this._debugLog('google_wallet_url_failed', {
                message: err && err.message ? String(err.message) : 'unknown',
            });
            if (typeof ToastManager !== 'undefined') {
                ToastManager.error(err.message || 'Google Wallet の取得に失敗しました');
            }
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    }
}

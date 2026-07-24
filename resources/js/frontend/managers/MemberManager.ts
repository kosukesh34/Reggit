import { AppConstants } from '../../shared/constants';
import { DomHelper } from '../../shared/utils/domHelper';
import { ToastManager } from '../../shared/utils/toast';
import { ConfirmDialog } from '../../shared/utils/confirmDialog';
import { getGuestRegisterMessage, getMemberRegistrationMode, shouldShowRegistrationForm, syncMemberRegistrationModeFromPayload } from '../utils/membershipPolicy';
import { isDemoDataMode, isFixedUiShell } from '../demo/demoMode';
import { buildBuiltinSampleMemberData } from '../demo/sampleData';

declare const liff: any;
declare const JsBarcode: any;
declare const QRCode: any;

export class MemberManager {
    /** WalletPassUrlIssueUseCase::TOKEN_TTL_MINUTES(3) より短めに余裕を持たせる */
    static WALLET_PASS_PREFETCH_TTL_MS = 150000;
    static GOOGLE_WALLET_PREFETCH_TTL_MS = 150000;

    apiClient: any;
    orderManager: any;
    memberData: Record<string, any> | null;
    _memberContextFetchedAt: number;
    _memberContextFetchPromise: Promise<any> | null;
    _memberContextFetchGeneration: number;
    _memberContextLineUserId: string | null;
    _trackedLineUserIdForMemberContext: string | null;
    _registerFullScreen: boolean;
    _forcedRegister: boolean;
    _profileEditFullScreen: boolean;
    _historyHandlerBound: boolean;
    _liffIdentityRetryRegistered: boolean;
    _autoRegisterAttempted: boolean;
    _isRegisterSubmitting: boolean;
    _lastMemberFetchWasNonMember: boolean;
    _googleWalletOpenUrl: string | null;
    _googleWalletOpenUrlAt: number;
    _walletVisibilityRefreshRegistered?: boolean;
    _appleWalletOpenUrl?: string | null;
    _appleWalletOpenUrlAt?: number;
    _appleWalletPassDownloadUrl?: string | null;
    _appleWalletPassDownloadUrlAt?: number;
    _walletSummaryTimer: ReturnType<typeof setInterval> | null = null;
    _walletSummaryLast: { points: number; stamps_total: number; pending_lottery_count: number } | null = null;
    _walletVisibilityBound = false;
    /** TEMP: 会員証表示でガチャ確認ポップアップを1回だけ出す */
    _gachaPopupShownForProfile = false;

    constructor(apiClient: any, orderManager: any) {
        this.apiClient = apiClient;
        this.orderManager = orderManager;
        this.memberData = null;
        this._memberContextFetchedAt = 0;
        this._memberContextFetchPromise = null;
        this._memberContextFetchGeneration = 0;
        this._memberContextLineUserId = null;
        this._trackedLineUserIdForMemberContext = null;
        this._registerFullScreen = false;
        this._forcedRegister = false;
        this._profileEditFullScreen = false;
        this._historyHandlerBound = false;
        this._liffIdentityRetryRegistered = false;
        this._autoRegisterAttempted = false;
        this._isRegisterSubmitting = false;
        this._lastMemberFetchWasNonMember = false;
        this._googleWalletOpenUrl = null;
        this._googleWalletOpenUrlAt = 0;
        this._bindScreenPopState();
    }

    _bindScreenPopState() {
        if (this._historyHandlerBound || typeof window === 'undefined') return;
        this._historyHandlerBound = true;
        window.addEventListener('popstate', () => {
            if (this._forcedRegister && !(this.memberData && this.memberData.is_member === true)) {
                this.openRegisterFullScreen({ skipHistory: true, forced: true });
                return;
            }
            const registerMode = this.isRegisterPageMode();
            const editMode = this.isEditPageMode();
            if (registerMode) {
                this.openRegisterFullScreen({ skipHistory: true, forced: this._forcedRegister });
                return;
            }
            if (editMode) {
                this.openProfileEdit({ skipHistory: true });
                return;
            }
            if (this._registerFullScreen || this._profileEditFullScreen) {
                this._registerFullScreen = false;
                this._profileEditFullScreen = false;
                this._syncMemberScreenOverlay();
                this.load();
            }
        });
    }

    static resolveLiffOs() {
        if (typeof window !== 'undefined' && window.__LIFF_OS__) {
            return window.__LIFF_OS__;
        }
        if (typeof window !== 'undefined' && window.__IS_LIFF__ && typeof liff !== 'undefined' && typeof liff.getOS === 'function') {
            try {
                const os = liff.getOS();
                if (os === 'ios' || os === 'android') {
                    window.__LIFF_OS__ = os;
                    return os;
                }
            } catch (e) { /* ignore */ }
        }
        return null;
    }

    static isIOS() {
        if (MemberManager.resolveLiffOs() === 'ios') return true;
        if (typeof navigator === 'undefined') return false;
        return /iPad|iPhone|iPod/.test(navigator.userAgent) ||
            (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    }

    static isAndroid() {
        if (MemberManager.resolveLiffOs() === 'android') return true;
        if (typeof navigator === 'undefined') return false;
        return /Android/.test(navigator.userAgent);
    }

    static isWalletPassEnabled() {
        return typeof window !== 'undefined' && window.__WALLET_PASS_ENABLED__ === true;
    }

    static isGoogleWalletEnabled() {
        return typeof window !== 'undefined' && window.__GOOGLE_WALLET_ENABLED__ === true;
    }

    static getLiffGlobalSetting(key, fallback) {
        return DomHelper.getLiffGlobalSetting(key, fallback);
    }

    static isMobileAppMode() {
        return typeof window !== 'undefined' && window.__MOBILE_APP_MODE__ === true;
    }

    /** ネイティブアプリ時は __MOBILE_DEVICE_INFO__.platform を優先。ブラウザ時は null。 */
    static getNativePlatform() {
        if (typeof window === 'undefined' || !window.__MOBILE_APP_MODE__ || !window.__MOBILE_DEVICE_INFO__) return null;
        const p = window.__MOBILE_DEVICE_INFO__.platform;
        return (p === 'ios' || p === 'android') ? p : null;
    }

    /**
     * Wallet 追加用中間ページの return_url と return_target を返す。
     * LINE の場合は liff.line.me 形式、ネイティブアプリの場合はアプリスキームを返す。
     * @returns {{ returnUrl: string, returnTarget: 'line'|'app'|'web' }}
     */
    static getWalletReturnParams() {
        if (typeof window !== 'undefined' && window.__IS_LIFF__ === true) {
            const projectKey = (typeof window !== 'undefined' && window.__PROJECT_KEY__)
                ? String(window.__PROJECT_KEY__)
                : '';
            const liffUrl = MemberManager.buildLiffEntryUrl(projectKey);
            if (liffUrl) {
                return { returnUrl: liffUrl, returnTarget: 'line' };
            }
            const currentHref = window.location && window.location.href ? window.location.href : '';
            return { returnUrl: currentHref, returnTarget: 'line' };
        }
        if (MemberManager.isMobileAppMode()) {
            const scheme = (typeof AppConstants !== 'undefined' && AppConstants.WALLET_RETURN_APP_SCHEME)
                ? AppConstants.WALLET_RETURN_APP_SCHEME
                : 'reggit://';
            return { returnUrl: scheme, returnTarget: 'app' };
        }
        const currentHref = typeof window !== 'undefined' && window.location && window.location.href ? window.location.href : '';
        return { returnUrl: currentHref, returnTarget: 'web' };
    }

    static getLineLiffIdFromMeta() {
        if (typeof document === 'undefined') return '';
        const meta = document.querySelector('meta[name="line-liff-id"]');
        const metaEl = meta as HTMLMetaElement | null;
        return metaEl && metaEl.content ? String(metaEl.content).trim() : '';
    }

    /** Wallet 追加後に LINE アプリへ戻るための LIFF エントリ URL */
    static buildLiffEntryUrl(projectKey) {
        const liffId = MemberManager.getLineLiffIdFromMeta();
        const pk = (projectKey && String(projectKey).trim()) || '';
        if (!liffId) return null;
        const liffState = new URLSearchParams({ project_key: pk }).toString();
        return 'https://liff.line.me/' + liffId + '?' + new URLSearchParams({ 'liff.state': liffState }).toString();
    }

    /** LIFF / WebView でも使えるアプリのオリジン（https://example.com） */
    static resolveAppOrigin() {
        if (typeof window === 'undefined' || !window.location) {
            return '';
        }
        try {
            if (window.location.origin && window.location.origin !== 'null') {
                return window.location.origin;
            }
        } catch (e) { /* ignore */ }
        const apiBase = (typeof window !== 'undefined' && window.__API_BASE__) ? window.__API_BASE__ : '/api';
        if (typeof apiBase === 'string' && (apiBase.startsWith('http://') || apiBase.startsWith('https://'))) {
            try {
                return new URL(apiBase).origin;
            } catch (e) { /* ignore */ }
        }
        return '';
    }

    /** API パス（/api/...）を絶対 URL にする */
    static resolveApiUrl(path) {
        const normalizedPath = (path && path.startsWith('/')) ? path : ('/' + (path || ''));
        if (normalizedPath.startsWith('http://') || normalizedPath.startsWith('https://')) {
            return normalizedPath;
        }
        const origin = MemberManager.resolveAppOrigin();
        return origin ? (origin + normalizedPath) : normalizedPath;
    }

    static isValidHttpUrl(url) {
        if (!url || typeof url !== 'string') return false;
        try {
            const parsed = new URL(url);
            return parsed.protocol === 'http:' || parsed.protocol === 'https:';
        } catch (e) {
            return false;
        }
    }

    /** LIFF / Safari 向けに https を優先（ngrok 等で http が混ざるのを防ぐ） */
    static normalizeExternalUrl(url) {
        if (!MemberManager.isValidHttpUrl(url)) {
            return null;
        }
        try {
            const parsed = new URL(url, typeof window !== 'undefined' ? window.location.href : undefined);
            const host = parsed.hostname;
            if (host !== 'localhost' && host !== '127.0.0.1' && parsed.protocol === 'http:') {
                parsed.protocol = 'https:';
            }
            return parsed.href;
        } catch (e) {
            return null;
        }
    }

    /**
     * Google Save URL を外部ブラウザで開く URL に正規化する。
     * サーバー短縮 URL（/google-wallet/save/…）と pay.google.com 直リンクの両方に対応。
     */
    static resolveGoogleWalletOpenUrl(issueUrl) {
        const normalized = MemberManager.normalizeExternalUrl(issueUrl);
        if (!normalized) {
            return null;
        }
        try {
            const parsed = new URL(normalized);
            if (parsed.hostname === 'pay.google.com' && parsed.pathname.indexOf('/gp/v/save/') === 0) {
                return parsed.href;
            }
            if (parsed.pathname.indexOf('/google-wallet/save/') === 0) {
                return parsed.href;
            }
        } catch (e) { /* ignore */ }

        return normalized;
    }

    static formatWalletErrorMessage(err, fallback) {
        const raw = err && err.message ? String(err.message) : '';
        if (/pattern/i.test(raw)) {
            return 'Safari で Wallet を開けませんでした。LINE の設定で外部ブラウザを許可するか、しばらくしてから再度お試しください。';
        }
        if (/外部ブラウザ|Wallet Pass の URL/i.test(raw)) {
            return raw;
        }
        return raw || fallback || '処理に失敗しました';
    }

    static isDesignerMemberPreview() {
        if (typeof window === 'undefined') return false;
        const params = new URLSearchParams(window.location.search || '');
        if (params.get('designer_live_preview') !== '1') return false;
        const source = params.get('designer_source');
        return source === 'member' || source === 'member_card';
    }

    /** Apple Wallet ボタンを表示すべきか（iOS またはネイティブ iOS アプリかつ有効時） */
    static shouldShowAppleWallet() {
        if (!MemberManager.isWalletPassEnabled()) return false;
        if (MemberManager.isDesignerMemberPreview()) return true;
        const nativePlatform = MemberManager.getNativePlatform();
        if (nativePlatform === 'ios') return true;
        if (nativePlatform === 'android') return false;
        return MemberManager.isIOS();
    }

    /** Google Wallet ボタンを表示すべきか（Android またはネイティブ Android アプリかつ有効時） */
    static shouldShowGoogleWallet() {
        if (!MemberManager.isGoogleWalletEnabled()) return false;
        if (MemberManager.isDesignerMemberPreview()) return true;
        const nativePlatform = MemberManager.getNativePlatform();
        if (nativePlatform === 'android') return true;
        if (nativePlatform === 'ios') return false;
        return MemberManager.isAndroid();
    }

    getMemberData() {
        return this.memberData;
    }

    isMember(): boolean {
        return !!(this.memberData && this.memberData.is_member === true);
    }

    isForcedRegistrationActive(): boolean {
        return this._forcedRegister === true && !(this.memberData && this.memberData.is_member === true);
    }

    isMemberScreenOverlayActive(): boolean {
        return this._registerFullScreen === true || this._profileEditFullScreen === true;
    }

    _syncMemberScreenOverlay() {
        if (typeof document === 'undefined') {
            return;
        }
        document.body.classList.toggle('member-screen-overlay', this.isMemberScreenOverlayActive());
    }

    hasAuthenticatedIdentity() {
        if (typeof window === 'undefined') return false;
        if (window.__LINE_USER_ID__) return true;
        if (this._getPersistedLineUserId()) return true;
        return !!(this.memberData && this.memberData.is_member === true);
    }

    _getPersistedLineUserId() {
        if (this.apiClient && typeof this.apiClient.getPersistedLineUserId === 'function') {
            return this.apiClient.getPersistedLineUserId();
        }
        return null;
    }

    _getLineUserIdForMemberApi() {
        if (this.apiClient && typeof this.apiClient.getLiffTokenLineUserId === 'function') {
            const tokenId = this.apiClient.getLiffTokenLineUserId();
            if (tokenId) {
                if (typeof window !== 'undefined' && window.__LINE_USER_ID__ && window.__LINE_USER_ID__ !== tokenId) {
                    this._debugLog('member_line_id_token_override', {
                        window_tail: String(window.__LINE_USER_ID__).slice(-4),
                        token_tail: tokenId.slice(-4),
                    });
                }
                if (typeof window !== 'undefined') {
                    window.__LINE_USER_ID__ = tokenId;
                }
                return tokenId;
            }
        }
        if (typeof window !== 'undefined' && window.__LINE_USER_ID__) {
            return String(window.__LINE_USER_ID__).trim();
        }
        if (typeof window !== 'undefined' && window.__IS_LIFF__) {
            return null;
        }
        return this._getPersistedLineUserId();
    }

    _debugLog(eventName, payload) {
        if (this.apiClient && typeof this.apiClient.debugLog === 'function') {
            this.apiClient.debugLog(eventName, payload || {});
        }
    }

    _invalidateMemberContextIfLineUserChanged() {
        const active = this._getLineUserIdForMemberApi() || null;
        if (active === this._trackedLineUserIdForMemberContext) {
            return;
        }
        this._trackedLineUserIdForMemberContext = active;
        this._memberContextFetchGeneration += 1;
        this.memberData = null;
        this._memberContextFetchedAt = 0;
        this._memberContextLineUserId = null;
        this._memberContextFetchPromise = null;
    }

    _shouldDeferMemberApiUntilLineIdentity() {
        if (typeof window === 'undefined' || !window.__IS_LIFF__) {
            return false;
        }
        return !this._getLineUserIdForMemberApi();
    }

    setMemberData(data) {
        this.memberData = data;
        this._syncLineUserIdFromMemberData(data);
    }

    /**
     * 会員API取得後に __LINE_USER_ID__ を同期し、他タブ（クーポン・予約・注文履歴）でログイン扱いにする。
     * @param {object} data - members/me の result.data または正規化済み payload
     */
    _syncLineUserIdFromMemberData(data) {
        if (typeof window === 'undefined' || !data || typeof data !== 'object') return;
        const user = data.user || (data.member && data.member.user) || null;
        const lineUserId = (user && user.line_user_id) ? String(user.line_user_id).trim() : null;
        if (!lineUserId || !/^U[0-9a-f]{32}$/i.test(lineUserId)) {
            return;
        }

        const tokenId = (this.apiClient && typeof this.apiClient.getLiffTokenLineUserId === 'function')
            ? this.apiClient.getLiffTokenLineUserId()
            : null;
        if (tokenId && tokenId !== lineUserId) {
            this._debugLog('member_line_id_server_mismatch', {
                server_tail: lineUserId.slice(-4),
                token_tail: tokenId.slice(-4),
                user_id: user && user.id ? user.id : null,
            });
            window.__LINE_USER_ID__ = tokenId;
            if (this.apiClient && typeof this.apiClient.persistLineUserId === 'function') {
                this.apiClient.persistLineUserId(tokenId);
            }
            this._invalidateMemberContextIfLineUserChanged();
            return;
        }

        window.__LINE_USER_ID__ = lineUserId;
        if (this.apiClient && typeof this.apiClient.persistLineUserId === 'function') {
            this.apiClient.persistLineUserId(lineUserId);
        }
    }

    _setMemberContextCache(payload, lineUserId) {
        if (payload && typeof payload === 'object') {
            syncMemberRegistrationModeFromPayload(payload);
            this.memberData = payload;
            this._memberContextFetchedAt = Date.now();
            this._memberContextLineUserId = lineUserId || this._getLineUserIdForMemberApi() || null;
        }
    }

    async _fetchMemberContextWithIdentityRetry(options: any = {}) {
        const opts = options || {};
        try {
            return await this.fetchMemberContext(opts);
        } catch (error) {
            const message = error && error.message ? String(error.message) : '';
            if (message === 'LINE identity not ready' || message === 'MEMBER_CONTEXT_STALE') {
                await this.resolveLineUserIdForClient();
                return await this.fetchMemberContext({ ...opts, force: true });
            }
            throw error;
        }
    }

    async fetchMemberContext(options: any = {}) {
        const opts = options || {};
        const force = !!opts.force;
        this._invalidateMemberContextIfLineUserChanged();

        if (this._shouldDeferMemberApiUntilLineIdentity()) {
            throw new Error('LINE identity not ready');
        }

        const fetchLineUserId = this._getLineUserIdForMemberApi() || '';
        const now = Date.now();
        if (
            !force
            && this.memberData
            && this._memberContextLineUserId === fetchLineUserId
            && (now - this._memberContextFetchedAt) < 5000
        ) {
            return this.memberData;
        }
        if (!force && this._memberContextFetchPromise) {
            return this._memberContextFetchPromise;
        }

        const generationAtStart = this._memberContextFetchGeneration;
        this._memberContextFetchPromise = (async () => {
            const result = await this.apiClient.get(AppConstants.API_ENDPOINTS.MEMBERS_ME);
            if (generationAtStart !== this._memberContextFetchGeneration) {
                throw new Error('MEMBER_CONTEXT_STALE');
            }
            const currentLineUserId = this._getLineUserIdForMemberApi() || '';
            if (currentLineUserId !== fetchLineUserId) {
                throw new Error('MEMBER_CONTEXT_STALE');
            }
            if (!result.success || !result.data) {
                throw new Error(result.error || (result.data && result.data.message) || '会員情報の取得に失敗しました');
            }
            const payload = this._normalizeMemberPayload(result.data);
            if (!payload) {
                throw new Error('会員情報の形式が正しくありません');
            }
            this._lastMemberFetchWasNonMember = payload.is_member !== true;
            this._setMemberContextCache(payload, fetchLineUserId);
            this._syncLineUserIdFromMemberData(result.data);
            if (payload.is_member === true) {
                this.startWalletSummaryPolling();
            }
            return payload;
        })();

        try {
            return await this._memberContextFetchPromise;
        } finally {
            this._memberContextFetchPromise = null;
        }
    }

    /**
     * LINE / ネイティブアプリで __LINE_USER_ID__ が未設定のとき、キャッシュ・LIFF・API から復元する。
     * @returns {Promise<boolean>} 呼び出し後に __LINE_USER_ID__ が設定されていれば true
     */
    async resolveLineUserIdForClient() {
        if (typeof window === 'undefined') return false;
        if (window.__LINE_USER_ID__) return true;

        if (this.apiClient && typeof this.apiClient.resolveLineUserId === 'function') {
            const resolved = this.apiClient.resolveLineUserId();
            if (resolved) return true;
        }

        if (MemberManager.isMobileAppMode()) {
            try {
                await this.fetchMemberContext({ force: true });
            } catch (e) {
                // 未ログインなどは無視
            }
            return !!window.__LINE_USER_ID__;
        }

        return !!window.__LINE_USER_ID__;
    }

    /** @deprecated resolveLineUserIdForClient を使用 */
    async resolveLineUserIdForNativeApp() {
        return this.resolveLineUserIdForClient();
    }

    _registerLiffIdentityRetry() {
        if (this._liffIdentityRetryRegistered || typeof document === 'undefined') return;
        this._liffIdentityRetryRegistered = true;

        const retry = async () => {
            this._liffIdentityRetryRegistered = false;
            const resolved = await this.resolveLineUserIdForClient();
            if (!resolved) return;
            if (typeof window !== 'undefined' && window.app && typeof window.app.refreshCurrentTab === 'function') {
                window.app.refreshCurrentTab();
            } else {
                await this.load();
            }
        };

        document.addEventListener('liff-ready', retry, { once: true });
        document.addEventListener('liff-profile-ready', retry, { once: true });
    }

    _registerWalletVisibilityRefresh() {
        if (this._walletVisibilityRefreshRegistered || typeof document === 'undefined') return;
        this._walletVisibilityRefreshRegistered = true;

        document.addEventListener('liff-ready', () => {
            MemberManager.resolveLiffOs();
            if (!this.memberData || this.memberData.is_member !== true) return;
            const shouldShow = MemberManager.shouldShowAppleWallet() || MemberManager.shouldShowGoogleWallet();
            if (shouldShow && !document.querySelector('.member-card-add-to-wallet-btn')) {
                this.render(this.memberData);
            }
        }, { once: true });
    }

    preload(_unused: any = null) {
        if (!this.canUseMemberFeature()) return;
        if (!window.__LINE_USER_ID__ && !MemberManager.isMobileAppMode()) return;
        this.fetchMemberContext({}).catch(function () {
            // ignore preload errors
        });
    }

    canUseMemberFeature() {
        if (typeof window === 'undefined') return false;
        if (window.__LINE_USER_ID__) return true;
        if (MemberManager.isMobileAppMode()) return true;
        if (window.__IS_LIFF__) return false;
        if (this._getPersistedLineUserId()) return true;
        return false;
    }

    canAutoRegister() {
        if (typeof window === 'undefined') return false;
        if (this.memberData && this.memberData.is_member === true) return false;
        if (!this._lastMemberFetchWasNonMember) return false;
        if (!this._getLineUserIdForMemberApi()) return false;
        return !!(window.__LINE_PROFILE__ && window.__LINE_PROFILE__.displayName);
    }

    /** LIFF 起動直後など、LINE ユーザー ID の解決を短時間待つ */
    async ensureIdentityReady(maxWaitMs: number = 0) {
        if (typeof window === 'undefined') return;
        const limit = typeof maxWaitMs === 'number' ? maxWaitMs : 1500;
        const start = Date.now();
        while (!this._getLineUserIdForMemberApi() && Date.now() - start < limit) {
            if (typeof this.resolveLineUserIdForClient === 'function') {
                await this.resolveLineUserIdForClient();
            }
            if (this._getLineUserIdForMemberApi()) break;
            await new Promise(function (resolve) { setTimeout(resolve, 50); });
        }
    }

    async _handleGuestContext(payload: any) {
        syncMemberRegistrationModeFromPayload(payload);
        const container = this.getContainer();
        if (!container) return;

        if (payload && payload.is_member === true) {
            this.render(payload);
            return;
        }
        if (this.memberData && this.memberData.is_member === true) {
            this.render(this.memberData);
            return;
        }
        if (shouldShowRegistrationForm()) {
            await this.ensureRegistrationFormShown();
            return;
        }
        if (payload && payload.show_member_card_for_guest === false) {
            container.innerHTML = this.getGuestNoCardTemplate();
            this._attachRegisterTrigger(container);
            return;
        }
        if (this.canAutoRegister() && !this._autoRegisterAttempted) {
            await this.autoRegister();
            return;
        }
        this.renderSampleMemberCard();
    }

    /** ゲスト向け会員登録誘導を描画 */
    renderGuestCard(): void {
        const container = this.getContainer();
        if (!container) return;
        container.innerHTML = this.getGuestRegisterTemplate();
        this._attachRegisterTrigger(container);
    }

    /** サンプル会員証を描画（デモモード時のみ。通常の固定UIはゲスト登録導線） */
    renderSampleMemberCard(): void {
        if (isDemoDataMode()) {
            const app = typeof window !== 'undefined'
                ? (window.app as { getDesignerSampleMemberData?: () => Record<string, unknown> } | undefined)
                : undefined;
            const sample = (app && typeof app.getDesignerSampleMemberData === 'function')
                ? app.getDesignerSampleMemberData()
                : buildBuiltinSampleMemberData();
            this.render({
                ...sample,
                is_sample_preview: true,
            });
            return;
        }
        this.renderGuestCard();
    }

    isRegisterFullScreenActive(): boolean {
        return this._registerFullScreen === true;
    }

    async ensureRegistrationFormShown(options: any = {}) {
        if (this.memberData && this.memberData.is_member === true) {
            this._forcedRegister = false;
            return;
        }
        if (!shouldShowRegistrationForm() && options.forced !== true) {
            return;
        }
        const forced = options.forced === true || getMemberRegistrationMode() === 'force';
        try {
            await this.ensureGuestMemberContext();
        } catch (e) {
            console.warn('ensureGuestMemberContext failed', e);
        }
        if (this.memberData && this.memberData.is_member === true) {
            this._forcedRegister = false;
            return;
        }
        if (this._registerFullScreen) {
            return;
        }
        let container = this.getContainer();
        if (!container) {
            await new Promise((resolve) => { window.setTimeout(resolve, 100); });
            container = this.getContainer();
        }
        if (!container) {
            return;
        }
        await this.openRegisterFullScreen({
            forced,
            skipHistory: true,
        });
    }

    _isMemberRegistrationPromptDismissed() {
        if (typeof window === 'undefined') {
            return false;
        }
        try {
            return window.sessionStorage.getItem('member_registration_prompt_dismissed') === '1';
        } catch (e) {
            return false;
        }
    }

    _dismissMemberRegistrationPrompt() {
        if (typeof window === 'undefined') {
            return;
        }
        try {
            window.sessionStorage.setItem('member_registration_prompt_dismissed', '1');
        } catch (e) {
            // ignore
        }
    }

    /**
     * GET /members/me のレスポンスから is_member を持つ payload を取得する。
     * フラット形式・{ member: ... }・{ data: ... } のいずれにも対応。
     * @param {object} resultData - apiClient が返す result.data
     * @returns {object|null} is_member を持つオブジェクト、または null
     */
    _normalizeMemberPayload(resultData) {
        if (!resultData || typeof resultData !== 'object') return null;
        let candidate = resultData;
        if (resultData.data && typeof resultData.data === 'object') {
            candidate = resultData.data;
        }
        if (candidate.member && typeof candidate.member === 'object' && typeof candidate.member.is_member !== 'undefined') {
            return candidate.member;
        }
        if (typeof candidate.is_member !== 'undefined') {
            return candidate;
        }
        return null;
    }

    async ensureGuestMemberContext() {
        if (this.memberData && typeof this.memberData.is_member !== 'undefined') {
            return this.memberData;
        }

        if (this._shouldDeferMemberApiUntilLineIdentity()) {
            this.memberData = this.memberData || {
                is_member: false,
                profile_field_settings: [],
                profile: {},
            };
            return this.memberData;
        }

        try {
            const result = await this.apiClient.get(AppConstants.API_ENDPOINTS.MEMBERS_ME);
            if (result && result.success && result.data) {
                const payload = this._normalizeMemberPayload(result.data);
                if (payload) {
                    this.memberData = payload;
                    return payload;
                }
            }
        } catch (error) {
            console.warn('Failed to fetch member context for register modal:', error);
        }

        this.memberData = this.memberData || {
            is_member: false,
            profile_field_settings: [],
            profile: {},
        };
        return this.memberData;
    }

    getContainer() {
        return DomHelper.getTabMount(
            AppConstants.TAB_MOUNTS.MEMBER,
            AppConstants.ELEMENT_IDS.MEMBER_SECTION,
            { silent: true }
        );
    }

    getLoginOptionalTemplate(title, message) {
        const t = title || '会員証';
        const m = message || 'ログインするとご利用いただけます。';
        return `
            <div class="member-card-container">
                <div class="member-card member-card--line-only">
                    <div class="member-card-header">
                        <h2 class="member-card-title"><i class="fas fa-id-card"></i> ${DomHelper.escapeHtml(t)}</h2>
                    </div>
                    <div class="member-card-line-only-body">
                        <p class="member-card-line-only-message">${DomHelper.escapeHtml(m)}</p>
                    </div>
                </div>
            </div>
        `;
    }

    /** 会員未登録（ゲスト）で会員証を表示しない設定のときの表示 */
    getGuestNoCardTemplate() {
        return `
            <div class="member-card-container">
                <div class="member-card member-card--line-only">
                    <div class="member-card-header">
                        <h2 class="member-card-title"><i class="fas fa-id-card"></i> 会員証</h2>
                    </div>
                    <div class="member-card-line-only-body">
                        <p class="member-card-line-only-message">会員登録されていません。</p>
                        <p class="member-card-line-only-sub">会員登録すると会員証が表示されます。</p>
                        <button type="button" class="btn btn-primary mt-2 member-register-trigger" data-action="member-open-register" onclick="typeof window.app !== 'undefined' && window.app.memberManager && window.app.memberManager.navigateToRegisterPage()">
                            <i class="fas fa-user-plus"></i> 会員登録する
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    getGuestRegisterTemplate() {
        const copy = getGuestRegisterMessage();
        return `
            <div class="member-card-container">
                <div class="member-card member-card--line-only">
                    <div class="member-card-header">
                        <h2 class="member-card-title"><i class="fas fa-id-card"></i> 会員証</h2>
                    </div>
                    <div class="member-card-line-only-body">
                        <p class="member-card-line-only-message">${DomHelper.escapeHtml(copy.title)}</p>
                        <p class="member-card-line-only-sub">${DomHelper.escapeHtml(copy.subtitle)}</p>
                        <button type="button" class="btn ${copy.buttonClass} mt-2 member-register-trigger" data-action="member-open-register" onclick="typeof window.app !== 'undefined' && window.app.memberManager && window.app.memberManager.navigateToRegisterPage()">
                            <i class="fas fa-user-plus"></i> ${DomHelper.escapeHtml(copy.buttonLabel)}
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    getErrorTemplate(error) {
        const message = error && error.message ? error.message : 'エラーが発生しました';
        return `
            <div class="member-card-container">
                <div class="member-card member-card--error">
                    <div class="member-card-header">
                        <h2 class="member-card-title"><i class="fas fa-id-card"></i> 会員証</h2>
                    </div>
                    <div class="member-card-error-body">
                        <p class="member-card-error-icon"><i class="fas fa-exclamation-circle"></i></p>
                        <p class="member-card-error-message">${DomHelper.escapeHtml(message)}</p>
                        <p class="member-card-error-hint">しばらくしてから再度お試しください。</p>
                        <button type="button" class="register-member-btn" onclick="window.app.memberManager.load()">
                            <i class="fas fa-redo"></i> 再読み込み
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    getSkeletonHtml() {
        return `
            <div class="member-card-container">
                <div class="member-card">
                    <div class="member-card-header">
                        <div class="skeleton-line skeleton-header"></div>
                    </div>
                    <div class="barcode-container">
                        <div class="skeleton-barcode">
                            <div class="skeleton-shimmer"></div>
                        </div>
                    </div>
                    <div class="points-display">
                        <div class="skeleton-line skeleton-points-value"></div>
                        <div class="skeleton-line skeleton-points-label"></div>
                    </div>
                </div>
            </div>
        `;
    }

    async load() {
        const container = this.getContainer();
        if (!container) return;

        if (this._registerFullScreen && !(this.memberData && this.memberData.is_member)) {
            return;
        }

        if (typeof window !== 'undefined' && window.__IS_LIFF__) {
            await this.ensureIdentityReady();
        } else if (!window.__LINE_USER_ID__) {
            await this.resolveLineUserIdForClient();
        }

        if (!this.canUseMemberFeature()) {
            if (typeof window !== 'undefined' && window.__IS_LIFF__) {
                this._registerLiffIdentityRetry();
                container.innerHTML = this.getSkeletonHtml();
                return;
            }
            container.innerHTML = this.getLoginOptionalTemplate('会員証', 'ログインすると会員証をご利用いただけます。');
            this.memberData = null;
            return;
        }

        if (this.memberData && this.memberData.is_member) {
            this.render(this.memberData);
            this.preload();
            return;
        }

        container.innerHTML = this.getSkeletonHtml();

        try {
            const payload = await this._fetchMemberContextWithIdentityRetry({ force: true });
            if (payload.is_member) {
                this.render(payload);
                return;
            }
            await this._handleGuestContext(payload);
        } catch (error) {
            console.error('Failed to load member card:', error);
            if (this.memberData && this.memberData.is_member) {
                this.render(this.memberData);
                return;
            }
            container.innerHTML = this.getErrorTemplate(error);
        }
    }

    async autoRegister() {
        if (this._autoRegisterAttempted) {
            return;
        }
        this._autoRegisterAttempted = true;

        const payload: any = {};
        if (typeof window !== 'undefined' && window.__LINE_PROFILE__ && window.__LINE_PROFILE__.displayName) {
            payload.name = window.__LINE_PROFILE__.displayName;
        }
        try {
            const result = await this.apiClient.post(AppConstants.API_ENDPOINTS.MEMBERS_REGISTER, payload);
            const raw = result.success && result.data ? result.data : null;
            const memberPayload = raw ? (this._normalizeMemberPayload(raw) || raw) : null;
            if (memberPayload && memberPayload.is_member) {
                this.memberData = memberPayload;
                this.render(memberPayload);
                ToastManager.success('会員証を作成しました');
                if (typeof window !== 'undefined' && window.app && typeof window.app.refreshCurrentTab === 'function') {
                    window.app.refreshCurrentTab();
                }
                return;
            }
            if (result.success) {
                await this.load();
                return;
            }
            const message = (result && (result.error || result.message)) ? String(result.error || result.message) : '';
            if (message.includes('既に会員登録済み')) {
                this._autoRegisterAttempted = false;
                await this.load();
                return;
            }
            throw new Error(result.error || message || '登録に失敗しました');
        } catch (error) {
            console.error('Auto register error:', error);
            const message = error && error.message ? String(error.message) : '';
            if (message.includes('既に会員登録済み')) {
                this._autoRegisterAttempted = false;
                await this.load();
                return;
            }
            try {
                const existing = await this._fetchMemberContextWithIdentityRetry({ force: true });
                if (existing && existing.is_member) {
                    this.render(existing);
                    return;
                }
            } catch (e) {
                // ignore
            }
            this._autoRegisterAttempted = false;
            const container = this.getContainer();
            if (container) {
                container.innerHTML = this.getGuestRegisterTemplate();
                this._attachRegisterTrigger(container);
            }
            if (typeof ToastManager !== 'undefined') {
                ToastManager.info('会員情報を入力して登録してください');
            }
        }
    }

    /** 会員登録ボタンにクリックを付与（Android WebView で onclick が効かない場合の対策） */
    _attachRegisterTrigger(container) {
        if (!container) return;
        const btn = container.querySelector('.member-register-trigger, [data-action="member-open-register"]');
        if (btn) {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.navigateToRegisterPage();
            });
        }
    }

    isRegisterPageMode() {
        if (typeof window === 'undefined') return false;
        const params = new URLSearchParams(window.location.search || '');
        return params.get('member_register') === '1' || params.get('member_screen') === 'register';
    }

    isEditPageMode() {
        if (typeof window === 'undefined') return false;
        const params = new URLSearchParams(window.location.search || '');
        return params.get('member_screen') === 'edit';
    }

    _updateMemberScreenUrl(screen, replaceHistory) {
        if (typeof window === 'undefined') return;
        const url = new URL(window.location.href);
        url.searchParams.set('tab', 'member');
        if (screen === 'register') {
            url.searchParams.set('member_register', '1');
            url.searchParams.set('member_screen', 'register');
        } else if (screen === 'edit') {
            url.searchParams.delete('member_register');
            url.searchParams.set('member_screen', 'edit');
        } else {
            url.searchParams.delete('member_register');
            url.searchParams.delete('member_screen');
        }
        const method = replaceHistory ? 'replaceState' : 'pushState';
        window.history[method]({ memberScreen: screen || 'member' }, '', url.toString());
    }

    navigateToRegisterPage() {
        if (typeof window === 'undefined') return;
        if (window.app && window.app.currentTab !== AppConstants.TABS.MEMBER && typeof window.app.switchTab === 'function') {
            window.app.switchTab(AppConstants.TABS.MEMBER);
        }
        this._updateMemberScreenUrl('register', false);
        this.openRegisterFullScreen({ skipHistory: true });
    }

    render(data) {
        const container = this.getContainer();
        if (!container) return;
        if (!data || typeof data !== 'object') return;
        this.startWalletSummaryPolling();

        if (isFixedUiShell()) {
            this._renderIosWalletMember(container, data);
            this._renderLotteryCta(container, this._walletSummaryLast?.pending_lottery_count ?? 0);
            this._openGachaPopupOnProfileShow('fixed_ui');
            return;
        }

        const member = data.member && typeof data.member === 'object' ? data.member : null;
        const user = data.user || (member && member.user) || null;
        const displayName =
            (user && user.name) ||
            (typeof window !== 'undefined' && window.__LINE_PROFILE__ && window.__LINE_PROFILE__.displayName) ||
            '';
        const points = data.points != null ? data.points : (member && member.points != null ? member.points : 0);
        const memberNumber = (member && member.member_number) ? member.member_number : '';
        const currentRankLabel = data.current_rank_label || 'ブロンズ';
        const currentRankIcon = data.current_rank_icon || null;
        const currentRankColor = data.current_rank_color || null;
        const nextRankLabel = data.next_rank_label || null;
        const pointsToNextRank = data.points_to_next_rank != null ? data.points_to_next_rank : null;
        const daysToNextRank = data.days_to_next_rank != null ? data.days_to_next_rank : null;
        const daysRequiredForNextRank = data.days_required_for_next_rank != null ? data.days_required_for_next_rank : null;
        const rankUpHistory = Array.isArray(data.rank_up_history) ? data.rank_up_history : [];
        const pointsExpiry = data.points_expiry || null;
        const currentRankKey = data.current_rank || 'bronze';
        const showPointsDisplay = data.show_points_display !== false;
        const showRankProgress = data.show_rank_progress !== false;
        const availableDesigns = ['default', 'logo_top', 'logo_center', 'logo_header', 'compact', 'split_banner', 'minimal_line', 'emblem'];
        const blockOverride = container.closest('[data-card-design]')
            ? container.closest('[data-card-design]').getAttribute('data-card-design')
            : '';
        const designerMemberCardDesign = MemberManager.getLiffGlobalSetting('member_card_design', '');
        let memberCardDesign = designerMemberCardDesign || data.member_card_design || 'default';
        if (blockOverride && availableDesigns.indexOf(blockOverride) >= 0) {
            memberCardDesign = blockOverride;
        }
        const memberCardLogoUrl = data.member_card_logo_url || null;
        const memberCardDisplayType =
            MemberManager.getLiffGlobalSetting('member_card_display_type', '') ||
            data.member_card_display_type ||
            'card';
        const memberCardCodeType =
            MemberManager.getLiffGlobalSetting('member_card_code_type', '') ||
            data.member_card_code_type ||
            'barcode';
        const memberCardAccent =
            MemberManager.getLiffGlobalSetting('member_card_accent_color', '') ||
            '';
        const memberCardBgStyle =
            MemberManager.getLiffGlobalSetting('member_card_bg_style', '') ||
            'gradient';

        let expiryText = '';
        if (pointsExpiry && pointsExpiry.expires_at_label) {
            expiryText =
                'ポイント有効期限: ' +
                pointsExpiry.expires_at_label +
                (pointsExpiry.points_expiring ? ' (' + pointsExpiry.points_expiring + ' pt)' : '');
        }

        const codeId = `member-code-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
        const isQR = memberCardCodeType === 'qrcode';
        const codeBlock = isQR
            ? `<div class="idnect-card-barcode-wrap idnect-card-code-wrap--qrcode"><div id="${codeId}" class="idnect-card-qrcode-wrap" aria-label="会員番号QRコード"></div></div>`
            : `<div class="idnect-card-barcode-wrap idnect-card-code-wrap--barcode"><svg id="${codeId}" class="idnect-card-barcode-svg"></svg></div>`;
        const cardStyleParts = [];
        if (currentRankColor) {
            cardStyleParts.push('--idnect-card-rank-color: ' + DomHelper.escapeHtml(currentRankColor));
        }
        if (memberCardAccent !== '') {
            cardStyleParts.push('--member-card-accent-color: ' + DomHelper.escapeHtml(memberCardAccent));
        }
        const cardStyle = cardStyleParts.length > 0
            ? ` data-rank-color="true" style="${cardStyleParts.join('; ')}"`
            : '';
        const cardDesignClass = 'idnect-card-wrapper--design-' + (availableDesigns.indexOf(memberCardDesign) >= 0 ? memberCardDesign : 'default')
            + ' idnect-card-wrapper--bg-' + (['solid', 'gradient', 'image'].indexOf(memberCardBgStyle) >= 0 ? memberCardBgStyle : 'gradient');
        const showLogoOnCard = (memberCardDesign === 'logo_top' || memberCardDesign === 'logo_center' || memberCardDesign === 'logo_header' || memberCardDesign === 'split_banner' || memberCardDesign === 'emblem') && memberCardLogoUrl;
        const logoBlock = showLogoOnCard
            ? `<div class="idnect-card-logo-wrap"><img src="${DomHelper.escapeHtml(memberCardLogoUrl)}" class="idnect-card-logo" alt="" onerror="this.parentElement.style.display='none'"></div>`
            : '';
        const isSheet = memberCardDisplayType === 'sheet';
        const cardBlock = isSheet
            ? `<div class="member-card-sheet-wrap"><div class="idnect-card-wrapper idnect-card-wrapper--sheet ${cardDesignClass}"${cardStyle}>
                <div class="idnect-card idnect-card--sheet" data-rank="${DomHelper.escapeHtml(currentRankKey)}">
                    <div class="idnect-card-shimmer" aria-hidden="true"></div>
                    <div class="idnect-card-face idnect-card-face--front">
                        <div class="idnect-card-front-inner">
                            ${logoBlock}
                            ${displayName ? '<p class="idnect-card-display-name">' + DomHelper.escapeHtml(displayName) + '</p>' : ''}
                            <div class="idnect-card-meta-row">
                                <div class="idnect-card-rank-wrap">
                                    ${currentRankIcon ? `<img src="${DomHelper.escapeHtml(currentRankIcon)}" class="idnect-card-rank-icon" alt="ランク">` : ''}
                                    <span class="idnect-card-rank-badge" data-rank="${DomHelper.escapeHtml(currentRankKey)}">${DomHelper.escapeHtml(currentRankLabel)}</span>
                                </div>
                                ${showPointsDisplay ? '<div class="idnect-card-points idnect-card-points--front"><span class="idnect-card-points-value">' + points.toLocaleString() + '</span><span class="idnect-card-points-label">pt</span></div>' : ''}
                            </div>
                            ${codeBlock}
                        </div>
                    </div>
                </div>
            </div></div>`
            : `<div class="idnect-card-wrapper ${cardDesignClass}" role="button" tabindex="0" aria-label="カードをタップで裏返します"${cardStyle}>
                <div class="idnect-card" data-rank="${DomHelper.escapeHtml(currentRankKey)}">
                    <div class="idnect-card-shimmer" aria-hidden="true"></div>
                    <div class="idnect-card-face idnect-card-face--front">
                        <div class="idnect-card-front-inner">
                            ${logoBlock}
                            ${displayName ? '<p class="idnect-card-display-name">' + DomHelper.escapeHtml(displayName) + '</p>' : ''}
                            <div class="idnect-card-meta-row">
                                <div class="idnect-card-rank-wrap">
                                    ${currentRankIcon ? `<img src="${DomHelper.escapeHtml(currentRankIcon)}" class="idnect-card-rank-icon" alt="ランク">` : ''}
                                    <span class="idnect-card-rank-badge" data-rank="${DomHelper.escapeHtml(currentRankKey)}">${DomHelper.escapeHtml(currentRankLabel)}</span>
                                </div>
                                ${showPointsDisplay ? '<div class="idnect-card-points idnect-card-points--front"><span class="idnect-card-points-value">' + points.toLocaleString() + '</span><span class="idnect-card-points-label">pt</span></div>' : ''}
                            </div>
                            ${codeBlock}
                            <p class="idnect-card-tap-hint"><i class="fas fa-sync-alt idnect-card-tap-hint-icon" aria-hidden="true"></i> タップで裏面を見る</p>
                        </div>
                    </div>
                    <div class="idnect-card-face idnect-card-face--back">
                        <div class="idnect-card-back-inner">
                            <p class="idnect-card-back-title">会員情報</p>
                            ${showPointsDisplay ? '<div class="idnect-card-points idnect-card-points--large"><span class="idnect-card-points-value">' + points.toLocaleString() + '</span><span class="idnect-card-points-label">ポイント</span></div>' : ''}
                            ${expiryText ? '<p class="idnect-card-expiry">' + DomHelper.escapeHtml(expiryText) + '</p>' : ''}
                            ${showRankProgress && nextRankLabel && pointsToNextRank !== null ? '<p class="idnect-card-next-rank">次のランク（' + DomHelper.escapeHtml(nextRankLabel) + '）まで <strong>' + pointsToNextRank.toLocaleString() + '</strong> pt</p>' : ''}
                            ${showRankProgress && daysRequiredForNextRank !== null && daysToNextRank !== null ? '<p class="idnect-card-next-rank idnect-card-days-to-rank">次のランクまで あと <strong>' + daysToNextRank + '</strong> 日</p>' : ''}
                            <p class="idnect-card-tap-hint"><i class="fas fa-sync-alt idnect-card-tap-hint-icon" aria-hidden="true"></i> タップで表に戻る</p>
                        </div>
                    </div>
                </div>
            </div>`;
        const enlargeBtnLabel = isQR ? '会員証（QRコード）を拡大' : '会員証（バーコード）を拡大';
        const cardVisualBlock =
            '<div class="member-card-visual-wrap">' +
            cardBlock +
            '<button type="button" class="member-card-enlarge-code-btn" data-member-number="' + DomHelper.escapeHtml(memberNumber) + '" data-code-type="' + DomHelper.escapeHtml(memberCardCodeType) + '" aria-label="' + DomHelper.escapeHtml(enlargeBtnLabel) + '"><i class="fas fa-search-plus" aria-hidden="true"></i> 会員証拡大</button>' +
            '</div>';
        const permissionDeniedNotice =
            typeof window !== 'undefined' && window.__LIFF_PERMISSION_DENIED__
                ? '<p class="member-card-profile-notice">LINEの表示名は取得していません。<button type="button" class="link-btn" onclick="location.reload()">再読み込み</button></p>'
                : '';
        const showAddToAppleWallet = MemberManager.shouldShowAppleWallet();
        const showAddToGoogleWallet = MemberManager.shouldShowGoogleWallet();
        const projectKey = (typeof window !== 'undefined' && window.__PROJECT_KEY__) ? window.__PROJECT_KEY__ : '';
        const walletPassUrl = AppConstants.API_ENDPOINTS.MEMBERS_WALLET_PASS + '?project_key=' + encodeURIComponent(projectKey);
        let addToWalletBlock = '';
        if (showAddToAppleWallet) {
            addToWalletBlock += `<div class="member-card-add-to-wallet-wrap">
                <button type="button" class="member-card-add-to-wallet-btn member-card-add-to-wallet-btn--apple" data-wallet-pass-url="${DomHelper.escapeHtml(walletPassUrl)}" aria-label="Apple Walletに追加">
                    <span class="member-card-add-to-wallet-text"><i class="fab fa-apple" aria-hidden="true"></i> Apple Walletに追加</span>
                </button>
            </div>`;
        }
        if (showAddToGoogleWallet) {
            addToWalletBlock += `<div class="member-card-add-to-wallet-wrap">
                <button type="button" class="member-card-add-to-wallet-btn member-card-add-to-wallet-btn--google" data-google-wallet aria-label="Google Walletに追加">
                    <span class="member-card-add-to-wallet-text"><i class="fab fa-google" aria-hidden="true"></i> Google Walletに追加</span>
                </button>
            </div>`;
        }

        container.innerHTML = `
            <div class="member-card-container${data.is_sample_preview === true ? ' member-card-container--demo' : ''}">
                <div class="member-card-idnect-header">
                    <h2 class="member-card-title">会員証</h2>
                    ${data.is_sample_preview === true
                        ? ''
                        : '<button type="button" class="member-card-edit-btn" onclick="window.app.memberManager.openProfileEdit()" aria-label="プロフィール編集"><i class="fas fa-pen"></i></button>'}
                </div>
                ${permissionDeniedNotice}
                ${cardVisualBlock}
                ${data.is_sample_preview === true ? '' : addToWalletBlock}
                ${data.is_sample_preview === true ? `
                    <div class="member-demo-actions">
                        <button type="button" class="btn btn-primary member-register-trigger" data-action="member-open-register">
                            会員登録する
                        </button>
                    </div>
                ` : `
                <div class="member-purchase-history">
                    <button type="button" class="member-purchase-history-toggle" aria-expanded="false" aria-controls="memberPurchaseHistoryList" id="memberPurchaseHistoryToggle">
                        <span class="member-purchase-history-toggle-label">購入履歴</span>
                        <i class="fas fa-chevron-down member-purchase-history-toggle-icon" aria-hidden="true"></i>
                    </button>
                    <div id="memberPurchaseHistoryList" class="member-purchase-history-list" hidden aria-labelledby="memberPurchaseHistoryToggle"></div>
                </div>
                <div class="member-point-history">
                    <button type="button" class="member-purchase-history-toggle" aria-expanded="false" aria-controls="memberPointHistoryList" id="memberPointHistoryToggle">
                        <span class="member-purchase-history-toggle-label">ポイント履歴</span>
                        <i class="fas fa-chevron-down member-purchase-history-toggle-icon" aria-hidden="true"></i>
                    </button>
                    <div id="memberPointHistoryList" class="member-purchase-history-list" hidden aria-labelledby="memberPointHistoryToggle"></div>
                </div>
                <div class="member-rank-history">
                    <button type="button" class="member-purchase-history-toggle" aria-expanded="false" aria-controls="memberRankHistoryList" id="memberRankHistoryToggle">
                        <span class="member-purchase-history-toggle-label">ランクアップ履歴</span>
                        <i class="fas fa-chevron-down member-purchase-history-toggle-icon" aria-hidden="true"></i>
                    </button>
                    <div id="memberRankHistoryList" class="member-purchase-history-list member-rank-history-list" hidden aria-labelledby="memberRankHistoryToggle">${this._formatRankUpHistoryHtml(rankUpHistory)}</div>
                </div>
                <div class="member-withdraw-wrap">
                    <button type="button" class="member-withdraw-btn" id="memberWithdrawBtn" aria-label="退会する">退会する</button>
                </div>
                `}
            </div>
        `;

        if (isQR) {
            this._generateQR(codeId, memberNumber);
        } else {
            this._generateBarcode(codeId, memberNumber);
        }
        if (!isSheet) this._setupFlip();
        this._setupCodeEnlargeButton();
        if (data.is_sample_preview === true) {
            this._attachRegisterTrigger(container);
            return;
        }
        this._setupAddToWallet();
        this._registerWalletVisibilityRefresh();
        this._setupPurchaseHistoryToggle();
        this._setupPointHistoryToggle();
        this._setupRankHistoryToggle();
        this._setupWithdrawButton();
        this._renderLotteryCta(container, this._walletSummaryLast?.pending_lottery_count ?? 0);
        this._openGachaPopupOnProfileShow('classic_ui');
    }

    /** TEMP: 会員証表示時にガチャUIを出して見た目確認する */
    _openGachaPopupOnProfileShow(source: string): void {
        if (this._gachaPopupShownForProfile) {
            return;
        }
        this._gachaPopupShownForProfile = true;
        // eslint-disable-next-line no-console
        console.info('[LotteryUI]', 'profile_show_trigger', { source, ts: Date.now() });
        if (typeof window.__clientDebugLog === 'function') {
            window.__clientDebugLog('lottery_ui_profile_show_trigger', { source });
        }
        const designLab = (window.app as any)?.designLabManager;
        if (designLab === undefined || typeof designLab.openGachaPopup !== 'function') {
            // eslint-disable-next-line no-console
            console.warn('[LotteryUI]', 'profile_show_no_design_lab', { source });
            return;
        }
        window.setTimeout(() => {
            void designLab.openGachaPopup({ force: true });
        }, 300);
    }

    /** 固定UI向け: LP会員証スクショ相当のウォレット型レイアウト */
    _renderIosWalletMember(container, data) {
        const member = data.member && typeof data.member === 'object' ? data.member : null;
        const user = data.user || (member && member.user) || null;
        const displayName =
            (user && user.name) ||
            (typeof window !== 'undefined' && window.__LINE_PROFILE__ && window.__LINE_PROFILE__.displayName) ||
            'ゲスト';
        const points = data.points != null ? data.points : (member && member.points != null ? member.points : 0);
        const memberNumber = (member && member.member_number) ? String(member.member_number) : '';
        const currentRankLabel = data.current_rank_label || 'MEMBER';
        const nextRankLabel = data.next_rank_label || null;
        const pointsToNextRank = data.points_to_next_rank != null ? Number(data.points_to_next_rank) : null;
        const showPointsDisplay = data.show_points_display !== false;
        const showRankProgress = data.show_rank_progress !== false;
        const isSample = data.is_sample_preview === true;
        const codeId = `member-code-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
        const progressTotal = pointsToNextRank !== null ? points + pointsToNextRank : Math.max(points, 1);
        const progressPct = pointsToNextRank !== null
            ? Math.max(8, Math.min(100, Math.round((points / progressTotal) * 100)))
            : 100;
        const progressText = nextRankLabel && pointsToNextRank !== null
            ? `${nextRankLabel}まであと ${pointsToNextRank.toLocaleString('ja-JP')} pt`
            : 'ポイントは失効しません';

        container.innerHTML = `
            <div class="ios-wallet-member${isSample ? ' ios-wallet-member--demo' : ''}">
                <header class="ios-wallet-member__hello">
                    <span class="ios-wallet-member__avatar" aria-hidden="true">${DomHelper.escapeHtml(String(displayName).slice(0, 1))}</span>
                    <div class="ios-wallet-member__hello-copy">
                        <p class="ios-wallet-member__hello-title">Hello, ${DomHelper.escapeHtml(displayName)}</p>
                        <p class="ios-wallet-member__hello-sub">いつもご来店ありがとうございます</p>
                    </div>
                    ${isSample ? '' : '<button type="button" class="ios-wallet-member__edit" onclick="window.app.memberManager.openProfileEdit()" aria-label="プロフィール編集"><i class="fas fa-pen"></i></button>'}
                </header>

                <article class="ios-wallet-card" aria-label="会員証">
                    <div class="ios-wallet-card__top">
                        <div class="ios-wallet-card__brand">
                            <span class="ios-wallet-card__logo" aria-hidden="true">R</span>
                            <div>
                                <p class="ios-wallet-card__brand-name">Reggit Rewards</p>
                                <p class="ios-wallet-card__brand-sub">MEMBER</p>
                            </div>
                        </div>
                        <span class="ios-wallet-card__tier">${DomHelper.escapeHtml(currentRankLabel)}</span>
                    </div>
                    ${showPointsDisplay ? `
                    <div class="ios-wallet-card__points">
                        <p class="ios-wallet-card__points-label">Available Points</p>
                        <p class="ios-wallet-card__points-value">${points.toLocaleString('ja-JP')} <small>Points</small></p>
                    </div>` : ''}
                    ${showRankProgress ? `
                    <div class="ios-wallet-card__progress">
                        <div class="ios-wallet-card__progress-bar" aria-hidden="true">
                            <span style="width:${progressPct}%"></span>
                        </div>
                        <p class="ios-wallet-card__progress-text">${DomHelper.escapeHtml(progressText)}</p>
                    </div>` : ''}
                    <div class="ios-wallet-card__scan">
                        <p class="ios-wallet-card__scan-label">SCAN TO EARN OR REDEEM</p>
                        <div class="ios-wallet-card__code-wrap">
                            <svg id="${codeId}" class="ios-wallet-card__barcode" aria-label="会員番号バーコード"></svg>
                        </div>
                        <p class="ios-wallet-card__number">${DomHelper.escapeHtml(memberNumber.replace(/(.{4})/g, '$1 ').trim())}</p>
                    </div>
                </article>

                <div class="ios-wallet-member__meta">
                    <p>ポイントは失効しません</p>
                    <button type="button" class="ios-wallet-member__enlarge" data-member-number="${DomHelper.escapeHtml(memberNumber)}" data-code-type="barcode">会員証を拡大</button>
                </div>

                <nav class="ios-wallet-member__quick" aria-label="会員メニュー">
                    <button type="button" class="ios-wallet-member__quick-item" data-ios-member-nav="coupons">
                        <span class="ios-wallet-member__quick-icon" aria-hidden="true"><i class="fas fa-ticket-alt"></i></span>クーポン
                    </button>
                    <button type="button" class="ios-wallet-member__quick-item" data-ios-member-nav="stamps">
                        <span class="ios-wallet-member__quick-icon" aria-hidden="true"><i class="fas fa-stamp"></i></span>スタンプ
                    </button>
                    <button type="button" class="ios-wallet-member__quick-item" data-ios-member-nav="orders">
                        <span class="ios-wallet-member__quick-icon" aria-hidden="true"><i class="fas fa-receipt"></i></span>履歴
                    </button>
                    <button type="button" class="ios-wallet-member__quick-item" data-action="member-open-register">
                        <span class="ios-wallet-member__quick-icon" aria-hidden="true"><i class="fas fa-user-plus"></i></span>登録
                    </button>
                </nav>

                <button type="button" class="member-card-enlarge-code-btn is-visually-hidden" data-member-number="${DomHelper.escapeHtml(memberNumber)}" data-code-type="barcode" tabindex="-1" aria-hidden="true">会員証拡大</button>

                ${isSample ? `
                    <div class="member-demo-actions">
                        <button type="button" class="btn btn-primary member-register-trigger" data-action="member-open-register">
                            会員登録する
                        </button>
                    </div>
                ` : `
                    <div class="member-purchase-history">
                        <button type="button" class="member-purchase-history-toggle" aria-expanded="false" aria-controls="memberPurchaseHistoryList" id="memberPurchaseHistoryToggle">
                            <span class="member-purchase-history-toggle-label">購入履歴</span>
                            <i class="fas fa-chevron-down member-purchase-history-toggle-icon" aria-hidden="true"></i>
                        </button>
                        <div id="memberPurchaseHistoryList" class="member-purchase-history-list" hidden aria-labelledby="memberPurchaseHistoryToggle"></div>
                    </div>
                    <div class="member-point-history">
                        <button type="button" class="member-purchase-history-toggle" aria-expanded="false" aria-controls="memberPointHistoryList" id="memberPointHistoryToggle">
                            <span class="member-purchase-history-toggle-label">ポイント履歴</span>
                            <i class="fas fa-chevron-down member-purchase-history-toggle-icon" aria-hidden="true"></i>
                        </button>
                        <div id="memberPointHistoryList" class="member-purchase-history-list" hidden aria-labelledby="memberPointHistoryToggle"></div>
                    </div>
                    <div class="member-withdraw-wrap">
                        <button type="button" class="member-withdraw-btn" id="memberWithdrawBtn" aria-label="退会する">退会する</button>
                    </div>
                `}
            </div>
        `;

        this._generateBarcode(codeId, memberNumber);
        this._setupCodeEnlargeButton();
        const enlargeBtn = container.querySelector('.ios-wallet-member__enlarge') as HTMLElement | null;
        const hiddenEnlarge = container.querySelector('.member-card-enlarge-code-btn') as HTMLElement | null;
        if (enlargeBtn !== null && hiddenEnlarge !== null) {
            enlargeBtn.addEventListener('click', () => hiddenEnlarge.click());
        }
        container.querySelectorAll('[data-ios-member-nav]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const tab = (btn as HTMLElement).getAttribute('data-ios-member-nav');
                if (tab && typeof window.app?.switchTab === 'function') {
                    void window.app.switchTab(tab);
                }
            });
        });
        if (isSample) {
            this._attachRegisterTrigger(container);
            return;
        }
        this._setupPurchaseHistoryToggle();
        this._setupPointHistoryToggle();
        this._setupWithdrawButton();
    }

    _formatRankUpHistoryHtml(history) {
        if (!history || history.length === 0) {
            return '<div class="member-rank-history-empty">まだランクアップ履歴がありません</div>';
        }
        return history.map(function (item) {
            const date = item.achieved_at || '';
            const fromLabel = (item.from_rank_label || '—');
            const toLabel = (item.to_rank_label || '—');
            return '<div class="member-rank-history-item">' +
                '<span class="member-rank-history-from">' + DomHelper.escapeHtml(fromLabel) + '</span>' +
                ' <i class="fas fa-arrow-right member-rank-history-arrow" aria-hidden="true"></i> ' +
                '<span class="member-rank-history-to">' + DomHelper.escapeHtml(toLabel) + '</span>' +
                '<span class="member-rank-history-date">' + DomHelper.escapeHtml(date) + '</span>' +
                '</div>';
        }).join('');
    }

    _setupRankHistoryToggle() {
        const toggle = document.getElementById('memberRankHistoryToggle');
        const listEl = document.getElementById('memberRankHistoryList');
        const icon = toggle ? toggle.querySelector('.member-purchase-history-toggle-icon') : null;
        if (!toggle || !listEl) return;
        toggle.addEventListener('click', function () {
            const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
            if (isExpanded) {
                toggle.setAttribute('aria-expanded', 'false');
                listEl.hidden = true;
                if (icon) {
                    icon.classList.remove('fa-chevron-up');
                    icon.classList.add('fa-chevron-down');
                }
                return;
            }
            toggle.setAttribute('aria-expanded', 'true');
            listEl.hidden = false;
            if (icon) {
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            }
        });
    }

    _setupWithdrawButton() {
        const btn = document.getElementById('memberWithdrawBtn');
        if (!btn) return;
        const self = this;
        btn.addEventListener('click', function () {
            self.requestWithdraw();
        });
    }

    /**
     * 退会確認ダイアログを表示し、了承されたら退会APIを呼び出す。
     */
    async requestWithdraw() {
        const message = '退会すると会員情報・ポイント・スタンプ等のデータは復元できません。本当に退会しますか？';
        if (typeof window !== 'undefined' && window.confirm && !window.confirm(message)) {
            return;
        }
        const btn = document.getElementById('memberWithdrawBtn') as HTMLButtonElement | null;
        if (btn) {
            btn.disabled = true;
            btn.textContent = '処理中...';
        }
        try {
            const result = await this.apiClient.post(AppConstants.API_ENDPOINTS.MEMBERS_WITHDRAW, {});
            if (result && result.success) {
                this.memberData = null;
                this._memberContextFetchedAt = 0;
                this._memberContextLineUserId = null;
                this._trackedLineUserIdForMemberContext = null;
                this._memberContextFetchGeneration += 1;
                if (typeof ToastManager !== 'undefined') ToastManager.success(result.message || '退会が完了しました');
                window.setTimeout(function () {
                    window.location.reload();
                }, 800);
            } else {
                throw new Error(result && result.message ? result.message : '退会に失敗しました');
            }
        } catch (error) {
            console.error('Withdraw error:', error);
            if (typeof ToastManager !== 'undefined') ToastManager.error(error.message || '退会処理に失敗しました');
            if (btn) {
                btn.disabled = false;
                btn.textContent = '退会する';
            }
        }
    }

    _setupAddToWallet() {
        const projectKey = (typeof window !== 'undefined' && window.__PROJECT_KEY__) ? window.__PROJECT_KEY__ : '';
        const appleBtn = document.querySelector('.member-card-add-to-wallet-btn[data-wallet-pass-url]');
        if (appleBtn) {
            appleBtn.addEventListener('click', (e) => this._onAddToAppleWallet(e, appleBtn, projectKey));
            if (this._shouldPrefetchAppleWalletUrl()) {
                this._setAppleWalletButtonPreparing(appleBtn, true);
                this._prefetchAppleWalletOpenUrl(projectKey);
            }
        }
        const googleBtn = document.querySelector('.member-card-add-to-wallet-btn[data-google-wallet]');
        if (googleBtn) {
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
        if (!MemberManager.isGoogleWalletEnabled()) return false;
        if (window.__IS_LIFF__ === true) return true;
        return MemberManager.isMobileAppMode() && typeof window.__nativeOpenExternalUrl__ === 'function';
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
        if (!Number.isFinite(age) || age < 0 || age >= MemberManager.GOOGLE_WALLET_PREFETCH_TTL_MS) {
            this._googleWalletOpenUrl = null;
            this._googleWalletOpenUrlAt = 0;
            return null;
        }
        return this._googleWalletOpenUrl;
    }

    _prefetchGoogleWalletOpenUrl(projectKey) {
        return this._fetchGoogleWalletIssueUrl(projectKey)
            .then((issueUrl) => {
                const openUrl = MemberManager.resolveGoogleWalletOpenUrl(issueUrl);
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
        const endpoint = AppConstants.API_ENDPOINTS.MEMBERS_GOOGLE_WALLET_URL
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
        return MemberManager.isMobileAppMode()
            && (MemberManager.canUseNativeExternalWallet() || MemberManager.canUseNativeAppleWalletPass());
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

    /** ネイティブ iOS で PassKit 追加が使えるか */
    static canUseNativeAppleWalletPass() {
        return MemberManager.isMobileAppMode()
            && MemberManager.getNativePlatform() === 'ios'
            && typeof window.__nativeAddAppleWalletPass__ === 'function';
    }

    /** ネイティブアプリで Safari / Chrome 経由の Wallet 追加が使えるか */
    static canUseNativeExternalWallet() {
        return MemberManager.isMobileAppMode()
            && typeof window.__nativeOpenExternalUrl__ === 'function';
    }

    /** API 発行 URL から .pkpass 直ダウンロード URL（ワンタイム token）を組み立てる */
    _buildAppleWalletPassDownloadUrl(projectKey, issueUrl) {
        const normalized = MemberManager.normalizeExternalUrl(issueUrl);
        if (!normalized) return null;
        try {
            const parsed = new URL(normalized, typeof window !== 'undefined' ? window.location.href : undefined);
            const params = new URLSearchParams(parsed.search);
            const passToken = params.get('pass_token');
            if (!passToken) return null;
            const pk = params.get('project_key') || projectKey || '';
            const origin = MemberManager.resolveAppOrigin();
            if (!origin) return null;
            const downloadParams = new URLSearchParams();
            downloadParams.set('token', passToken);
            downloadParams.set('project_key', pk);
            const relative = MemberManager.resolveApiUrl(AppConstants.API_ENDPOINTS.MEMBERS_WALLET_PASS)
                + '?' + downloadParams.toString();
            return MemberManager.normalizeExternalUrl(relative) || relative;
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
        if (!Number.isFinite(age) || age < 0 || age >= MemberManager.WALLET_PASS_PREFETCH_TTL_MS) {
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
        return Number.isFinite(age) && age >= 0 && age < MemberManager.WALLET_PASS_PREFETCH_TTL_MS;
    }

    async _handleNativeAppleWalletClick(btn, projectKey) {
        let passDownloadUrl = this._getFreshAppleWalletPassDownloadUrl(btn);

        if (MemberManager.canUseNativeAppleWalletPass() && !passDownloadUrl) {
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
                    ToastManager.error(MemberManager.formatWalletErrorMessage(err, 'Wallet Pass の URL 取得に失敗しました'));
                }
                return true;
            }
            this._setAppleWalletButtonPreparing(btn, false);
        }

        if (MemberManager.canUseNativeAppleWalletPass() && passDownloadUrl) {
            if (window.__nativeAddAppleWalletPass__(passDownloadUrl)) {
                this._debugLog('apple_wallet_native_passkit_ok', { url_length: passDownloadUrl.length });
                if (typeof ToastManager !== 'undefined') {
                    ToastManager.success('Apple Wallet に追加します');
                }
                return true;
            }
        }

        const openUrl = this._getFreshAppleWalletOpenUrl(btn);
        if (MemberManager.canUseNativeExternalWallet() && openUrl) {
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
        const normalized = MemberManager.normalizeExternalUrl(url);
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

    static isWalletAddUrl(url) {
        if (!url || typeof url !== 'string') return false;
        try {
            const parsed = new URL(url, typeof window !== 'undefined' && window.location ? window.location.href : undefined);
            return parsed.pathname === '/wallet-add';
        } catch (e) {
            return false;
        }
    }

    async _fetchWalletPassIssueUrl(projectKey) {
        await this.ensureIdentityReady(2500);
        const endpoint = AppConstants.API_ENDPOINTS.MEMBERS_WALLET_PASS_URL
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
            return MemberManager.resolveGoogleWalletOpenUrl(issueUrl);
        }

        const normalized = MemberManager.normalizeExternalUrl(issueUrl);
        if (!normalized) return null;

        const origin = MemberManager.resolveAppOrigin();
        if (!origin) return normalized;

        try {
            const parsed = new URL(normalized, typeof window !== 'undefined' ? window.location.href : undefined);
            const params = new URLSearchParams(parsed.search);
            const passToken = params.get('pass_token');
            const projectKey = params.get('project_key')
                || (typeof window !== 'undefined' && window.__PROJECT_KEY__ ? window.__PROJECT_KEY__ : '');
            const walletType = type || params.get('type') || 'apple';
            const ret = MemberManager.getWalletReturnParams();

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

            if (MemberManager.isWalletAddUrl(normalized)) {
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
        const normalized = MemberManager.normalizeExternalUrl(url);
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
        const ret = MemberManager.getWalletReturnParams();
        const origin = MemberManager.resolveAppOrigin();
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

        if (MemberManager.isMobileAppMode()) {
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
            const useExternalBrowser = MemberManager.canUseNativeExternalWallet()
                && !MemberManager.canUseNativeAppleWalletPass();

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

            const fullUrl = url.startsWith('http') ? url : MemberManager.resolveApiUrl(url);
            if (MemberManager.isMobileAppMode() || MemberManager.isIOS()) {
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
            if (MemberManager.isIOS()) {
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
                ToastManager.error(MemberManager.formatWalletErrorMessage(err, 'Wallet Pass の取得に失敗しました'));
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
            || (MemberManager.isMobileAppMode() && typeof window.__nativeOpenExternalUrl__ === 'function');

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
            const openUrl = MemberManager.resolveGoogleWalletOpenUrl(issueUrl);
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

    _generateBarcode(barcodeId, memberNumber) {
        if (typeof JsBarcode === 'undefined' || !memberNumber) return;
        setTimeout(() => {
            try {
                const el = document.getElementById(barcodeId);
                if (el) {
                    const isWalletCard = el.classList.contains('ios-wallet-card__barcode');
                    JsBarcode(`#${barcodeId}`, memberNumber, {
                        format: 'CODE128',
                        width: isWalletCard ? 2.2 : 3,
                        height: isWalletCard ? 96 : 150,
                        displayValue: !isWalletCard,
                        fontSize: 18,
                        margin: isWalletCard ? 6 : 14,
                        background: '#ffffff',
                        lineColor: '#000000',
                    });
                }
            } catch (err) {
                console.error('Barcode generation error:', err);
            }
        }, AppConstants.ANIMATION_DURATION.BARCODE_DELAY);
    }

    _generateQR(containerId, memberNumber) {
        if (!memberNumber) return;
        setTimeout(() => {
            const container = document.getElementById(containerId);
            if (!container || typeof QRCode === 'undefined') return;
            try {
                new QRCode(container, {
                    text: memberNumber,
                    width: 120,
                    height: 120,
                    colorDark: '#000000',
                    colorLight: '#ffffff',
                });
            } catch (err) {
                console.error('QR generation error:', err);
            }
        }, AppConstants.ANIMATION_DURATION.BARCODE_DELAY);
    }

    _setupFlip() {
        const wrapper = document.querySelector('.idnect-card-wrapper');
        const card = document.querySelector('.idnect-card');
        if (!wrapper || !card) return;
        const flip = () => card.classList.toggle('is-flipped');
        wrapper.addEventListener('click', (e) => {
            e.preventDefault();
            if ((e.target as HTMLElement | null)?.closest('.member-card-edit-btn')) return;
            flip();
        });
        wrapper.addEventListener('keydown', (e: KeyboardEvent) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                flip();
            }
        });
    }

    _setupCodeEnlargeButton() {
        const btn = document.querySelector('.member-card-enlarge-code-btn');
        if (!btn) return;
        const self = this;
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const memberNumber = (btn.getAttribute('data-member-number') || '').trim();
            const codeType = (btn.getAttribute('data-code-type') || 'barcode').toLowerCase();
            if (!memberNumber) return;
            self._showEnlargedCode(memberNumber, codeType === 'qrcode');
        });
    }

    /**
     * 会員証のバーコードまたはQRコードを拡大表示するモーダルを表示する。
     * @param {string} memberNumber - 会員番号
     * @param {boolean} isQR - true のときQRコード、false のときバーコード
     */
    _showEnlargedCode(memberNumber, isQR) {
        const overlay = document.createElement('div');
        overlay.className = 'member-code-enlarge-overlay';
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');
        overlay.setAttribute('aria-label', isQR ? '会員証 QRコード拡大' : '会員証 バーコード拡大');
        const codeId = 'member-code-enlarge-' + Date.now();
        const title = isQR ? '会員証（QRコード）' : '会員証（バーコード）';
        overlay.innerHTML =
            '<div class="member-code-enlarge-backdrop"></div>' +
            '<div class="member-code-enlarge-content">' +
            '<div class="member-code-enlarge-header">' +
            '<h3 class="member-code-enlarge-title">' + DomHelper.escapeHtml(title) + '</h3>' +
            '<button type="button" class="member-code-enlarge-close" aria-label="閉じる"><i class="fas fa-times" aria-hidden="true"></i></button>' +
            '</div>' +
            '<div class="member-code-enlarge-body">' +
            (isQR
                ? '<div id="' + codeId + '" class="member-code-enlarge-qr-wrap" aria-label="会員番号QRコード"></div>'
                : '<div class="member-code-enlarge-barcode-wrap"><svg id="' + codeId + '" class="member-code-enlarge-barcode-svg"></svg></div>') +
            '</div>' +
            '</div>';
        document.body.appendChild(overlay);

        const close = function () {
            overlay.remove();
        };
        overlay.querySelector('.member-code-enlarge-backdrop').addEventListener('click', close);
        overlay.querySelector('.member-code-enlarge-close').addEventListener('click', close);
        overlay.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                e.preventDefault();
                close();
            }
        });

        if (isQR) {
            if (typeof QRCode !== 'undefined') {
                try {
                    const container = document.getElementById(codeId);
                    if (container) {
                        new QRCode(container, {
                            text: memberNumber,
                            width: 280,
                            height: 280,
                            colorDark: '#000000',
                            colorLight: '#ffffff',
                        });
                    }
                } catch (err) {
                    console.error('QR enlarge error:', err);
                }
            }
        } else {
            if (typeof JsBarcode !== 'undefined') {
                try {
                    JsBarcode('#' + codeId, memberNumber, {
                        format: 'CODE128',
                        width: 4,
                        height: 220,
                        displayValue: true,
                        fontSize: 24,
                        margin: 20,
                        background: '#ffffff',
                        lineColor: '#000000',
                    });
                } catch (err) {
                    console.error('Barcode enlarge error:', err);
                }
            }
        }
    }

    _setupPurchaseHistoryToggle() {
        const toggle = document.getElementById('memberPurchaseHistoryToggle');
        const listEl = document.getElementById('memberPurchaseHistoryList');
        const icon = document.querySelector('.member-purchase-history-toggle-icon');
        if (!toggle || !listEl) return;

        toggle.addEventListener('click', () => {
            const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
            if (isExpanded) {
                toggle.setAttribute('aria-expanded', 'false');
                listEl.hidden = true;
                if (icon) {
                    icon.classList.remove('fa-chevron-up');
                    icon.classList.add('fa-chevron-down');
                }
                return;
            }
            toggle.setAttribute('aria-expanded', 'true');
            listEl.hidden = false;
            if (icon) {
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            }
            if (!listEl.hasAttribute('data-loaded')) {
                this._loadPurchaseHistory(listEl);
            }
        });
    }

    _setupPointHistoryToggle() {
        const toggle = document.getElementById('memberPointHistoryToggle');
        const listEl = document.getElementById('memberPointHistoryList');
        const icon = toggle ? toggle.querySelector('.member-purchase-history-toggle-icon') : null;
        if (!toggle || !listEl) return;

        toggle.addEventListener('click', () => {
            const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
            if (isExpanded) {
                toggle.setAttribute('aria-expanded', 'false');
                listEl.hidden = true;
                if (icon) {
                    icon.classList.remove('fa-chevron-up');
                    icon.classList.add('fa-chevron-down');
                }
                return;
            }
            toggle.setAttribute('aria-expanded', 'true');
            listEl.hidden = false;
            if (icon) {
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            }
            if (!listEl.hasAttribute('data-loaded')) {
                this._loadPointHistory(listEl);
            }
        });
    }

    async _loadPointHistory(listEl) {
        listEl.innerHTML =
            '<div class="loading member-purchase-history-loading"><i class="fas fa-spinner fa-spin"></i><p>読み込み中...</p></div>';
        try {
            const result = await this.apiClient.get(AppConstants.API_ENDPOINTS.MEMBERS_POINTS);
            listEl.setAttribute('data-loaded', 'true');
            const transactions = (result.data && result.data.transactions) ? result.data.transactions : [];
            if (!transactions.length) {
                listEl.innerHTML = '<div class="member-purchase-history-empty">ポイント履歴がありません</div>';
                return;
            }
            const rows = transactions.map((tx) => {
                const date = tx.created_at ? new Date(tx.created_at).toLocaleDateString('ja-JP', {
                    year: 'numeric', month: 'short', day: 'numeric',
                }) : '';
                const points = tx.points || 0;
                const label = points >= 0 ? '獲得' : '使用';
                const cls = points >= 0 ? 'member-point-history-earned' : 'member-point-history-used';
                return `
                    <div class="member-point-history-item ${cls}">
                        <div class="member-point-history-row">
                            <span class="member-point-history-label">${DomHelper.escapeHtml(label)}</span>
                            <span class="member-point-history-points">${points > 0 ? '+' : ''}${points.toLocaleString()}pt</span>
                        </div>
                        <div class="member-point-history-meta">
                            <span>${DomHelper.escapeHtml(date)}</span>
                            ${tx.description ? `<span>${DomHelper.escapeHtml(tx.description)}</span>` : ''}
                        </div>
                    </div>
                `;
            }).join('');
            listEl.innerHTML = rows;
        } catch (error) {
            console.error('Failed to load point history:', error);
            listEl.innerHTML = '<div class="member-purchase-history-error"><p>ポイント履歴の読み込みに失敗しました</p></div>';
        }
    }

    async _loadPurchaseHistory(listEl) {
        listEl.innerHTML =
            '<div class="loading member-purchase-history-loading"><i class="fas fa-spinner fa-spin"></i><p>読み込み中...</p></div>';
        try {
            const orders = await this.orderManager.fetchOrders();
            listEl.setAttribute('data-loaded', 'true');
            this.orderManager.renderOrderListOnly(listEl, orders);
        } catch (error) {
            console.error('Failed to load purchase history:', error);
            listEl.innerHTML =
                '<div class="member-purchase-history-error"><p>購入履歴の読み込みに失敗しました</p></div>';
        }
    }

    openProfileEdit(options: any = {}) {
        options = options || {};
        if (!this.memberData) return;
        const user = this.memberData.user || (this.memberData.member && this.memberData.member.user);
        const member = this.memberData.member;
        const container = this.getContainer();
        if (!container) return;
        if (!options.skipHistory && !this.isEditPageMode()) {
            this._updateMemberScreenUrl('edit', false);
        }
        this._registerFullScreen = false;
        this._profileEditFullScreen = true;
        container.innerHTML = this._getProfileEditFormHtml(user, member, { fullScreen: true, isRegister: false });
        container.scrollTop = 0;
        const form = document.getElementById('profileEditForm');
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                window.app.memberManager.saveProfileEdit(e);
            });
        }
        this._setupPostalCodeLookup(container);
        this._syncMemberScreenOverlay();
    }

    closeProfileEdit(options: any = {}) {
        options = options || {};
        this._profileEditFullScreen = false;
        this._syncMemberScreenOverlay();
        if (!options.skipHistory) {
            this._updateMemberScreenUrl('', true);
        }
        this.load();
    }

    closeMemberScreen() {
        if (this.isForcedRegistrationActive()) {
            return;
        }
        if (this._registerFullScreen) {
            this.closeRegisterFullScreen();
            return;
        }
        if (this._profileEditFullScreen) {
            this.closeProfileEdit();
        }
    }

    _setupPostalCodeLookup(container) {
        // getElementById は document にしかないため、必ず document で取得する
        const doc = typeof document !== 'undefined' ? document : null;
        if (!doc) return;
        const postalInput = (doc.getElementById('profileEdit_postal_code') || doc.getElementById('profileEditPostalCode')) as HTMLInputElement | null;
        const addressInput = (doc.getElementById('profileEdit_address') || doc.getElementById('profileEditAddress')) as HTMLInputElement | null;
        if (!postalInput || !addressInput) return;

        const showInputFailed = (message: any = null) => {
            const msg = message || '入力に失敗しました。手動で入力してください。';
            if (typeof ToastManager !== 'undefined') ToastManager.info(msg);
        };

        const scope = container && container.querySelector ? container : doc;
        const lookupBtn = (scope.querySelector('.postal-code-lookup-btn')
            || scope.querySelector('[data-action="postal-code-lookup"]')) as HTMLButtonElement | null;

        const fetchAddress = () => {
            if (postalInput.disabled) {
                return;
            }
            const raw = (postalInput.value || '').trim().replace(/[‐－−ー]/g, '-');
            const digits = raw.replace(/\D/g, '');
            if (digits.length < 3) {
                showInputFailed('郵便番号を3桁以上入力してください。');
                return;
            }
            if (digits.length < 7) {
                showInputFailed('郵便番号は7桁で入力してください。');
                return;
            }

            const url = AppConstants.API_ENDPOINTS.POSTAL_CODE + '?postal_code=' + encodeURIComponent(digits);
            const lookupBtnHtml = lookupBtn ? lookupBtn.innerHTML : '';
            postalInput.disabled = true;
            if (lookupBtn) {
                lookupBtn.disabled = true;
                lookupBtn.setAttribute('aria-busy', 'true');
                lookupBtn.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> 検索中...';
            }
            this.apiClient.get(url)
                .then((result) => {
                    if (result && result.success === false) {
                        showInputFailed();
                        return;
                    }
                    const data = (result && result.data) || result || {};
                    const serverError = data.error || null;
                    const addresses = Array.isArray(data.addresses) ? data.addresses : (Array.isArray(data) ? data : []);
                    if (addresses.length > 0) {
                        const first = addresses[0];
                        const full = (first && (first.full_address || first.fullAddress)) ? (first.full_address || first.fullAddress) : '';
                        if (full) {
                            addressInput.value = full;
                            const prefectureInput = (doc.getElementById('profileEdit_prefecture') || doc.getElementById('profileEditPrefecture')) as HTMLInputElement | null;
                            const cityInput = (doc.getElementById('profileEdit_city') || doc.getElementById('profileEditCity')) as HTMLInputElement | null;
                            const streetInput = (doc.getElementById('profileEdit_street') || doc.getElementById('profileEditStreet')) as HTMLInputElement | null;
                            const pref = first.pref_name || first.prefName || '';
                            const city = first.city_name || first.cityName || '';
                            const town = first.town_name || first.townName || '';
                            const block = first.block_name || first.blockName || '';
                            const streetPart = [town, block].filter(Boolean).join('');
                            if (prefectureInput) prefectureInput.value = pref;
                            if (cityInput) cityInput.value = city;
                            if (streetInput) streetInput.value = streetPart;
                            if (addresses.length > 1 && typeof ToastManager !== 'undefined') {
                                ToastManager.success('住所を入力しました。複数候補がある場合は修正してください。');
                            } else if (typeof ToastManager !== 'undefined') {
                                ToastManager.success('住所を入力しました');
                            }
                        } else {
                            showInputFailed();
                        }
                    } else {
                        // API エラーや該当なし（IP制限の場合は serverError にメッセージが入る）
                        const msg = serverError ? (serverError + ' 手動で入力してください。') : null;
                        showInputFailed(msg);
                    }
                })
                .catch(() => { showInputFailed(); })
                .finally(() => {
                    postalInput.disabled = false;
                    if (lookupBtn) {
                        lookupBtn.disabled = false;
                        lookupBtn.removeAttribute('aria-busy');
                        if (lookupBtnHtml !== '') {
                            lookupBtn.innerHTML = lookupBtnHtml;
                        }
                    }
                });
        };

        postalInput.addEventListener('blur', fetchAddress);
        postalInput.addEventListener('input', function (this: HTMLInputElement) {
            const digits = (this.value || '').replace(/\D/g, '');
            if (digits.length === 7) fetchAddress();
        });
        if (lookupBtn) {
            lookupBtn.addEventListener('click', () => fetchAddress());
        }
    }

    _getProfileEditFormHtml(user, member, options) {
        options = options || {};
        const isRegister = options.isRegister === true;
        const root = (this.memberData && this.memberData.member) ? this.memberData.member : this.memberData;
        // 会員APIは profile / profile_field_settings をトップレベルで返すため、memberData から直接参照する
        const fields = (this.memberData && this.memberData.profile_field_settings) ? this.memberData.profile_field_settings : (root && root.profile_field_settings) ? root.profile_field_settings : [
            { key: 'name', label: 'お名前', required: true },
            { key: 'kana', label: 'お名前（カナ）', required: false },
            { key: 'phone', label: '電話番号', required: false },
            { key: 'email', label: 'メールアドレス', required: false },
            { key: 'birthday', label: '誕生日', required: false },
            { key: 'postal_code', label: '郵便番号', required: false },
            { key: 'prefecture', label: '都道府県', required: false },
            { key: 'city', label: '市区町村', required: false },
            { key: 'street', label: '番地・建物名', required: false },
            { key: 'address', label: '住所', required: false },
            { key: 'sex', label: '性別', required: false },
        ];
        let profile = (this.memberData && this.memberData.profile && typeof this.memberData.profile === 'object') ? this.memberData.profile : (root && root.profile) ? root.profile : {};
        if (isRegister && options.initialProfile && typeof options.initialProfile === 'object') {
            profile = Object.assign({}, profile, options.initialProfile);
        }
        const birthdayVal = (member && member.birthday) ? String(member.birthday).split('T')[0] : (profile.birthday || '');
        const nameParts = (profile.name != null && String(profile.name).trim() !== '') ? String(profile.name).trim().split(/\s+/) : ['', ''];
        const nameLast = nameParts[0] || '';
        const nameFirst = nameParts.slice(1).join(' ') || '';
        const kanaParts = (profile.kana != null && String(profile.kana).trim() !== '') ? String(profile.kana).trim().split(/\s+/) : ['', ''];
        const kanaLast = kanaParts[0] || '';
        const kanaFirst = kanaParts.slice(1).join(' ') || '';
        const rows = fields.flatMap(function (f) {
            const key = f.key;
            const label = f.label || key;
            const required = f.required ? ' required' : '';
            const val = profile[key] != null ? String(profile[key]) : '';
            const inputType = f.input_type || 'text';
            const values = Array.isArray(f.values) ? f.values : [];
            const description = f.description ? '<p class="member-form-field-hint">' + DomHelper.escapeHtml(String(f.description)) + '</p>' : '';
            if (key === 'name') {
                const idLast = 'profileEdit_name_last';
                const idFirst = 'profileEdit_name_first';
                const inputLast = '<input type="text" id="' + idLast + '" name="name_last" value="' + DomHelper.escapeHtml(nameLast) + '" maxlength="85" placeholder="例: 山田" autocomplete="family-name"' + required + '>';
                const inputFirst = '<input type="text" id="' + idFirst + '" name="name_first" value="' + DomHelper.escapeHtml(nameFirst) + '" maxlength="85" placeholder="例: 太郎" autocomplete="given-name"' + required + '>';
                return [
                    '<div class="member-form-name-row">' +
                    '<div class="member-form-group"><label for="' + idLast + '">姓' + (f.required ? ' <span class="required">*</span>' : '') + '</label>' + inputLast + '</div>' +
                    '<div class="member-form-group"><label for="' + idFirst + '">名' + (f.required ? ' <span class="required">*</span>' : '') + '</label>' + inputFirst + '</div>' +
                    '</div>',
                ];
            }
            if (key === 'kana') {
                const idLast = 'profileEdit_kana_last';
                const idFirst = 'profileEdit_kana_first';
                const inputLast = '<input type="text" id="' + idLast + '" name="kana_last" value="' + DomHelper.escapeHtml(kanaLast) + '" maxlength="85" placeholder="例: ヤマダ" inputmode="katakana"' + required + '>';
                const inputFirst = '<input type="text" id="' + idFirst + '" name="kana_first" value="' + DomHelper.escapeHtml(kanaFirst) + '" maxlength="85" placeholder="例: タロウ" inputmode="katakana"' + required + '>';
                return [
                    '<div class="member-form-name-row">' +
                    '<div class="member-form-group"><label for="' + idLast + '">フリガナ（姓）' + (f.required ? ' <span class="required">*</span>' : '') + '</label>' + inputLast + '</div>' +
                    '<div class="member-form-group"><label for="' + idFirst + '">フリガナ（名）' + (f.required ? ' <span class="required">*</span>' : '') + '</label>' + inputFirst + '</div>' +
                    '</div>',
                ];
            }
            const id = 'profileEdit_' + key.replace(/[^a-z0-9_]/gi, '_');
            let input = '';
            if (key === 'sex') {
                const checked0 = (!val || val === '0') ? ' checked' : '';
                const checked1 = val === '1' ? ' checked' : '';
                const checked2 = val === '2' ? ' checked' : '';
                return [
                    '<fieldset class="member-form-group member-form-group--choice">' +
                    '<legend class="member-form-group-label">' + DomHelper.escapeHtml(label) + (f.required ? ' <span class="required">*</span>' : '') + '</legend>' +
                    '<div class="member-form-segment member-form-segment--cols-3" role="radiogroup" aria-label="' + DomHelper.escapeHtml(label) + '">' +
                    '<label class="member-form-segment-btn">' +
                    '<input type="radio" id="' + id + '_1" name="sex" value="1"' + checked1 + required + '>' +
                    '<span class="member-form-segment-btn-text">男性</span></label>' +
                    '<label class="member-form-segment-btn">' +
                    '<input type="radio" id="' + id + '_2" name="sex" value="2"' + checked2 + required + '>' +
                    '<span class="member-form-segment-btn-text">女性</span></label>' +
                    '<label class="member-form-segment-btn">' +
                    '<input type="radio" id="' + id + '_0" name="sex" value=""' + checked0 + '>' +
                    '<span class="member-form-segment-btn-text">未選択</span></label>' +
                    '</div></fieldset>',
                ];
            }
            if (key === 'mail_receive_flag') {
                // 未設定はどちらも未選択のままにする（デフォルト許可で既存の拒否設定を上書きしない）
                const checked1 = val === '1' ? ' checked' : '';
                const checked0 = val === '0' ? ' checked' : '';
                return [
                    '<fieldset class="member-form-group member-form-group--choice">' +
                    '<legend class="member-form-group-label">' + DomHelper.escapeHtml(label) + (f.required ? ' <span class="required">*</span>' : '') + '</legend>' +
                    '<div class="member-form-segment member-form-segment--cols-2" role="radiogroup" aria-label="' + DomHelper.escapeHtml(label) + '">' +
                    '<label class="member-form-segment-btn">' +
                    '<input type="radio" id="' + id + '_1" name="mail_receive_flag" value="1"' + checked1 + required + '>' +
                    '<span class="member-form-segment-btn-text">許可する</span></label>' +
                    '<label class="member-form-segment-btn">' +
                    '<input type="radio" id="' + id + '_0" name="mail_receive_flag" value="0"' + checked0 + required + '>' +
                    '<span class="member-form-segment-btn-text">許可しない</span></label>' +
                    '</div></fieldset>',
                ];
            }
            if (inputType === 'radio' && values.length > 0) {
                const optionsHtml = values.map(function (option, optionIndex) {
                    const optionVal = String(option);
                    const checked = val === optionVal ? ' checked' : '';
                    return '<label class="member-form-segment-btn">' +
                        '<input type="radio" id="' + id + '_' + optionIndex + '" name="' + DomHelper.escapeHtml(key) + '" value="' + DomHelper.escapeHtml(optionVal) + '"' + checked + required + '>' +
                        '<span class="member-form-segment-btn-text">' + DomHelper.escapeHtml(optionVal) + '</span></label>';
                }).join('');
                return [
                    '<fieldset class="member-form-group member-form-group--choice">' +
                    '<legend class="member-form-group-label">' + DomHelper.escapeHtml(label) + (f.required ? ' <span class="required">*</span>' : '') + '</legend>' +
                    '<div class="member-form-segment" role="radiogroup" aria-label="' + DomHelper.escapeHtml(label) + '">' + optionsHtml + '</div>' +
                    description +
                    '</fieldset>',
                ];
            }
            if (inputType === 'selectbox' && values.length > 0) {
                const optionsHtml = ['<option value="">未選択</option>'].concat(values.map(function (option) {
                    const optionVal = String(option);
                    const selected = val === optionVal ? ' selected' : '';
                    return '<option value="' + DomHelper.escapeHtml(optionVal) + '"' + selected + '>' + DomHelper.escapeHtml(optionVal) + '</option>';
                })).join('');
                input = '<select id="' + id + '" name="' + DomHelper.escapeHtml(key) + '"' + required + '>' + optionsHtml + '</select>';
                return ['<div class="member-form-group"><label for="' + id + '">' + DomHelper.escapeHtml(label) + (f.required ? ' <span class="required">*</span>' : '') + '</label>' + input + description + '</div>'];
            }
            if (inputType === 'checkbox' && values.length > 0) {
                let checkedList: string[] = [];
                if (Array.isArray(profile[key])) {
                    checkedList = profile[key].map(function (v: any) { return String(v); });
                } else if (val) {
                    checkedList = [val];
                }
                const optionsHtml = values.map(function (option, optionIndex) {
                    const optionVal = String(option);
                    const checked = checkedList.indexOf(optionVal) !== -1 ? ' checked' : '';
                    return '<label class="member-form-checkbox">' +
                        '<input type="checkbox" id="' + id + '_' + optionIndex + '" name="' + DomHelper.escapeHtml(key) + '[]" value="' + DomHelper.escapeHtml(optionVal) + '"' + checked + '>' +
                        '<span>' + DomHelper.escapeHtml(optionVal) + '</span></label>';
                }).join('');
                return [
                    '<fieldset class="member-form-group member-form-group--choice">' +
                    '<legend class="member-form-group-label">' + DomHelper.escapeHtml(label) + (f.required ? ' <span class="required">*</span>' : '') + '</legend>' +
                    '<div class="member-form-checkbox-list">' + optionsHtml + '</div>' +
                    description +
                    '</fieldset>',
                ];
            }
            if (inputType === 'textarea' || key === 'address') {
                const maxLen = key === 'address' ? 85 : 500;
                input = '<textarea id="' + id + '" name="' + DomHelper.escapeHtml(key) + '" maxlength="' + maxLen + '" rows="3"' + required + '>' + DomHelper.escapeHtml(val) + '</textarea>';
                return ['<div class="member-form-group"><label for="' + id + '">' + DomHelper.escapeHtml(label) + (f.required ? ' <span class="required">*</span>' : '') + '</label>' + input + description + '</div>'];
            }
            if (key === 'birthday') {
                input = '<input type="date" id="' + id + '" name="' + DomHelper.escapeHtml(key) + '" value="' + DomHelper.escapeHtml(val || birthdayVal) + '"' + required + '>';
            } else if (key === 'email') {
                input = '<input type="email" id="' + id + '" name="' + DomHelper.escapeHtml(key) + '" value="' + DomHelper.escapeHtml(val) + '" maxlength="255"' + required + '>';
            } else if (key === 'postal_code') {
                input = '<div class="member-form-postal-row">' +
                    '<input type="text" id="' + id + '" name="' + DomHelper.escapeHtml(key) + '" value="' + DomHelper.escapeHtml(val) + '" placeholder="例: 100-0001" maxlength="8" inputmode="numeric" autocomplete="postal-code"' + required + '>' +
                    '<button type="button" class="member-btn member-btn--secondary postal-code-lookup-btn" data-action="postal-code-lookup"><i class="fas fa-search-location"></i> 住所を自動入力</button>' +
                    '</div>';
            } else if (key === 'phone') {
                input = '<input type="tel" id="' + id + '" name="' + DomHelper.escapeHtml(key) + '" value="' + DomHelper.escapeHtml(val) + '" maxlength="20"' + required + '>';
            } else {
                input = '<input type="text" id="' + id + '" name="' + DomHelper.escapeHtml(key) + '" value="' + DomHelper.escapeHtml(val) + '" maxlength="255"' + required + '>';
            }
            return ['<div class="member-form-group"><label for="' + id + '">' + DomHelper.escapeHtml(label) + (f.required ? ' <span class="required">*</span>' : '') + '</label>' + input + description + '</div>'];
        });
        const addressKeys = ['postal_code', 'prefecture', 'city', 'street', 'address'];
        const groupedRows = [];
        let addressChunk = [];
        const flushAddressChunk = function () {
            if (addressChunk.length === 0) return;
            const hasPostal = fields.some(function (field) { return field.key === 'postal_code'; });
            const sectionHint = hasPostal
                ? '<p class="member-form-section-hint">郵便番号を入力して「住所を自動入力」を押すと住所が入力されます</p>'
                : '';
            groupedRows.push(
                '<section class="member-form-section member-form-section--address">' +
                '<h4 class="member-form-section-title">住所</h4>' +
                sectionHint +
                addressChunk.join('') +
                '</section>'
            );
            addressChunk = [];
        };
        rows.forEach(function (rowHtml) {
            const isAddressRow = addressKeys.some(function (addressKey) {
                return rowHtml.indexOf('profileEdit_' + addressKey) !== -1;
            });
            if (isAddressRow) {
                addressChunk.push(rowHtml);
                return;
            }
            flushAddressChunk();
            groupedRows.push(rowHtml);
        });
        flushAddressChunk();
        const title = isRegister ? '会員登録' : 'プロフィール編集';
        const formId = isRegister ? 'registerForm' : 'profileEditForm';
        const submitLabel = isRegister ? '登録する' : '保存';
        const titleIcon = isRegister ? 'fa-user-plus' : 'fa-user-edit';
        const isFullScreen = options.fullScreen === true;
        const hideCancel = options.forced === true || (isRegister && this._forcedRegister);
        const cancelLabel = 'キャンセル';
        const cancelOnclick = isFullScreen ? 'window.app.memberManager.closeMemberScreen()' : 'window.app.memberManager.closeProfileEdit()';
        const actionsHtml = hideCancel
            ? `
                    <div class="member-form-actions">
                        <button type="submit" class="member-btn member-btn--primary"${isFullScreen ? ' form="' + formId + '"' : ''}><i class="fas fa-check"></i> ${DomHelper.escapeHtml(submitLabel)}</button>
                    </div>`
            : `
                    <div class="member-form-actions">
                        <button type="button" class="member-btn member-btn--secondary" onclick="${cancelOnclick}">${DomHelper.escapeHtml(cancelLabel)}</button>
                        <button type="submit" class="member-btn member-btn--primary"${isFullScreen ? ' form="' + formId + '"' : ''}><i class="fas fa-check"></i> ${DomHelper.escapeHtml(submitLabel)}</button>
                    </div>`;
        const formInner = `
            <div class="member-modal-inner">
                <h3 class="member-modal-title"><i class="fas ${titleIcon}"></i> ${DomHelper.escapeHtml(title)}</h3>
                <form id="${formId}" class="member-form">
                    ${groupedRows.join('')}
                    ${isFullScreen ? '' : actionsHtml}
                </form>
            </div>
        `;
        if (isFullScreen) {
            return `
                <div class="member-register-fullscreen">
                    <div class="member-register-fullscreen-header">
                        <h2 class="member-register-fullscreen-title">${DomHelper.escapeHtml(title)}</h2>
                    </div>
                    <div class="member-register-fullscreen-body">${formInner}</div>
                    <div class="member-register-fullscreen-footer">${actionsHtml}</div>
                </div>
            `;
        }
        return formInner;
    }

    async openRegisterModal() {
        await this.openRegisterFullScreen();
    }

    async openRegisterFullScreen(options: any = {}) {
        options = options || {};
        try {
            await this.ensureGuestMemberContext();
        } catch (e) {
            console.warn('ensureGuestMemberContext failed', e);
        }
        if (this.memberData && this.memberData.is_member) {
            this._forcedRegister = false;
            return;
        }
        if (!options.skipHistory && !this.isRegisterPageMode()) {
            this._updateMemberScreenUrl('register', false);
        }
        const container = this.getContainer();
        if (!container) return;
        const initialProfile = (typeof window !== 'undefined' && window.__LINE_PROFILE__ && window.__LINE_PROFILE__.displayName)
            ? { name: window.__LINE_PROFILE__.displayName } : {};
        this._registerFullScreen = true;
        this._forcedRegister = options.forced === true;
        this._profileEditFullScreen = false;
        container.innerHTML = this._getProfileEditFormHtml(null, null, {
            isRegister: true,
            initialProfile: initialProfile,
            fullScreen: true,
            forced: this._forcedRegister,
        });
        container.scrollTop = 0;
        const form = document.getElementById('registerForm');
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                window.app.memberManager.saveRegisterForm(e);
            });
        }
        this._setupPostalCodeLookup(container);
        this._syncMemberScreenOverlay();
    }

    closeRegisterFullScreen() {
        if (this.isForcedRegistrationActive()) {
            return;
        }
        if (getMemberRegistrationMode() === 'prompt') {
            this._dismissMemberRegistrationPrompt();
        }
        this._registerFullScreen = false;
        this._forcedRegister = false;
        this._syncMemberScreenOverlay();
        this._updateMemberScreenUrl('', true);
        this.load();
    }

    async saveRegisterForm(e: any) {
        if (e) e.preventDefault();
        if (this._isRegisterSubmitting) {
            return;
        }
        const form = document.getElementById('registerForm') as HTMLFormElement | null;
        if (!form) return;

        this._isRegisterSubmitting = true;
        const submitButtons = Array.from(document.querySelectorAll(
            '#registerForm button[type="submit"], button[type="submit"][form="registerForm"]'
        )) as HTMLButtonElement[];
        const submitButtonHtml = submitButtons.map((btn) => btn.innerHTML);
        submitButtons.forEach((btn) => {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> 登録中...';
        });

        try {
            const root = this.memberData || {};
            const fields = (root.profile_field_settings) ? root.profile_field_settings : [];
            const payload: any = this._collectProfileFormPayload(form, fields);
            if (typeof window !== 'undefined' && window.__LINE_PROFILE__ && window.__LINE_PROFILE__.displayName && !payload.name) {
                payload.name = window.__LINE_PROFILE__.displayName;
            }
            const result = await this.apiClient.post(AppConstants.API_ENDPOINTS.MEMBERS_REGISTER, payload);
            const raw = (result && result.data) != null ? result.data : result;
            const data = this._normalizeMemberPayload(raw) || raw;
            if (data && (data.is_member === true || typeof data.is_member !== 'undefined')) {
                this.memberData = data;
                this._forcedRegister = false;
                if (this._registerFullScreen) {
                    this.closeRegisterFullScreen();
                } else {
                    this.render(data);
                    this.closeProfileEdit({ skipHistory: true });
                }
                ToastManager.success('会員登録が完了しました');
                if (typeof window !== 'undefined' && window.app && typeof window.app.refreshCurrentTab === 'function') {
                    window.app.refreshCurrentTab();
                }
            } else if (data) {
                this.memberData = data;
                this._forcedRegister = false;
                if (this._registerFullScreen) {
                    this.closeRegisterFullScreen();
                } else {
                    this.render(data);
                    this.closeProfileEdit({ skipHistory: true });
                }
                ToastManager.success(result.message || '会員登録が完了しました');
                if (typeof window !== 'undefined' && window.app && typeof window.app.refreshCurrentTab === 'function') {
                    window.app.refreshCurrentTab();
                }
            } else {
                throw new Error(result.error || result.message || '登録に失敗しました');
            }
        } catch (error) {
            console.error('Register error:', error);
            ToastManager.error('登録に失敗しました: ' + (error.message || 'エラーが発生しました'));
        } finally {
            this._isRegisterSubmitting = false;
            submitButtons.forEach((btn, index) => {
                if (document.body.contains(btn)) {
                    btn.disabled = false;
                    if (submitButtonHtml[index] != null) {
                        btn.innerHTML = submitButtonHtml[index];
                    }
                }
            });
        }
    }

    async saveProfileEdit(e: any) {
        if (e) e.preventDefault();
        const form = document.getElementById('profileEditForm') as HTMLFormElement | null;
        if (!form) return;
        const submitButtons = Array.from(document.querySelectorAll(
            '#profileEditForm button[type="submit"], button[type="submit"][form="profileEditForm"]'
        )) as HTMLButtonElement[];
        const submitButtonHtml = submitButtons.map((btn) => btn.innerHTML);
        submitButtons.forEach((btn) => {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> 保存中...';
        });
        const root = (this.memberData && this.memberData.profile_field_settings)
            ? this.memberData
            : ((this.memberData && this.memberData.member) ? this.memberData.member : this.memberData);
        const fields = (root && root.profile_field_settings)
            ? root.profile_field_settings
            : ((this.memberData && this.memberData.profile_field_settings) ? this.memberData.profile_field_settings : []);
        const payload: any = this._collectProfileFormPayload(form, fields);
        try {
            const result = await this.apiClient.request(AppConstants.API_ENDPOINTS.MEMBERS_ME, {
                method: 'PUT',
                body: JSON.stringify(payload),
                headers: { 'Content-Type': 'application/json' },
            });
            if (result.success) {
                const full = result.data && typeof result.data === 'object' ? result.data : null;
                if (!full) {
                    throw new Error('会員情報の形式が正しくありません');
                }
                this.setMemberData(full);
                this.render(full);
                this.closeProfileEdit();
                ToastManager.success('プロフィールを更新しました');
                if (typeof window !== 'undefined' && window.app && typeof window.app.refreshCurrentTab === 'function') {
                    window.app.refreshCurrentTab();
                }
            } else {
                throw new Error(result.error);
            }
        } catch (error) {
            console.error('Profile update error:', error);
            ToastManager.error('更新に失敗しました: ' + (error.message || 'エラーが発生しました'));
        } finally {
            submitButtons.forEach((btn, index) => {
                if (document.body.contains(btn)) {
                    btn.disabled = false;
                    if (submitButtonHtml[index] != null) {
                        btn.innerHTML = submitButtonHtml[index];
                    }
                }
            });
        }
    }

    _collectProfileFormPayload(form: HTMLFormElement, fields: any[]): Record<string, any> {
        const payload: Record<string, any> = {};
        const asInput = (el: Element | null): HTMLInputElement | null => el as HTMLInputElement | null;
        const fieldList = Array.isArray(fields) ? fields : [];
        fieldList.forEach((field) => {
            const key = field && field.key != null ? String(field.key) : '';
            if (key === '') {
                return;
            }
            const inputType = field.input_type || 'text';
            if (key === 'name') {
                const elLast = asInput(form.querySelector('[name="name_last"]'));
                const elFirst = asInput(form.querySelector('[name="name_first"]'));
                const lastVal = elLast ? (elLast.value || '').trim() : '';
                const firstVal = elFirst ? (elFirst.value || '').trim() : '';
                payload.name = (lastVal + ' ' + firstVal).trim() || null;
                return;
            }
            if (key === 'kana') {
                const elLast = asInput(form.querySelector('[name="kana_last"]'));
                const elFirst = asInput(form.querySelector('[name="kana_first"]'));
                const lastVal = elLast ? (elLast.value || '').trim() : '';
                const firstVal = elFirst ? (elFirst.value || '').trim() : '';
                payload.kana = (lastVal + ' ' + firstVal).trim() || null;
                return;
            }
            if (inputType === 'checkbox') {
                const checked = Array.from(form.querySelectorAll('[name="' + key.replace(/"/g, '\\"') + '[]"]:checked')) as HTMLInputElement[];
                payload[key] = checked.map((el) => (el.value || '').trim()).filter((v) => v !== '');
                return;
            }
            if (inputType === 'radio' || key === 'sex' || key === 'mail_receive_flag') {
                const el = asInput(form.querySelector('[name="' + key.replace(/"/g, '\\"') + '"]:checked'));
                if (el) {
                    const v = (el.value || '').trim();
                    if (key === 'mail_receive_flag') {
                        payload[key] = v;
                    } else {
                        payload[key] = v || null;
                    }
                } else if (key === 'sex') {
                    payload[key] = null;
                }
                return;
            }
            const el = asInput(form.querySelector('[name="' + key.replace(/"/g, '\\"') + '"]'));
            if (el) {
                let v: any = (el.value || '').trim();
                if (key === 'birthday' && !v) {
                    v = null;
                }
                payload[key] = v || null;
            }
        });
        return payload;
    }

    startWalletSummaryPolling(): void {
        if (isDemoDataMode()) {
            return;
        }
        this.stopWalletSummaryPolling();
        void this._pollWalletSummary();
        // 会計直後の付与をすぐ拾うため短間隔（軽量 API）
        this._walletSummaryTimer = window.setInterval(() => {
            void this._pollWalletSummary();
        }, 2000);
        if (!this._walletVisibilityBound && typeof document !== 'undefined') {
            this._walletVisibilityBound = true;
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'hidden') {
                    this.stopWalletSummaryPolling();
                    return;
                }
                this.startWalletSummaryPolling();
            });
        }
    }

    stopWalletSummaryPolling(): void {
        if (this._walletSummaryTimer !== null) {
            window.clearInterval(this._walletSummaryTimer);
            this._walletSummaryTimer = null;
        }
    }

    async _pollWalletSummary(): Promise<void> {
        try {
            const summary = await this.apiClient.get(AppConstants.API_ENDPOINTS.MEMBERS_WALLET_SUMMARY);
            // wallet-summary も apiClient 経由なら data 包みの可能性あり
            const raw = summary && typeof summary === 'object' && 'data' in summary
                ? (summary as { data?: Record<string, unknown> }).data
                : null;
            const src = raw !== null && typeof raw === 'object' ? raw : summary;
            const normalized = {
                points: Number((src as any)?.points ?? 0),
                stamps_total: Number((src as any)?.stamps_total ?? 0),
                pending_lottery_count: Number((src as any)?.pending_lottery_count ?? 0),
            };
            const prev = this._walletSummaryLast;
            this._walletSummaryLast = normalized;
            if (prev !== null
                && prev.points === normalized.points
                && prev.stamps_total === normalized.stamps_total
                && prev.pending_lottery_count === normalized.pending_lottery_count) {
                return;
            }

            // eslint-disable-next-line no-console
            console.info('[LotteryUI]', 'wallet_summary_changed', {
                prev,
                next: normalized,
                ts: Date.now(),
            });
            if (typeof window.__clientDebugLog === 'function') {
                window.__clientDebugLog('lottery_ui_wallet_summary_changed', {
                    prev,
                    next: normalized,
                });
            }

            const pointsChanged = prev === null || prev.points !== normalized.points;
            const lotteryIncreased = prev !== null
                && normalized.pending_lottery_count > prev.pending_lottery_count;

            if (this.memberData !== null && pointsChanged) {
                this.memberData.points = normalized.points;
                if ((window.app as any)?.currentTab === AppConstants.TABS.MEMBER) {
                    this.render(this.memberData);
                }
            }

            const container = this.getContainer();
            if (container !== null && (window.app as any)?.currentTab === AppConstants.TABS.MEMBER) {
                this._renderLotteryCta(container, normalized.pending_lottery_count);
            }

            if (lotteryIncreased) {
                ToastManager.success('ガチャの権利が付与されました');
                const designLab = (window.app as any)?.designLabManager;
                if (designLab !== undefined && typeof designLab.openGachaPopup === 'function') {
                    void designLab.openGachaPopup({ force: false });
                }
            }
        } catch (error: unknown) {
            const message = error instanceof Error ? error.message : 'unknown';
            // eslint-disable-next-line no-console
            console.warn('[LotteryUI]', 'wallet_summary_error', { message });
        }
    }

    _renderLotteryCta(container: Element, pendingCount: number): void {
        const existing = container.querySelector('.member-lottery-cta');
        if (pendingCount <= 0) {
            existing?.remove();
            return;
        }
        const html = `
            <div class="member-lottery-cta">
                <p class="member-lottery-cta__text">プレイできる権利が ${pendingCount} 件あります</p>
                <button type="button" class="pp-cta" data-open-lottery>ガチャを開く</button>
            </div>
        `;
        if (existing !== null) {
            existing.outerHTML = html;
        } else {
            container.insertAdjacentHTML('beforeend', html);
        }
        container.querySelector('[data-open-lottery]')?.addEventListener('click', () => {
            const designLab = (window.app as any)?.designLabManager;
            if (designLab !== undefined && typeof designLab.openGachaPopup === 'function') {
                void designLab.openGachaPopup();
                return;
            }
            if (typeof (window.app as any)?.switchTab === 'function') {
                (window.app as any).switchTab('designlab');
            }
        });
    }
}

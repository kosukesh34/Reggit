import { AppConstants } from '../../shared/constants';
import { DomHelper } from '../../shared/utils/domHelper';
import { ToastManager } from '../../shared/utils/toast';
import { clearAcquisitionSource, getAcquisitionSource } from '../../shared/utils/acquisitionSource';
import { getMemberRegistrationMode, shouldAutoCreateMember, syncMemberRegistrationModeFromPayload } from '../utils/membershipPolicy';
import { isDemoDataMode } from '../demo/demoMode';
import { getLiffEcho } from '../realtime/liffEcho';
import { MemberWalletActions } from '../member/MemberWalletActions';
import {
    isMobileAppMode,
} from '../member/walletEnvironment';
import { normalizeMemberPayload, normalizeWalletSummary, walletSummariesEqual } from '../member/memberPayload';
import { buildProfilePayload } from '../member/profilePayload';
import { renderProfileForm } from '../member/profileFormRenderer';
import { resolvePostalAddress, validatePostalCode } from '../member/postalAddressPolicy';

declare const liff: any;

export class MemberManager {
    apiClient: any;
    orderManager: any;
    walletActions: MemberWalletActions;
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
    _walletEchoChannelName: string | null = null;
    _walletSummaryLast: { points: number; stamps_total: number; pending_lottery_count: number } | null = null;
    _walletVisibilityBound = false;
    /** 同一の pending 件数では自動ポップアップを繰り返さない */
    _gachaPopupOpenedForPendingCount: number | null = null;

    constructor(apiClient: any, orderManager: any) {
        this.apiClient = apiClient;
        this.orderManager = orderManager;
        this.walletActions = new MemberWalletActions(
            apiClient,
            (maxWaitMs) => this.ensureIdentityReady(maxWaitMs),
            (eventName, payload) => this._debugLog(eventName, payload),
        );
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
        this._bindScreenPopState();
        this._bindWalletSummaryVisibilityRefresh();
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
        if (isMobileAppMode()) {
            const deviceId = window.__MOBILE_DEVICE_INFO__
                && String(window.__MOBILE_DEVICE_INFO__.device_identifier || '').trim();
            if (deviceId) {
                return true;
            }
        }
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
        if (shouldAutoCreateMember()) {
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
     * @param {object} data - member-profile の result.data または正規化済み payload
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
            const result = await this.apiClient.get(AppConstants.API_ENDPOINTS.MEMBER_PROFILE);
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
                this.refreshWalletSummaryOnLaunch();
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

        if (isMobileAppMode()) {
            try {
                await this.fetchMemberContext({ force: true });
            } catch (e) {
                // 未ログインなどは無視
            }
            return !!window.__LINE_USER_ID__;
        }

        return !!window.__LINE_USER_ID__;
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

    preload(_unused: any = null) {
        if (!this.canUseMemberFeature()) return;
        if (!window.__LINE_USER_ID__ && !isMobileAppMode()) return;
        this.fetchMemberContext({}).catch(function () {
            // ignore preload errors
        });
    }

    canUseMemberFeature() {
        if (typeof window === 'undefined') return false;
        if (window.__LINE_USER_ID__) return true;
        if (isMobileAppMode()) return true;
        if (window.__IS_LIFF__) return false;
        if (this._getPersistedLineUserId()) return true;
        return false;
    }

    canAutoRegister() {
        if (typeof window === 'undefined') return false;
        if (this.memberData && this.memberData.is_member === true) return false;
        if (shouldAutoCreateMember()) {
            return true;
        }
        if (!this._getLineUserIdForMemberApi()) return false;
        if (!this._lastMemberFetchWasNonMember) return false;
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
     * GET /member-profile のレスポンスから is_member を持つ payload を取得する。
     * フラット形式・{ member: ... }・{ data: ... } のいずれにも対応。
     * @param {object} resultData - apiClient が返す result.data
     * @returns {object|null} is_member を持つオブジェクト、または null
     */
    _normalizeMemberPayload(resultData) {
        return normalizeMemberPayload(resultData);
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
            const result = await this.apiClient.get(AppConstants.API_ENDPOINTS.MEMBER_PROFILE);
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
        ) || DomHelper.getTabMount(
            AppConstants.TAB_MOUNTS.MEMBER,
            AppConstants.ELEMENT_IDS.PROFILE_SECTION,
            { silent: true }
        );
    }

    _emitMemberReactEvent(name: string, detail?: Record<string, unknown>): void {
        if (typeof document === 'undefined') {
            return;
        }
        document.dispatchEvent(new CustomEvent(name, { detail: detail || {} }));
    }

    async load() {
        const container = this.getContainer();
        if (!container) return;
        this._emitMemberReactEvent('liff-member-reload');
    }

    async autoRegister() {
        if (this._autoRegisterAttempted) {
            return;
        }
        this._autoRegisterAttempted = true;

        const payload: any = { acquisition_source: getAcquisitionSource() };
        if (typeof window !== 'undefined' && window.__LINE_PROFILE__ && window.__LINE_PROFILE__.displayName) {
            payload.line_name = window.__LINE_PROFILE__.displayName;
        }
        try {
            const result = await this.apiClient.post(AppConstants.API_ENDPOINTS.MEMBERS_REGISTER, payload);
            const raw = result.success && result.data ? result.data : null;
            const memberPayload = raw ? (this._normalizeMemberPayload(raw) || raw) : null;
            if (memberPayload && memberPayload.is_member) {
                clearAcquisitionSource();
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
                this._emitMemberReactEvent('liff-member-reload');
            }
            if (typeof ToastManager !== 'undefined') {
                ToastManager.info('会員情報を入力して登録してください');
            }
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
        this._forcedRegister = getMemberRegistrationMode() === 'force';
        this._registerFullScreen = true;
        this._emitMemberReactEvent('liff-member-open-register', { forced: this._forcedRegister });
    }

    render(data) {
        const container = this.getContainer();
        if (!container) return;
        if (!data || typeof data !== 'object') return;
        this.memberData = data;
        this._emitMemberReactEvent('liff-member-reload');
    }

    /**
     * 未消化のガチャ権利があるときだけポップアップを開く。
     * 同一 pending 件数では重複表示しない（取引更新で件数が増えたら再度開く）。
     */
    _maybeOpenGachaPopupForPending(pendingCount: number): void {
        if (pendingCount <= 0) {
            this._gachaPopupOpenedForPendingCount = null;
            return;
        }
        if (this._gachaPopupOpenedForPendingCount === pendingCount) {
            return;
        }
        const designLab = (window.app as any)?.designLabManager;
        if (designLab === undefined || typeof designLab.openGachaPopup !== 'function') {
            return;
        }
        this._gachaPopupOpenedForPendingCount = pendingCount;
        window.setTimeout(() => {
            void designLab.openGachaPopup().then((opened: unknown) => {
                // openGachaPopup が権利なし等で表示しなかった場合は再試行できるようにする
                if (opened === false) {
                    this._gachaPopupOpenedForPendingCount = null;
                }
            }).catch(() => {
                this._gachaPopupOpenedForPendingCount = null;
            });
        }, 300);
    }

    _setupAddToWallet() {
        this.walletActions.setup();
    }

    async openProfileEdit(options: any = {}) {
        options = options || {};
        try {
            await this.fetchMemberContext({ force: true });
        } catch (error) {
            console.warn('fetchMemberContext for profile edit failed', error);
        }
        if (!this.memberData) return;
        if (!options.skipHistory && !this.isEditPageMode()) {
            this._updateMemberScreenUrl('edit', false);
        }
        this._registerFullScreen = false;
        this._profileEditFullScreen = true;
        this._emitMemberReactEvent('liff-member-open-edit');
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
            const validation = validatePostalCode(postalInput.value);
            if (validation.errorMessage) {
                showInputFailed(validation.errorMessage);
                return;
            }

            const url = AppConstants.API_ENDPOINTS.POSTAL_CODE + '?postal_code=' + encodeURIComponent(validation.digits);
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
                        const address = resolvePostalAddress(addresses[0]);
                        if (address) {
                            addressInput.value = address.fullAddress;
                            const prefectureInput = (doc.getElementById('profileEdit_prefecture') || doc.getElementById('profileEditPrefecture')) as HTMLInputElement | null;
                            const cityInput = (doc.getElementById('profileEdit_city') || doc.getElementById('profileEditCity')) as HTMLInputElement | null;
                            const streetInput = (doc.getElementById('profileEdit_street') || doc.getElementById('profileEditStreet')) as HTMLInputElement | null;
                            if (prefectureInput) prefectureInput.value = address.prefecture;
                            if (cityInput) cityInput.value = address.city;
                            if (streetInput) streetInput.value = address.street;
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

    _getProfileEditFormHtml(_user, member, options) {
        const renderOptions = options || {};
        return renderProfileForm(
            this.memberData,
            member,
            {
                ...renderOptions,
                forced: renderOptions.forced === true || (renderOptions.isRegister === true && this._forcedRegister),
                isNativeApp: typeof window !== "undefined" && window.__MOBILE_APP_MODE__ === true,
            },
            DomHelper.escapeHtml,
        );
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
        if (options.into == null) {
            this._registerFullScreen = true;
            this._forcedRegister = options.forced === true;
            this._profileEditFullScreen = false;
            this._emitMemberReactEvent('liff-member-open-register', { forced: this._forcedRegister });
            return;
        }
        const container = (options.into as HTMLElement | undefined) || this.getContainer();
        if (!container) return;
        await this.paintRegisterInto(container as HTMLElement, { forced: options.forced === true });
    }

    async paintRegisterInto(container: HTMLElement, options: { forced?: boolean } = {}): Promise<void> {
        try {
            await this.ensureGuestMemberContext();
        } catch (e) {
            console.warn('ensureGuestMemberContext failed', e);
        }
        if (this.memberData && this.memberData.is_member) {
            this._forcedRegister = false;
            return;
        }
        const initialProfile = {};
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
        const form = container.querySelector('#registerForm') || document.getElementById('registerForm');
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
        const mode = getMemberRegistrationMode();
        if (mode === 'prompt' || mode === 'popup') {
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
            if (typeof window !== 'undefined' && window.__LINE_PROFILE__ && window.__LINE_PROFILE__.displayName) {
                payload.line_name = window.__LINE_PROFILE__.displayName;
            }
            payload.acquisition_source = getAcquisitionSource();
            const result = await this.apiClient.post(AppConstants.API_ENDPOINTS.MEMBERS_REGISTER, payload);
            // 失敗レスポンスを会員データとして扱うと「登録完了」と表示してしまう
            if (!result.success) {
                throw new Error(result.error || '登録に失敗しました');
            }
            const raw = result.data != null ? result.data : result;
            const data = this._normalizeMemberPayload(raw) || raw;
            if (data && (data.is_member === true || typeof data.is_member !== 'undefined')) {
                if (data.is_member === true) clearAcquisitionSource();
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

    async saveProfileImage(file: File) {
        const body = new FormData();
        body.append('image', file);
        const result = await this.apiClient.request(AppConstants.API_ENDPOINTS.MEMBER_PROFILE_IMAGE, {
            method: 'POST',
            body,
        });
        if (!result.success || !result.data || typeof result.data !== 'object') {
            throw new Error(result.success ? '画像の更新に失敗しました' : result.error);
        }
        this.setMemberData(result.data);
        return result.data;
    }

    async deleteProfileImage() {
        const result = await this.apiClient.request(AppConstants.API_ENDPOINTS.MEMBER_PROFILE_IMAGE, {
            method: 'DELETE',
        });
        if (!result.success || !result.data || typeof result.data !== 'object') {
            throw new Error(result.success ? '画像の削除に失敗しました' : result.error);
        }
        this.setMemberData(result.data);
        return result.data;
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
            const result = await this.apiClient.request(AppConstants.API_ENDPOINTS.MEMBER_PROFILE, {
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
        const selector = (name: string) => `[name="${name.replace(/"/g, '\\"')}"]`;
        const input = (query: string) => form.querySelector(query) as HTMLInputElement | null;

        return buildProfilePayload(fields, {
            value: (name) => input(selector(name))?.value,
            checkedValue: (name) => input(`${selector(name)}:checked`)?.value,
            checkedValues: (name) => Array.from(form.querySelectorAll(`${selector(name)}:checked`))
                .map((element) => (element as HTMLInputElement).value),
        });
    }

    /**
     * ポイント／スタンプ／ガチャ権利の変更を購読する（POS会計の即時反映用）。
     */
    startWalletSummaryPolling(): void {
        if (isDemoDataMode()) {
            return;
        }
        this._bindWalletSummaryVisibilityRefresh();
        this._subscribeLiffWalletChannel();
        void this._pollWalletSummary();
    }

    refreshWalletSummaryOnLaunch(options: { force?: boolean } = {}): void {
        this.startWalletSummaryPolling();
        if (options.force === true) {
            void this._pollWalletSummary();
        }
    }

    _walletMemberId(): number {
        const raw = this.memberData?.member?.id ?? this.memberData?.id;
        const memberId = Number(raw);
        return Number.isFinite(memberId) && memberId > 0 ? memberId : 0;
    }

    _subscribeLiffWalletChannel(): void {
        const memberId = this._walletMemberId();
        if (memberId <= 0) {
            return;
        }
        const echo = getLiffEcho();
        if (echo === null) {
            return;
        }
        const channelName = `liff.member.${memberId}`;
        if (this._walletEchoChannelName === channelName) {
            return;
        }
        this._leaveWalletEchoChannel();
        this._walletEchoChannelName = channelName;
        echo.private(channelName).listen('.wallet.updated', (payload: Record<string, unknown>) => {
            this._applyNormalizedWalletSummary(payload);
        });
    }

    _leaveWalletEchoChannel(): void {
        if (this._walletEchoChannelName === null) {
            return;
        }
        const echo = getLiffEcho();
        if (echo !== null) {
            echo.leave(this._walletEchoChannelName);
        }
        this._walletEchoChannelName = null;
    }

    _bindWalletSummaryVisibilityRefresh(): void {
        if (this._walletVisibilityBound || typeof document === 'undefined') {
            return;
        }
        this._walletVisibilityBound = true;
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState !== 'visible') {
                return;
            }
            this._subscribeLiffWalletChannel();
            void this._pollWalletSummary();
        });
        document.addEventListener('liff-payment-completed', () => {
            void this._pollWalletSummary();
        });
        document.addEventListener('liff-member-reload', () => {
            void this._pollWalletSummary();
        });
    }

    async _pollWalletSummary(): Promise<void> {
        try {
            const summary = await this.apiClient.get(AppConstants.API_ENDPOINTS.MEMBER_WALLET_SUMMARY);
            const raw = summary && typeof summary === 'object' && 'data' in summary
                ? (summary as { data?: Record<string, unknown> }).data
                : null;
            const src = raw !== null && typeof raw === 'object' ? raw : summary;
            this._applyNormalizedWalletSummary(src as Record<string, unknown>);
        } catch (error: unknown) {
            const message = error instanceof Error ? error.message : 'unknown';

            console.warn('[LotteryUI]', 'wallet_summary_error', { message });
        }
    }

    _applyNormalizedWalletSummary(src: Record<string, unknown> | null | undefined): void {
        if (src === null || src === undefined) {
            return;
        }
        const normalized = normalizeWalletSummary(src);
        const prev = this._walletSummaryLast;
        this._walletSummaryLast = normalized;
        const displayedPoints = this.memberData !== null ? Number(this.memberData.points ?? 0) : null;
        const pointsDifferFromUi = displayedPoints !== null && displayedPoints !== normalized.points;
        if (walletSummariesEqual(prev, normalized)) {
            return;
        }

        const lotteryIncreased = prev !== null
            && normalized.pending_lottery_count > prev.pending_lottery_count;
        const hasPendingOnFirstPoll = prev === null
            && normalized.pending_lottery_count > 0;

        if (this.memberData !== null && pointsDifferFromUi) {
            this.memberData.points = normalized.points;
        }

        const container = this.getContainer();
        if (container !== null) {
            this._renderLotteryCta(container, normalized.pending_lottery_count);
        }
        if (typeof document !== 'undefined') {
            document.dispatchEvent(new CustomEvent('liff-wallet-summary-updated', {
                detail: {
                    pending_lottery_count: normalized.pending_lottery_count,
                    points: normalized.points,
                    stamps_total: normalized.stamps_total,
                },
            }));
        }

        if (lotteryIncreased) {
            ToastManager.success('ガチャの権利が付与されました');
        }
        if (lotteryIncreased || hasPendingOnFirstPoll) {
            this._maybeOpenGachaPopupForPending(normalized.pending_lottery_count);
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
                <button type="button" class="pp-cta" data-open-lottery>あそびを開く</button>
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
                void designLab.openGachaPopup().then((opened: unknown) => {
                    if (opened === true) {
                        return;
                    }
                    if (typeof (window.app as any)?.switchTab === 'function') {
                        (window.app as any).switchTab('designlab');
                    }
                });
                return;
            }
            if (typeof (window.app as any)?.switchTab === 'function') {
                (window.app as any).switchTab('designlab');
            }
        });
    }
}

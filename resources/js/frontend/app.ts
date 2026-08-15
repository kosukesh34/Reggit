import { AppConstants } from '../shared/constants';
import { ApiClient } from '../shared/utils/apiClient';
import { DomHelper } from '../shared/utils/domHelper';
import { ToastManager } from '../shared/utils/toast';
import { AnnouncementManager } from './managers/AnnouncementManager';
import { GalleryManager } from './managers/GalleryManager';
import { CartManager } from './managers/CartManager';
import { ProductManager } from './managers/ProductManager';
import { OrderManager } from './managers/OrderManager';
import { DeviceManager } from './managers/DeviceManager';
import { StampsManager } from './managers/StampsManager';
import { CheckoutManager } from './managers/CheckoutManager';
import { QueueManager } from './managers/QueueManager';
import { CouponsManager } from './managers/CouponsManager';
import { getMemberRegistrationMode, isMembershipRequired, isRegistrationFormDismissed, shouldAutoCreateMember, shouldShowRegistrationForm, shouldShowStartupLoginPopup, syncMemberRegistrationModeFromPayload } from './utils/membershipPolicy';
import { LiffLayoutManager } from './managers/LiffLayoutManager';
import { ReservationManager } from './managers/ReservationManager';
import { DesignLabManager } from './managers/DesignLabManager';
import { MemberManager } from './managers/MemberManager';
import { LiffPageRouter, parseLiffHash, isDeepLiffHash } from './navigation/LiffPageRouter';
import type { LiffPageKey } from './navigation/LiffPageRouter';
import { LIFF_UI_NAVIGATE, pathForLegacyTab } from '../liff-ui/navigation/tabPath';
import { primaryForTab } from './demo/navigation';
import { isFixedUiShell } from './demo/demoMode';
import {
    buildBuiltinSampleCoupons,
    buildBuiltinSampleMemberData,
    buildBuiltinSampleQueueEntry,
    buildBuiltinSampleReservations,
    buildBuiltinSampleStampCards,
} from './demo/sampleData';
import './managers/liff-blocks/renderPremiumBlocks';
import '../shared/utils/liffInit';

declare const liff: any;

class ReggitApp {
    apiClient: ApiClient;
    deviceManager: DeviceManager;
    productManager: ProductManager;
    cartManager: CartManager;
    orderManager: OrderManager;
    memberManager: MemberManager;
    stampsManager: StampsManager;
    couponsManager: CouponsManager;
    designLabManager: DesignLabManager;
    galleryManager: GalleryManager;
    layoutManager: LiffLayoutManager;
    queueManager: QueueManager;
    reservationManager: ReservationManager;
    announcementManager: AnnouncementManager;
    checkoutManager: CheckoutManager;
    pageRouter: LiffPageRouter;
    orderMode: string;
    designerPreviewSource: string | null;
    designerPreviewEmbed: boolean;
    designerPreviewSample: boolean;
    designerPreviewBootstrappedSource: string | null;
    currentTab: string;
    /** 外部ページタブへ入る直前のタブ（戻るで復元） */
    linkTabReturnTab: string;
    lastOrderCompleteMessage: string | null = null;
    lastOrderReferenceNumber: string | null = null;
    _ordersSilentRefreshInFlight = false;
    _memberRegistrationGateHandled: boolean = false;
    _memberTabLoadInFlight: Promise<void> | null = null;
    _productsViewMode: string | null = null;

    constructor() {
        this.apiClient = new ApiClient();
        this.deviceManager = new DeviceManager(this.apiClient);
        this.productManager = new ProductManager();
        this.cartManager = new CartManager();
        this.orderManager = new OrderManager(this.apiClient);
        this.memberManager = new MemberManager(this.apiClient, this.orderManager);
        this.stampsManager = new StampsManager(this.apiClient);
        this.couponsManager = new CouponsManager(this.apiClient);
        this.designLabManager = new DesignLabManager(this.apiClient);
        this.galleryManager = new GalleryManager(this.apiClient);
        this.layoutManager = new LiffLayoutManager(this);
        this.queueManager = new QueueManager(this.apiClient);
        this.reservationManager = new ReservationManager(this);
        this.announcementManager = new AnnouncementManager(this.apiClient);
        this.checkoutManager = new CheckoutManager(this.apiClient, this.cartManager);
        this.pageRouter = new LiffPageRouter();
        this.orderMode = this.getOrderMode();
        this.designerPreviewSource = this.getDesignerPreviewSource();
        this.designerPreviewEmbed = this.isDesignerPreviewEmbed();
        this.designerPreviewSample = this.isDesignerPreviewSample();
        this.designerPreviewBootstrappedSource = null;

        this.currentTab = this.getInitialTab();
        this.linkTabReturnTab = AppConstants.TABS.HOME;
        this.init();
    }

    getInitialTab() {
        const allowedTabs = new Set(['home', 'products', 'orders', 'stamps', 'coupons', 'designlab', 'queue', 'member', 'profile', 'reservations', 'gallery', 'announcements']);
        if (typeof window.__NATIVE_TARGET_TAB__ !== 'undefined' && window.__NATIVE_TARGET_TAB__ && allowedTabs.has(window.__NATIVE_TARGET_TAB__)) {
            return window.__NATIVE_TARGET_TAB__;
        }
        const fromUrl = this.getTabFromUrl();
        if (fromUrl) return fromUrl;
        const fromHash = this.getTabFromHash();
        if (fromHash) return fromHash;
        const firstBtn = (document.querySelector('.main-tab-btn.active') || document.querySelector('.main-tab-btn')) as HTMLElement | null;
        if (firstBtn && firstBtn.dataset.tab) return firstBtn.dataset.tab;
        return AppConstants.TABS.HOME;
    }

    getTabFromUrl() {
        if (typeof window === 'undefined') return null;
        const allowedTabs = new Set(['home', 'products', 'orders', 'stamps', 'coupons', 'designlab', 'queue', 'member', 'profile', 'reservations', 'gallery', 'announcements']);
        const aliases: Record<string, string> = {
            order: 'products',
            card: 'member',
            profile: 'profile',
            visit: 'queue',
            more: 'home',
        };
        const resolve = (raw: string | null): string | null => {
            if (raw === null || raw === '') {
                return null;
            }
            const mapped = aliases[raw] ?? raw;
            if (allowedTabs.has(mapped) || /^link_[0-9]+$/.test(mapped)) {
                return mapped;
            }
            return null;
        };
        const params = new URLSearchParams(window.location.search || '');
        const fromQuery = resolve(params.get('tab'));
        if (fromQuery !== null) {
            return fromQuery;
        }
        const liffState = params.get('liff.state');
        if (liffState) {
            const normalized = liffState.startsWith('?') ? liffState.slice(1) : liffState;
            const stateParams = new URLSearchParams(normalized);
            return resolve(stateParams.get('tab'));
        }
        return null;
    }

    getTabFromHash() {
        if (typeof window === 'undefined') return null;
        const parsed = parseLiffHash(window.location.hash || '');
        if (parsed.kind === 'announcement') {
            return AppConstants.TABS.ANNOUNCEMENTS;
        }
        if (parsed.kind === 'product' || parsed.kind === 'order') {
            return AppConstants.TABS.PRODUCTS;
        }
        if (parsed.kind === 'coupon') {
            return AppConstants.TABS.COUPONS;
        }
        if (parsed.kind === 'gallery') {
            return AppConstants.TABS.GALLERY;
        }
        if (parsed.kind === 'stamp') {
            return AppConstants.TABS.STAMPS;
        }
        if (parsed.kind === 'reservation') {
            return AppConstants.TABS.RESERVATIONS;
        }
        if (parsed.kind === 'tab') {
            return parsed.tab;
        }
        return null;
    }

    getOrderMode() {
        if (typeof window === 'undefined') return 'both';
        const mode = String(window.__ORDER_MODE__ || 'both');
        if (mode === 'mobile_only' || mode === 'in_store_only' || mode === 'both') return mode;
        return 'both';
    }

    getDesignerPreviewSource() {
        if (typeof window === 'undefined') return null;
        const params = new URLSearchParams(window.location.search || '');
        if (params.get('designer_live_preview') !== '1') return null;
        const source = params.get('designer_source');
        const allowed = new Set(['products', 'orders', 'stamps', 'coupons', 'queue', 'reservations', 'member', 'member_card', 'profile', 'gallery', 'announcements']);
        if (!source || !allowed.has(source)) return null;
        return source;
    }

    isDesignerPreviewEmbed() {
        if (typeof window === 'undefined') return false;
        const params = new URLSearchParams(window.location.search || '');
        return params.get('designer_embed') === '1';
    }

    isDesignerPreviewSample() {
        if (typeof window === 'undefined') return false;
        const params = new URLSearchParams(window.location.search || '');
        return params.get('designer_sample') === '1';
    }

    getDesignerPreviewTab(source) {
        const map = {
            products: 'products',
            orders: 'orders',
            stamps: 'stamps',
            coupons: 'coupons',
            queue: 'queue',
            reservations: 'reservations',
            member: 'member',
            member_card: 'member',
            profile: 'profile',
            gallery: 'gallery',
            announcements: 'announcements',
        };
        return map[source] || 'home';
    }

    shouldOpenMemberRegisterOnLoad() {
        if (typeof window === 'undefined') return false;
        const params = new URLSearchParams(window.location.search || '');
        return params.get('member_register') === '1';
    }

    get memberData() {
        return this.memberManager.getMemberData();
    }

    init() {
        const app = this;
        if (typeof window.__mobileOrderLog === 'function') {
            window.__mobileOrderLog('app.init', { currentTab: app.currentTab, ua: typeof navigator !== 'undefined' ? navigator.userAgent : '' });
        }
        (async () => {
            await app.deviceManager.restoreSessionIfNeeded();
            app._continueInit();
        })();
    }

    _continueInit() {
        this.deviceManager.init();
        this.preventZoom();
        // DesignMock 全面刷新 UI では DOM タブ／ハッシュ制御をしない（API・会員だけ維持）
        if (typeof window !== 'undefined' && window.__REGGIT_LIFF_UI__ === true) {
            if (!(typeof window !== 'undefined' && window.__IS_LIFF__ && !window.__LINE_USER_ID__)) {
                this.memberManager.preload(null);
            }
            this.setupNativePushDeepLink();
            return;
        }
        this.setupEventListeners();
        this.setupPullToRefresh();
        // デザイナー埋め込みプレビューではカスタムレイアウトを描画せず、
        // 実画面ローダー（products/member/stamps など）だけを使って表示する
        if (!(this.designerPreviewSource && this.designerPreviewEmbed)) {
            this.layoutManager.render(this.currentTab);
        }
        // React タブマウントとの競合を吸収し、active クラスを確実に合わせる
        window.requestAnimationFrame(() => {
            void this.switchTab(this.currentTab);
        });
        if (typeof window.__mobileOrderLog === 'function') window.__mobileOrderLog('app.init render done', { tab: this.currentTab });
        if (this.designerPreviewSource) {
            this.applyDesignerPreviewMode(false);
        }
        this.loadProductsWhenReady();
        this.setupHashRouting();
        this.announcementManager.load();
        if (!(typeof window !== 'undefined' && window.__IS_LIFF__ && !window.__LINE_USER_ID__)) {
            this.memberManager.preload(null);
        }
        if (this.shouldOpenMemberRegisterOnLoad()) {
            void this.openMemberRegisterOnLoad();
        } else {
            void this.applyMemberRegistrationGate();
        }
        this.setupNativePushDeepLink();
    }

    async openMemberRegisterOnLoad() {
        await this.switchTab(AppConstants.TABS.MEMBER);
        await this.loadMemberTab();
        await this.memberManager.ensureRegistrationFormShown({ forced: true });
    }

    async applyMemberRegistrationGate() {
        if (typeof window !== 'undefined' && window.__IS_DESIGNER_EMBED_PREVIEW__) {
            return;
        }
        if (this._memberRegistrationGateHandled) {
            return;
        }
        const emitLoginPopup = (forced: boolean): void => {
            document.dispatchEvent(new CustomEvent('liff-member-open-login-popup', { detail: { forced } }));
        };
        try {
            await this.memberManager.ensureIdentityReady(1500);
            if (typeof window !== 'undefined' && window.__IS_LIFF__ && !window.__LINE_USER_ID__) {
                const mode = getMemberRegistrationMode();
                if (mode === 'popup' || mode === 'prompt' || mode === 'force') {
                    emitLoginPopup(mode === 'force');
                }
                this._memberRegistrationGateHandled = true;
                return;
            }
            if (typeof this.memberManager.fetchMemberContext === 'function') {
                await this.memberManager.fetchMemberContext({});
            }
        } catch (e) {
            if (typeof window !== 'undefined' && window.__IS_LIFF__ && !window.__LINE_USER_ID__) {
                const mode = getMemberRegistrationMode();
                if (mode === 'popup' || mode === 'prompt' || mode === 'force') {
                    emitLoginPopup(mode === 'force');
                }
                this._memberRegistrationGateHandled = true;
                return;
            }
        }
        syncMemberRegistrationModeFromPayload(this.memberManager.getMemberData());
        const data = this.memberManager.getMemberData();
        if (data && data.is_member === true) {
            this._memberRegistrationGateHandled = true;
            return;
        }
        if (shouldAutoCreateMember() && this.memberManager.canAutoRegister()) {
            await this.memberManager.autoRegister();
            this._memberRegistrationGateHandled = true;
            return;
        }
        if (shouldShowStartupLoginPopup()) {
            emitLoginPopup(false);
            this._memberRegistrationGateHandled = true;
            return;
        }
        if (!shouldShowRegistrationForm()) {
            this._memberRegistrationGateHandled = true;
            return;
        }
        this._memberRegistrationGateHandled = true;
        await this.switchTab(AppConstants.TABS.MEMBER);
        await this.loadMemberTab();
        await this.memberManager.ensureRegistrationFormShown({
            forced: getMemberRegistrationMode() === 'force',
        });
    }

    isMemberRegistrationPromptDismissed(): boolean {
        return isRegistrationFormDismissed();
    }

    setupHashRouting() {
        if (typeof window === 'undefined') return;
        const applyHash = () => {
            if (this.pageRouter.shouldIgnoreHashChange()) {
                return;
            }
            const raw = window.location.hash || '';
            const parsed = parseLiffHash(raw);
            if (parsed.kind === 'announcement') {
                void this.announcementManager.openDetail(parsed.id);
                return;
            }
            if (parsed.kind === 'product') {
                void this.openProductDetailFromHash(parsed.id);
                return;
            }
            if (parsed.kind === 'coupon') {
                void this.couponsManager.openDetail(parsed.id);
                return;
            }
            if (parsed.kind === 'gallery') {
                void this.galleryManager.openDetail(parsed.id);
                return;
            }
            if (parsed.kind === 'stamp') {
                void this.stampsManager.openDetail(parsed.id);
                return;
            }
            if (parsed.kind === 'order') {
                this.openOrderFlow(parsed.step, { fromHash: true });
                return;
            }
            if (parsed.kind === 'reservation') {
                void this.reservationManager.openFlowStep(parsed.step, { fromHash: true });
                return;
            }
            const wasPage = this.pageRouter.isOpen();
            if (wasPage) {
                this.closeActivePage(false);
            }
            if (parsed.kind === 'tab') {
                if (parsed.tab !== this.currentTab || wasPage) {
                    void this.switchTab(parsed.tab);
                }
                if (parsed.tab === AppConstants.TABS.PRODUCTS && typeof parsed.category === 'string') {
                    this.applyProductCategory(parsed.category);
                }
            }
        };
        window.addEventListener('hashchange', applyHash);
        document.addEventListener('click', (e) => {
            const target = e.target as Element | null;
            const back = target !== null
                ? target.closest('[data-liff-page-back], [data-announcement-back]') as HTMLElement | null
                : null;
            if (back === null) return;
            e.preventDefault();
            this.closeActivePage(true);
        });
        applyHash();
    }

    closeActivePage(updateHash = true): void {
        const key = this.pageRouter.getCurrentKey();
        if (key === 'announcement_detail') {
            this.announcementManager.closeDetail(updateHash);
            return;
        }
        if (updateHash) {
            this.pageRouter.closePage(true);
        } else {
            this.pageRouter.closePageQuiet();
        }
    }

    /** ネイティブアプリ: プッシュ通知タップ等で指定タブを開く */
    setupNativePushDeepLink() {
        if (typeof window === 'undefined' || !window.__MOBILE_APP_MODE__) return;
        const allowedTabs = new Set(['home', 'products', 'orders', 'stamps', 'coupons', 'designlab', 'queue', 'member', 'profile', 'reservations', 'gallery', 'announcements']);
        window.__nativeOpenTab__ = (tabKey, options) => {
            options = options || {};
            const aliases: Record<string, string> = {
                order: 'products',
                card: 'member',
                visit: 'queue',
                more: 'home',
            };
            const resolved = aliases[tabKey] ?? tabKey;
            if (!resolved || !allowedTabs.has(resolved)) return false;
            this.switchTab(resolved);
            if (window.__REGGIT_LIFF_UI__ === true) {
                return true;
            }
            if (resolved === AppConstants.TABS.COUPONS && this.couponsManager) {
                if (options.couponReceived) {
                    this.couponsManager.onCouponReceived(options);
                } else {
                    this.couponsManager.load();
                }
            }
            if (resolved === AppConstants.TABS.QUEUE && this.queueManager) {
                this.queueManager.load();
            }
            return true;
        };
        window.addEventListener('mobile-coupon-received', () => {
            if (this.couponsManager) {
                this.couponsManager.onCouponReceived({ silent: false });
            }
        });
        if (typeof window.__NATIVE_PENDING_TAB__ !== 'undefined' && window.__NATIVE_PENDING_TAB__) {
            const pending = window.__NATIVE_PENDING_TAB__;
            window.__NATIVE_PENDING_TAB__ = null;
            window.__nativeOpenTab__(pending, { couponReceived: pending === AppConstants.TABS.COUPONS });
        }
    }

    /** スワイプ（プル）で画面を更新する */
    setupPullToRefresh() {
        if (typeof window === 'undefined' || !window.__MOBILE_APP_MODE__) return;
        let startY = 0;
        let pullDistance = 0;
        let refreshEl: HTMLDivElement | null = null;
        // 誤発動を減らすため、リロードに必要な引き下げ距離を長めにする
        const MIN_PULL = 300;
        const ACTIVATE_PULL = 48;
        const MAX_VISUAL_PULL = 140;
        let isPullTracking = false;

        const getPullRoot = (): HTMLElement =>
            (document.querySelector('.app-container') as HTMLElement | null)
            || document.body;

        const resetPullVisual = () => {
            const root = getPullRoot();
            root.style.transition = 'transform 0.2s ease-out';
            root.style.transform = '';
            window.setTimeout(() => {
                if (root.style.transform === '' || root.style.transform === 'none') {
                    root.style.transition = '';
                }
            }, 220);
        };

        const updatePullVisual = (distance: number) => {
            const root = getPullRoot();
            if (distance <= 0) {
                root.style.transition = '';
                root.style.transform = '';
                return;
            }
            const visual = Math.min(distance * 0.4, MAX_VISUAL_PULL);
            root.style.transition = 'none';
            root.style.transform = `translateY(${visual}px)`;
        };

        const createRefreshIndicator = () => {
            if (refreshEl) return refreshEl;
            refreshEl = document.createElement('div');
            refreshEl.className = 'pull-to-refresh-indicator';
            refreshEl.setAttribute('aria-hidden', 'true');
            refreshEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>更新中...</span>';
            refreshEl.style.cssText = 'position:fixed;top:0;left:0;right:0;padding:12px;text-align:center;background:#f5f5f5;z-index:1000;transform:translateY(-100%);transition:transform 0.2s;font-size:14px;';
            document.body.appendChild(refreshEl);
            return refreshEl;
        };

        const getScrollContainer = () => document.querySelector('.tab-content.active')
            || document.querySelector('.tab-content.active .tab-section')
            || document.querySelector('.main-content');
        const scrollContainer = document.querySelector('.main-content') || document.body;
        scrollContainer.addEventListener('touchstart', (e: TouchEvent) => {
            if (e.touches.length !== 1) return;
            const el = getScrollContainer();
            if (!el) return;
            const scrollTop = el.scrollTop || 0;
            isPullTracking = scrollTop <= 0;
            startY = isPullTracking ? e.touches[0].clientY : 0;
            pullDistance = 0;
            if (!isPullTracking) {
                resetPullVisual();
            }
        }, { passive: true });

        scrollContainer.addEventListener('touchmove', (e: TouchEvent) => {
            if (!isPullTracking || e.touches.length !== 1) return;
            const el = getScrollContainer();
            if (!el || (el.scrollTop || 0) > 0) {
                isPullTracking = false;
                pullDistance = 0;
                resetPullVisual();
                return;
            }
            const y = e.touches[0].clientY;
            pullDistance = Math.max(0, y - startY);
            if (pullDistance > ACTIVATE_PULL) {
                e.preventDefault();
                updatePullVisual(pullDistance);
            } else {
                updatePullVisual(0);
            }
        }, { passive: false });

        scrollContainer.addEventListener('touchend', () => {
            const el = getScrollContainer();
            const distance = pullDistance;
            const canRefresh = isPullTracking && el !== null && (el.scrollTop || 0) <= 0 && distance >= MIN_PULL;
            isPullTracking = false;
            pullDistance = 0;
            resetPullVisual();
            if (!canRefresh) return;
            const indicator = createRefreshIndicator();
            indicator.style.transform = 'translateY(0)';
            indicator.querySelector('i')?.classList.add('fa-spin');
            this.layoutManager.render(this.currentTab);
            const loader = this.getTabLoader(this.currentTab);
            if (loader) loader();
            setTimeout(() => {
                indicator.style.transform = 'translateY(-100%)';
                indicator.querySelector('i')?.classList.remove('fa-spin');
            }, 600);
        }, { passive: true });

        scrollContainer.addEventListener('touchcancel', () => {
            isPullTracking = false;
            pullDistance = 0;
            resetPullVisual();
        }, { passive: true });
    }

    /** タブごとのデータローダー（プルリフレッシュ・情報更新時の再表示で共通利用） */
    getTabLoader(tab) {
        const loaders = {
            home: () => this.announcementManager.load(),
            products: () => this.loadProducts(),
            orders: () => this.loadOrdersTab(),
            stamps: () => this.stampsManager.load(),
            coupons: () => this.couponsManager.load(),
            designlab: () => this.designLabManager.load(),
            queue: () => this.queueManager.load(),
            member: () => this.loadMemberTab(),
            reservations: () => this.checkMembershipAndLoadReservations(),
            gallery: () => this.galleryManager.load(),
        };
        return loaders[tab] || null;
    }

    /** 情報更新時に現在表示中のタブのデータだけ再読み込みする（レイアウトは触らず表示を更新する） */
    refreshCurrentTab() {
        const loader = this.getTabLoader(this.currentTab);
        if (loader) loader();
    }

    /** 会員情報が更新されたとき（注文完了・ポイント付与など）に会員データを再取得し、全画面の表示を更新する */
    async refreshAfterMemberUpdate() {
        try {
            const payload = await this.memberManager.fetchMemberContext({ force: true });
            if (payload) {
                this.memberManager.setMemberData(payload);
                this.memberManager.render(payload);
            }
        } catch (e) {
            // 未ログインなどは無視
        }
        try {
            this.refreshCurrentTab();
        } catch (e) {
            console.warn('refreshCurrentTab error:', e);
        }
    }

    async onLiffReady() {
        // ネイティブアプリ内では注入タイミングが遅いことがあるため、ready 時にも init してデバイス登録を確実にする
        this.deviceManager.init();
        await this.memberManager.ensureIdentityReady();
        if (window.__LINE_USER_ID__ && this.apiClient) {
            this.apiClient.persistLineUserId(window.__LINE_USER_ID__);
        }
        try {
            const payload = await this.memberManager._fetchMemberContextWithIdentityRetry({ force: false });
            if (payload && payload.is_member) {
                this.memberManager.setMemberData(payload);
                if (this.currentTab === AppConstants.TABS.MEMBER) {
                    this.memberManager.render(payload);
                }
            }
        } catch (e) {
            // 未ログインなどは無視
        }
        this.memberManager.preload(null);
        const projectKey = (typeof window !== 'undefined' && window.__PROJECT_KEY__) ? window.__PROJECT_KEY__ : '';
        if (this.memberManager && typeof this.memberManager._prefetchAppleWalletOpenUrl === 'function') {
            this.memberManager._prefetchAppleWalletOpenUrl(projectKey);
        }
        const hasQueueToken = this.queueManager.getQueueTokenFromUrl() !== null;

        if (hasQueueToken) {
            await this.queueManager.linkPendingQueueOnStartup();
            this.switchTab(AppConstants.TABS.QUEUE);
            if (window.__REGGIT_LIFF_UI__ !== true) {
                await this.queueManager.load();
            }
            return;
        }
        if (this.currentTab === AppConstants.TABS.QUEUE) {
            if (window.__REGGIT_LIFF_UI__ !== true) {
                this.queueManager.load();
            }
            return;
        }
        if (this.currentTab === AppConstants.TABS.MEMBER) {
            await this.loadMemberTab();
        }
        await this.applyMemberRegistrationGate();
    }

    preventZoom() {
        document.addEventListener('gesturestart', (e) => e.preventDefault());
        document.addEventListener('gesturechange', (e) => e.preventDefault());
        document.addEventListener('gestureend', (e) => e.preventDefault());
        document.addEventListener(
            'touchstart',
            (e) => {
                if (e.touches.length > 1) e.preventDefault();
            },
            { passive: false }
        );
        document.addEventListener(
            'touchmove',
            (e) => {
                if (e.touches.length > 1) e.preventDefault();
            },
            { passive: false }
        );
        document.addEventListener(
            'touchend',
            (e) => {
                if (e.touches.length > 1) e.preventDefault();
            },
            { passive: false }
        );
        document.addEventListener(
            'dblclick',
            (e) => e.preventDefault(),
            { passive: false }
        );
        window.addEventListener(
            'wheel',
            (e) => {
                if (e.ctrlKey) e.preventDefault();
            },
            { passive: false }
        );
    }

    setupEventListeners() {
        this.setupCartEventListeners();
        this.setupFriendAddLink();
        this.setupTabEventListeners();
        this.setupCategoryEventListeners();
        this.setupOrderEventListeners();
        this.setupNativeAppEventListeners();
    }

    /** 外部Webページタブ: LIFF内は外部ブラウザで、それ以外は新規タブで開く */
    openExternalUrl(url: string) {
        if (typeof window !== 'undefined' && window.__IS_LIFF__ && typeof liff !== 'undefined' && typeof liff.openWindow === 'function') {
            liff.openWindow({ url, external: true });
            return;
        }
        window.open(url, '_blank', 'noopener');
    }

    /** 友だち追加リンク: LINE内なら liff.openWindow で開き、LINE内ブラウザで友だち追加画面を表示 */
    setupFriendAddLink() {
        const url = typeof window !== 'undefined' ? (window.__LINE_FRIEND_ADD_URL__ || '') : '';
        if (!url) return;
        const el = document.getElementById('headerFriendAdd');
        if (!el) return;
        el.addEventListener('click', (e) => {
            if (typeof window !== 'undefined' && window.__IS_LIFF__ && typeof liff !== 'undefined' && typeof liff.openWindow === 'function') {
                e.preventDefault();
                liff.openWindow({ url: url, external: false });
            }
        });
    }

    /** ネイティブアプリでデバイス情報が注入されたあと、初期タブへ切り替え・会員タブのウォレット表示を更新する */
    setupNativeAppEventListeners() {
        const app = this;
        const allowedTabs = new Set(['home', 'products', 'orders', 'stamps', 'coupons', 'designlab', 'queue', 'member', 'profile', 'reservations', 'gallery', 'announcements']);
        document.addEventListener('mobile-device-info-injected', () => {
            if (typeof window.__MOBILE_APP_MODE__ === 'undefined' || !window.__MOBILE_APP_MODE__) return;
            if (!window.__NATIVE_WEB_READY__) return;
            if (window.__NATIVE_DEVICE_INFO_TAB_SYNCED__) return;
            window.__NATIVE_DEVICE_INFO_TAB_SYNCED__ = true;
            const params = new URLSearchParams(window.location.search || '');
            const inMemberFlow = params.get('member_register') === '1'
                || params.get('member_screen') === 'register'
                || params.get('member_screen') === 'edit';
            const targetTab = typeof window.__NATIVE_TARGET_TAB__ !== 'undefined' ? window.__NATIVE_TARGET_TAB__ : app.currentTab;
            if (!inMemberFlow && targetTab && allowedTabs.has(targetTab) && targetTab !== app.currentTab) {
                app.switchTab(targetTab);
            }
            if (app.currentTab === AppConstants.TABS.MEMBER || targetTab === AppConstants.TABS.MEMBER) {
                app.memberManager.load();
            }
            if (app.currentTab === AppConstants.TABS.COUPONS) {
                app.couponsManager.load();
            }
        });
    }

    setupCartEventListeners() {
        const cartBtn = DomHelper.getElementById(AppConstants.ELEMENT_IDS.CART_BTN);
        const accountBtn = DomHelper.getElementById(AppConstants.ELEMENT_IDS.ACCOUNT_BTN);
        const menuBtn = DomHelper.getElementById(AppConstants.ELEMENT_IDS.MENU_BTN);
        const closeMenuBtn = DomHelper.getElementById(AppConstants.ELEMENT_IDS.CLOSE_MENU_BTN);
        const sideMenu = DomHelper.getElementById(AppConstants.ELEMENT_IDS.SIDE_MENU);
        const closeCartBtn = DomHelper.getElementById(AppConstants.ELEMENT_IDS.CLOSE_CART_BTN);
        const overlay = DomHelper.getElementById(AppConstants.ELEMENT_IDS.OVERLAY);
        if (cartBtn) {
            cartBtn.removeAttribute('data-tooltip');
            cartBtn.addEventListener('click', () => this.openCart());
        }
        if (accountBtn) {
            accountBtn.addEventListener('click', () => {
                this.switchTab(AppConstants.TABS.MEMBER);
            });
        }
        if (menuBtn) {
            menuBtn.addEventListener('click', () => this.openSideMenu());
        }
        if (closeMenuBtn) {
            closeMenuBtn.addEventListener('click', () => this.closeSideMenu());
        }
        if (sideMenu) {
            sideMenu.addEventListener('click', (event) => {
                const target = event.target as HTMLElement | null;
                const item = target !== null ? target.closest('[data-menu-tab]') as HTMLElement | null : null;
                if (!item || !item.dataset.menuTab) {
                    return;
                }
                this.closeSideMenu();
                void this.switchTab(item.dataset.menuTab);
            });
        }
        if (closeCartBtn) closeCartBtn.addEventListener('click', () => this.closeCart());
        if (overlay) {
            overlay.addEventListener('click', () => {
                this.closeCart();
                this.closeSideMenu();
            });
        }
    }

    setupTabEventListeners() {
        // イベント委譲でタブボタンにバインド（注文後などでDOMが更新されても切り替え可能にする）
        const delegateRoot = document.body;
        if (!delegateRoot) return;
        delegateRoot.addEventListener('click', (e) => {
            const target = e.target as HTMLElement | null;
            const btn = target ? target.closest('.main-tab-btn') as HTMLElement | null : null;
            if (!btn || !btn.dataset.tab) return;
            e.preventDefault();
            // 外部ページタブは常にアプリ内（iframe）で表示する
            this.switchTab(btn.dataset.tab);
        });
    }

    applyDesignerPreviewMode(fromSwitch) {
        if (!this.designerPreviewSource) return;
        const source = this.designerPreviewSource;
        const targetTab = this.getDesignerPreviewTab(source);
        const needsBootstrap = this.designerPreviewBootstrappedSource !== source;
        document.body.classList.add('designer-live-preview-mode');
        if (this.designerPreviewEmbed) {
            document.body.classList.add('designer-live-preview-embed');
        }
        const header = document.querySelector('header.header') as HTMLElement | null;
        const cart = document.getElementById('cartSidebar') as HTMLElement | null;
        const overlay = document.getElementById('overlay') as HTMLElement | null;
        const footer = document.querySelector('footer.main-tabs') as HTMLElement | null;
        if (header) header.style.display = 'none';
        if (cart) cart.style.display = 'none';
        if (overlay) overlay.style.display = 'none';
        if (footer) footer.style.display = 'none';

        // 上部お知らせバー（タブ本体の #announcementsSection とは別）
        const topAnnouncements = document.getElementById('announcementsTopBar') as HTMLElement | null;
        if (topAnnouncements) {
            topAnnouncements.style.display = 'none';
        }

        if (this.currentTab !== targetTab) {
            this.switchTab(targetTab);
            return;
        }

        // 対象タブ以外のコンテンツを非表示（描画ロジック自体は本番と同じ）
        DomHelper.querySelectorAll('.tab-content').forEach((content) => {
            const active = content.id === (targetTab + 'Tab');
            (content as HTMLElement).style.display = active ? '' : 'none';
        });

        if (this.designerPreviewEmbed && needsBootstrap) {
            this.mountDesignerPreviewSource(source);
        }

        if (!fromSwitch || needsBootstrap) {
            const loaderBySource = {
                products: () => this.loadProducts(),
                orders: () => this.loadOrdersTab(),
                stamps: () => this.stampsManager.load(),
                coupons: () => this.couponsManager.load(),
                queue: () => this.queueManager.load(),
                reservations: () => this.checkMembershipAndLoadReservations(),
                member: () => this.loadMemberTab(this.designerPreviewSample),
                member_card: () => this.loadMemberTab(this.designerPreviewSample),
                profile: () => this.loadMemberTab(this.designerPreviewSample),
                gallery: () => this.galleryManager.load(),
                announcements: () => this.announcementManager.load(),
            };
            const loader = loaderBySource[source];
            if (loader) loader();
            this.designerPreviewBootstrappedSource = source;
        }
    }

    mountDesignerPreviewSource(source) {
        const targetTab = this.getDesignerPreviewTab(source);
        const sectionIdMap = {
            home: 'homeSection',
            products: 'productsSection',
            orders: 'ordersSection',
            stamps: 'stampsSection',
            coupons: 'couponsSection',
            queue: 'queueSection',
            member: 'memberSection',
            profile: 'profileSection',
            reservations: 'reservationsSection',
            gallery: 'gallerySection',
            announcements: 'announcementsSection',
        };
        const sectionId = sectionIdMap[targetTab];
        if (!sectionId) return;
        const section = document.getElementById(sectionId);
        if (!section) return;

        if (source === 'products') {
            section.innerHTML = `
                <div class="products-data-mount">
                    <div class="category-tabs products-category-tabs" id="categoryTabs">
                        <button class="tab-btn active" data-category="all"><i class="fas fa-th-large"></i><span>すべて</span></button>
                    </div>
                    <div class="products-section"><div class="products-grid products-data-grid" id="productsGrid"></div></div>
                </div>
            `;
            return;
        }
        if (source === 'announcements') {
            section.innerHTML = '';
            return;
        }
        section.innerHTML = '';
    }

    setupCategoryEventListeners() {
        // カテゴリタブのイベントは動的に設定（renderCategoryTabs内で行う）
        this._attachCategoryListeners();
    }

    _attachCategoryListeners() {
        const container = DomHelper.getCategoryTabsContainer();
        if (!container) return;
        container.querySelectorAll('.tab-btn[data-category]').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                const tabs = DomHelper.getCategoryTabsContainer();
                if (tabs) {
                    tabs.querySelectorAll('.tab-btn[data-category]').forEach((b) =>
                        DomHelper.removeClass(b, AppConstants.CSS_CLASSES.ACTIVE)
                    );
                }
                const currentTarget = e.currentTarget as HTMLElement | null;
                if (!currentTarget) return;
                DomHelper.addClass(currentTarget, AppConstants.CSS_CLASSES.ACTIVE);
                this.productManager.setCurrentCategory(currentTarget.dataset.category);
                this.renderProducts();
            });
        });
    }

    setupOrderEventListeners() {
        const orderBtn = DomHelper.getElementById(AppConstants.ELEMENT_IDS.ORDER_BTN);
        const inStoreOrderBtn = DomHelper.getElementById(AppConstants.ELEMENT_IDS.IN_STORE_ORDER_BTN);
        if (orderBtn) orderBtn.addEventListener('click', () => this.checkoutManager.placeMobileOrder());
        if (inStoreOrderBtn) inStoreOrderBtn.addEventListener('click', () => this.checkoutManager.placeInStoreOrder());
        this.applyOrderModeButtons();
    }

    applyOrderModeButtons() {
        const orderBtn = DomHelper.getElementById(AppConstants.ELEMENT_IDS.ORDER_BTN);
        const inStoreOrderBtn = DomHelper.getElementById(AppConstants.ELEMENT_IDS.IN_STORE_ORDER_BTN);
        const mode = this.orderMode || 'both';
        if (orderBtn) orderBtn.style.display = (mode === 'in_store_only') ? 'none' : '';
        if (inStoreOrderBtn) inStoreOrderBtn.style.display = (mode === 'mobile_only') ? 'none' : '';
        this.applyOrderButtonEmphasis(orderBtn, inStoreOrderBtn, mode);
    }

    /**
     * LIFF/Web は店内注文を主CTA、ネイティブアプリはモバイル注文を主CTAにする。
     * 片方のみ表示のときは表示中のボタンを主CTAにする。
     */
    applyOrderButtonEmphasis(
        orderBtn: HTMLElement | null,
        inStoreOrderBtn: HTMLElement | null,
        mode: string,
    ) {
        const preferMobilePrimary =
            mode === 'mobile_only'
            || (mode !== 'in_store_only' && typeof window !== 'undefined' && window.__MOBILE_APP_MODE__ === true);
        const setEmphasis = (el: HTMLElement | null, isPrimary: boolean) => {
            if (!el) return;
            el.classList.toggle('order-btn--primary', isPrimary);
            el.classList.toggle('order-btn--secondary', !isPrimary);
            el.classList.remove('order-btn--mobile', 'order-btn--in-store');
        };
        setEmphasis(orderBtn, preferMobilePrimary);
        setEmphasis(inStoreOrderBtn, !preferMobilePrimary);

        const actions = orderBtn?.parentElement ?? inStoreOrderBtn?.parentElement;
        if (actions !== null && actions !== undefined && orderBtn !== null && inStoreOrderBtn !== null) {
            if (preferMobilePrimary) {
                actions.insertBefore(orderBtn, inStoreOrderBtn);
            } else {
                actions.insertBefore(inStoreOrderBtn, orderBtn);
            }
        }
    }

    loadProductsWhenReady() {
        const run = () => {
            if (this.currentTab !== AppConstants.TABS.PRODUCTS) return;
            this.loadProducts();
        };
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', run);
        } else {
            run();
        }
    }

    /** 旧 React 商品グリッドが DOM を所有しているか（liff-ui では未マウント） */
    _hasReactProducts(): boolean {
        return document.querySelector('[data-react-products]') !== null;
    }

    _hasReactCart(): boolean {
        return document.querySelector('[data-react-cart]') !== null;
    }

    _dispatchProductsUpdated(): void {
        document.dispatchEvent(new CustomEvent('liff-products-updated'));
    }

    _dispatchCartUpdated(): void {
        document.dispatchEvent(new CustomEvent('liff-cart-updated'));
    }

    async loadProducts() {
        if (this._hasReactProducts()) {
            try {
                await this.productManager.loadProducts();
                this._dispatchProductsUpdated();
            } catch (error) {
                this.handleProductLoadError(error);
            }
            return;
        }
        const grid = DomHelper.getProductsGrid();
        if (!grid) {
            if (typeof window.__mobileOrderLog === 'function') window.__mobileOrderLog('loadProducts: productsGrid not found', { currentTab: this.currentTab });
            return;
        }
        grid.innerHTML = this.productManager.getProductSkeleton();
        try {
            await this.productManager.loadProducts();
            this.renderCategoryTabs();
            this.renderProducts();
        } catch (error) {
            this.handleProductLoadError(error);
        }
    }

    /** ブロックの action（#products?category=xxx）から商品カテゴリを絞り込む */
    applyProductCategory(category: string) {
        this.productManager.setCurrentCategory(category);
        if (this._hasReactProducts()) {
            this._dispatchProductsUpdated();
            return;
        }
        const container = DomHelper.getCategoryTabsContainer();
        if (container) {
            container.querySelectorAll('.tab-btn[data-category]').forEach((btn) => {
                DomHelper.removeClass(btn, AppConstants.CSS_CLASSES.ACTIVE);
                if ((btn as HTMLElement).dataset.category === category) {
                    DomHelper.addClass(btn, AppConstants.CSS_CLASSES.ACTIVE);
                }
            });
        }
        if (this.productManager.getCategories().length > 0) {
            this.renderProducts();
        }
    }

    /** 商品グリッドへ同期（レガシー HTML タブ描画は廃止） */
    renderCategoryTabs() {
        this._dispatchProductsUpdated();
    }

    handleProductLoadError(error) {
        console.error('Failed to load products:', error);
        if (this._hasReactProducts()) {
            this._dispatchProductsUpdated();
            return;
        }
        const grid = DomHelper.getProductsGrid();
        if (grid) {
            grid.innerHTML = this.getErrorTemplate(
                '商品の読み込みに失敗しました',
                error.message || 'エラーが発生しました'
            );
        }
    }

    getErrorTemplate(title, message) {
        return `
            <div class="loading" style="color: #e60012;">
                <i class="fas fa-exclamation-triangle"></i>
                <p>${DomHelper.escapeHtml(title)}</p>
                <p style="font-size: 12px; margin-top: 8px;">${DomHelper.escapeHtml(message)}</p>
                <button onclick="location.reload()" style="margin-top: 12px; padding: 8px 16px; background: #e60012; color: white; border: none; border-radius: 6px; cursor: pointer;">
                    <i class="fas fa-redo"></i> 再読み込み
                </button>
            </div>
        `;
    }

    getProductsViewMode() {
        if (this._productsViewMode === 'collection' || this._productsViewMode === 'list') {
            return this._productsViewMode;
        }
        try {
            const stored = String(window.localStorage.getItem('liff_products_view_mode') || '').trim();
            if (stored === 'collection' || stored === 'list') {
                this._productsViewMode = stored;
                return stored;
            }
        } catch (_e) { /* ignore */ }
        this._productsViewMode = 'list';
        return 'list';
    }

    setProductsViewMode(mode) {
        const next = mode === 'collection' ? 'collection' : 'list';
        this._productsViewMode = next;
        try {
            window.localStorage.setItem('liff_products_view_mode', next);
        } catch (_e) { /* ignore */ }
        const section = document.getElementById('productsSection');
        if (section !== null) {
            const mount = section.querySelector('.products-data-mount');
            if (mount instanceof HTMLElement) {
                mount.setAttribute('data-products-view', next);
            }
            section.querySelectorAll('.ios-view-toggle__btn').forEach((btn) => {
                const view = btn.getAttribute('data-products-view');
                const isActive = view === next;
                btn.classList.toggle('is-active', isActive);
                btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });
        }
    }

    bindProductsViewToggle(container) {
        if (!(container instanceof HTMLElement)) {
            return;
        }
        container.querySelectorAll('[data-products-view]').forEach((btn) => {
            if (!(btn instanceof HTMLButtonElement)) {
                return;
            }
            btn.addEventListener('click', () => {
                const view = btn.getAttribute('data-products-view');
                if (view === 'collection' || view === 'list') {
                    this.setProductsViewMode(view);
                }
            });
        });
        this.setProductsViewMode(this.getProductsViewMode());
    }

    /** 商品グリッドへ同期（レガシー HTML 商品描画は廃止） */
    renderProducts() {
        this._dispatchProductsUpdated();
        this._dispatchCartUpdated();
    }

    /** 商品をワンタップで追加（オプションなし商品向けのフォールバック）。 */
    addToCart(productId) {
        const product = this.productManager.getProductById(productId);
        if (!product) {
            ToastManager.error('商品が見つかりませんでした');
            return;
        }
        this.cartManager.addItem({
            id: product.id,
            name: product.name || '',
            price: product.price ?? 0,
            image_url: product.image_url,
        });
        this.updateCartUI();
        this.showCartNotification();
        this.renderProducts();
        ToastManager.success(`${product.name || '商品'}をカートに追加しました`);
    }

    /** オーダー一覧から数量を増減する（0で削除） */
    adjustProductQuantity(productId, change) {
        const product = this.productManager.getProductById(productId);
        if (!product) {
            return;
        }
        const hasOptions = Array.isArray(product.option_groups) && product.option_groups.length > 0;
        if (change > 0) {
            const lineKey = this.cartManager.getAdjustableLineKey(productId);
            if (lineKey === null) {
                if (hasOptions) {
                    this.openProductDetail(productId);
                    return;
                }
                this.addToCart(productId);
                return;
            }
            const line = this.cartManager.getItem(lineKey);
            if (line && line.optionIds && line.optionIds.length > 0 && hasOptions) {
                this.openProductDetail(productId);
                return;
            }
            this.updateQuantity(lineKey, 1);
            return;
        }
        const lineKey = this.cartManager.getAdjustableLineKey(productId);
        if (lineKey === null) {
            return;
        }
        const before = this.cartManager.getItem(lineKey);
        this.updateQuantity(lineKey, -1);
        if (before && this.cartManager.getItem(lineKey) === undefined) {
            ToastManager.info(`${before.name}をカートから削除しました`);
        }
    }

    removeFromCart(lineKey) {
        const item = this.cartManager.getItem(lineKey);
        this.cartManager.removeItem(lineKey);
        this.updateCartUI();
        this.renderProducts();
        if (item) ToastManager.info(`${item.name}をカートから削除しました`);
    }

    updateQuantity(lineKey, change) {
        this.cartManager.updateQuantity(lineKey, change);
        this.updateCartUI();
        this.renderProducts();
    }

    updateCartUI() {
        // バッジ／合計／注文ボタンを同期する。
        this._dispatchCartUpdated();
        this._dispatchProductsUpdated();

        const count = this.cartManager.getTotalCount();
        const countEl = DomHelper.getElementById(AppConstants.ELEMENT_IDS.CART_COUNT);
        if (countEl) countEl.textContent = String(count);
        const cartBtn = DomHelper.getElementById(AppConstants.ELEMENT_IDS.CART_BTN);
        if (cartBtn) {
            if (count > 0) {
                DomHelper.addClass(cartBtn, 'has-items');
            } else {
                DomHelper.removeClass(cartBtn, 'has-items');
            }
        }

        const total = this.cartManager.getTotalAmount();
        const orderBtn = DomHelper.getElementById(AppConstants.ELEMENT_IDS.ORDER_BTN);
        const inStoreOrderBtn = DomHelper.getElementById(AppConstants.ELEMENT_IDS.IN_STORE_ORDER_BTN);
        const totalPrice = DomHelper.getElementById(AppConstants.ELEMENT_IDS.TOTAL_PRICE);

        if (this.cartManager.isEmpty()) {
            if (orderBtn) (orderBtn as HTMLButtonElement).disabled = true;
            if (inStoreOrderBtn) (inStoreOrderBtn as HTMLButtonElement).disabled = true;
        } else {
            if (orderBtn) (orderBtn as HTMLButtonElement).disabled = false;
            if (inStoreOrderBtn) (inStoreOrderBtn as HTMLButtonElement).disabled = false;
        }
        if (this.orderMode === 'mobile_only' && inStoreOrderBtn) (inStoreOrderBtn as HTMLButtonElement).disabled = true;
        if (this.orderMode === 'in_store_only' && orderBtn) (orderBtn as HTMLButtonElement).disabled = true;
        if (totalPrice) totalPrice.textContent = DomHelper.formatPrice(total);
    }

    /* ===== 商品詳細ページ（#product/{id}） ===== */

    async openProductDetailFromHash(productId: number | string): Promise<void> {
        try {
            if (this.productManager.products.length === 0) {
                await this.productManager.loadProducts();
            }
        } catch {
            ToastManager.error('商品の読み込みに失敗しました');
            return;
        }
        this.openProductDetail(productId, { fromHash: true });
    }

    openProductDetail(productId, options: { fromHash?: boolean } = {}) {
        const product = this.productManager.getProductById(productId);
        if (!product) {
            ToastManager.error('商品が見つかりませんでした');
            return;
        }

        const section = this.pageRouter.openPage({
            key: 'product_detail',
            hash: 'product/' + String(productId),
            returnHash: this.pageRouter.resolveReturnHash('products'),
            title: product.name || '商品詳細',
            bodyClass: 'liff-page-product-detail',
        });
        if (section === null) {
            return;
        }
        // liff-ui では HashRouter が画面を持つ。旧 DOM 詳細ホストは無い。
    }

    closeProductDetail() {
        if (this.pageRouter.getCurrentKey() === 'product_detail') {
            this.pageRouter.closePage(true);
        }
        const host = document.getElementById('productDetailModal');
        if (host) {
            host.classList.remove('is-open');
            host.innerHTML = '';
        }
        document.body.style.overflow = '';
    }

    openCart() {
        this.closeSideMenu();
        this.updateCartUI();
        const sidebar = DomHelper.getElementById(AppConstants.ELEMENT_IDS.CART_SIDEBAR);
        const overlay = DomHelper.getElementById(AppConstants.ELEMENT_IDS.OVERLAY);
        if (sidebar) DomHelper.addClass(sidebar, AppConstants.CSS_CLASSES.OPEN);
        if (overlay) DomHelper.addClass(overlay, AppConstants.CSS_CLASSES.ACTIVE);
        document.body.style.overflow = 'hidden';
    }

    closeCart() {
        const key = this.pageRouter.getCurrentKey();
        if (key === 'order_cart' || key === 'order_confirm' || key === 'order_complete') {
            this.pageRouter.closePage(true);
        }
        const sidebar = DomHelper.getElementById(AppConstants.ELEMENT_IDS.CART_SIDEBAR);
        const overlay = DomHelper.getElementById(AppConstants.ELEMENT_IDS.OVERLAY);
        if (sidebar) DomHelper.removeClass(sidebar, AppConstants.CSS_CLASSES.OPEN);
        const menu = DomHelper.getElementById(AppConstants.ELEMENT_IDS.SIDE_MENU);
        const menuOpen = menu !== null && menu.classList.contains('is-open');
        if (!menuOpen) {
            if (overlay) DomHelper.removeClass(overlay, AppConstants.CSS_CLASSES.ACTIVE);
            document.body.style.overflow = '';
        }
    }

    openSideMenu() {
        this.closeCart();
        const menu = DomHelper.getElementById(AppConstants.ELEMENT_IDS.SIDE_MENU);
        const overlay = DomHelper.getElementById(AppConstants.ELEMENT_IDS.OVERLAY);
        const menuBtn = DomHelper.getElementById(AppConstants.ELEMENT_IDS.MENU_BTN);
        if (menu !== null) {
            menu.setAttribute('aria-hidden', 'false');
            DomHelper.addClass(menu, 'is-open');
        }
        if (overlay) DomHelper.addClass(overlay, AppConstants.CSS_CLASSES.ACTIVE);
        if (menuBtn !== null) menuBtn.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
    }

    closeSideMenu() {
        const menu = DomHelper.getElementById(AppConstants.ELEMENT_IDS.SIDE_MENU);
        const overlay = DomHelper.getElementById(AppConstants.ELEMENT_IDS.OVERLAY);
        const menuBtn = DomHelper.getElementById(AppConstants.ELEMENT_IDS.MENU_BTN);
        const sidebar = DomHelper.getElementById(AppConstants.ELEMENT_IDS.CART_SIDEBAR);
        if (menu !== null) {
            menu.setAttribute('aria-hidden', 'true');
            DomHelper.removeClass(menu, 'is-open');
        }
        if (menuBtn !== null) menuBtn.setAttribute('aria-expanded', 'false');
        const cartOpen = sidebar !== null && sidebar.classList.contains(AppConstants.CSS_CLASSES.OPEN);
        if (!cartOpen) {
            if (overlay) DomHelper.removeClass(overlay, AppConstants.CSS_CLASSES.ACTIVE);
            document.body.style.overflow = '';
        }
    }

    openOrderFlow(step: 'cart' | 'confirm' | 'complete', options: { fromHash?: boolean } = {}) {
        if (step === 'cart' && options.fromHash !== true) {
            this.openCart();
            return;
        }
        const sidebar = DomHelper.getElementById(AppConstants.ELEMENT_IDS.CART_SIDEBAR);
        const overlay = DomHelper.getElementById(AppConstants.ELEMENT_IDS.OVERLAY);
        if (sidebar) DomHelper.removeClass(sidebar, AppConstants.CSS_CLASSES.OPEN);
        if (overlay) DomHelper.removeClass(overlay, AppConstants.CSS_CLASSES.ACTIVE);
        document.body.style.overflow = '';

        const keyMap: Record<typeof step, LiffPageKey> = {
            cart: 'order_cart',
            confirm: 'order_confirm',
            complete: 'order_complete',
        };
        const key = keyMap[step];
        if (step === 'confirm' && this.cartManager.isEmpty()) {
            this.openOrderFlow('cart');
            return;
        }
        if (step === 'complete' && this.lastOrderCompleteMessage === null && !options.fromHash) {
            this.lastOrderCompleteMessage = 'ご注文ありがとうございました';
        }
        if (step === 'complete' && options.fromHash === true) {
            this.lastOrderReferenceNumber = null;
        }
        const section = this.pageRouter.openPage({
            key,
            hash: 'order/' + step,
            returnHash: this.pageRouter.resolveReturnHash('products'),
            title: step === 'cart' ? 'カート' : (step === 'confirm' ? '注文確認' : '注文完了'),
            bodyClass: 'liff-page-order-flow',
        });
        if (section === null) {
            return;
        }
        // liff-ui では HashRouter が画面を持つ。旧 DOM 詳細ホストは無い。
    }

    showCartNotification() {
        const btn = DomHelper.getElementById(AppConstants.ELEMENT_IDS.CART_BTN);
        if (btn) {
            btn.style.transform = 'scale(1.1)';
            setTimeout(() => (btn.style.transform = 'scale(1)'), AppConstants.ANIMATION_DURATION.CART_NOTIFICATION);
        }
    }

    handleOrderSuccess() {
        ToastManager.success(AppConstants.MESSAGES.ORDER_SUCCESS);
        this.lastOrderCompleteMessage = AppConstants.MESSAGES.ORDER_SUCCESS;
        this.cartManager.clear();
        this.updateCartUI();
        this.renderProducts();
        this.openOrderFlow('complete');
    }

    handlePaymentSuccess() {
        if (typeof this.checkoutManager?.closePaymentModal === 'function') {
            this.checkoutManager.closePaymentModal(false);
        } else {
            const paymentModal = document.querySelector('.payment-modal');
            if (paymentModal && typeof paymentModal.remove === 'function') {
                paymentModal.remove();
            }
        }
        this.handleOrderSuccess();
    }

    _refreshActiveTabDataMounts() {
        const tabContentEl = document.querySelector('.tab-content.active');
        if (!tabContentEl) return;
        const M = AppConstants.TAB_MOUNTS;
        if (tabContentEl.querySelector('.' + M.MEMBER)) {
            if (this.memberManager.isRegisterFullScreenActive()) {
                return;
            }
            const memberData = this.memberManager.getMemberData();
            if (memberData && memberData.is_member === true) {
                this.memberManager.render(memberData);
            } else {
                this.loadMemberTab();
            }
        }
        if (tabContentEl.querySelector('.' + M.PRODUCTS)) this.loadProducts();
        if (tabContentEl.querySelector('.' + M.ORDERS)) this.loadOrdersTab();
        if (tabContentEl.querySelector('.' + M.STAMPS)) this.stampsManager.load();
        if (tabContentEl.querySelector('.' + M.COUPONS)) this.couponsManager.load();
        if (tabContentEl.querySelector('.' + M.QUEUE)) this.queueManager.load();
        if (tabContentEl.querySelector('.' + M.RESERVATIONS)) this.checkMembershipAndLoadReservations();
        if (tabContentEl.querySelector('.' + M.GALLERY)) this.galleryManager.load();
        if (tabContentEl.querySelector('.' + M.ANNOUNCEMENTS)) this.announcementManager.load();
    }

    isLinkTab(tab: string): boolean {
        return /^link_[0-9]+$/.test(tab);
    }

    /** 外部Webページタブ（埋め込み表示）: iframe内に閉じ込め、上部に戻る導線を付ける */
    mountLinkTabEmbed(section: HTMLElement | null) {
        if (!section || section.dataset.layoutRendered === '1') return;
        const url = section.dataset.linkUrl || '';
        if (url === '') return;
        section.dataset.layoutRendered = '1';
        section.classList.add('tab-section--link-embed');

        let navDepth = 0;
        let ignoreNextLoad = false;
        const initialUrl = url;

        const chrome = document.createElement('div');
        chrome.className = 'link-tab-chrome';
        chrome.innerHTML = `
            <button type="button" class="link-tab-chrome__back" aria-label="戻る">‹</button>
            <span class="link-tab-chrome__title">Webページ</span>
        `;
        const backBtn = chrome.querySelector('.link-tab-chrome__back') as HTMLButtonElement | null;

        const iframe = document.createElement('iframe');
        iframe.className = 'link-tab-frame';
        iframe.src = initialUrl;
        iframe.setAttribute('loading', 'lazy');
        iframe.setAttribute('referrerpolicy', 'no-referrer');
        // allow-popups を付けない: target=_blank / window.open を抑止しアプリ内で完結させる
        iframe.setAttribute('sandbox', 'allow-scripts allow-same-origin allow-forms allow-downloads');
        iframe.setAttribute('title', '埋め込みWebページ');

        const leaveLinkTab = () => {
            const returnTab = this.linkTabReturnTab && !this.isLinkTab(this.linkTabReturnTab)
                ? this.linkTabReturnTab
                : 'home';
            void this.switchTab(returnTab);
        };

        const goBackInEmbed = () => {
            if (navDepth <= 1) {
                leaveLinkTab();
                return;
            }
            ignoreNextLoad = true;
            navDepth = Math.max(1, navDepth - 1);
            try {
                iframe.contentWindow?.history.back();
            } catch (_e) {
                ignoreNextLoad = false;
                iframe.src = initialUrl;
                navDepth = 1;
            }
        };

        backBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            goBackInEmbed();
        });

        iframe.addEventListener('load', () => {
            if (ignoreNextLoad) {
                ignoreNextLoad = false;
                return;
            }
            navDepth += 1;
        });

        section.appendChild(chrome);
        section.appendChild(iframe);
    }

    switchTab(tab) {
        if (typeof window.__mobileOrderLog === 'function') window.__mobileOrderLog('switchTab', { tab });
        if (this.memberManager && typeof this.memberManager.isForcedRegistrationActive === 'function'
            && this.memberManager.isForcedRegistrationActive()
            && tab !== AppConstants.TABS.MEMBER) {
            return Promise.resolve();
        }

        if (typeof window !== 'undefined' && window.__REGGIT_LIFF_UI__ === true) {
            this.currentTab = (tab === 'membership' || tab === 'profile') ? 'member' : tab;
            if (document.body !== null) {
                document.body.classList.toggle('liff-lottery-ui', tab === 'designlab');
            }
            const path = pathForLegacyTab(tab);
            if (path !== null) {
                const hashPath = String(window.location.hash || '').split('?')[0];
                const nextHash = '#' + path;
                // 同じ画面なら ?grant= などのクエリを消さない
                if (hashPath !== nextHash) {
                    window.history.replaceState(null, '', nextHash);
                    document.dispatchEvent(new CustomEvent(LIFF_UI_NAVIGATE, { detail: { path, replace: true } }));
                }
            }
            return Promise.resolve();
        }

        const apply = () => {
            if (this.isLinkTab(tab) && !this.isLinkTab(this.currentTab)) {
                this.linkTabReturnTab = this.currentTab || 'home';
            }
            this.currentTab = tab;

            if (typeof document !== 'undefined' && document.body !== null) {
                document.body.classList.toggle('liff-lottery-ui', tab === 'designlab');
            }

            let closedDetailPage = false;
            if (this.pageRouter.isOpen()) {
                this.closeActivePage(false);
                closedDetailPage = true;
            }

            const primary = primaryForTab(tab);
            const useFixedPrimary = isFixedUiShell();
            DomHelper.querySelectorAll('.main-tab-btn').forEach((btn) => {
                DomHelper.removeClass(btn, AppConstants.CSS_CLASSES.ACTIVE);
                const el = btn as HTMLElement;
                if (useFixedPrimary) {
                    const btnPrimary = el.dataset.primaryTab || el.dataset.tab;
                    if (primary !== null && btnPrimary === primary) {
                        DomHelper.addClass(btn, AppConstants.CSS_CLASSES.ACTIVE);
                    } else if (primary === null && el.dataset.tab === tab) {
                        DomHelper.addClass(btn, AppConstants.CSS_CLASSES.ACTIVE);
                    }
                    return;
                }
                if (el.dataset.tab === tab) {
                    DomHelper.addClass(btn, AppConstants.CSS_CLASSES.ACTIVE);
                }
            });
            DomHelper.querySelectorAll('.tab-content').forEach((content) => {
                DomHelper.removeClass(content, AppConstants.CSS_CLASSES.ACTIVE);
                (content as HTMLElement).style.display = '';
            });

            const tabIdMap = {
                [AppConstants.TABS.HOME]: AppConstants.ELEMENT_IDS.HOME_TAB,
                [AppConstants.TABS.PRODUCTS]: AppConstants.ELEMENT_IDS.PRODUCTS_TAB,
                [AppConstants.TABS.ORDERS]: AppConstants.ELEMENT_IDS.ORDERS_TAB,
                [AppConstants.TABS.STAMPS]: AppConstants.ELEMENT_IDS.STAMPS_TAB,
                [AppConstants.TABS.COUPONS]: AppConstants.ELEMENT_IDS.COUPONS_TAB,
                [AppConstants.TABS.QUEUE]: AppConstants.ELEMENT_IDS.QUEUE_TAB,
                [AppConstants.TABS.MEMBER]: AppConstants.ELEMENT_IDS.MEMBER_TAB,
                [AppConstants.TABS.RESERVATIONS]: AppConstants.ELEMENT_IDS.RESERVATIONS_TAB,
                [AppConstants.TABS.GALLERY]: AppConstants.ELEMENT_IDS.GALLERY_TAB,
                [AppConstants.TABS.ANNOUNCEMENTS]: AppConstants.ELEMENT_IDS.ANNOUNCEMENTS_TAB,
            };
            const tabEl = DomHelper.getElementById(tabIdMap[tab]) || document.getElementById(tab + 'Tab');
            if (tabEl) DomHelper.addClass(tabEl, AppConstants.CSS_CLASSES.ACTIVE);
            if (typeof window !== 'undefined') {
                window.dispatchEvent(new CustomEvent('ios-tab-changed', { detail: { tab, primary } }));
            }

            const mainContent = document.querySelector('.main-content') as HTMLElement | null;
            if (mainContent) {
                mainContent.scrollTop = 0;
            }
            window.scrollTo(0, 0);

            const sectionContainer = document.getElementById(tab + 'Section');
            const layoutWasRendered = !!(sectionContainer && sectionContainer.dataset.layoutRendered === '1');

            if (this.isLinkTab(tab)) {
                this.mountLinkTabEmbed(sectionContainer);
            } else if (
                !(this.designerPreviewSource && this.designerPreviewEmbed)
                && !(sectionContainer && sectionContainer.dataset.reactOwned === '1')
            ) {
                this.layoutManager.render(tab);
            }
            if (layoutWasRendered) {
                this._refreshActiveTabDataMounts();
            }
            if (this.designerPreviewSource) {
                this.applyDesignerPreviewMode(true);
            }

            if (
                typeof window !== 'undefined'
                && !(this.designerPreviewSource && this.designerPreviewEmbed)
                && (closedDetailPage || !isDeepLiffHash(window.location.hash || ''))
            ) {
                const nextHash = '#' + tab;
                if (window.location.hash !== nextHash) {
                    window.history.replaceState(null, '', nextHash);
                }
            }
        };

        const canViewTransition =
            typeof document !== 'undefined'
            && typeof (document as Document & { startViewTransition?: (cb: () => void) => unknown }).startViewTransition === 'function'
            && !(this.designerPreviewSource && this.designerPreviewEmbed)
            && !(typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches);

        if (canViewTransition) {
            try {
                return new Promise<void>((resolve) => {
                    (document as Document & { startViewTransition: (cb: () => void) => unknown }).startViewTransition(() => {
                        apply();
                        resolve();
                    });
                });
            } catch (_err) {
                // fallback below
            }
        }

        if (typeof document !== 'undefined') {
            document.body.classList.add('liff-nav-animating');
            window.setTimeout(() => document.body.classList.remove('liff-nav-animating'), 320);
        }
        apply();
        return Promise.resolve();
    }

    async loadOrdersTab(options: { silent?: boolean } = {}) {
        const silent = options.silent === true;
        const section = DomHelper.getTabMount(
            AppConstants.TAB_MOUNTS.ORDERS,
            AppConstants.ELEMENT_IDS.ORDERS_SECTION,
            { silent: true }
        );
        if (!section && this.pageRouter.getCurrentKey() !== 'order_complete') {
            return;
        }
        const reactOwned = !!(section && (
            (section as HTMLElement).dataset.reactOwned === '1'
            || section.closest('[data-react-owned="1"]') !== null
        ));
        if (typeof window !== 'undefined' && !window.__LINE_USER_ID__ && this.memberManager) {
            await this.memberManager.resolveLineUserIdForClient();
        }
        // 会員登録なしでも、LINE / ネイティブ端末 identity があれば現在の注文を確認できる
        const hasIdentity = this.memberManager && typeof this.memberManager.hasAuthenticatedIdentity === 'function'
            ? this.memberManager.hasAuthenticatedIdentity()
            : (typeof window !== 'undefined' && !!window.__LINE_USER_ID__);
        const canViewOrders = hasIdentity
            || (typeof MemberManager !== 'undefined' && MemberManager.isMobileAppMode());
        if (!canViewOrders) {
            if (!silent) {
                this.orderManager.stopOrdersPolling();
                if (section && !reactOwned) {
                    section.innerHTML = this.memberManager.getLoginOptionalTemplate(
                        '注文状況',
                        'ログインすると注文状況をご確認いただけます。'
                    );
                }
            }
            return;
        }
        if (!silent && !reactOwned) {
            this.orderManager.resetFingerprint();
            if (section) {
                section.innerHTML =
                    '<div class="loading"><i class="fas fa-spinner fa-spin"></i><p>読み込み中...</p></div>';
            }
        } else if (silent && this._ordersSilentRefreshInFlight) {
            return;
        }
        if (silent) {
            this._ordersSilentRefreshInFlight = true;
        }
        try {
            const orders = await this.orderManager.fetchOrders();
            this.syncOrderCompleteLiveStatus(orders);
            if (reactOwned) {
                // 一覧は React OrdersView が描画。order_complete のライブ更新のみここで維持
                if (this.pageRouter.getCurrentKey() === 'order_complete') {
                    this.applyOrdersPollingState(orders);
                }
                return;
            }
            if (silent && this.orderManager.shouldSkipRender(orders)) {
                this.applyOrdersPollingState(orders);
                return;
            }
            if (section) {
                this.orderManager.renderOrdersSection(section, orders);
            }
            this.applyOrdersPollingState(orders);
        } catch (error) {
            console.error('Failed to load orders:', error);
            if (!silent) {
                this.orderManager.stopOrdersPolling();
                if (section && !reactOwned) {
                    section.innerHTML = this.getOrdersErrorTemplate(error);
                }
            }
        } finally {
            if (silent) {
                this._ordersSilentRefreshInFlight = false;
            }
        }
    }

    shouldKeepOrdersPolling(orders: Array<{ status?: string }>): boolean {
        if (this.currentTab === AppConstants.TABS.ORDERS) {
            return true;
        }
        if (this.pageRouter.getCurrentKey() === 'order_complete') {
            return true;
        }
        return this.orderManager.hasActiveOrders(orders);
    }

    applyOrdersPollingState(orders: Array<{ status?: string }>): void {
        if (this.shouldKeepOrdersPolling(orders)) {
            this.orderManager.startOrdersPolling(() => this.loadOrdersTab({ silent: true }));
            return;
        }
        this.orderManager.stopOrdersPolling();
    }

    syncOrderCompleteLiveStatus(orders: Array<{ id?: number | string; order_number?: string; status?: string }>): void {
        if (this.pageRouter.getCurrentKey() !== 'order_complete') {
            return;
        }
        const liveMount = document.querySelector('[data-order-complete-live]');
        if (!liveMount) {
            return;
        }
        const liveOrder = this.orderManager.pickLiveOrder(orders, this.lastOrderReferenceNumber);
        const preferred = typeof this.lastOrderReferenceNumber === 'string'
            ? this.lastOrderReferenceNumber.trim()
            : '';
        const preferredCompleted = preferred !== ''
            && (orders || []).some((order) => {
                return String(order.status || '') === 'completed'
                    && DomHelper.formatReferenceNumber(order.order_number) === preferred;
            });
        this.orderManager.renderOrderCompleteLiveStatus(liveMount, liveOrder, {
            showCompleted: preferredCompleted,
        });
    }

    getOrdersErrorTemplate(error) {
        const message = error && error.message ? error.message : '注文履歴の読み込みに失敗しました';
        return `
            <div class="orders-error">
                <i class="fas fa-exclamation-circle"></i>
                <p>${DomHelper.escapeHtml(message)}</p>
                <button type="button" class="btn btn-secondary" onclick="app.loadOrdersTab()">
                    <i class="fas fa-redo"></i> 再読み込み
                </button>
            </div>
        `;
    }

    async loadMemberTab(useSample = false) {
        if (this._memberTabLoadInFlight) {
            return this._memberTabLoadInFlight;
        }
        this._memberTabLoadInFlight = this._loadMemberTabBody(useSample).finally(() => {
            this._memberTabLoadInFlight = null;
        });
        return this._memberTabLoadInFlight;
    }

    async _loadMemberTabBody(useSample = false) {
        if (typeof window !== 'undefined' && window.__IS_LIFF__) {
            await this.memberManager.ensureIdentityReady();
        } else if (typeof window !== 'undefined' && !window.__LINE_USER_ID__) {
            await this.memberManager.resolveLineUserIdForClient();
        }
        const isDesignerPreviewMember = !!this.designerPreviewSource && (this.designerPreviewSource === 'member' || this.designerPreviewSource === 'member_card' || this.designerPreviewSource === 'profile');
        if (isDesignerPreviewMember && useSample) {
            // デザイナー埋め込みプレビュー: バックエンドから渡されたランク設定ベースの実表示用ペイロードを優先（DBの会員証設定を反映）
            const payload = typeof window !== 'undefined' && window.__DESIGNER_PREVIEW_MEMBER_PAYLOAD__;
            if (payload && typeof payload === 'object' && payload.is_member) {
                this.memberManager.render(payload);
                return;
            }
            this.memberManager.render(this.getDesignerSampleMemberData());
            return;
        }
        // React MemberView が描画を担当する場合はデータ取得＋イベントのみ
        if (this.memberManager._isReactOwnedMemberMount()) {
            try {
                const payload = await this.memberManager._fetchMemberContextWithIdentityRetry({ force: true });
                syncMemberRegistrationModeFromPayload(payload);
                if (payload && payload.is_member) {
                    this.memberManager.setMemberData(payload);
                    this.memberManager.preload(null);
                } else if (payload) {
                    await this.memberManager._handleGuestContext(payload);
                }
            } catch (e) {
                // 未ログインなど
            }
            document.dispatchEvent(new CustomEvent('liff-member-reload'));
            return;
        }
        const cached = this.memberManager.getMemberData();
        if (this.memberManager.isRegisterFullScreenActive() && !(cached && cached.is_member)) {
            return;
        }
        if (cached && cached.is_member) {
            this.memberManager.render(cached);
        } else if (!this.memberManager.isRegisterFullScreenActive()) {
            this.memberManager.renderSampleMemberCard();
        }

        try {
            const payload = await this.memberManager._fetchMemberContextWithIdentityRetry({
                force: !(cached && cached.is_member),
            });
            syncMemberRegistrationModeFromPayload(payload);
            if (payload && payload.is_member) {
                this.memberManager.setMemberData(payload);
                this.memberManager.render(payload);
                this.memberManager.preload(null);
                return;
            }
            if (payload) {
                await this.memberManager._handleGuestContext(payload);
                return;
            }
        } catch (e) {
            // 未ログインなど
        }
        const data = this.memberManager.getMemberData();
        if (data && data.is_member) {
            this.memberManager.render(data);
            this.memberManager.preload(null);
        } else if (!this.memberManager.isRegisterFullScreenActive()) {
            this.memberManager.renderSampleMemberCard();
        }
    }

    getDesignerSampleMemberData() {
        return buildBuiltinSampleMemberData();
    }

    getDesignerSampleProducts() {
        return ProductManager.buildBuiltinSampleCatalog();
    }

    getDesignerSampleQueueEntry() {
        return buildBuiltinSampleQueueEntry();
    }

    getDesignerSampleStampCards() {
        return buildBuiltinSampleStampCards();
    }

    getDesignerSampleCoupons() {
        return buildBuiltinSampleCoupons();
    }

    getDesignerSampleReservations() {
        return buildBuiltinSampleReservations();
    }

    async checkMembershipAndLoadReservations() {
        const section = DomHelper.getTabMount(
            AppConstants.TAB_MOUNTS.RESERVATIONS,
            AppConstants.ELEMENT_IDS.RESERVATIONS_SECTION,
            { silent: true }
        );
        if (!section) return;
        if (typeof window !== 'undefined' && !window.__LINE_USER_ID__ && this.memberManager) {
            await this.memberManager.resolveLineUserIdForClient();
        }
        const hasIdentity = this.memberManager && typeof this.memberManager.hasAuthenticatedIdentity === 'function'
            ? this.memberManager.hasAuthenticatedIdentity()
            : (typeof window !== 'undefined' && !!window.__LINE_USER_ID__);
        if (!hasIdentity) {
            this.reservationManager.renderSampleReservations();
            return;
        }
        try {
            const result = await this.apiClient.get(AppConstants.API_ENDPOINTS.MEMBERS_ME);
            if (result.success === true) {
                const memberPayload = result.data as { is_member?: boolean } | null | undefined;
                if (memberPayload?.is_member) {
                    this.memberManager.setMemberData(memberPayload);
                    this.reservationManager.loadReservations();
                } else if (!isMembershipRequired()) {
                    this.reservationManager.loadReservations();
                } else {
                    this.reservationManager.renderSampleReservations();
                }
            } else if (!isMembershipRequired()) {
                this.reservationManager.loadReservations();
            } else {
                this.reservationManager.renderSampleReservations();
            }
        } catch (error) {
            console.error('Failed to check membership:', error);
            if (!isMembershipRequired()) {
                this.reservationManager.loadReservations();
            } else {
                this.reservationManager.renderSampleReservations();
            }
        }
    }
}

if (typeof window !== 'undefined' && !window.__LIFF_APP_BOOTSTRAP__) {
    window.__LIFF_APP_BOOTSTRAP__ = true;
    if (typeof window.__clientDebugLog === 'function') {
        window.__clientDebugLog('app_bootstrap_set', {});
    }

    function hideLiffLoading() {
        const el = document.getElementById('liff-loading');
        if (el) el.classList.add('liff-loading--hidden');
    }

    function restorePersistedLineUserIdBeforeApp() {
        // LIFF では liff-ready 後に LIFF トークンで ID を確定するため、ここでは復元しない
        if (typeof window === 'undefined' || window.__LINE_USER_ID__ || window.__IS_LIFF__) return;
        try {
            const projectKey = window.__PROJECT_KEY__ || '';
            const scoped = String(window.localStorage.getItem('mobile_order_line_user_id_' + projectKey) || '').trim();
            const legacy = String(window.localStorage.getItem('mobile_order_line_user_id') || '').trim();
            const cached = /^U[0-9a-f]{32}$/i.test(scoped) ? scoped : (/^U[0-9a-f]{32}$/i.test(legacy) ? legacy : '');
            if (cached) {
                window.__LINE_USER_ID__ = cached;
            }
        } catch (e) { /* ignore */ }
    }

    function createApp() {
        if (window.app) return;
        if (!window.__IS_LIFF__) {
            restorePersistedLineUserIdBeforeApp();
        }
        window.app = new ReggitApp();
        if (typeof window.__clientDebugLog === 'function') {
            window.__clientDebugLog('app_created', {
                require_membership: isMembershipRequired(),
                project_key: window.__PROJECT_KEY__ || null,
            });
        }
        hideLiffLoading();
    }

    document.addEventListener(
        'liff-ready',
        function fn() {
            document.removeEventListener('liff-ready', fn);
            if (!window.app) {
                createApp();
            }
            if (window.app && typeof window.app.onLiffReady === 'function') {
                if (typeof window.__clientDebugLog === 'function') {
                    window.__clientDebugLog('app_on_liff_ready_called', {});
                }
                window.app.onLiffReady();
            }
        },
        { once: true }
    );

    function canBootstrapWithoutLiffReady() {
        if (!window.__IS_LIFF__) return true;
        if (window.__LIFF_ID_MISSING__) return true;
        return false;
    }

    // フォールバック: 5秒以内にliff-readyが来なければ起動（LIFFでは LINE ID 確定後のみ members/me を叩く）
    setTimeout(() => {
        if (!window.app && typeof ReggitApp !== 'undefined' && canBootstrapWithoutLiffReady()) {
            createApp();
        }
        const loadingEl = document.getElementById('liff-loading');
        if (loadingEl && !loadingEl.classList.contains('liff-loading--hidden')) {
            loadingEl.classList.add('liff-loading--hidden');
        }
    }, 5000);

    // LIFF 初期化が極端に遅い場合の最終フォールバック
    setTimeout(() => {
        if (!window.app && typeof ReggitApp !== 'undefined') {
            createApp();
        }
    }, 15000);
}

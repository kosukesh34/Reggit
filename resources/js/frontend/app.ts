import { ApiClient } from '../shared/utils/apiClient';
import { DeviceManager } from './managers/DeviceManager';
import { MemberManager } from './managers/MemberManager';
import { OrderManager } from './managers/OrderManager';
import { CartManager } from './managers/CartManager';
import { CheckoutManager } from './managers/CheckoutManager';
import { bootstrapLegacyApp } from './bootstrapLegacyApp';
import { isMembershipRequired } from './utils/membershipPolicy';
import { LIFF_UI_NAVIGATE, pathForLegacyTab } from '../liff-ui/navigation/tabPath';
import '../shared/utils/liffInit';

/** LINE identity, membership and Stripe services used by the shared React screens. */
class LiffServices {
    apiClient = new ApiClient();
    deviceManager = new DeviceManager(this.apiClient);
    orderManager = new OrderManager(this.apiClient);
    memberManager = new MemberManager(this.apiClient, this.orderManager);
    cartManager = new CartManager();
    checkoutManager = new CheckoutManager(this.apiClient, this.cartManager);
    currentTab = 'home';

    constructor() {
        void this.deviceManager.restoreSessionIfNeeded().then(() => {
            this.deviceManager.init();
            if (!window.__IS_LIFF__ || window.__LINE_USER_ID__) this.memberManager.preload(null);
        });
        window.__nativeOpenTab__ = (tab: string) => { void this.switchTab(tab); return true; };
        window.addEventListener('mobile-coupon-received', () => this.refreshCurrentTab());
        const pending = window.__NATIVE_PENDING_TAB__ || window.__NATIVE_TARGET_TAB__;
        if (pending) { window.__NATIVE_PENDING_TAB__ = null; void this.switchTab(pending); }
    }

    get memberData() { return this.memberManager.getMemberData(); }

    switchTab(tab: string) {
        this.currentTab = tab;
        document.dispatchEvent(new CustomEvent(LIFF_UI_NAVIGATE, { detail: { path: pathForLegacyTab(tab) ?? `/${tab}` } }));
        return Promise.resolve();
    }

    refreshCurrentTab() { document.dispatchEvent(new CustomEvent('liff-member-reload')); }
    loadOrdersTab() { this.refreshCurrentTab(); }

    async refreshAfterMemberUpdate() {
        try {
            const payload = await this.memberManager.fetchMemberContext({ force: true });
            if (payload) this.memberManager.setMemberData(payload);
        } finally { this.refreshCurrentTab(); }
    }

    async onLiffReady() {
        this.deviceManager.init();
        await this.memberManager.ensureIdentityReady();
        if (window.__LINE_USER_ID__) this.apiClient.persistLineUserId(window.__LINE_USER_ID__);
        this.memberManager.preload(null);
        this.refreshCurrentTab();
    }
}
bootstrapLegacyApp(() => new LiffServices(), isMembershipRequired);

import type { AppConstantsType } from '../shared/constants';
import type { ApiClient } from '../shared/utils/apiClient';
import type { DomHelper } from '../shared/utils/domHelper';
import type { ToastManager } from '../shared/utils/toast';
import type { ConfirmDialog } from '../shared/utils/confirmDialog';
import type { TooltipManager } from '../shared/utils/tooltip';

export {};

type LiffLike = {
    isLoggedIn?: () => boolean;
    getDecodedIDToken?: () => { sub?: string } | null;
    login?: (opts?: { redirectUri?: string }) => void;
};

type QRCodeCtor = new (
    container: HTMLElement,
    options: {
        text: string;
        width?: number;
        height?: number;
        colorDark?: string;
        colorLight?: string;
    }
) => {
    makeCode?: (text: string) => void;
};

type StripePaymentCtor = new () => {
    setupPaymentForm: (elementId: string, amount: number, orderId: number | string) => Promise<void>;
    confirmPayment: (orderId: number | string) => Promise<{ success?: boolean; message?: string } | null>;
};

type LayoutManagerLike = {
    render?: (tab: string) => void;
    renderCustomLayout?: (container: HTMLElement, tab: string, blocks: unknown[]) => void;
};

type AppLike = {
    switchTab?: (tab: string) => void | Promise<void>;
    currentTab?: string;
    pageRouter?: import('../frontend/navigation/LiffPageRouter').LiffPageRouter;
    layoutManager?: LayoutManagerLike;
    loadProducts?: () => void | Promise<void>;
    loadOrdersTab?: () => void | Promise<void>;
    loadMemberTab?: () => void | Promise<void>;
    checkMembershipAndLoadReservations?: () => void | Promise<void>;
    apiClient?: ApiClient;
    couponsManager?: { load?: () => void | Promise<void> };
    stampsManager?: {
        load?: () => void | Promise<void>;
        openDetail?: (id: number | string, returnHash?: string) => void | Promise<void>;
        primeCache?: (cards: unknown[], defaultImage?: string | null) => void;
    };
    queueManager?: { load?: () => void | Promise<void> };
    galleryManager?: {
        load?: () => void | Promise<void>;
        openDetail?: (id: number | string, returnHash?: string) => void | Promise<void>;
    };
    announcementManager?: {
        load?: () => void | Promise<void>;
        openDetail?: (id: number | string, returnHash?: string) => void | Promise<void>;
    };
    designLabManager?: { load?: () => void | Promise<void> };
};

declare global {
    interface Window {
        __PROJECT_KEY__?: string;
        __REVERB_APP_KEY__?: string;
        __REVERB_WS_HOST__?: string;
        __REGGIT_LIFF_UI__?: boolean;
        __API_BASE__?: string;
        __API_TOKEN__?: string;
        __ORDER_MODE__?: string;
        __MOBILE_APP_MODE__?: boolean;
        __IS_LIFF__?: boolean;
        __LIFF_READY__?: boolean;
        __LINE_FRIEND_ADD_URL__?: string;
        __LINE_USER_ID__?: string | null;
        __LINE_PROFILE__?: { displayName?: string; [key: string]: unknown } | null;
        __FEATURE_FLAGS__?: Record<string, boolean>;
        __QUEUE_REQUIRE_MEMBERSHIP__?: boolean;
        __MEMBER_REGISTRATION_MODE__?: 'optional' | 'popup' | 'prompt' | 'force' | string;
        __FRONTEND_MENU__?: Array<{ tab: string; label?: string; icon?: string; url?: string; open?: string }>;
        __FRONTEND_HEADER__?: { title?: string; logoUrl?: string | null; friendAddUrl?: string | null };
        __LIFF_TAB_LAYOUTS__?: Record<string, unknown[]>;
        __LIFF_TAB_SETTINGS__?: Record<string, unknown>;
        __IS_DESIGNER_EMBED_PREVIEW__?: boolean;
        __COUPON_FORM_PREVIEW__?: boolean;
        __ANNOUNCEMENT_FORM_PREVIEW__?: boolean;
        __MOBILE_DEVICE_INFO__?: { device_identifier?: string; [key: string]: unknown };
        __GOOGLE_WALLET_ENABLED__?: boolean;
        __WALLET_PASS_ENABLED__?: boolean;
        __LIFF_OS__?: 'ios' | 'android' | string | null;
        __NATIVE_TARGET_TAB__?: string | null;
        __NATIVE_PENDING_TAB__?: string | null;
        __NATIVE_WEB_READY__?: boolean;
        __NATIVE_DEVICE_INFO_TAB_SYNCED__?: boolean;
        __DESIGNER_PREVIEW_MEMBER_PAYLOAD__?: Record<string, unknown>;
        __mobileOrderLog?: (eventName: string, payload?: Record<string, unknown>) => void;
        __nativeOpenTab__?: (tabKey: string, options?: { couponReceived?: boolean; [key: string]: unknown }) => boolean;
        __clientDebugLog?: (eventName: string, payload?: Record<string, unknown>) => void;
        JsBarcode?: (target: string | SVGElement | HTMLCanvasElement, text: string, options?: Record<string, unknown>) => void;
        QRCode?: QRCodeCtor;
        liff?: LiffLike;
        LiffLayoutBlocks?: { premium?: Record<string, (...args: any[]) => string> };
        CouponBulkIssueConfig?: Record<string, unknown>;
        StripePayment?: StripePaymentCtor;
        app?: AppLike;
        AppConstants?: AppConstantsType;
        ApiClient?: typeof ApiClient;
        DomHelper?: typeof DomHelper;
        ToastManager?: typeof ToastManager;
        ConfirmDialog?: typeof ConfirmDialog;
        TooltipManager?: typeof TooltipManager;
    }
}

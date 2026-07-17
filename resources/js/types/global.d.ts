import type { AppConstantsType } from '../shared/constants';
import type { ApiClient } from '../shared/utils/apiClient';
import type { DomHelper } from '../shared/utils/domHelper';
import type { ToastManager } from '../shared/utils/toast';
import type { ConfirmDialog } from '../shared/utils/confirmDialog';
import type { TooltipManager } from '../shared/utils/tooltip';
import type { AnnouncementManager } from '../frontend/managers/AnnouncementManager';
import type { GalleryManager } from '../frontend/managers/GalleryManager';
import type { CartManager } from '../frontend/managers/CartManager';
import type { ProductManager } from '../frontend/managers/ProductManager';
import type { OrderManager } from '../frontend/managers/OrderManager';
import type { DeviceManager } from '../frontend/managers/DeviceManager';
import type { StampsManager } from '../frontend/managers/StampsManager';
import type { CheckoutManager } from '../frontend/managers/CheckoutManager';
import type { QueueManager } from '../frontend/managers/QueueManager';
import type { CouponsManager } from '../frontend/managers/CouponsManager';
import type { LiffLayoutManager } from '../frontend/managers/LiffLayoutManager';
import type { ReservationManager } from '../frontend/managers/ReservationManager';
import type { MemberManager } from '../frontend/managers/MemberManager';

export {};

type LiffLike = {
    isLoggedIn?: () => boolean;
    getDecodedIDToken?: () => { sub?: string } | null;
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

type AppLike = {
    updateCartUI?: () => void;
    renderProducts?: () => void;
    closeCart?: () => void;
    openOrderFlow?: (step: 'cart' | 'confirm' | 'complete') => void;
    lastOrderCompleteMessage?: string | null;
    loadOrdersTab?: () => void;
    refreshAfterMemberUpdate?: () => Promise<void>;
    switchTab?: (tab: string) => void;
    onLiffReady?: () => void;
    currentTab?: string;
    refreshCurrentTab?: () => void;
    memberManager?: InstanceType<typeof MemberManager>;
    pageRouter?: import('../frontend/navigation/LiffPageRouter').LiffPageRouter;
    announcementManager?: InstanceType<typeof AnnouncementManager>;
    couponsManager?: InstanceType<typeof CouponsManager>;
    galleryManager?: InstanceType<typeof GalleryManager>;
    stampsManager?: InstanceType<typeof StampsManager>;
    layoutManager?: InstanceType<typeof LiffLayoutManager>;
    reservationManager?: InstanceType<typeof ReservationManager>;
};

declare global {
    interface Window {
        __PROJECT_KEY__?: string;
        __API_BASE__?: string;
        __API_TOKEN__?: string;
        __ORDER_MODE__?: string;
        __MOBILE_APP_MODE__?: boolean;
        __IS_LIFF__?: boolean;
        __LINE_USER_ID__?: string | null;
        __FEATURE_FLAGS__?: Record<string, boolean>;
        __FRONTEND_MENU__?: Array<{ tab: string; label?: string; icon?: string }>;
        __FRONTEND_HEADER__?: { title?: string; logoUrl?: string; friendAddUrl?: string };
        __IS_DESIGNER_EMBED_PREVIEW__?: boolean;
        __LIFF_TAB_SETTINGS__?: { _global?: Record<string, unknown> } & Record<string, Record<string, unknown>>;
        __LIFF_TAB_LAYOUTS__?: Record<string, unknown[]>;
        __LIFF_DETAIL_LAYOUTS__?: Record<string, unknown[]>;
        __LIFF_BUILDER__?: unknown[];
        __LINE_FRIEND_ADD_URL__?: string;
        LiffBuilderManager?: new (...args: any[]) => any;
        LiffBuilderPageConfig?: {
            templates?: Array<{ id: string; name?: string; blocks: Record<string, any>[] }>;
            blocks?: Record<string, any>[];
        };
        __MOBILE_DEVICE_INFO__?: {
            device_identifier?: string;
            platform?: string;
            push_token?: string | null;
            os_version?: string | null;
            app_version?: string | null;
        };
        __MOBILE_PENDING_PUSH_TOKEN__?: string | null;
        __nativeSetPushToken?: (token: string | null | undefined) => void;
        __clientDebugLog?: (eventName: string, payload?: Record<string, unknown>) => void;
        __ldToastTimer?: number;
        __ldDesigner?: unknown;
        __ldShowToast?: (message: string, isError?: boolean, durationMs?: number) => void;
        __LINE_PROFILE__?: any;
        __nativeOpenExternalUrl__?: (url: string) => boolean | void;
        __nativeAddAppleWalletPass__?: (url: string) => boolean | void;
        __LIFF_ID_MISSING__?: boolean;
        __LIFF_APP_BOOTSTRAP__?: boolean;
        __LIFF_OS__?: 'ios' | 'android';
        __LIFF_PERMISSION_DENIED__?: boolean;
        __LIFF_INIT_BOOTSTRAPPED__?: boolean;
        __WALLET_PASS_ENABLED__?: boolean;
        __GOOGLE_WALLET_ENABLED__?: boolean;
        __NATIVE_TARGET_TAB__?: string | null;
        __NATIVE_PENDING_TAB__?: string | null;
        __NATIVE_WEB_READY__?: boolean;
        __NATIVE_DEVICE_INFO_TAB_SYNCED__?: boolean;
        __DESIGNER_PREVIEW_MEMBER_PAYLOAD__?: Record<string, unknown>;
        __mobileOrderLog?: (eventName: string, payload?: Record<string, unknown>) => void;
        __nativeOpenTab__?: (tabKey: string, options?: { couponReceived?: boolean; [key: string]: unknown }) => boolean;
        JsBarcode?: (target: string | SVGElement | HTMLCanvasElement, text: string, options?: Record<string, unknown>) => void;
        QRCode?: QRCodeCtor;
        liff?: LiffLike;
        LiffLayoutBlocks?: { premium?: Record<string, (...args: any[]) => string> };
        LiffDesigner?: new (config: Record<string, unknown>) => unknown;
        LiffDesignerModules?: Record<string, any>;
        CouponBulkIssueConfig?: Record<string, unknown>;
        StripePayment?: StripePaymentCtor;
        app?: AppLike;
        AppConstants?: AppConstantsType;
        ApiClient?: typeof ApiClient;
        DomHelper?: typeof DomHelper;
        ToastManager?: typeof ToastManager;
        ConfirmDialog?: typeof ConfirmDialog;
        TooltipManager?: typeof TooltipManager;
        AnnouncementManager?: typeof AnnouncementManager;
        GalleryManager?: typeof GalleryManager;
        CartManager?: typeof CartManager;
        ProductManager?: typeof ProductManager;
        OrderManager?: typeof OrderManager;
        DeviceManager?: typeof DeviceManager;
        StampsManager?: typeof StampsManager;
        CheckoutManager?: typeof CheckoutManager;
        QueueManager?: typeof QueueManager;
        CouponsManager?: typeof CouponsManager;
        LiffLayoutManager?: typeof LiffLayoutManager;
        ReservationManager?: typeof ReservationManager;
        MemberManager?: typeof MemberManager;
    }
}

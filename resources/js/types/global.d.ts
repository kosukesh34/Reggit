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
    switchTab?: (tab: string) => void;
    currentTab?: string;
    pageRouter?: import('../frontend/navigation/LiffPageRouter').LiffPageRouter;
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
        __QUEUE_REQUIRE_MEMBERSHIP__?: boolean;
        __MEMBER_REGISTRATION_MODE__?: 'optional' | 'prompt' | 'force' | string;
        __FRONTEND_MENU__?: Array<{ tab: string; label?: string; icon?: string; url?: string; open?: string }>;
        __FRONTEND_HEADER__?: { title?: string; logoUrl?: string | null; friendAddUrl?: string | null };
        __IS_DESIGNER_EMBED_PREVIEW__?: boolean;
        __MOBILE_DEVICE_INFO__?: { device_identifier?: string; [key: string]: unknown };
        __GOOGLE_WALLET_ENABLED__?: boolean;
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

const API_BASE =
    typeof window !== 'undefined' && window.__API_BASE__ ? window.__API_BASE__ : '/api';

function readNativeAppMeta(name: string, fallback: string): string {
    if (typeof document === 'undefined') {
        return fallback;
    }
    const el = document.querySelector(`meta[name="${name}"]`);
    const value = el !== null && el.getAttribute('content') ? el.getAttribute('content').trim() : '';
    return value || fallback;
}

const NATIVE_APP_USER_AGENT_TOKEN = readNativeAppMeta('native-app-user-agent-token', 'Reggit');
const NATIVE_APP_URL_SCHEME = readNativeAppMeta('native-app-url-scheme', 'reggit');
const NATIVE_APP_ORDER_TYPE_MOBILE_LABEL = readNativeAppMeta('native-app-order-type-mobile-label', 'Reggit');

export const AppConstants = {
    API_BASE,
    NATIVE_APP_USER_AGENT_TOKEN,
    NATIVE_APP_URL_SCHEME,
    NATIVE_APP_ORDER_TYPE_MOBILE_LABEL,
    API_ENDPOINTS: {
        PRODUCTS: `${API_BASE}/products`,
        ORDERS: `${API_BASE}/orders`,
        MEMBER_PROFILE: `${API_BASE}/member-profile`,
        MEMBER_PROFILE_IMAGE: `${API_BASE}/member-profile/image`,
        MEMBERS_REGISTER: `${API_BASE}/members/register`,
        MEMBERS_WITHDRAW: `${API_BASE}/members/withdraw`,
        MEMBERS_SESSION_CLEAR: `${API_BASE}/members/session/clear`,
        DEVICE_REGISTER: `${API_BASE}/devices/register`,
        DEVICE_SESSION: `${API_BASE}/devices/session`,
        POSTAL_CODE: `${API_BASE}/postal-code`,
        MEMBERS_POINTS: `${API_BASE}/members/points`,
        MEMBER_WALLET_PASS: `${API_BASE}/member-wallet/pass`,
        MEMBER_WALLET_PASS_URL: `${API_BASE}/member-wallet/pass-url`,
        MEMBER_GOOGLE_WALLET_URL: `${API_BASE}/member-wallet/google-url`,
        RESERVATIONS: `${API_BASE}/reservations`,
        RESERVATIONS_AVAILABLE_DATES: `${API_BASE}/reservations/available-dates`,
        ANNOUNCEMENTS: `${API_BASE}/announcements`,
        ANNOUNCEMENT_READ: (id: string | number) => `${API_BASE}/announcements/${id}/read`,
        STAMPS: `${API_BASE}/stamps`,
        MEMBER_COUPONS: `${API_BASE}/member-coupons`,
        MEMBER_COUPON_REDEEM: (id: string | number) => `${API_BASE}/member-coupons/${id}/redeem`,
        DEVICE_COUPONS: `${API_BASE}/device-coupons`,
        DEVICE_COUPON_CLAIM: (id: string | number) => `${API_BASE}/device-coupons/${id}/claim`,
        MEMBER_LOTTERY: `${API_BASE}/member-lottery`,
        MEMBER_LOTTERY_PLAY: (id: string | number) => `${API_BASE}/member-lottery/${id}/play`,
        MEMBER_LOTTERY_NOTIFY_WIN: (id: string | number) => `${API_BASE}/member-lottery/${id}/notify-win`,
        MEMBER_WALLET_SUMMARY: `${API_BASE}/member-wallet/summary`,
        GALLERY: `${API_BASE}/gallery`,
        QUEUE: `${API_BASE}/queue`,
        CURRENT_QUEUE: `${API_BASE}/queue/current`,
        QUEUE_QR: `${API_BASE}/queue/qr`,
    },

    WALLET_RETURN_APP_SCHEME: `${NATIVE_APP_URL_SCHEME}://`,

    HTTP_METHODS: {
        GET: 'GET',
        POST: 'POST',
        PUT: 'PUT',
        DELETE: 'DELETE',
    },

    CONTENT_TYPES: {
        JSON: 'application/json',
    },

    HEADERS: {
        JSON: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
        },
    },

    TABS: {
        HOME: 'home',
        PRODUCTS: 'products',
        ORDERS: 'orders',
        STAMPS: 'stamps',
        COUPONS: 'coupons',
        QUEUE: 'queue',
        MEMBER: 'member',
        DESIGNLAB: 'designlab',
        RESERVATIONS: 'reservations',
        GALLERY: 'gallery',
        ANNOUNCEMENTS: 'announcements',
    },

    CATEGORIES: {
        ALL: 'all',
        FOOD: 'food',
        DRINK: 'drink',
        SIDE: 'side',
    },

    PAYMENT_METHODS: {
        CASH: 'cash',
        STRIPE: 'stripe',
    },

    ORDER_STATUS: {
        PENDING: 'pending',
        CONFIRMED: 'confirmed',
        PREPARING: 'preparing',
        READY: 'ready',
        COMPLETED: 'completed',
        CANCELLED: 'cancelled',
    },
    ORDER_STATUS_LABELS: {
        pending: '受付中',
        confirmed: '確認済み',
        preparing: '準備中',
        ready: '準備完了',
        completed: '完了',
        cancelled: 'キャンセル',
    },
    ORDER_TYPE: {
        MOBILE: 'mobile',
        IN_STORE: 'in_store',
    },
    ORDER_TYPE_LABELS: {
        mobile: 'モバイル注文',
        in_store: '店内注文',
    },

    RESERVATION_STATUS: {
        PENDING: 'pending',
        CONFIRMED: 'confirmed',
        CANCELLED: 'cancelled',
        COMPLETED: 'completed',
    },

    RESERVATION_STATUS_LABELS: {
        PENDING: '予約待ち',
        CONFIRMED: '予約確定',
        CANCELLED: 'キャンセル',
        COMPLETED: '完了',
    },

    RESERVATION_STATUS_ICONS: {
        PENDING: 'fa-clock',
        CONFIRMED: 'fa-check-circle',
        CANCELLED: 'fa-times-circle',
        COMPLETED: 'fa-check-double',
        DEFAULT: 'fa-circle',
    },

    RESERVATION_STATUS_CLASSES: {
        PENDING: 'badge-warning',
        CONFIRMED: 'badge-success',
        CANCELLED: 'badge-danger',
        COMPLETED: 'badge-info',
        DEFAULT: 'badge-info',
    },

    RESERVATION_MESSAGES: {
        LOAD_ERROR: '予約の読み込みに失敗しました',
        CANCEL_SUCCESS: '予約をキャンセルしました',
        CANCEL_ERROR: '予約のキャンセルに失敗しました',
        CREATE_SUCCESS: '予約が完了しました',
        CREATE_ERROR: '予約に失敗しました',
        UPDATE_SUCCESS: '予約を更新しました',
        UPDATE_ERROR: '予約の更新に失敗しました',
        CANCEL_CONFIRM_TITLE: '予約キャンセルの確認',
        CANCEL_CONFIRM_MESSAGE: 'この予約をキャンセルしますか？',
        EMPTY_TITLE: '予約がありません',
        EMPTY_MESSAGE: '新しい予約を作成して、お店を予約しましょう',
        EMPTY_BUTTON: '新規予約を作成',
        ERROR_TITLE: '予約の読み込みに失敗しました',
        ERROR_DEFAULT: 'エラーが発生しました',
        RELOAD_BUTTON: '再読み込み',
    },

    RESERVATION_LABELS: {
        LIST_TITLE: '予約一覧',
        CREATE_BUTTON: '新規予約',
        CREATE_TITLE: '新規予約',
        EDIT_TITLE: '予約を編集',
        EDIT_BUTTON: '編集',
        UPDATE_BUTTON: '更新する',
        DATE_TIME: '予約日時',
        NUMBER_OF_PEOPLE: '人数',
        NOTES: '備考',
        NOTES_OPTIONAL: '備考（任意）',
        SUBMIT_BUTTON: '予約する',
        CANCEL_BUTTON: 'キャンセル',
        RESERVATION_NUMBER: '予約番号',
    },

    RESERVATION_VALIDATION: {
        MIN_PEOPLE: 1,
        MAX_PEOPLE: 10,
        MAX_NOTES_LENGTH: 500,
        TEXTAREA_ROWS: 3,
    },

    DATE_FORMAT: {
        LOCALE: 'ja-JP',
        DATE_OPTIONS: {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        },
        TIME_OPTIONS: {
            hour: '2-digit',
            minute: '2-digit',
        },
    },

    TAB_MOUNTS: {
        PRODUCTS: 'products-data-mount',
        ORDERS: 'orders-data-mount',
        STAMPS: 'stamps-data-mount',
        COUPONS: 'coupons-data-mount',
        QUEUE: 'queue-data-mount',
        RESERVATIONS: 'reservations-data-mount',
        GALLERY: 'gallery-data-mount',
        ANNOUNCEMENTS: 'announcements-data-mount',
        MEMBER: 'member-card-mount',
        PROFILE: 'member-card-mount',
        DESIGNLAB: 'designlab-data-mount',
    },

    ELEMENT_IDS: {
        ANNOUNCEMENTS_SECTION: 'announcementsSection',
        ANNOUNCEMENTS_TOP_BAR: 'announcementsTopBar',
        HOME_SECTION: 'homeSection',
        PRODUCTS_GRID: 'productsGrid',
        CART_BTN: 'cartBtn',
        ACCOUNT_BTN: 'accountBtn',
        MENU_BTN: 'menuBtn',
        CLOSE_MENU_BTN: 'closeMenuBtn',
        SIDE_MENU: 'sideMenu',
        CART_COUNT: 'cartCount',
        CART_ITEMS: 'cartItems',
        CART_SIDEBAR: 'cartSidebar',
        CLOSE_CART_BTN: 'closeCartBtn',
        OVERLAY: 'overlay',
        ORDER_BTN: 'orderBtn',
        IN_STORE_ORDER_BTN: 'inStoreOrderBtn',
        TOTAL_PRICE: 'totalPrice',
        MEMBER_SECTION: 'memberSection',
        PRODUCTS_TAB: 'productsTab',
        ORDERS_TAB: 'ordersTab',
        ORDERS_SECTION: 'ordersSection',
        STAMPS_TAB: 'stampsTab',
        STAMPS_SECTION: 'stampsSection',
        COUPONS_TAB: 'couponsTab',
        COUPONS_SECTION: 'couponsSection',
        QUEUE_TAB: 'queueTab',
        QUEUE_SECTION: 'queueSection',
        MEMBER_TAB: 'memberTab',
        RESERVATIONS_TAB: 'reservationsTab',
        RESERVATIONS_SECTION: 'reservationsSection',
        HOME_TAB: 'homeTab',
        GALLERY_TAB: 'galleryTab',
        GALLERY_SECTION: 'gallerySection',
        ANNOUNCEMENTS_TAB: 'announcementsTab',
        PROFILE_SECTION: 'profileSection',
    },

    CSS_CLASSES: {
        ACTIVE: 'active',
        IN_CART: 'in-cart',
        ADDED: 'added',
        OPEN: 'open',
        SKELETON: 'skeleton',
    },

    // Legacy constants.js overwrote the earlier SKELETON_COUNT; keep the same final value.
    SKELETON_COUNT: {
        PRODUCTS: 6,
    },

    ANIMATION_DURATION: {
        CART_NOTIFICATION: 200,
        BARCODE_DELAY: 100,
        /** クーポン使用後のバーコード／QR表示の有効時間（5分） */
        COUPON_CODE_DISPLAY_MS: 5 * 60 * 1000,
    },

    POINTS: {
        EARN_RATE: 100,
    },

    MESSAGES: {
        CART_OPEN_TOOLTIP: 'カートを開く',
        CART_REMOVE_TOOLTIP: '数量変更はカートでできます',
        CART_REMOVE_BUTTON: 'カートから削除',
        CART_ADD_TOOLTIP: 'カートに追加',
        ORDER_SUCCESS: '注文が完了しました！',
        ORDER_ERROR: '注文に失敗しました',
        ORDER_ERROR_DEFAULT: 'しばらくしてから再度お試しください',
    },

    DEFAULT_VALUES: {
        USER_ID: 'test-user',
    },
};

export type AppConstantsType = typeof AppConstants;

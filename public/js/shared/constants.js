const API_BASE = (typeof window !== 'undefined' && window.__API_BASE__) ? window.__API_BASE__ : '/api';

const AppConstants = {
    API_BASE,
    API_ENDPOINTS: {
        PRODUCTS: `${API_BASE}/products`,
        ORDERS: `${API_BASE}/orders`,
        MEMBERS_ME: `${API_BASE}/members/me`,
        MEMBERS_REGISTER: `${API_BASE}/members/register`,
        POSTAL_CODE: `${API_BASE}/postal-code`,
        MEMBERS_POINTS: `${API_BASE}/members/points`,
        MEMBERS_WALLET_PASS: `${API_BASE}/members/me/wallet-pass`,
        MEMBERS_WALLET_PASS_URL: `${API_BASE}/members/me/wallet-pass-url`,
        RESERVATIONS: `${API_BASE}/reservations`,
        RESERVATIONS_AVAILABLE_DATES: `${API_BASE}/reservations/available-dates`,
        ANNOUNCEMENTS: `${API_BASE}/announcements`,
        ANNOUNCEMENT_READ: (id) => `${API_BASE}/announcements/${id}/read`,
        STAMPS: `${API_BASE}/stamps`,
        MY_COUPONS: `${API_BASE}/members/me/coupons`,
        GALLERY: `${API_BASE}/gallery`,
        QUEUE: `${API_BASE}/queue`,
        QUEUE_ME: `${API_BASE}/queue/me`,
        QUEUE_QR: `${API_BASE}/queue/qr`,
    },
    
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
            'Accept': 'application/json',
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
        RESERVATIONS: 'reservations',
        GALLERY: 'gallery',
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
        mobile: 'モバイルオーダー',
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
    
    SKELETON_COUNT: {
        PRODUCTS: 6,
        RESERVATIONS: 6,
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
    
    ELEMENT_IDS: {
        ANNOUNCEMENTS_SECTION: 'announcementsSection',
        HOME_SECTION: 'homeSection',
        PRODUCTS_GRID: 'productsGrid',
        CART_BTN: 'cartBtn',
        CART_COUNT: 'cartCount',
        CART_ITEMS: 'cartItems',
        CART_SIDEBAR: 'cartSidebar',
        CLOSE_CART_BTN: 'closeCartBtn',
        OVERLAY: 'overlay',
        ORDER_BTN: 'orderBtn',
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
    },
    
    CSS_CLASSES: {
        ACTIVE: 'active',
        IN_CART: 'in-cart',
        ADDED: 'added',
        OPEN: 'open',
        SKELETON: 'skeleton',
    },
    
    SKELETON_COUNT: {
        PRODUCTS: 6,
    },
    
    ANIMATION_DURATION: {
        CART_NOTIFICATION: 200,
        BARCODE_DELAY: 100,
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

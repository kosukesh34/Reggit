const AppConstants = {
    API_ENDPOINTS: {
        PRODUCTS: '/api/products',
        ORDERS: '/api/orders',
        MEMBERS_ME: '/api/members/me',
        MEMBERS_REGISTER: '/api/members/register',
        RESERVATIONS: '/api/reservations',
        RESERVATIONS_AVAILABLE_DATES: '/api/reservations/available-dates',
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
        PRODUCTS: 'products',
        MEMBER: 'member',
        RESERVATIONS: 'reservations',
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
        COMPLETED: 'completed',
        CANCELLED: 'cancelled',
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
        MEMBER_TAB: 'memberTab',
        RESERVATIONS_TAB: 'reservationsTab',
        RESERVATIONS_SECTION: 'reservationsSection',
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
        CART_REMOVE_TOOLTIP: 'カートから削除するには、カートを開いてください',
        CART_ADD_TOOLTIP: 'カートに追加',
        ORDER_SUCCESS: '注文が完了しました！',
    },
    
    DEFAULT_VALUES: {
        USER_ID: 'test-user',
    },
};


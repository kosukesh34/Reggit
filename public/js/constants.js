const AppConstants = {
    API_ENDPOINTS: {
        PRODUCTS: '/api/products',
        ORDERS: '/api/orders',
        MEMBERS_ME: '/api/members/me',
        MEMBERS_REGISTER: '/api/members/register',
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
};


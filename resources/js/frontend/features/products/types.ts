export type ProductOption = {
    id: number;
    name: string;
    price_delta?: number;
    is_default?: boolean;
};

export type ProductOptionGroup = {
    id: number;
    name: string;
    is_required?: boolean;
    selection_type?: string;
    max_select?: number;
    options?: ProductOption[];
};

export type Product = {
    id: number | string;
    name?: string;
    price?: number | string;
    category?: string;
    image_url?: string;
    description?: string;
    option_groups?: ProductOptionGroup[];
    [key: string]: unknown;
};

export type ProductCategory = {
    key: string;
    label: string;
    icon: string;
    count: number;
    sort?: number;
};

export const LIFF_PRODUCTS_UPDATED = 'liff-products-updated';
export const LIFF_CART_UPDATED = 'liff-cart-updated';
export const LIFF_OPEN_PAYMENT = 'liff-open-payment';
export const LIFF_COUPONS_UPDATED = 'liff-coupons-updated';
export const LIFF_COUPONS_RELOAD = 'liff-coupons-reload';

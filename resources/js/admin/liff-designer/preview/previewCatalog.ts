import type { DesignerConfig } from '../catalog/types';

export type PreviewProduct = {
    id: string | number;
    name: string;
    price: string | number;
    category: string;
    image_url?: string | null;
};

export type PreviewCoupon = {
    id: string | number;
    title: string;
    meta: string;
    image_url?: string | null;
    available: boolean;
};

export type PreviewStamp = {
    id: string | number;
    name: string;
    required: number;
    reward: string;
    image_url?: string | null;
};

export type DesignerPreviewData = {
    products: PreviewProduct[];
    coupons: PreviewCoupon[];
    stamps: PreviewStamp[];
    member: Record<string, unknown> | null;
};

const FALLBACK_PRODUCTS: PreviewProduct[] = [
    { id: 'sample-drink-1', name: 'アイスラテ', price: 480, category: 'drink', image_url: '/images/liff/menu-iced-latte.jpg' },
    { id: 'sample-drink-2', name: '抹茶ラテ', price: 520, category: 'drink', image_url: '/images/liff/menu-matcha.jpg' },
    { id: 'sample-food-1', name: 'クロワッサンサンド', price: 590, category: 'food', image_url: '/images/liff/menu-croissant.jpg' },
    { id: 'sample-dessert-1', name: 'ベリーチーズケーキ', price: 540, category: 'dessert', image_url: '/images/liff/menu-cheesecake.jpg' },
];

const FALLBACK_COUPONS: PreviewCoupon[] = [
    { id: 'c1', title: 'ドリンク1杯無料', meta: '会員限定', available: true, image_url: '/images/liff/coupon-drink.jpg' },
    { id: 'c2', title: '会計から10%OFF', meta: '平日限定', available: true, image_url: '/images/liff/coupon-dessert.jpg' },
    { id: 'c3', title: 'デザートプレゼント', meta: '使用済み', available: false },
];

const CATEGORY_LABELS: Record<string, string> = {
    food: 'フード',
    drink: 'ドリンク',
    dessert: 'デザート',
    side: 'サイドメニュー',
    other: 'その他',
};

export function readPreviewData(config: DesignerConfig | undefined): DesignerPreviewData {
    const raw = (config?.previewData || {}) as Partial<DesignerPreviewData>;
    const products = Array.isArray(raw.products) ? raw.products : [];
    const coupons = Array.isArray(raw.coupons) ? raw.coupons : [];
    const stamps = Array.isArray(raw.stamps) ? raw.stamps : [];
    return {
        products: products.length > 0 ? products : FALLBACK_PRODUCTS,
        coupons: coupons.length > 0 ? coupons : FALLBACK_COUPONS,
        stamps,
        member: raw.member && typeof raw.member === 'object' ? raw.member : null,
    };
}

export function formatPrice(value: string | number): string {
    const n = typeof value === 'number' ? value : Number(value);
    if (Number.isFinite(n)) {
        return Math.round(n).toLocaleString('ja-JP');
    }
    return String(value);
}

export function categoryLabel(key: string): string {
    return CATEGORY_LABELS[key] || key;
}

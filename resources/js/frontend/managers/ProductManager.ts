import { ApiClient } from '../../shared/utils/apiClient';
import { AppConstants } from '../../shared/constants';
import { isDemoDataMode, isFixedUiShell } from '../demo/demoMode';

type Product = {
    id: number | string;
    name?: string;
    price?: number | string;
    category?: string;
    image_url?: string;
    option_groups?: Array<{
        id: number;
        name: string;
        is_required?: boolean;
        selection_type?: string;
        max_select?: number;
        options?: Array<{ id: number; name: string; price_delta?: number }>;
    }>;
    [key: string]: unknown;
};

type Category = {
    key: string;
    label: string;
    icon: string;
    count: number;
    sort?: number;
};

type ProductCatalog = {
    products: Product[];
    categories: Category[];
};

export class ProductManager {
    products: Product[];
    categories: Category[];
    currentCategory: string;
    apiClient: ApiClient;

    constructor() {
        this.products = [];
        this.categories = [];
        this.currentCategory = 'all';
        this.apiClient = new ApiClient();
    }

    async loadProducts(): Promise<Product[]> {
        if (isDemoDataMode()) {
            return this._applySampleCatalog();
        }

        try {
            const result = await this.apiClient.get(AppConstants.API_ENDPOINTS.PRODUCTS);
            if (result.success !== true) {
                throw new Error(result.error || 'Failed to load products');
            }

            const payload = result.data;
            if (payload && (payload as { products?: Product[] }).products
                && Array.isArray((payload as { products: Product[] }).products)) {
                const data = payload as { products: Product[]; categories?: Category[] };
                this.products = data.products;
                this.categories = data.categories || [];
            } else if (Array.isArray(payload)) {
                this.products = payload as Product[];
                this.categories = this._buildCategoriesFromProducts(payload as Product[]);
            } else {
                throw new Error('Invalid product data format');
            }

            if (this.products.length === 0 && this._isFixedUi()) {
                return this._applySampleCatalog();
            }

            this._fillMissingProductImages();
            return this.products;
        } catch (error) {
            if (this._isFixedUi()) {
                return this._applySampleCatalog();
            }
            throw error;
        }
    }

    _isFixedUi(): boolean {
        return isFixedUiShell();
    }

    _applySampleCatalog(): Product[] {
        const catalog = this.getSampleCatalog();
        this.products = catalog.products;
        this.categories = catalog.categories;
        return this.products;
    }

    _fillMissingProductImages(): void {
        const samples = ProductManager.buildBuiltinSampleCatalog().products;
        if (samples.length === 0) {
            return;
        }
        this.products = this.products.map((product, index) => {
            if (typeof product.image_url === 'string' && product.image_url !== '') {
                return product;
            }
            const byName = samples.find((sample) => sample.name === product.name);
            const fallback = byName || samples[index % samples.length];
            return {
                ...product,
                image_url: fallback.image_url,
            };
        });
    }

    getSampleCatalog(): ProductCatalog {
        if (typeof window !== 'undefined'
            && window.app
            && typeof (window.app as { getDesignerSampleProducts?: () => ProductCatalog }).getDesignerSampleProducts === 'function') {
            return (window.app as { getDesignerSampleProducts: () => ProductCatalog }).getDesignerSampleProducts();
        }
        return ProductManager.buildBuiltinSampleCatalog();
    }

    static buildBuiltinSampleCatalog(): ProductCatalog {
        const products: Product[] = [
            {
                id: 'sample-drink-1',
                name: 'アイスラテ',
                price: 480,
                category: 'drink',
                image_url: '/images/liff/menu-iced-latte.jpg',
                is_sample_preview: true,
            },
            {
                id: 'sample-drink-2',
                name: '抹茶ラテ',
                price: 520,
                category: 'drink',
                image_url: '/images/liff/menu-matcha.jpg',
                is_sample_preview: true,
            },
            {
                id: 'sample-drink-3',
                name: 'カプチーノ',
                price: 450,
                category: 'drink',
                image_url: '/images/liff/menu-cappuccino.jpg',
                is_sample_preview: true,
                option_groups: [
                    {
                        id: 1,
                        name: 'サイズ',
                        is_required: true,
                        selection_type: 'single',
                        options: [
                            { id: 11, name: 'レギュラー', price_delta: 0 },
                            { id: 12, name: 'ラージ', price_delta: 80 },
                        ],
                    },
                    {
                        id: 2,
                        name: 'ミルク',
                        is_required: false,
                        selection_type: 'single',
                        options: [
                            { id: 21, name: '普通', price_delta: 0 },
                            { id: 22, name: 'オーツミルク', price_delta: 50 },
                        ],
                    },
                ],
            },
            {
                id: 'sample-drink-4',
                name: 'フレッシュオレンジジュース',
                price: 420,
                category: 'drink',
                image_url: '/images/liff/menu-orange-juice.jpg',
                is_sample_preview: true,
            },
            {
                id: 'sample-food-1',
                name: 'クロワッサンサンド',
                price: 590,
                category: 'food',
                image_url: '/images/liff/menu-croissant.jpg',
                is_sample_preview: true,
            },
            {
                id: 'sample-food-2',
                name: 'アボカドトースト',
                price: 680,
                category: 'food',
                image_url: '/images/liff/menu-avocado-toast.jpg',
                is_sample_preview: true,
            },
            {
                id: 'sample-dessert-1',
                name: 'ベリーチーズケーキ',
                price: 540,
                category: 'dessert',
                image_url: '/images/liff/menu-cheesecake.jpg',
                is_sample_preview: true,
            },
            {
                id: 'sample-dessert-2',
                name: 'チョコチップクッキー',
                price: 280,
                category: 'dessert',
                image_url: '/images/liff/menu-cookie.jpg',
                is_sample_preview: true,
            },
        ];
        return {
            products,
            categories: [
                { key: 'all', label: 'すべて', icon: 'fa-th-large', count: products.length, sort: 0 },
                { key: 'drink', label: 'ドリンク', icon: 'fa-coffee', count: 4, sort: 1 },
                { key: 'food', label: 'フード', icon: 'fa-utensils', count: 2, sort: 2 },
                { key: 'dessert', label: 'デザート', icon: 'fa-ice-cream', count: 2, sort: 3 },
            ],
        };
    }

    _buildCategoriesFromProducts(products: Product[]): Category[] {
        const countMap: Record<string, number> = {};
        products.forEach((p) => {
            const cat = p.category || 'other';
            countMap[cat] = (countMap[cat] || 0) + 1;
        });
        const catLabels: Record<string, string> = {
            food: 'フード',
            drink: 'ドリンク',
            dessert: 'デザート',
            side: 'サイドメニュー',
            other: 'その他',
        };
        const catIcons: Record<string, string> = {
            food: 'fa-utensils',
            drink: 'fa-coffee',
            dessert: 'fa-ice-cream',
            side: 'fa-french-fries',
            other: 'fa-ellipsis-h',
        };
        const categories: Category[] = [
            { key: 'all', label: 'すべて', icon: 'fa-th-large', count: products.length, sort: 0 },
        ];
        Object.entries(countMap).forEach(([key, count]) => {
            categories.push({
                key,
                label: catLabels[key] || key,
                icon: catIcons[key] || 'fa-circle',
                count,
            });
        });
        return categories;
    }

    getCategories(): Category[] {
        return this.categories;
    }

    getProductsByCategory(category: string): Product[] {
        if (!category || category === 'all') {
            return this.products;
        }
        return this.products.filter((product) => product.category === category);
    }

    getProductsGroupedByCategory(): Record<string, Product[]> {
        const groups: Record<string, Product[]> = {};
        this.products.forEach((product) => {
            const cat = product.category || 'other';
            if (!groups[cat]) {
                groups[cat] = [];
            }
            groups[cat].push(product);
        });
        return groups;
    }

    getProductById(productId: number | string): Product | undefined {
        return this.products.find((product) => String(product.id) === String(productId));
    }

    setCurrentCategory(category: string): void {
        this.currentCategory = category;
    }

    getCurrentCategory(): string {
        return this.currentCategory;
    }

    getProductSkeleton(count = 6): string {
        return Array(count)
            .fill(0)
            .map(
                () => `
            <div class="product-card skeleton">
                <div class="product-image-wrapper skeleton-image">
                    <div class="skeleton-shimmer"></div>
                </div>
                <div class="product-info">
                    <div class="skeleton-line skeleton-title"></div>
                    <div class="skeleton-line skeleton-price"></div>
                    <div class="skeleton-line skeleton-button"></div>
                </div>
            </div>
        `,
            )
            .join('');
    }
}

import { ApiClient } from '../../shared/utils/apiClient';
import { AppConstants } from '../../shared/constants';

type Product = {
    id: number | string;
    name?: string;
    price?: number | string;
    category?: string;
    image_url?: string;
    [key: string]: unknown;
};

type Category = {
    key: string;
    label: string;
    icon: string;
    count: number;
    sort?: number;
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

        return this.products;
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

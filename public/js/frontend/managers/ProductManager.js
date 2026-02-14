class ProductManager {
    constructor() {
        this.products = [];
        this.categories = [];
        this.currentCategory = 'all';
        this.apiClient = new ApiClient();
    }

    async loadProducts() {
        const result = await this.apiClient.get(AppConstants.API_ENDPOINTS.PRODUCTS);

        if (!result.success) {
            throw new Error(result.error || 'Failed to load products');
        }

        // 新しいレスポンス形式: { products: [...], categories: [...] }
        // 旧形式: [...] (配列直接)
        if (result.data && result.data.products && Array.isArray(result.data.products)) {
            this.products = result.data.products;
            this.categories = result.data.categories || [];
        } else if (Array.isArray(result.data)) {
            this.products = result.data;
            this.categories = this._buildCategoriesFromProducts(result.data);
        } else {
            throw new Error('Invalid product data format');
        }

        return this.products;
    }

    /** 旧APIフォールバック: 商品データからカテゴリを生成 */
    _buildCategoriesFromProducts(products) {
        const countMap = {};
        products.forEach(p => {
            const cat = p.category || 'other';
            countMap[cat] = (countMap[cat] || 0) + 1;
        });
        const catLabels = {
            food: 'フード', drink: 'ドリンク', dessert: 'デザート',
            side: 'サイドメニュー', other: 'その他'
        };
        const catIcons = {
            food: 'fa-utensils', drink: 'fa-coffee', dessert: 'fa-ice-cream',
            side: 'fa-french-fries', other: 'fa-ellipsis-h'
        };
        const categories = [
            { key: 'all', label: 'すべて', icon: 'fa-th-large', count: products.length, sort: 0 }
        ];
        Object.entries(countMap).forEach(([key, count]) => {
            categories.push({
                key,
                label: catLabels[key] || key,
                icon: catIcons[key] || 'fa-circle',
                count
            });
        });
        return categories;
    }

    getCategories() {
        return this.categories;
    }

    getProductsByCategory(category) {
        if (!category || category === 'all') {
            return this.products;
        }
        return this.products.filter(product => product.category === category);
    }

    /** カテゴリごとにグループ化して返す */
    getProductsGroupedByCategory() {
        const groups = {};
        this.products.forEach(product => {
            const cat = product.category || 'other';
            if (!groups[cat]) groups[cat] = [];
            groups[cat].push(product);
        });
        return groups;
    }

    getProductById(productId) {
        return this.products.find(product => product.id === productId);
    }

    setCurrentCategory(category) {
        this.currentCategory = category;
    }

    getCurrentCategory() {
        return this.currentCategory;
    }

    getProductSkeleton(count = 6) {
        return Array(count).fill(0).map(() => `
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
        `).join('');
    }
}

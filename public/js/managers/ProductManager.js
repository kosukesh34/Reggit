class ProductManager {
    constructor() {
        this.products = [];
        this.currentCategory = AppConstants.CATEGORIES.ALL;
        this.apiClient = new ApiClient();
    }

    async loadProducts() {
        const result = await this.apiClient.get(AppConstants.API_ENDPOINTS.PRODUCTS);
        
        if (!result.success) {
            throw new Error(result.error || 'Failed to load products');
        }

        if (!Array.isArray(result.data)) {
            throw new Error('Invalid product data format');
        }

        this.products = result.data;
        return this.products;
    }

    getProductsByCategory(category) {
        if (category === AppConstants.CATEGORIES.ALL) {
            return this.products;
        }
        return this.products.filter(product => product.category === category);
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

    getProductSkeleton(count = AppConstants.SKELETON_COUNT.PRODUCTS) {
        return Array(count).fill(0).map(() => `
            <div class="product-card ${AppConstants.CSS_CLASSES.SKELETON}">
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


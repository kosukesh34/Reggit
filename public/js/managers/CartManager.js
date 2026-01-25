class CartManager {
    constructor() {
        this.items = [];
        this.apiClient = new ApiClient();
    }

    addItem(product) {
        const existingItem = this.items.find(item => item.id === product.id);
        
        if (existingItem) {
            existingItem.quantity += 1;
        } else {
            this.items.push({
                id: product.id,
                name: product.name,
                price: product.price,
                image_url: product.image_url,
                quantity: 1,
            });
        }
    }

    removeItem(productId) {
        this.items = this.items.filter(item => item.id !== productId);
    }

    updateQuantity(productId, change) {
        const item = this.items.find(item => item.id === productId);
        if (!item) return false;

        item.quantity += change;
        
        if (item.quantity <= 0) {
            this.removeItem(productId);
            return false;
        }
        
        return true;
    }

    getItem(productId) {
        return this.items.find(item => item.id === productId);
    }

    getTotalCount() {
        return this.items.reduce((sum, item) => sum + item.quantity, 0);
    }

    getTotalAmount() {
        return this.items.reduce((sum, item) => sum + (parseInt(item.price) * item.quantity), 0);
    }

    clear() {
        this.items = [];
    }

    isEmpty() {
        return this.items.length === 0;
    }

    getItemsForOrder() {
        return this.items.map(item => ({
            product_id: item.id,
            quantity: item.quantity,
        }));
    }
}


class CartManager {
    constructor() {
        this.items = [];
        this.apiClient = new ApiClient();
    }

    /**
     * カートに商品を追加する。
     * オプション（サイズ・トッピング・セット等）が異なる同一商品は別ラインとして扱う。
     *
     * @param {object} product 商品（id, name, price, image_url）
     * @param {object} [opts] { options: [{group_name, option_id, name, price_delta}], quantity, notes }
     */
    addItem(product, opts) {
        opts = opts || {};
        const options = Array.isArray(opts.options) ? opts.options : [];
        const quantity = (opts.quantity && opts.quantity > 0) ? opts.quantity : 1;
        const notes = (opts.notes || '').trim();

        const optionIds = options.map(o => Number(o.option_id)).filter(n => !Number.isNaN(n)).sort((a, b) => a - b);
        const basePrice = parseInt(product.price, 10) || 0;
        const optionsDelta = options.reduce((sum, o) => sum + (parseInt(o.price_delta, 10) || 0), 0);
        const unitPrice = basePrice + optionsDelta;
        const lineKey = 'p' + product.id + (optionIds.length ? ':' + optionIds.join('-') : '') + (notes ? ':n' + this._hash(notes) : '');

        const existing = this.items.find(item => item.lineKey === lineKey);
        if (existing) {
            existing.quantity += quantity;
            return;
        }

        this.items.push({
            lineKey,
            id: product.id,
            name: product.name,
            basePrice,
            price: unitPrice,
            image_url: product.image_url,
            quantity,
            options,
            optionIds,
            notes,
        });
    }

    removeItem(lineKey) {
        this.items = this.items.filter(item => item.lineKey !== lineKey);
    }

    updateQuantity(lineKey, change) {
        const item = this.items.find(item => item.lineKey === lineKey);
        if (!item) return false;

        item.quantity += change;

        if (item.quantity <= 0) {
            this.removeItem(lineKey);
            return false;
        }

        return true;
    }

    getItem(lineKey) {
        return this.items.find(item => item.lineKey === lineKey);
    }

    /** 指定商品IDのカート内合計数量（オプション違いのラインも合算）。 */
    getQuantityForProduct(productId) {
        return this.items
            .filter(item => item.id === productId)
            .reduce((sum, item) => sum + item.quantity, 0);
    }

    getTotalCount() {
        return this.items.reduce((sum, item) => sum + item.quantity, 0);
    }

    getTotalAmount() {
        return this.items.reduce((sum, item) => sum + (parseInt(item.price, 10) * item.quantity), 0);
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
            option_ids: item.optionIds || [],
            notes: item.notes || '',
        }));
    }

    _hash(str) {
        let h = 0;
        for (let i = 0; i < str.length; i++) {
            h = ((h << 5) - h + str.charCodeAt(i)) | 0;
        }
        return Math.abs(h).toString(36);
    }
}

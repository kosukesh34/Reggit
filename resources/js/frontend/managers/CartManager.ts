import { ApiClient } from '../../shared/utils/apiClient';

export type CartOption = {
    group_name?: string;
    option_id: number | string;
    name: string;
    price_delta?: number | string;
};

export type CartProduct = {
    id: number | string;
    name: string;
    price: number | string;
    image_url?: string;
};

export type CartAddOpts = {
    options?: CartOption[];
    quantity?: number;
    notes?: string;
};

export type CartItem = {
    lineKey: string;
    id: number | string;
    name: string;
    basePrice: number;
    price: number;
    image_url?: string;
    quantity: number;
    options: CartOption[];
    optionIds: number[];
    notes: string;
};

export class CartManager {
    items: CartItem[];
    apiClient: ApiClient;

    constructor() {
        this.items = [];
        this.apiClient = new ApiClient();
    }

    addItem(product: CartProduct, opts?: CartAddOpts): void {
        const resolvedOpts = opts || {};
        const options = Array.isArray(resolvedOpts.options) ? resolvedOpts.options : [];
        const quantity = resolvedOpts.quantity && resolvedOpts.quantity > 0 ? resolvedOpts.quantity : 1;
        const notes = (resolvedOpts.notes || '').trim();

        const optionIds = options
            .map((o) => Number(o.option_id))
            .filter((n) => !Number.isNaN(n))
            .sort((a, b) => a - b);
        const basePrice = Number(product.price) || 0;
        const optionsDelta = options.reduce((sum, o) => sum + (Number(o.price_delta) || 0), 0);
        const unitPrice = basePrice + optionsDelta;
        const lineKey =
            'p'
            + product.id
            + (optionIds.length ? ':' + optionIds.join('-') : '')
            + (notes ? ':n' + this._hash(notes) : '');

        const existing = this.items.find((item) => item.lineKey === lineKey);
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

    removeItem(lineKey: string): void {
        this.items = this.items.filter((item) => item.lineKey !== lineKey);
    }

    updateQuantity(lineKey: string, change: number): boolean {
        const item = this.items.find((entry) => entry.lineKey === lineKey);
        if (!item) {
            return false;
        }

        item.quantity += change;

        if (item.quantity <= 0) {
            this.removeItem(lineKey);
            return false;
        }

        return true;
    }

    getItem(lineKey: string): CartItem | undefined {
        return this.items.find((item) => item.lineKey === lineKey);
    }

    getQuantityForProduct(productId: number | string): number {
        return this.items
            .filter((item) => item.id === productId)
            .reduce((sum, item) => sum + item.quantity, 0);
    }

    /** 数量調整用の主ライン（オプションなし優先、なければ末尾） */
    getAdjustableLineKey(productId: number | string): string | null {
        const items = this.items.filter((item) => String(item.id) === String(productId));
        if (items.length === 0) {
            return null;
        }
        const plain = items.find((item) => !item.optionIds || item.optionIds.length === 0);
        return (plain || items[items.length - 1]).lineKey;
    }

    getTotalCount(): number {
        return this.items.reduce((sum, item) => sum + item.quantity, 0);
    }

    getTotalAmount(): number {
        return this.items.reduce((sum, item) => sum + (Number(item.price) || 0) * item.quantity, 0);
    }

    clear(): void {
        this.items = [];
    }

    isEmpty(): boolean {
        return this.items.length === 0;
    }

    getItemsForOrder(): Array<{
        product_id: number | string;
        quantity: number;
        option_ids: number[];
        notes: string;
    }> {
        return this.items.map((item) => ({
            product_id: item.id,
            quantity: item.quantity,
            option_ids: item.optionIds || [],
            notes: item.notes || '',
        }));
    }

    _hash(str: string): string {
        let h = 0;
        for (let i = 0; i < str.length; i++) {
            h = ((h << 5) - h + str.charCodeAt(i)) | 0;
        }
        return Math.abs(h).toString(36);
    }
}

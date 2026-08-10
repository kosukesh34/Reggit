import React, {
    createContext,
    useCallback,
    useContext,
    useEffect,
    useMemo,
    useState,
} from 'react';
import type { CartItem, CartManager } from '../../managers/CartManager';
import { LIFF_CART_UPDATED } from '../products/types';

type CartContextValue = {
    items: CartItem[];
    totalCount: number;
    totalAmount: number;
    isEmpty: boolean;
    refresh: () => void;
    updateQuantity: (lineKey: string, change: number) => void;
    removeFromCart: (lineKey: string) => void;
};

const CartContext = createContext<CartContextValue | null>(null);

function getCartManager(): CartManager | null {
    const app = window.app as { cartManager?: CartManager } | undefined;
    return app?.cartManager ?? null;
}

function snapshot(): Omit<CartContextValue, 'refresh' | 'updateQuantity' | 'removeFromCart'> {
    const cm = getCartManager();
    if (cm === null) {
        return { items: [], totalCount: 0, totalAmount: 0, isEmpty: true };
    }
    return {
        items: [...cm.items],
        totalCount: cm.getTotalCount(),
        totalAmount: cm.getTotalAmount(),
        isEmpty: cm.isEmpty(),
    };
}

export function CartProvider({ children }: { children: React.ReactNode }): JSX.Element {
    const [state, setState] = useState(snapshot);

    const refresh = useCallback((): void => {
        setState(snapshot());
    }, []);

    useEffect(() => {
        refresh();
        const onCart = (): void => {
            refresh();
        };
        const onReady = (): void => {
            refresh();
        };
        document.addEventListener(LIFF_CART_UPDATED, onCart);
        document.addEventListener('liff-ready', onReady);
        return () => {
            document.removeEventListener(LIFF_CART_UPDATED, onCart);
            document.removeEventListener('liff-ready', onReady);
        };
    }, [refresh]);

    const updateQuantity = useCallback((lineKey: string, change: number): void => {
        const app = window.app as {
            updateQuantity?: (lineKey: string, change: number) => void;
        } | undefined;
        if (typeof app?.updateQuantity === 'function') {
            app.updateQuantity(lineKey, change);
            return;
        }
        const cm = getCartManager();
        cm?.updateQuantity(lineKey, change);
        document.dispatchEvent(new CustomEvent(LIFF_CART_UPDATED));
        refresh();
    }, [refresh]);

    const removeFromCart = useCallback((lineKey: string): void => {
        const app = window.app as {
            removeFromCart?: (lineKey: string) => void;
        } | undefined;
        if (typeof app?.removeFromCart === 'function') {
            app.removeFromCart(lineKey);
            return;
        }
        const cm = getCartManager();
        cm?.removeItem(lineKey);
        document.dispatchEvent(new CustomEvent(LIFF_CART_UPDATED));
        refresh();
    }, [refresh]);

    const value = useMemo(
        () => ({
            ...state,
            refresh,
            updateQuantity,
            removeFromCart,
        }),
        [state, refresh, updateQuantity, removeFromCart],
    );

    return <CartContext.Provider value={value}>{children}</CartContext.Provider>;
}

export function useCart(): CartContextValue {
    const ctx = useContext(CartContext);
    if (ctx === null) {
        throw new Error('useCart must be used within CartProvider');
    }
    return ctx;
}

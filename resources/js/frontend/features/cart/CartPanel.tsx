import React, { useEffect } from 'react';
import { DomHelper } from '../../../shared/utils/domHelper';
import { CartItemRow } from './CartItemRow';
import { useCart } from './CartContext';

/**
 * カートサイドバーのアイテム一覧 + 合計・ボタン状態の同期。
 * #cartItems を React で描画する（FrontendChrome 内に直接埋め込む）。
 */
export function CartPanel(): JSX.Element {
    const { items, isEmpty, totalAmount, totalCount, updateQuantity, removeFromCart } = useCart();

    useEffect(() => {
        const countEl = document.getElementById('cartCount');
        if (countEl !== null) {
            countEl.textContent = String(totalCount);
        }
        const cartBtn = document.getElementById('cartBtn');
        if (cartBtn !== null) {
            cartBtn.classList.toggle('has-items', totalCount > 0);
        }
        const totalPrice = document.getElementById('totalPrice');
        if (totalPrice !== null) {
            totalPrice.textContent = DomHelper.formatPrice(totalAmount);
        }
        const orderBtn = document.getElementById('orderBtn') as HTMLButtonElement | null;
        const inStoreOrderBtn = document.getElementById('inStoreOrderBtn') as HTMLButtonElement | null;
        const orderMode = String(window.__ORDER_MODE__ || 'both');
        if (orderBtn !== null) {
            orderBtn.disabled = isEmpty || orderMode === 'in_store_only';
        }
        if (inStoreOrderBtn !== null) {
            inStoreOrderBtn.disabled = isEmpty || orderMode === 'mobile_only';
        }
    }, [items, isEmpty, totalAmount, totalCount]);

    if (isEmpty) {
        return <div className="empty-cart" data-react-cart="1">カートは空です</div>;
    }

    return (
        <div data-react-cart="1">
            {items.map((item) => (
                <CartItemRow
                    key={item.lineKey}
                    item={item}
                    onUpdateQuantity={updateQuantity}
                    onRemove={removeFromCart}
                />
            ))}
        </div>
    );
}

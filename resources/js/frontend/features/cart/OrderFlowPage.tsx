import React, { useEffect } from 'react';
import { DomHelper } from '../../../shared/utils/domHelper';
import { CartItemRow } from './CartItemRow';
import { useCart } from './CartContext';

export type OrderFlowStep = 'cart' | 'confirm' | 'complete';

type OrderFlowPageProps = {
    step: OrderFlowStep;
};

type AppOrderFlow = {
    lastOrderCompleteMessage?: string | null;
    openOrderFlow?: (step: OrderFlowStep) => void;
    switchTab?: (tab: string) => void;
    loadOrdersTab?: (options?: { silent?: boolean }) => void;
    checkoutManager?: {
        isMobileOrderEnabled?: () => boolean;
        isInStoreOrderEnabled?: () => boolean;
        placeMobileOrder?: (paymentMethod?: 'stripe' | 'cash') => void;
        placeInStoreOrder?: () => void;
    };
};

function getApp(): AppOrderFlow | undefined {
    return window.app as AppOrderFlow | undefined;
}

/**
 * 注文フロー（#order/cart|confirm|complete）。app.renderOrderFlowPage の JSX 版。
 */
export function OrderFlowPage({ step }: OrderFlowPageProps): JSX.Element {
    const { items, isEmpty, totalAmount, updateQuantity, removeFromCart } = useCart();
    const app = getApp();
    const total = DomHelper.formatPrice(totalAmount);
    const canOrder = !isEmpty;
    const mobileEnabled = app?.checkoutManager?.isMobileOrderEnabled?.() === true;
    const inStoreEnabled = app?.checkoutManager?.isInStoreOrderEnabled?.() === true;

    useEffect(() => {
        if (step !== 'complete') {
            return;
        }
        const loadOrdersTab = getApp()?.loadOrdersTab;
        if (typeof loadOrdersTab === 'function') {
            void loadOrdersTab({ silent: true });
        }
    }, [step]);

    if (step === 'complete') {
        const message = app?.lastOrderCompleteMessage || 'ご注文ありがとうございました';
        return (
            <div className="liff-flow-page order-flow-complete">
                <div className="order-flow-complete__icon">
                    <i className="fas fa-check-circle" aria-hidden="true" />
                </div>
                <h2>注文を受け付けました</h2>
                <div className="order-flow-complete__live" data-order-complete-live="" />
                <p className="order-flow-complete__message">{message}</p>
                <div className="liff-flow-actions">
                    <button
                        type="button"
                        className="btn btn-primary"
                        onClick={() => app?.switchTab?.('orders')}
                    >
                        注文状況を確認
                    </button>
                    <button
                        type="button"
                        className="btn btn-secondary"
                        onClick={() => app?.switchTab?.('home')}
                    >
                        ホームへ
                    </button>
                </div>
            </div>
        );
    }

    const itemsBlock = isEmpty ? (
        <div className="empty-cart">カートは空です</div>
    ) : (
        items.map((item) => (
            <CartItemRow
                key={item.lineKey}
                item={item}
                onUpdateQuantity={updateQuantity}
                onRemove={removeFromCart}
            />
        ))
    );

    if (step === 'cart') {
        return (
            <div className="liff-flow-page order-flow-cart">
                <div className="cart-items">{itemsBlock}</div>
                <div className="cart-total">
                    <span>合計</span>
                    <strong>{total}</strong>
                </div>
                <div className="liff-flow-actions">
                    <button
                        type="button"
                        className="btn btn-primary"
                        disabled={!canOrder}
                        onClick={() => app?.openOrderFlow?.('confirm')}
                    >
                        注文内容を確認
                    </button>
                    <button
                        type="button"
                        className="btn btn-secondary"
                        onClick={() => app?.switchTab?.('products')}
                    >
                        商品一覧へ
                    </button>
                </div>
            </div>
        );
    }

    return (
        <div className="liff-flow-page order-flow-confirm">
            <div className="cart-items">{itemsBlock}</div>
            <div className="cart-total">
                <span>合計</span>
                <strong>{total}</strong>
            </div>
            <div className="liff-flow-actions">
                {mobileEnabled ? (
                    <>
                        <button
                            type="button"
                            className="btn btn-primary"
                            disabled={!canOrder}
                            onClick={() => app?.checkoutManager?.placeMobileOrder?.('stripe')}
                        >
                            <i className="fas fa-credit-card" aria-hidden="true" /> カードで払う
                        </button>
                        <button
                            type="button"
                            className="btn btn-secondary"
                            disabled={!canOrder}
                            onClick={() => app?.checkoutManager?.placeMobileOrder?.('cash')}
                        >
                            <i className="fas fa-store" aria-hidden="true" /> 店頭で払う
                        </button>
                    </>
                ) : null}
                {inStoreEnabled ? (
                    <button
                        type="button"
                        className="btn btn-secondary"
                        disabled={!canOrder}
                        onClick={() => app?.checkoutManager?.placeInStoreOrder?.()}
                    >
                        <i className="fas fa-utensils" aria-hidden="true" /> 店内注文する
                    </button>
                ) : null}
                <button
                    type="button"
                    className="btn btn-secondary"
                    onClick={() => app?.openOrderFlow?.('cart')}
                >
                    カートへ戻る
                </button>
            </div>
        </div>
    );
}

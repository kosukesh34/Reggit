import React, { useCallback, useEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { DomHelper } from '../../../shared/utils/domHelper';
import { ToastManager } from '../../../shared/utils/toast';
import { LIFF_OPEN_PAYMENT } from '../products/types';

type OrderLike = {
    id?: number | string;
    order_number?: string;
    total_amount?: number | string;
};

type CheckoutManagerLike = {
    stripePayment: {
        setupPaymentForm: (elementId: string, amount: number, orderId: number | string) => Promise<void>;
        confirmPayment: (orderId: number | string) => Promise<{
            success?: boolean;
            message?: string;
            order?: OrderLike;
        } | null>;
    } | null;
    isPaymentCompleted: boolean;
    pendingMobileOrderId: number | null;
    isPaymentFlowActive: boolean;
    closePaymentModal: (shouldCancelOrder: boolean) => void;
    afterOrderCompleted: (orderNumber: string | number | null) => void;
};

const PAYMENT_ELEMENT_ID = 'mobileOrderPaymentElement';
const LIFF_CLOSE_PAYMENT = 'liff-close-payment';

type OpenPaymentDetail = {
    order: OrderLike;
};

/**
 * Stripe 決済モーダル（CheckoutManager.openPaymentModal の JSX 版）。
 * `liff-open-payment` で開き、実処理は CheckoutManager の Stripe / キャンセル API を使う。
 */
export function CheckoutModal(): JSX.Element | null {
    const [order, setOrder] = useState<OrderLike | null>(null);
    const [errorMessage, setErrorMessage] = useState('');
    const [isReady, setIsReady] = useState(false);
    const [isConfirming, setIsConfirming] = useState(false);
    const setupToken = useRef(0);
    const closingRef = useRef(false);

    const getCheckout = (): CheckoutManagerLike | null => {
        const app = window.app as { checkoutManager?: CheckoutManagerLike } | undefined;
        return app?.checkoutManager ?? null;
    };

    const resetUi = useCallback((): void => {
        setOrder(null);
        setErrorMessage('');
        setIsReady(false);
        setIsConfirming(false);
    }, []);

    const requestClose = useCallback((shouldCancel: boolean): void => {
        if (closingRef.current) {
            return;
        }
        closingRef.current = true;
        const cm = getCheckout();
        if (cm !== null) {
            cm.closePaymentModal(shouldCancel);
        } else {
            resetUi();
        }
        closingRef.current = false;
    }, [resetUi]);

    useEffect(() => {
        const onOpen = (event: Event): void => {
            const detail = (event as CustomEvent<OpenPaymentDetail>).detail;
            if (detail?.order === undefined || detail.order === null) {
                return;
            }
            closingRef.current = false;
            setOrder(detail.order);
            setErrorMessage('');
            setIsReady(false);
            setIsConfirming(false);
        };
        const onClose = (): void => {
            resetUi();
        };
        document.addEventListener(LIFF_OPEN_PAYMENT, onOpen);
        document.addEventListener(LIFF_CLOSE_PAYMENT, onClose);
        return () => {
            document.removeEventListener(LIFF_OPEN_PAYMENT, onOpen);
            document.removeEventListener(LIFF_CLOSE_PAYMENT, onClose);
        };
    }, [resetUi]);

    useEffect(() => {
        if (order === null || order.id === undefined || order.id === null) {
            return;
        }
        const token = ++setupToken.current;
        const cm = getCheckout();
        if (cm?.stripePayment === null || cm?.stripePayment === undefined) {
            const message = '決済機能の読み込みに失敗しました';
            setErrorMessage(message);
            ToastManager.error(message);
            requestClose(true);
            return;
        }
        void cm.stripePayment
            .setupPaymentForm(PAYMENT_ELEMENT_ID, Number(order.total_amount || 0), order.id)
            .then(() => {
                if (setupToken.current === token) {
                    setIsReady(true);
                }
            })
            .catch((error: unknown) => {
                if (setupToken.current !== token) {
                    return;
                }
                const message =
                    error instanceof Error && error.message
                        ? error.message
                        : '決済フォームの準備に失敗しました';
                setErrorMessage(message);
                ToastManager.error(message);
                requestClose(true);
            });
    }, [order, requestClose]);

    const handleConfirm = async (): Promise<void> => {
        if (order === null || order.id === undefined || order.id === null || isConfirming) {
            return;
        }
        const cm = getCheckout();
        if (cm?.stripePayment === null || cm?.stripePayment === undefined) {
            ToastManager.error('決済機能の読み込みに失敗しました');
            return;
        }
        setIsConfirming(true);
        setErrorMessage('');
        try {
            const paymentResult = await cm.stripePayment.confirmPayment(order.id);
            if (paymentResult === null || paymentResult.success !== true) {
                throw new Error(
                    (paymentResult && paymentResult.message) || '決済の確認に失敗しました',
                );
            }
            cm.isPaymentCompleted = true;
            ToastManager.success('モバイル注文が完了しました');
            const confirmedOrder = paymentResult.order;
            document.dispatchEvent(new CustomEvent('liff-payment-completed', {
                detail: { order: confirmedOrder ?? order },
            }));
            requestClose(false);
            cm.afterOrderCompleted(confirmedOrder?.order_number ?? null);
        } catch (error) {
            const message =
                error instanceof Error && error.message ? error.message : '決済に失敗しました';
            setErrorMessage(message);
            ToastManager.error(message);
            setIsConfirming(false);
        }
    };

    if (order === null) {
        return null;
    }

    return createPortal(
        <div
            className="payment-modal"
            onClick={(e) => {
                if (e.target === e.currentTarget) {
                    requestClose(true);
                }
            }}
        >
            <div className="payment-modal-content">
                <div className="payment-modal-header">
                    <h2>
                        <i className="fas fa-credit-card" aria-hidden="true" /> モバイル決済
                    </h2>
                    <button
                        type="button"
                        className="close-payment-btn"
                        onClick={() => requestClose(true)}
                        aria-label="閉じる"
                    >
                        <i className="fas fa-times" aria-hidden="true" />
                    </button>
                </div>
                <div className="payment-modal-body">
                    <div className="payment-summary">
                        <div className="payment-summary-item">
                            <span>お支払い金額</span>
                            <span className="payment-amount">
                                {DomHelper.formatPrice(order.total_amount || 0)}
                            </span>
                        </div>
                    </div>
                    <div className="payment-form-section">
                        <h3>
                            <i className="fas fa-lock" aria-hidden="true" /> カード情報
                        </h3>
                        <div className="payment-element-container" id={PAYMENT_ELEMENT_ID}>
                            {!isReady && errorMessage === '' ? (
                                <div className="payment-loading">
                                    <i className="fas fa-spinner fa-spin" aria-hidden="true" />
                                    <p>決済フォームを準備中...</p>
                                </div>
                            ) : null}
                        </div>
                    </div>
                    {errorMessage !== '' ? (
                        <div className="payment-error-message" style={{ display: 'flex' }}>
                            <i className="fas fa-exclamation-circle" aria-hidden="true" />
                            <span>{errorMessage}</span>
                        </div>
                    ) : null}
                    <div className="payment-actions">
                        <button
                            type="button"
                            className="confirm-payment-btn"
                            disabled={!isReady || isConfirming}
                            onClick={() => {
                                void handleConfirm();
                            }}
                        >
                            {isConfirming ? (
                                <>
                                    <i className="fas fa-spinner fa-spin" aria-hidden="true" /> 決済中...
                                </>
                            ) : (
                                <>
                                    <i className="fas fa-check-circle" aria-hidden="true" /> 決済して注文確定
                                </>
                            )}
                        </button>
                        <button
                            type="button"
                            className="cancel-payment-btn"
                            onClick={() => requestClose(true)}
                        >
                            <i className="fas fa-times" aria-hidden="true" /> キャンセル
                        </button>
                    </div>
                </div>
            </div>
        </div>,
        document.body,
    );
}

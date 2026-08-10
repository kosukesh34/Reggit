import React, { useCallback, useEffect, useRef, useState } from 'react';
import { AppConstants } from '../../../shared/constants';
import { DomHelper } from '../../../shared/utils/domHelper';
import { ToastManager } from '../../../shared/utils/toast';
import {
    ACTIVE_ORDER_STATUSES,
    ACTIVE_TRACKER_STEPS,
    canViewOrders,
    fetchOrders,
    trackerIndex,
    type Order,
} from './api';

type ViewState =
    | { kind: 'loading' }
    | { kind: 'login' }
    | { kind: 'error'; message: string }
    | { kind: 'ready'; orders: Order[] };

function StatusTracker({ status, isActive }: { status: string | undefined; isActive: boolean }): JSX.Element {
    if (status === 'cancelled') {
        return (
            <div className="order-launch-timeline order-launch-timeline--cancelled" role="status">
                <span className="order-launch-timeline__cancelled">キャンセル済み</span>
            </div>
        );
    }
    const current = trackerIndex(status);
    return (
        <div
            className={`order-launch-timeline${isActive ? ' order-launch-timeline--live' : ''}`}
            role="list"
            aria-label="注文状況タイムライン"
        >
            <div className="order-launch-timeline__header">
                <i className="fas fa-satellite" aria-hidden="true" />
                <span>MISSION TIMELINE</span>
            </div>
            <ol className="order-launch-timeline__list">
                {ACTIVE_TRACKER_STEPS.map((step, index) => {
                    const isDone = index < current;
                    const isCurrent = index === current;
                    const stateClass = isDone ? 'is-done' : isCurrent ? 'is-current' : 'is-todo';
                    return (
                        <li key={step.key} className={`order-launch-timeline__item ${stateClass}`} role="listitem">
                            <span className="order-launch-timeline__t">{step.tMark}</span>
                            <span className="order-launch-timeline__rail" aria-hidden="true">
                                <span className="order-launch-timeline__node">
                                    {isCurrent ? (
                                        <i className="fas fa-rocket order-launch-timeline__rocket" aria-hidden="true" />
                                    ) : null}
                                </span>
                            </span>
                            <div className="order-launch-timeline__body">
                                <span className="order-launch-timeline__title">{step.label}</span>
                                <span className="order-launch-timeline__detail">{step.detail}</span>
                            </div>
                        </li>
                    );
                })}
            </ol>
        </div>
    );
}

function OrderCard({ order, large }: { order: Order; large?: boolean }): JSX.Element {
    const labels = AppConstants.ORDER_STATUS_LABELS || {};
    const status = String(order.status || '');
    const statusLabel = labels[order.status as keyof typeof labels] || order.status || '';
    const typeLabels = AppConstants.ORDER_TYPE_LABELS || {};
    const typeKey = order.order_type || '';
    const typeLabel = typeKey && typeLabels[typeKey as keyof typeof typeLabels]
        ? typeLabels[typeKey as keyof typeof typeLabels]
        : '';
    const dateStr = order.created_at
        ? new Date(order.created_at).toLocaleDateString('ja-JP', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        })
        : '';
    const isActive = ACTIVE_ORDER_STATUSES.includes(status);

    if (status === 'completed') {
        return (
            <article className="order-card order-card--done" data-order-id={String(order.id ?? '')} data-order-status="completed">
                <div className="order-card-header">
                    <div className="order-card-meta">
                        <span className="order-card-date">{dateStr}</span>
                        {typeLabel !== '' ? (
                            <span className={`order-card-type order-card-type--${typeKey}`}>{typeLabel}</span>
                        ) : null}
                        <span className="order-card-status order-card-status--completed">受け渡し完了</span>
                    </div>
                </div>
                <p className="order-card-done-message">ご来店ありがとうございました</p>
            </article>
        );
    }

    return (
        <article
            className={`order-card${large === true ? ' order-card--hero' : ''}`}
            data-order-id={String(order.id ?? '')}
            data-order-status={status}
        >
            <div className="order-card-header">
                <div className="order-card-meta">
                    <span className="order-card-date">{dateStr}</span>
                    {typeLabel !== '' ? (
                        <span className={`order-card-type order-card-type--${typeKey}`}>{typeLabel}</span>
                    ) : null}
                    <span className={`order-card-status order-card-status--${status}`}>{String(statusLabel)}</span>
                </div>
            </div>
            <StatusTracker status={order.status} isActive={isActive} />
            <div className="order-card-items">
                {(order.items || []).map((item, index) => {
                    const name = (item.product && item.product.name) || item.product_name || '商品';
                    return (
                        <span key={index} className="order-item-line">
                            {name} × {item.quantity || 0}
                        </span>
                    );
                })}
            </div>
            <div className="order-card-total">合計: {DomHelper.formatPrice(order.total_amount || 0)}</div>
        </article>
    );
}

export function OrdersView(): JSX.Element {
    const [view, setView] = useState<ViewState>({ kind: 'loading' });
    const knownStatuses = useRef<Record<string, string>>({});
    const pollRef = useRef<ReturnType<typeof setInterval> | null>(null);

    const notifyReady = useCallback((orders: Order[]) => {
        const nextKnown: Record<string, string> = {};
        orders.forEach((order) => {
            const id = String(order.id ?? '');
            if (id === '') {
                return;
            }
            const status = String(order.status ?? '');
            nextKnown[id] = status;
            const prev = knownStatuses.current[id];
            if (prev !== undefined && prev !== 'ready' && status === 'ready') {
                const ref = DomHelper.formatReferenceNumber(order.order_number);
                ToastManager.success(
                    ref !== '' ? `引換番号 ${ref} が受け取り可能になりました` : '注文が受け取り可能になりました',
                    5000,
                );
            }
        });
        knownStatuses.current = nextKnown;
    }, []);

    const load = useCallback(async (silent = false) => {
        if (!silent) {
            setView({ kind: 'loading' });
        }
        try {
            const allowed = await canViewOrders();
            if (!allowed) {
                if (pollRef.current !== null) {
                    clearInterval(pollRef.current);
                    pollRef.current = null;
                }
                setView({ kind: 'login' });
                return;
            }
            const orders = await fetchOrders();
            notifyReady(orders);
            setView({ kind: 'ready', orders });

            const hasActive = orders.some((o) => ACTIVE_ORDER_STATUSES.includes(String(o.status || '')));
            if (hasActive) {
                if (pollRef.current === null) {
                    pollRef.current = setInterval(() => {
                        void load(true);
                    }, 2500);
                }
            } else if (pollRef.current !== null) {
                clearInterval(pollRef.current);
                pollRef.current = null;
            }
        } catch (error) {
            if (!silent) {
                const message = error instanceof Error ? error.message : '注文履歴の読み込みに失敗しました';
                setView({ kind: 'error', message });
            }
        }
    }, [notifyReady]);

    useEffect(() => {
        void load(false);
        const onVisible = (): void => {
            if (document.visibilityState === 'visible' && pollRef.current !== null) {
                void load(true);
            }
        };
        document.addEventListener('visibilitychange', onVisible);
        return () => {
            document.removeEventListener('visibilitychange', onVisible);
            if (pollRef.current !== null) {
                clearInterval(pollRef.current);
                pollRef.current = null;
            }
        };
    }, [load]);

    if (view.kind === 'loading') {
        return (
            <div className="loading">
                <i className="fas fa-spinner fa-spin" />
                <p>読み込み中...</p>
            </div>
        );
    }

    if (view.kind === 'login') {
        return (
            <div className="member-card-container">
                <div className="member-card member-card--line-only">
                    <div className="member-card-header">
                        <h2 className="member-card-title">
                            <i className="fas fa-id-card" /> 注文状況
                        </h2>
                    </div>
                    <div className="member-card-line-only-body">
                        <p className="member-card-line-only-message">ログインすると注文状況をご確認いただけます。</p>
                    </div>
                </div>
            </div>
        );
    }

    if (view.kind === 'error') {
        return (
            <div className="orders-error">
                <i className="fas fa-exclamation-circle" />
                <p>{view.message}</p>
                <button type="button" className="btn btn-secondary" onClick={() => void load(false)}>
                    <i className="fas fa-redo" /> 再読み込み
                </button>
            </div>
        );
    }

    const orders = view.orders;
    if (orders.length === 0) {
        return (
            <div className="orders-empty">
                <i className="fas fa-receipt" />
                <p>注文履歴はありません</p>
                <p className="orders-empty-sub">商品タブから注文してみましょう</p>
            </div>
        );
    }

    const grouped: Record<string, Order[]> = {};
    orders.forEach((order) => {
        const s = order.status || 'pending';
        if (!grouped[s]) {
            grouped[s] = [];
        }
        grouped[s].push(order);
    });
    const activeOrders = ACTIVE_ORDER_STATUSES.flatMap((s) => grouped[s] || []);
    const pastOrders = ['completed', 'cancelled'].flatMap((s) => grouped[s] || []);

    return (
        <>
            <div className="orders-header">
                <h2 className="section-title">
                    <i className="fas fa-receipt" /> 注文状況
                </h2>
            </div>
            {activeOrders.length > 0 ? (
                <div className="order-group">
                    <div className="order-group-header order-group-header--active">
                        <i className="fas fa-bolt" />
                        <span>進行中の注文</span>
                        <span className="order-group-count">{activeOrders.length}件</span>
                    </div>
                    <div className="orders-list orders-list--active">
                        {activeOrders.map((order) => (
                            <OrderCard key={String(order.id)} order={order} large />
                        ))}
                    </div>
                </div>
            ) : null}
            {pastOrders.length > 0 ? (
                <div className="order-group">
                    <div className="order-group-header">
                        <i className="fas fa-history" />
                        <span>過去の注文</span>
                        <span className="order-group-count">{pastOrders.length}件</span>
                    </div>
                    <div className="orders-list">
                        {pastOrders.map((order) => (
                            <OrderCard key={String(order.id)} order={order} />
                        ))}
                    </div>
                </div>
            ) : null}
        </>
    );
}

import React from 'react';
import { DomHelper } from '../../../shared/utils/domHelper';
import type { CartItem } from '../../managers/CartManager';

const FALLBACK_IMG =
    "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='80' height='80'%3E%3Crect fill='%23f0f0f0' width='80' height='80'/%3E%3C/svg%3E";

type CartItemRowProps = {
    item: CartItem;
    onUpdateQuantity: (lineKey: string, change: number) => void;
    onRemove: (lineKey: string) => void;
};

export function CartItemRow({ item, onUpdateQuantity, onRemove }: CartItemRowProps): JSX.Element {
    const optionsText = item.options && item.options.length > 0
        ? item.options.map((o) => o.name).join('・')
        : '';
    const notesText = item.notes || '';

    return (
        <div className="cart-item">
            <img
                src={item.image_url || FALLBACK_IMG}
                alt={item.name}
                className="cart-item-image"
                onError={(e) => {
                    e.currentTarget.src = FALLBACK_IMG;
                }}
            />
            <div className="cart-item-info">
                <div className="cart-item-name">{item.name}</div>
                {optionsText !== '' ? <div className="cart-item-options">{optionsText}</div> : null}
                {notesText !== '' ? (
                    <div className="cart-item-note">
                        <i className="fas fa-comment-dots" aria-hidden="true" /> {notesText}
                    </div>
                ) : null}
                <div className="cart-item-price">{DomHelper.formatPrice(item.price)}</div>
                <div className="cart-item-controls">
                    <button
                        type="button"
                        className="quantity-btn"
                        onClick={() => onUpdateQuantity(item.lineKey, -1)}
                    >
                        <i className="fas fa-minus" aria-hidden="true" />
                    </button>
                    <span className="quantity">{item.quantity}</span>
                    <button
                        type="button"
                        className="quantity-btn"
                        onClick={() => onUpdateQuantity(item.lineKey, 1)}
                    >
                        <i className="fas fa-plus" aria-hidden="true" />
                    </button>
                    <button
                        type="button"
                        className="remove-btn"
                        onClick={() => onRemove(item.lineKey)}
                    >
                        <i className="fas fa-trash" aria-hidden="true" />
                    </button>
                </div>
            </div>
        </div>
    );
}

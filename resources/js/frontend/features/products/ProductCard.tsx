import React from 'react';
import { DomHelper } from '../../../shared/utils/domHelper';
import type { Product } from './types';

type ProductCardProps = {
    product: Product;
    cartQuantity: number;
    onOpenDetail: (productId: number | string) => void;
    onAddToCart: (productId: number | string) => void;
    onAdjustQuantity: (productId: number | string, change: number) => void;
};

const PLACEHOLDER = '/images/liff/product-placeholder.jpg';

export function ProductCard({
    product,
    cartQuantity,
    onOpenDetail,
    onAddToCart,
    onAdjustQuantity,
}: ProductCardProps): JSX.Element {
    const isInCart = cartQuantity > 0;
    const imageUrl = product.image_url || PLACEHOLDER;
    const hasOptions = Array.isArray(product.option_groups) && product.option_groups.length > 0;
    const priceLabel = DomHelper.formatPrice(product.price);

    const handleCardClick = (): void => {
        if (hasOptions) {
            onOpenDetail(product.id);
            return;
        }
        if (!isInCart) {
            onAddToCart(product.id);
        }
    };

    return (
        <div
            className={`product-card${isInCart ? ' in-cart' : ''}`}
            data-product-id={String(product.id)}
            onClick={hasOptions || !isInCart ? handleCardClick : undefined}
            role={hasOptions || !isInCart ? 'button' : undefined}
        >
            <div className="product-image-wrapper">
                {isInCart ? <div className="cart-badge">{cartQuantity}</div> : null}
                <img
                    src={imageUrl}
                    alt={product.name || ''}
                    className="product-image"
                    loading="lazy"
                    onError={(e) => {
                        const img = e.currentTarget;
                        img.onerror = null;
                        img.src = PLACEHOLDER;
                    }}
                />
                {hasOptions ? (
                    <span className="product-option-tag">
                        <i className="fas fa-sliders-h" aria-hidden="true" /> カスタム可
                    </span>
                ) : null}
            </div>
            <div className="product-info">
                <div className="product-name">{product.name}</div>
                <div className="product-price">
                    {priceLabel}
                    {hasOptions ? <span className="product-price-from">〜</span> : null}
                </div>
                {hasOptions ? (
                    <button
                        type="button"
                        className="product-add-btn"
                        onClick={(e) => {
                            e.stopPropagation();
                            onOpenDetail(product.id);
                        }}
                    >
                        <i className="fas fa-sliders-h" aria-hidden="true" /> 選んで追加
                    </button>
                ) : isInCart ? (
                    <div className="product-qty-stepper" onClick={(e) => e.stopPropagation()}>
                        <button
                            type="button"
                            className="product-qty-btn"
                            aria-label="減らす"
                            onClick={() => onAdjustQuantity(product.id, -1)}
                        >
                            −
                        </button>
                        <span className="product-qty-value" aria-live="polite">{cartQuantity}</span>
                        <button
                            type="button"
                            className="product-qty-btn"
                            aria-label="増やす"
                            onClick={() => onAdjustQuantity(product.id, 1)}
                        >
                            +
                        </button>
                    </div>
                ) : (
                    <button
                        type="button"
                        className="product-add-btn"
                        onClick={(e) => {
                            e.stopPropagation();
                            onAddToCart(product.id);
                        }}
                    >
                        <i className="fas fa-cart-plus" aria-hidden="true" /> カートに追加
                    </button>
                )}
            </div>
        </div>
    );
}

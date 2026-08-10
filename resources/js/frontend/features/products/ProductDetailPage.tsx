import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { DomHelper } from '../../../shared/utils/domHelper';
import { ToastManager } from '../../../shared/utils/toast';
import { LIFF_CART_UPDATED, LIFF_PRODUCTS_UPDATED, type Product, type ProductOptionGroup } from './types';

type SelectedOption = {
    group_name: string;
    option_id: number;
    name: string;
    price_delta: number;
};

type ProductDetailPageProps = {
    productId: string;
};

function getProduct(productId: string): Product | null {
    const app = window.app as {
        productManager?: { getProductById?: (id: string) => Product | null | undefined };
    } | undefined;
    const product = app?.productManager?.getProductById?.(productId);
    return product ?? null;
}

function initialSelected(groups: ProductOptionGroup[]): Record<string, number[]> {
    const selected: Record<string, number[]> = {};
    groups.forEach((group) => {
        const options = Array.isArray(group.options) ? group.options : [];
        const multiple = group.selection_type === 'multiple';
        if (multiple) {
            selected[String(group.id)] = options
                .filter((opt) => opt.is_default === true)
                .map((opt) => Number(opt.id));
            return;
        }
        const def = options.find((opt) => opt.is_default === true) ?? options[0];
        selected[String(group.id)] = def !== undefined ? [Number(def.id)] : [];
    });
    return selected;
}

function collectSelection(
    product: Product,
    selected: Record<string, number[]>,
): { options: SelectedOption[]; delta: number; valid: boolean } {
    const groups = Array.isArray(product.option_groups) ? product.option_groups : [];
    const options: SelectedOption[] = [];
    let delta = 0;
    let valid = true;

    groups.forEach((group) => {
        const ids = selected[String(group.id)] ?? [];
        if (group.is_required === true && ids.length === 0) {
            valid = false;
        }
        const max = group.max_select || 0;
        if (group.selection_type === 'multiple' && max > 0 && ids.length > max) {
            valid = false;
        }
        const groupOptions = Array.isArray(group.options) ? group.options : [];
        ids.forEach((id) => {
            const opt = groupOptions.find((entry) => Number(entry.id) === id);
            if (opt === undefined) {
                return;
            }
            const price = Number(opt.price_delta) || 0;
            delta += price;
            options.push({
                group_name: group.name,
                option_id: id,
                name: opt.name,
                price_delta: price,
            });
        });
    });

    return { options, delta, valid };
}

/**
 * 商品詳細（#product/{id}）。app._renderProductDetailPage の JSX 版。
 */
export function ProductDetailPage({ productId }: ProductDetailPageProps): JSX.Element {
    const [product, setProduct] = useState<Product | null>(() => getProduct(productId));
    const [qty, setQty] = useState(1);
    const [notes, setNotes] = useState('');
    const [selected, setSelected] = useState<Record<string, number[]>>(() => {
        const p = getProduct(productId);
        return initialSelected(Array.isArray(p?.option_groups) ? p.option_groups : []);
    });
    const [loadError, setLoadError] = useState(false);

    useEffect(() => {
        let cancelled = false;
        const sync = (): void => {
            const next = getProduct(productId);
            if (next !== null) {
                if (!cancelled) {
                    setProduct(next);
                    setSelected(initialSelected(Array.isArray(next.option_groups) ? next.option_groups : []));
                    setQty(1);
                    setNotes('');
                    setLoadError(false);
                }
                return;
            }
            const app = window.app as {
                productManager?: { loadProducts?: () => Promise<unknown> };
            } | undefined;
            if (typeof app?.productManager?.loadProducts !== 'function') {
                if (!cancelled) {
                    setLoadError(true);
                }
                return;
            }
            void app.productManager.loadProducts().then(() => {
                if (cancelled) {
                    return;
                }
                const loaded = getProduct(productId);
                if (loaded === null) {
                    setLoadError(true);
                    return;
                }
                setProduct(loaded);
                setSelected(initialSelected(Array.isArray(loaded.option_groups) ? loaded.option_groups : []));
                setQty(1);
                setNotes('');
                setLoadError(false);
            }).catch(() => {
                if (!cancelled) {
                    setLoadError(true);
                    ToastManager.error('商品の読み込みに失敗しました');
                }
            });
        };
        sync();
        document.addEventListener(LIFF_PRODUCTS_UPDATED, sync);
        return () => {
            cancelled = true;
            document.removeEventListener(LIFF_PRODUCTS_UPDATED, sync);
        };
    }, [productId]);

    const selection = useMemo(
        () => (product !== null ? collectSelection(product, selected) : { options: [], delta: 0, valid: true }),
        [product, selected],
    );

    const totalLabel = useMemo(() => {
        if (product === null) {
            return '';
        }
        const base = Number(product.price) || 0;
        return DomHelper.formatPrice((base + selection.delta) * qty);
    }, [product, selection.delta, qty]);

    const toggleOption = useCallback((group: ProductOptionGroup, optionId: number, checked: boolean): void => {
        const groupKey = String(group.id);
        const multiple = group.selection_type === 'multiple';
        const max = group.max_select || 0;

        setSelected((prev) => {
            if (!multiple) {
                return { ...prev, [groupKey]: checked ? [optionId] : [] };
            }
            const current = prev[groupKey] ?? [];
            if (checked) {
                if (max > 0 && current.length >= max) {
                    ToastManager.info(`${group.name}は最大${max}個までです`);
                    return prev;
                }
                if (current.includes(optionId)) {
                    return prev;
                }
                return { ...prev, [groupKey]: [...current, optionId] };
            }
            return { ...prev, [groupKey]: current.filter((id) => id !== optionId) };
        });
    }, []);

    const handleAdd = useCallback((): void => {
        if (product === null) {
            return;
        }
        const sel = collectSelection(product, selected);
        if (!sel.valid) {
            ToastManager.error('必須の選択を確認してください');
            return;
        }
        const app = window.app as {
            cartManager?: {
                addItem: (
                    product: Product,
                    opts: { options: SelectedOption[]; quantity: number; notes: string },
                ) => void;
            };
            closeProductDetail?: () => void;
            updateCartUI?: () => void;
            showCartNotification?: () => void;
            renderProducts?: () => void;
        } | undefined;
        if (typeof app?.cartManager?.addItem !== 'function') {
            return;
        }
        app.cartManager.addItem(product, {
            options: sel.options,
            quantity: qty,
            notes,
        });
        if (typeof app.closeProductDetail === 'function') {
            app.closeProductDetail();
        }
        if (typeof app.updateCartUI === 'function') {
            app.updateCartUI();
        } else {
            document.dispatchEvent(new CustomEvent(LIFF_CART_UPDATED));
        }
        if (typeof app.showCartNotification === 'function') {
            app.showCartNotification();
        }
        if (typeof app.renderProducts === 'function') {
            app.renderProducts();
        }
        ToastManager.success(`${product.name || '商品'}をカートに追加しました`);
    }, [product, selected, qty, notes]);

    if (loadError) {
        return (
            <div className="liff-entity-detail product-detail-page">
                <div className="pd-page">
                    <div className="pd-body">
                        <p className="pd-desc">商品が見つかりませんでした</p>
                    </div>
                </div>
            </div>
        );
    }

    if (product === null) {
        return (
            <div className="liff-entity-detail product-detail-page">
                <div className="pd-page">
                    <div className="pd-body">
                        <p className="pd-desc">読み込み中...</p>
                    </div>
                </div>
            </div>
        );
    }

    const groups = Array.isArray(product.option_groups) ? product.option_groups : [];
    const imageUrl = product.image_url || '';

    return (
        <div className="liff-entity-detail product-detail-page">
            <div className="pd-page" id="productDetailHost">
                <div className="pd-hero">
                    {imageUrl !== '' ? (
                        <img
                            src={imageUrl}
                            alt={product.name || ''}
                            className="pd-image"
                            onError={(e) => {
                                e.currentTarget.style.display = 'none';
                            }}
                        />
                    ) : null}
                </div>
                <div className="pd-body">
                    <h2 className="pd-name">{product.name}</h2>
                    <div className="pd-base-price">{DomHelper.formatPrice(product.price)}</div>
                    {product.description ? <p className="pd-desc">{product.description}</p> : null}
                    {groups.map((group) => {
                        const multiple = group.selection_type === 'multiple';
                        const type = multiple ? 'checkbox' : 'radio';
                        const max = group.max_select || 0;
                        const meta = multiple
                            ? (max > 0 ? `最大${max}個` : '複数選択可')
                            : '1つ選択';
                        const groupKey = String(group.id);
                        const checkedIds = selected[groupKey] ?? [];
                        const options = Array.isArray(group.options) ? group.options : [];

                        return (
                            <div className="pd-group" data-group-wrap={groupKey} key={groupKey}>
                                <div className="pd-group-head">
                                    <span className="pd-group-name">{group.name}</span>
                                    <span className="pd-group-meta">
                                        {group.is_required === true ? <span className="pd-required">必須</span> : null}
                                        {meta}
                                    </span>
                                </div>
                                <div className="pd-group-options">
                                    {options.map((opt) => {
                                        const delta = Number(opt.price_delta) || 0;
                                        const deltaLabel = delta > 0
                                            ? `+${DomHelper.formatPrice(delta)}`
                                            : (delta < 0 ? DomHelper.formatPrice(delta) : '');
                                        const optId = Number(opt.id);
                                        return (
                                            <label className="pd-option" key={optId}>
                                                <input
                                                    type={type}
                                                    name={`grp_${groupKey}`}
                                                    value={optId}
                                                    data-opt=""
                                                    data-group={groupKey}
                                                    data-group-type={group.selection_type}
                                                    data-max={max}
                                                    data-price={delta}
                                                    data-name={opt.name}
                                                    checked={checkedIds.includes(optId)}
                                                    onChange={(e) => toggleOption(group, optId, e.target.checked)}
                                                />
                                                <span className="pd-option-check" />
                                                <span className="pd-option-name">{opt.name}</span>
                                                {deltaLabel !== '' ? (
                                                    <span className="pd-option-price">{deltaLabel}</span>
                                                ) : null}
                                            </label>
                                        );
                                    })}
                                </div>
                            </div>
                        );
                    })}
                    <div className="pd-group">
                        <div className="pd-group-head">
                            <span className="pd-group-name">ご要望（任意）</span>
                        </div>
                        <input
                            type="text"
                            id="pdNotes"
                            className="pd-notes"
                            maxLength={500}
                            placeholder="例: ソース少なめ など"
                            value={notes}
                            onChange={(e) => setNotes(e.target.value)}
                        />
                    </div>
                </div>
                <div className="pd-footer pd-footer--page">
                    <div className="pd-qty">
                        <button
                            type="button"
                            className="pd-qty-btn"
                            aria-label="減らす"
                            onClick={() => setQty((q) => Math.max(1, q - 1))}
                        >
                            <i className="fas fa-minus" aria-hidden="true" />
                        </button>
                        <span id="detailQtyValue" className="pd-qty-value">{qty}</span>
                        <button
                            type="button"
                            className="pd-qty-btn"
                            aria-label="増やす"
                            onClick={() => setQty((q) => q + 1)}
                        >
                            <i className="fas fa-plus" aria-hidden="true" />
                        </button>
                    </div>
                    <button
                        type="button"
                        className={`pd-add-btn${selection.valid ? '' : ' is-disabled'}`}
                        id="pdAddBtn"
                        onClick={handleAdd}
                    >
                        <span>カートに追加</span>
                        <span className="pd-add-total" id="pdAddTotal">{totalLabel}</span>
                    </button>
                </div>
            </div>
        </div>
    );
}

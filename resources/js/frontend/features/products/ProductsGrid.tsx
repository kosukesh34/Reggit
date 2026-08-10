import React, { useCallback, useEffect, useState } from 'react';
import { CategoryTabs } from './CategoryTabs';
import { ProductCard } from './ProductCard';
import {
    LIFF_CART_UPDATED,
    LIFF_PRODUCTS_UPDATED,
    type Product,
    type ProductCategory,
} from './types';

type AppProducts = {
    productManager?: {
        loadProducts: () => Promise<Product[]>;
        getCategories: () => ProductCategory[];
        getCurrentCategory: () => string;
        setCurrentCategory: (key: string) => void;
        getProductsByCategory: (key: string) => Product[];
        getProductsGroupedByCategory: () => Record<string, Product[]>;
        getProductSkeleton?: (count?: number) => string;
    };
    cartManager?: {
        getQuantityForProduct: (productId: number | string) => number;
    };
    addToCart?: (productId: number | string) => void;
    adjustProductQuantity?: (productId: number | string, change: number) => void;
    openProductDetail?: (productId: number | string) => void;
    applyProductCategory?: (category: string) => void;
};

type LoadState = 'idle' | 'loading' | 'ready' | 'error';

function getApp(): AppProducts | undefined {
    return window.app as AppProducts | undefined;
}

function readCatalog(app: AppProducts): {
    categories: ProductCategory[];
    currentCategory: string;
    products: Product[];
    grouped: Record<string, Product[]>;
} {
    const pm = app.productManager;
    if (pm === undefined) {
        return { categories: [], currentCategory: 'all', products: [], grouped: {} };
    }
    const currentCategory = pm.getCurrentCategory() || 'all';
    return {
        categories: pm.getCategories() || [],
        currentCategory,
        products: pm.getProductsByCategory(currentCategory),
        grouped: pm.getProductsGroupedByCategory(),
    };
}

type ProductsGridProps = {
    /** Builder 埋め込み時は外側に products-data-mount がある */
    embed?: boolean;
    categoryTabsId?: string;
    gridId?: string;
};

export function ProductsGrid({
    embed = false,
    categoryTabsId = 'categoryTabs',
    gridId = 'productsGrid',
}: ProductsGridProps): JSX.Element {
    const [loadState, setLoadState] = useState<LoadState>('idle');
    const [errorMessage, setErrorMessage] = useState('');
    const [categories, setCategories] = useState<ProductCategory[]>([]);
    const [currentCategory, setCurrentCategory] = useState('all');
    const [products, setProducts] = useState<Product[]>([]);
    const [grouped, setGrouped] = useState<Record<string, Product[]>>({});
    const [cartTick, setCartTick] = useState(0);

    const syncFromApp = useCallback((): void => {
        const app = getApp();
        if (app?.productManager === undefined) {
            return;
        }
        const next = readCatalog(app);
        setCategories(next.categories);
        setCurrentCategory(next.currentCategory);
        setProducts(next.products);
        setGrouped(next.grouped);
        setLoadState('ready');
    }, []);

    const load = useCallback(async (): Promise<boolean> => {
        const app = getApp();
        if (app?.productManager === undefined || typeof app.productManager.loadProducts !== 'function') {
            return false;
        }
        // 既に app.loadProducts / イベント経由で入っている場合は再フェッチしない
        if ((app.productManager as { products?: Product[] }).products?.length) {
            syncFromApp();
            return true;
        }
        setLoadState('loading');
        setErrorMessage('');
        try {
            await app.productManager.loadProducts();
            syncFromApp();
            document.dispatchEvent(new CustomEvent(LIFF_PRODUCTS_UPDATED));
        } catch (error) {
            const message = error instanceof Error ? error.message : 'エラーが発生しました';
            setErrorMessage(message);
            setLoadState('error');
        }
        return true;
    }, [syncFromApp]);

    useEffect(() => {
        let cancelled = false;
        const tryLoad = (): boolean => {
            if (cancelled) {
                return true;
            }
            const app = getApp();
            if (app?.productManager === undefined) {
                return false;
            }
            void load();
            return true;
        };

        if (!tryLoad()) {
            const onReady = (): void => {
                void load();
            };
            document.addEventListener('liff-ready', onReady);
            const timer = window.setInterval(() => {
                if (tryLoad()) {
                    window.clearInterval(timer);
                }
            }, 50);
            const stop = window.setTimeout(() => window.clearInterval(timer), 5000);
            return () => {
                cancelled = true;
                document.removeEventListener('liff-ready', onReady);
                window.clearInterval(timer);
                window.clearTimeout(stop);
            };
        }
        return () => {
            cancelled = true;
        };
    }, [load]);

    useEffect(() => {
        const onProducts = (): void => {
            const app = getApp();
            if (app?.productManager === undefined) {
                return;
            }
            // 外部の loadProducts 完了後は必ず同期（空カタログも反映）
            setLoadState('ready');
            setErrorMessage('');
            syncFromApp();
        };
        const onCart = (): void => {
            setCartTick((n) => n + 1);
        };
        document.addEventListener(LIFF_PRODUCTS_UPDATED, onProducts);
        document.addEventListener(LIFF_CART_UPDATED, onCart);
        return () => {
            document.removeEventListener(LIFF_PRODUCTS_UPDATED, onProducts);
            document.removeEventListener(LIFF_CART_UPDATED, onCart);
        };
    }, [syncFromApp]);

    const handleSelectCategory = (key: string): void => {
        const app = getApp();
        if (app?.productManager !== undefined) {
            app.productManager.setCurrentCategory(key);
        }
        setCurrentCategory(key);
        syncFromApp();
    };

    const handleOpenDetail = (productId: number | string): void => {
        const app = getApp();
        if (typeof app?.openProductDetail === 'function') {
            app.openProductDetail(productId);
        }
    };

    const handleAddToCart = (productId: number | string): void => {
        const app = getApp();
        if (typeof app?.addToCart === 'function') {
            app.addToCart(productId);
        }
    };

    const handleAdjustQuantity = (productId: number | string, change: number): void => {
        const app = getApp();
        if (typeof app?.adjustProductQuantity === 'function') {
            app.adjustProductQuantity(productId, change);
        }
    };

    const cartQty = (productId: number | string): number => {
        void cartTick;
        const app = getApp();
        if (app?.cartManager === undefined) {
            return 0;
        }
        return app.cartManager.getQuantityForProduct(productId);
    };

    const renderCards = (list: Product[]): JSX.Element => (
        <div className="products-grid-inner">
            {list.map((product) => (
                <ProductCard
                    key={String(product.id)}
                    product={product}
                    cartQuantity={cartQty(product.id)}
                    onOpenDetail={handleOpenDetail}
                    onAddToCart={handleAddToCart}
                    onAdjustQuantity={handleAdjustQuantity}
                />
            ))}
        </div>
    );

    let gridBody: JSX.Element;
    if (loadState === 'loading' || loadState === 'idle') {
        gridBody = (
            <div className="products-grid-inner">
                {Array.from({ length: 6 }).map((_, i) => (
                    <div key={i} className="product-card skeleton">
                        <div className="product-image-wrapper skeleton-image">
                            <div className="skeleton-shimmer" />
                        </div>
                        <div className="product-info">
                            <div className="skeleton-line skeleton-title" />
                            <div className="skeleton-line skeleton-price" />
                            <div className="skeleton-line skeleton-button" />
                        </div>
                    </div>
                ))}
            </div>
        );
    } else if (loadState === 'error') {
        gridBody = (
            <div className="loading" style={{ color: '#e60012' }}>
                <i className="fas fa-exclamation-triangle" aria-hidden="true" />
                <p>商品の読み込みに失敗しました</p>
                <p style={{ fontSize: 12, marginTop: 8 }}>{errorMessage}</p>
                <button
                    type="button"
                    style={{
                        marginTop: 12,
                        padding: '8px 16px',
                        background: '#e60012',
                        color: 'white',
                        border: 'none',
                        borderRadius: 6,
                        cursor: 'pointer',
                    }}
                    onClick={() => {
                        void load();
                    }}
                >
                    <i className="fas fa-redo" aria-hidden="true" /> 再読み込み
                </button>
            </div>
        );
    } else if (products.length === 0) {
        gridBody = (
            <div className="loading">
                <i className="fas fa-search" aria-hidden="true" />
                <p>商品が見つかりませんでした</p>
            </div>
        );
    } else if (currentCategory === 'all') {
        const cats = categories.filter((c) => c.key !== 'all');
        gridBody = (
            <>
                {cats.map((cat) => {
                    const catProducts = grouped[cat.key];
                    if (catProducts === undefined || catProducts.length === 0) {
                        return null;
                    }
                    return (
                        <div key={cat.key} className="product-category-section">
                            <div className="product-category-header">
                                <i className={`fas ${cat.icon}`} aria-hidden="true" />
                                <h3>{cat.label}</h3>
                                <span className="product-category-count">{catProducts.length}品</span>
                            </div>
                            {renderCards(catProducts)}
                        </div>
                    );
                })}
            </>
        );
    } else {
        gridBody = renderCards(products);
    }

    const content = (
        <>
            <CategoryTabs
                categories={categories}
                currentCategory={currentCategory}
                onSelect={handleSelectCategory}
                id={categoryTabsId}
            />
            <div className="products-section">
                <div className="products-grid products-data-grid" id={gridId} data-react-products="1">
                    {gridBody}
                </div>
            </div>
        </>
    );

    if (embed) {
        return (
            <div className="products-data-mount" data-react-products="1">
                {content}
            </div>
        );
    }

    return content;
}

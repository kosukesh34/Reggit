import React, { useEffect, useRef, useState } from 'react';
import { isFixedUiShell } from '../../demo/demoMode';
import { BuilderLayout } from '../../layout/BuilderLayout';
import { getTabBlocks } from '../../layout/getTabBlocks';
import { ProductsGrid } from './ProductsGrid';

type ProductsViewMode = 'collection' | 'list';

function readViewMode(): ProductsViewMode {
    const app = window.app as { getProductsViewMode?: () => string } | undefined;
    if (typeof app?.getProductsViewMode === 'function') {
        const mode = app.getProductsViewMode();
        if (mode === 'collection' || mode === 'list') {
            return mode;
        }
    }
    try {
        const stored = String(window.localStorage.getItem('liff_products_view_mode') || '').trim();
        if (stored === 'collection' || stored === 'list') {
            return stored;
        }
    } catch {
        /* ignore */
    }
    return 'list';
}

function ProductsViewToggle({
    mode,
    onChange,
}: {
    mode: ProductsViewMode;
    onChange: (mode: ProductsViewMode) => void;
}): JSX.Element {
    return (
        <div className="ios-large-title-row">
            <h1 className="ios-large-title">オーダー</h1>
            <div className="ios-view-toggle" role="group" aria-label="表示切替">
                <button
                    type="button"
                    className={`ios-view-toggle__btn${mode === 'collection' ? ' is-active' : ''}`}
                    data-products-view="collection"
                    aria-label="コレクション表示"
                    title="コレクション"
                    aria-pressed={mode === 'collection'}
                    onClick={() => onChange('collection')}
                >
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <rect x="3" y="3" width="7.5" height="7.5" rx="1.5" stroke="currentColor" strokeWidth="1.8" />
                        <rect x="13.5" y="3" width="7.5" height="7.5" rx="1.5" stroke="currentColor" strokeWidth="1.8" />
                        <rect x="3" y="13.5" width="7.5" height="7.5" rx="1.5" stroke="currentColor" strokeWidth="1.8" />
                        <rect x="13.5" y="13.5" width="7.5" height="7.5" rx="1.5" stroke="currentColor" strokeWidth="1.8" />
                    </svg>
                </button>
                <button
                    type="button"
                    className={`ios-view-toggle__btn${mode === 'list' ? ' is-active' : ''}`}
                    data-products-view="list"
                    aria-label="リスト表示"
                    title="リスト"
                    aria-pressed={mode === 'list'}
                    onClick={() => onChange('list')}
                >
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M8 6h13M8 12h13M8 18h13M3.5 6h.01M3.5 12h.01M3.5 18h.01" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" />
                    </svg>
                </button>
            </div>
        </div>
    );
}

function ProductsShell({ fixed }: { fixed: boolean }): JSX.Element {
    const sectionRef = useRef<HTMLDivElement>(null);
    const [viewMode, setViewMode] = useState<ProductsViewMode>(() => (fixed ? readViewMode() : 'list'));

    useEffect(() => {
        const section = sectionRef.current;
        if (section === null) {
            return;
        }
        section.dataset.reactOwned = '1';
        section.dataset.layoutRendered = '1';
    }, []);

    const handleViewChange = (mode: ProductsViewMode): void => {
        setViewMode(mode);
        const app = window.app as { setProductsViewMode?: (m: string) => void } | undefined;
        if (typeof app?.setProductsViewMode === 'function') {
            app.setProductsViewMode(mode);
        }
    };

    return (
        <div
            ref={sectionRef}
            className="tab-section"
            id="productsSection"
            data-tab="products"
            data-react-owned="1"
            data-layout-rendered="1"
        >
            {fixed ? <ProductsViewToggle mode={viewMode} onChange={handleViewChange} /> : null}
            <div
                className="products-data-mount"
                data-react-products="1"
                data-products-view={fixed ? viewMode : undefined}
            >
                <ProductsGrid />
            </div>
        </div>
    );
}

/**
 * オーダー: Designer レイアウトがあれば BuilderLayout、
 * 固定デモUI・未設定は React ProductsGrid。
 */
export const ProductsPage = React.memo(function ProductsPage(): JSX.Element {
    if (isFixedUiShell()) {
        return <ProductsShell fixed />;
    }
    const blocks = getTabBlocks('products');
    if (blocks !== null) {
        return <BuilderLayout tab="products" blocks={blocks} />;
    }
    return <ProductsShell fixed={false} />;
});

import React, { useEffect, useRef } from 'react';
import { isFixedUiShell } from '../../demo/demoMode';
import { BuilderLayout } from '../../layout/BuilderLayout';
import { getTabBlocks } from '../../layout/getTabBlocks';
import { ManagerTabHost } from '../ManagerTabHost';

/**
 * オーダー: Designer レイアウトがあれば BuilderLayout、
 * 固定デモUIは Manager、それ以外は React シェル（同一クラス）+ app.loadProducts。
 */
function ProductsShell(): JSX.Element {
    const sectionRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        const section = sectionRef.current;
        if (section === null) {
            return;
        }
        section.dataset.reactOwned = '1';
        section.dataset.layoutRendered = '1';

        const load = (): boolean => {
            const app = window.app as { loadProducts?: () => void | Promise<void> } | undefined;
            if (app === undefined || typeof app.loadProducts !== 'function') {
                return false;
            }
            void app.loadProducts();
            return true;
        };

        if (load()) {
            return;
        }

        const onReady = (): void => {
            load();
        };
        document.addEventListener('liff-ready', onReady);
        const timer = window.setInterval(() => {
            if (load()) {
                window.clearInterval(timer);
            }
        }, 50);
        const stop = window.setTimeout(() => window.clearInterval(timer), 5000);

        return () => {
            document.removeEventListener('liff-ready', onReady);
            window.clearInterval(timer);
            window.clearTimeout(stop);
        };
    }, []);

    return (
        <div
            ref={sectionRef}
            className="tab-section"
            id="productsSection"
            data-tab="products"
            data-react-owned="1"
        >
            <div className="products-data-mount">
                <div className="category-tabs products-category-tabs" id="categoryTabs">
                    <button type="button" className="tab-btn active" data-category="all">
                        <i className="fas fa-th-large" aria-hidden="true" />
                        <span>すべて</span>
                    </button>
                </div>
                <div className="products-section">
                    <div className="products-grid products-data-grid" id="productsGrid" />
                </div>
            </div>
        </div>
    );
}

export const ProductsPage = React.memo(function ProductsPage(): JSX.Element {
    if (isFixedUiShell()) {
        return <ManagerTabHost tab="products" />;
    }
    const blocks = getTabBlocks('products');
    if (blocks !== null) {
        return <BuilderLayout tab="products" blocks={blocks} />;
    }
    return <ProductsShell />;
});

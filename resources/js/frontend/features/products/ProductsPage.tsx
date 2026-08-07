import React, { useCallback, useEffect, useState } from 'react';
import { AppConstants } from '../../../shared/constants';
import { useFrontendApp } from '../../context/FrontendAppContext';

type Product = {
    id: number | string;
    name?: string;
    price?: number;
    image_url?: string;
    description?: string;
    category?: string;
};

export function ProductsPage(): JSX.Element {
    const { apiClient } = useFrontendApp();
    const [products, setProducts] = useState<Product[]>([]);
    const [status, setStatus] = useState<'loading' | 'ready' | 'error'>('loading');

    const load = useCallback(async () => {
        setStatus('loading');
        try {
            const result = await apiClient.get(AppConstants.API_ENDPOINTS.PRODUCTS);
            const data = result.success ? result.data as { products?: Product[] } | Product[] : null;
            const list = Array.isArray(data) ? data : (data?.products ?? []);
            setProducts(list);
            setStatus('ready');
        } catch {
            setStatus('error');
        }
    }, [apiClient]);

    useEffect(() => {
        void load();
    }, [load]);

    if (status === 'loading') {
        return <div className="loading"><i className="fas fa-spinner fa-spin" /><p>読み込み中...</p></div>;
    }
    if (status === 'error') {
        return <div className="products-error"><p>商品の読み込みに失敗しました</p></div>;
    }
    if (products.length === 0) {
        return (
            <div className="products-empty">
                <p>商品はまだありません</p>
            </div>
        );
    }

    return (
        <div className="products-grid">
            {products.map((p) => (
                <div key={String(p.id)} className="product-card" data-product-id={String(p.id)}>
                    {p.image_url ? <img src={p.image_url} alt={p.name || ''} loading="lazy" /> : null}
                    <div className="product-card__body">
                        <h3 className="product-card__name">{p.name || ''}</h3>
                        <p className="product-card__price">¥{Number(p.price ?? 0).toLocaleString()}</p>
                    </div>
                </div>
            ))}
        </div>
    );
}

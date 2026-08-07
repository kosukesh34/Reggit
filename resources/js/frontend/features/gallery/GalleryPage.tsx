import React, { useCallback, useEffect, useState } from 'react';
import { AppConstants } from '../../../shared/constants';
import { isDemoDataMode, isFixedUiShell } from '../../demo/demoMode';
import { buildBuiltinSampleGallery } from '../../demo/sampleData';
import { useFrontendApp } from '../../context/FrontendAppContext';

type GalleryCategory = { id: string | number; name: string };
type GalleryItem = {
    id?: string | number;
    category_id?: string | number;
    size?: string;
    image_url?: string;
    title?: string;
    description?: string;
};

export function GalleryPage(): JSX.Element {
    const { apiClient, pageRouter } = useFrontendApp();
    const [categoryId, setCategoryId] = useState<string | number | null>(null);
    const [categories, setCategories] = useState<GalleryCategory[]>([]);
    const [items, setItems] = useState<GalleryItem[]>([]);
    const [status, setStatus] = useState<'loading' | 'ready' | 'error'>('loading');

    const load = useCallback(async (nextCategory: string | number | null) => {
        setStatus('loading');
        setCategoryId(nextCategory);
        try {
            const url = nextCategory !== null && nextCategory !== ''
                ? `${AppConstants.API_ENDPOINTS.GALLERY}?category_id=${encodeURIComponent(String(nextCategory))}`
                : AppConstants.API_ENDPOINTS.GALLERY;
            const result = await apiClient.get(url);
            const data = (result.success ? result.data : null) as {
                categories?: GalleryCategory[];
                items?: GalleryItem[];
            } | null;
            let nextCategories = data?.categories ?? [];
            let nextItems = (data?.items ?? []).map((item, index) => ({ ...item, id: item.id ?? index }));
            if (nextItems.length === 0 && isDemoDataMode()) {
                const sample = buildBuiltinSampleGallery();
                nextCategories = sample.categories;
                nextItems = nextCategory === null || nextCategory === ''
                    ? sample.items
                    : sample.items.filter((item) => String(item.category_id) === String(nextCategory));
            }
            setCategories(nextCategories);
            setItems(nextItems);
            setStatus('ready');
        } catch {
            if (isDemoDataMode()) {
                const sample = buildBuiltinSampleGallery();
                setCategories(sample.categories);
                setItems(sample.items);
                setStatus('ready');
                return;
            }
            setStatus('error');
        }
    }, [apiClient]);

    useEffect(() => {
        void load(null);
    }, [load]);

    const openDetail = (item: GalleryItem) => {
        const section = pageRouter.openPage({
            key: 'gallery_detail',
            hash: 'gallery/' + String(item.id),
            returnHash: pageRouter.resolveReturnHash('gallery'),
            title: item.title || 'ギャラリー',
            bodyClass: 'liff-page-gallery-detail',
        });
        if (section === null) {
            return;
        }
        section.innerHTML = `
            <div class="liff-entity-detail gallery-detail-page">
                <div class="liff-entity-detail__image">
                    <img src="${item.image_url || ''}" alt="${item.title || ''}">
                </div>
                ${item.title ? `<h1 class="liff-entity-detail__title">${item.title}</h1>` : ''}
                ${item.description ? `<div class="liff-entity-detail__body">${item.description}</div>` : ''}
            </div>
        `;
    };

    if (status === 'loading') {
        return <div className="loading"><i className="fas fa-spinner fa-spin" /><p>読み込み中...</p></div>;
    }
    if (status === 'error') {
        return <div className="gallery-error"><p>ギャラリーの読み込みに失敗しました</p></div>;
    }

    return (
        <>
            {!isFixedUiShell() ? (
                <div className="gallery-header">
                    <h2 className="section-title"><i className="fas fa-images" /> ギャラリー</h2>
                </div>
            ) : null}
            <div className="gallery-tabs">
                <button
                    type="button"
                    className={`gallery-tab${categoryId === null || categoryId === '' ? ' active' : ''}`}
                    onClick={() => void load(null)}
                >
                    すべて
                </button>
                {categories.map((cat) => (
                    <button
                        key={String(cat.id)}
                        type="button"
                        className={`gallery-tab${String(categoryId ?? '') === String(cat.id) ? ' active' : ''}`}
                        onClick={() => void load(cat.id)}
                    >
                        {cat.name}
                    </button>
                ))}
            </div>
            {items.length === 0 ? (
                <div className="gallery-empty">
                    <span className="gallery-empty__icon" aria-hidden="true"><i className="fas fa-images" /></span>
                    <p className="gallery-empty__title">ギャラリー画像はまだありません</p>
                    <p className="gallery-empty__hint">店舗の写真が登録されるとここに表示されます</p>
                </div>
            ) : (
                <div className="gallery-grid">
                    {items.map((item) => (
                        <button
                            key={String(item.id)}
                            type="button"
                            className={`gallery-item gallery-item--${item.size || 'medium'}`}
                            onClick={() => openDetail(item)}
                        >
                            <img src={item.image_url || ''} alt={item.title || ''} loading="lazy" />
                            {item.title ? <div className="gallery-item-title">{item.title}</div> : null}
                            {item.description ? <div className="gallery-item-desc">{item.description}</div> : null}
                        </button>
                    ))}
                </div>
            )}
        </>
    );
}

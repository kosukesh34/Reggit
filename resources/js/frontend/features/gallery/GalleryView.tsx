import type { JSX } from 'react';
import React, { useCallback, useEffect, useState } from 'react';
import { isFixedUiShell } from '../../demo/demoMode';
import {
    fetchGallery,
    type GalleryCategory,
    type GalleryItem,
} from './api';

type LoadState = 'loading' | 'ready' | 'error';

type GalleryViewProps = {
    /** Builder 埋め込み時はヘッダーなし・タブなしのグリッドのみ */
    embed?: boolean;
};

function EmptyState(): JSX.Element {
    return (
        <div className="gallery-empty">
            <span className="gallery-empty__icon" aria-hidden="true">
                <i className="fas fa-images" />
            </span>
            <p className="gallery-empty__title">ギャラリー画像はまだありません</p>
            <p className="gallery-empty__hint">店舗の写真が登録されるとここに表示されます</p>
        </div>
    );
}

function GalleryItemButton({ item, onOpen }: { item: GalleryItem; onOpen: (id: string) => void }): JSX.Element {
    const id = String(item.id ?? '');
    return (
        <button
            type="button"
            className={`gallery-item gallery-item--${item.size || 'medium'}`}
            data-gallery-id={id}
            onClick={() => {
                if (id !== '') {
                    onOpen(id);
                }
            }}
        >
            <img src={item.image_url || ''} alt={item.title || ''} loading="lazy" />
            {item.title ? <div className="gallery-item-title">{item.title}</div> : null}
            {item.description ? <div className="gallery-item-desc">{item.description}</div> : null}
        </button>
    );
}

export function GalleryView({ embed = false }: GalleryViewProps): JSX.Element {
    const [state, setState] = useState<LoadState>('loading');
    const [categories, setCategories] = useState<GalleryCategory[]>([]);
    const [items, setItems] = useState<GalleryItem[]>([]);
    const [categoryId, setCategoryId] = useState<string | number | null>(null);
    const [selected, setSelected] = useState<GalleryItem | null>(null);

    const load = useCallback(async (nextCategory: string | number | null) => {
        setState('loading');
        try {
            const data = await fetchGallery(nextCategory);
            setCategories(data.categories);
            setItems(data.items);
            setCategoryId(nextCategory);
            setState('ready');
        } catch {
            setState('error');
        }
    }, []);

    useEffect(() => {
        void load(null);
    }, [load]);

    const onOpen = (id: string): void => {
        const item = items.find((row) => String(row.id) === id);
        if (item !== undefined) {
            setSelected(item);
        }
    };

    if (selected !== null) {
        return (
            <div className="gallery-detail">
                <button type="button" onClick={() => setSelected(null)}>
                    ギャラリー一覧
                </button>
                {selected.image_url !== undefined && selected.image_url !== '' ? (
                    <img src={selected.image_url} alt={selected.title || ''} />
                ) : null}
                <div className="gallery-detail__body">
                    {selected.title !== undefined && selected.title !== '' ? <h2>{selected.title}</h2> : null}
                    {selected.description !== undefined && selected.description !== '' ? <p>{selected.description}</p> : null}
                </div>
            </div>
        );
    }

    if (state === 'loading') {
        return (
            <div className="loading">
                <i className="fas fa-spinner fa-spin" />
                <p>読み込み中...</p>
            </div>
        );
    }

    if (state === 'error') {
        return (
            <div className="gallery-error">
                <p>ギャラリーの読み込みに失敗しました</p>
            </div>
        );
    }

    if (embed) {
        if (items.length === 0) {
            return <EmptyState />;
        }
        return (
            <div className="gallery-grid">
                {items.map((item) => (
                    <GalleryItemButton key={String(item.id)} item={item} onOpen={onOpen} />
                ))}
            </div>
        );
    }

    const showHeader = !isFixedUiShell();

    return (
        <>
            {showHeader ? (
                <div className="gallery-header">
                    <h2 className="section-title">
                        <i className="fas fa-images" /> ギャラリー
                    </h2>
                </div>
            ) : null}
            <div className="gallery-tabs">
                <button
                    type="button"
                    className={`gallery-tab${categoryId === null || categoryId === '' ? ' active' : ''}`}
                    data-category=""
                    onClick={() => void load(null)}
                >
                    すべて
                </button>
                {categories.map((cat) => (
                    <button
                        key={String(cat.id)}
                        type="button"
                        className={`gallery-tab${String(categoryId ?? '') === String(cat.id) ? ' active' : ''}`}
                        data-category={String(cat.id)}
                        onClick={() => void load(cat.id)}
                    >
                        {cat.name}
                    </button>
                ))}
            </div>
            {items.length === 0 ? (
                <EmptyState />
            ) : (
                <div className="gallery-grid">
                    {items.map((item) => (
                        <GalleryItemButton key={String(item.id)} item={item} onOpen={onOpen} />
                    ))}
                </div>
            )}
        </>
    );
}

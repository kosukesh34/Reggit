import React, { useMemo, useState } from 'react';
import { useDesigner } from '../state/DesignerContext';
import {
    categoryLabel,
    collectCategoryKeys,
    featuredTemplates,
    galleryAccentFor,
    isAllScreensTemplate,
    loadFavoriteIds,
    paletteLabelFor,
    previewBlocksFor,
    saveFavoriteIds,
    templateCategoryKey,
    templateDisplayName,
    templateTags,
    type TemplateItem,
} from './templateGalleryMeta';

function countBlocks(tpl: TemplateItem): number {
    if (isAllScreensTemplate(tpl) && tpl.layouts !== undefined) {
        return Object.values(tpl.layouts).reduce((sum, rows) => sum + (Array.isArray(rows) ? rows.length : 0), 0);
    }
    return previewBlocksFor(tpl).length;
}

export function TemplateGallery({ compact = false }: { compact?: boolean }) {
    const { dispatch, config } = useDesigner();
    const templates = (Array.isArray(config.templates) ? config.templates : []) as TemplateItem[];
    const [category, setCategory] = useState('all');
    const [scope, setScope] = useState<'all' | 'tab' | 'super'>('all');
    const [keyword, setKeyword] = useState('');
    const [favoritesOnly, setFavoritesOnly] = useState(false);
    const [favoriteIds, setFavoriteIds] = useState<Set<string>>(() => loadFavoriteIds());

    const categoryKeys = useMemo(() => collectCategoryKeys(templates), [templates]);
    const featured = useMemo(() => featuredTemplates(templates, 6), [templates]);

    const filtered = useMemo(() => {
        const q = keyword.trim().toLowerCase();
        return templates.filter((tpl) => {
            const isSuper = isAllScreensTemplate(tpl);
            if (scope === 'tab' && isSuper) return false;
            if (scope === 'super' && !isSuper) return false;
            const cat = templateCategoryKey(tpl);
            if (category !== 'all' && cat !== category) return false;
            const id = String(tpl.id || '');
            if (favoritesOnly && !favoriteIds.has(id)) return false;
            if (q === '') return true;
            const hay = [
                templateDisplayName(tpl),
                tpl.description || '',
                cat,
                categoryLabel(cat),
                paletteLabelFor(tpl),
                ...templateTags(tpl),
                ...(Array.isArray(tpl.target_tabs) ? tpl.target_tabs : []),
            ].join(' ').toLowerCase();
            return hay.includes(q);
        });
    }, [templates, scope, category, keyword, favoritesOnly, favoriteIds]);

    const toggleFavorite = (id: string) => {
        setFavoriteIds((prev) => {
            const next = new Set(prev);
            if (next.has(id)) next.delete(id);
            else next.add(id);
            saveFavoriteIds(next);
            return next;
        });
    };

    const openApply = (tpl: TemplateItem) => {
        dispatch({
            type: 'SET_UI',
            patch: {
                templateOpen: true,
                pendingTemplateId: tpl.id !== undefined ? String(tpl.id) : null,
            },
        });
    };

    return (
        <div className={`ld-inline-gallery${compact ? ' ld-inline-gallery--compact' : ''}`}>
            <div className="ld-inline-gallery__toolbar">
                <div className="ld-inline-gallery__search">
                    <i className="fas fa-search" />
                    <input
                        type="search"
                        className="ld-form-input"
                        value={keyword}
                        onChange={(e) => setKeyword(e.target.value)}
                        placeholder="カフェ / サロン / ホテル…"
                    />
                </div>
                <button
                    type="button"
                    className={`ld-studio__icon-btn${favoritesOnly ? ' is-active' : ''}`}
                    onClick={() => setFavoritesOnly((v) => !v)}
                    title="お気に入りのみ"
                >
                    <i className={`${favoritesOnly ? 'fas' : 'far'} fa-heart`} />
                </button>
            </div>

            <div className="ld-studio__segment ld-inline-gallery__scope">
                <button type="button" className={scope === 'all' ? 'is-active' : ''} onClick={() => setScope('all')}>すべて</button>
                <button type="button" className={scope === 'tab' ? 'is-active' : ''} onClick={() => setScope('tab')}>画面用</button>
                <button type="button" className={scope === 'super' ? 'is-active' : ''} onClick={() => setScope('super')}>全画面</button>
            </div>

            <div className="ld-palette-categories ld-inline-gallery__cats">
                <button type="button" className={`ld-palette-cat${category === 'all' ? ' is-active' : ''}`} onClick={() => setCategory('all')}>すべて</button>
                {categoryKeys.map((key) => (
                    <button
                        key={key}
                        type="button"
                        className={`ld-palette-cat${category === key ? ' is-active' : ''}`}
                        onClick={() => setCategory(key)}
                    >
                        {categoryLabel(key)}
                    </button>
                ))}
            </div>

            {!favoritesOnly && keyword === '' && category === 'all' && scope === 'all' ? (
                <div className="ld-inline-gallery__featured">
                    <p className="ld-inline-gallery__label">おすすめ</p>
                    <div className="ld-inline-gallery__featured-row">
                        {featured.map((tpl, i) => {
                            const accent = galleryAccentFor(tpl);
                            return (
                                <button
                                    key={String(tpl.id || i)}
                                    type="button"
                                    className="ld-inline-gallery__featured-card"
                                    style={{ borderColor: `${accent.color}44`, background: accent.soft }}
                                    onClick={() => openApply(tpl)}
                                >
                                    <i className={`fas ${accent.icon}`} style={{ color: accent.color }} />
                                    <span>{templateDisplayName(tpl, i)}</span>
                                </button>
                            );
                        })}
                    </div>
                </div>
            ) : null}

            <p className="ld-inline-gallery__count">{filtered.length} / {templates.length}</p>
            <div className="ld-inline-gallery__grid">
                {filtered.length === 0 ? (
                    <div className="ld-palette-empty">
                        <i className="fas fa-search" />
                        <p>該当するテンプレートがありません</p>
                    </div>
                ) : filtered.map((tpl, i) => {
                    const id = String(tpl.id || i);
                    const accent = galleryAccentFor(tpl);
                    const isSuper = isAllScreensTemplate(tpl);
                    const blocks = countBlocks(tpl);
                    const isFav = favoriteIds.has(String(tpl.id || ''));
                    const bg = String(tpl.theme?.bg_color || accent.soft);
                    return (
                        <article key={id} className="ld-inline-gallery__tile">
                            <button
                                type="button"
                                className="ld-inline-gallery__tile-main"
                                onClick={() => openApply(tpl)}
                                style={{
                                    background: `linear-gradient(165deg, ${bg} 0%, ${accent.soft} 100%)`,
                                    borderColor: `${accent.color}33`,
                                }}
                            >
                                <span className="ld-inline-gallery__tile-icon" style={{ color: accent.color, background: '#fff' }}>
                                    <i className={`fas ${accent.icon}`} />
                                </span>
                                <strong>{templateDisplayName(tpl, i)}</strong>
                                <em>
                                    {isSuper ? '全画面' : '単一画面'} · {blocks}ブロック
                                </em>
                                <span className="ld-inline-gallery__tile-tags">
                                    {templateTags(tpl).slice(0, 3).map((tag) => (
                                        <i key={tag}>{tag}</i>
                                    ))}
                                </span>
                            </button>
                            <button
                                type="button"
                                className={`ld-inline-gallery__fav${isFav ? ' is-active' : ''}`}
                                title="お気に入り"
                                onClick={() => {
                                    if (tpl.id !== undefined) toggleFavorite(String(tpl.id));
                                }}
                            >
                                <i className={`${isFav ? 'fas' : 'far'} fa-heart`} />
                            </button>
                        </article>
                    );
                })}
            </div>

            <button
                type="button"
                className="ld-btn ld-btn--soft ld-btn--block"
                onClick={() => dispatch({ type: 'SET_UI', patch: { templateOpen: true, pendingTemplateId: null } })}
            >
                <i className="fas fa-expand" /> 大きなギャラリーで開く
            </button>
        </div>
    );
}

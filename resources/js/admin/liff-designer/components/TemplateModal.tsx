import React, { useMemo, useState } from 'react';
import { useDesigner } from '../state/DesignerContext';
import type { DesignerState, LiffBlock } from '../catalog/types';
import { buildTemplateApplyResult, resolvePreferredTab } from './applyTemplate';
import { TemplatePhoneMock } from './TemplatePhoneMock';
import {
    CATEGORY_ACCENTS,
    categoryLabel,
    collectCategoryKeys,
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

type TemplateImpact = {
    isAllScreens: boolean;
    targetLabel: string;
    screenLabels: string[];
    currentBlockCount: number;
    newBlockCount: number;
    appliesTheme: boolean;
    appliesTabSettings: boolean;
};

type ViewMode = 'gallery' | 'preview' | 'confirm';

function countBlocksInLayouts(layouts: Record<string, LiffBlock[]>): number {
    return Object.values(layouts).reduce((sum, rows) => sum + rows.length, 0);
}

function hasLayoutsObject(tpl: TemplateItem): boolean {
    return tpl.layouts !== undefined && tpl.layouts !== null && typeof tpl.layouts === 'object' && !Array.isArray(tpl.layouts);
}

function describeTemplateImpact(tpl: TemplateItem, state: DesignerState): TemplateImpact {
    const menuByTab = Object.fromEntries(state.menuItems.map((m) => [m.tab, m]));
    const appliesTheme = tpl.theme !== undefined && typeof tpl.theme === 'object' && tpl.theme !== null;
    const appliesTabSettings = tpl.tab_settings !== undefined && typeof tpl.tab_settings === 'object' && tpl.tab_settings !== null;

    if (isAllScreensTemplate(tpl) && hasLayoutsObject(tpl)) {
        const tabs = Object.keys(tpl.layouts!);
        const newBlockCount = tabs.reduce((sum, tab) => {
            const rows = Array.isArray(tpl.layouts?.[tab]) ? tpl.layouts[tab] : [];
            return sum + rows.length;
        }, 0);
        return {
            isAllScreens: true,
            targetLabel: '全画面',
            screenLabels: tabs.map((tab) => menuByTab[tab]?.label || tab),
            currentBlockCount: countBlocksInLayouts(state.layouts),
            newBlockCount,
            appliesTheme,
            appliesTabSettings,
        };
    }

    const tab = resolvePreferredTab(tpl, state);
    const rows = Array.isArray(tpl.blocks) ? tpl.blocks : [];
    return {
        isAllScreens: false,
        targetLabel: menuByTab[tab]?.label || tab,
        screenLabels: [menuByTab[tab]?.label || tab],
        currentBlockCount: (state.layouts[tab] || []).length,
        newBlockCount: rows.length,
        appliesTheme,
        appliesTabSettings,
    };
}

export function TemplateModal() {
    const { state, dispatch, config } = useDesigner();
    const [viewMode, setViewMode] = useState<ViewMode>('gallery');
    const [activeTemplate, setActiveTemplate] = useState<TemplateItem | null>(null);
    const [category, setCategory] = useState('all');
    const [scope, setScope] = useState<'all' | 'tab' | 'super'>('all');
    const [keyword, setKeyword] = useState('');
    const [favoritesOnly, setFavoritesOnly] = useState(false);
    const [favoriteIds, setFavoriteIds] = useState<Set<string>>(() => loadFavoriteIds());

    const templates = (Array.isArray(config.templates) ? config.templates : []) as TemplateItem[];
    const categoryKeys = useMemo(() => collectCategoryKeys(templates), [templates]);
    const impact = useMemo(
        () => (activeTemplate !== null ? describeTemplateImpact(activeTemplate, state) : null),
        [activeTemplate, state],
    );

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
                paletteLabelFor(tpl),
                ...(Array.isArray(tpl.target_tabs) ? tpl.target_tabs : []),
            ]
                .join(' ')
                .toLowerCase();
            return hay.includes(q);
        });
    }, [templates, scope, category, keyword, favoritesOnly, favoriteIds]);

    if (!state.templateOpen) return null;

    const closeModal = () => {
        setViewMode('gallery');
        setActiveTemplate(null);
        setKeyword('');
        setCategory('all');
        setScope('all');
        setFavoritesOnly(false);
        dispatch({ type: 'SET_UI', patch: { templateOpen: false } });
    };

    const toggleFavorite = (id: string) => {
        setFavoriteIds((prev) => {
            const next = new Set(prev);
            if (next.has(id)) next.delete(id);
            else next.add(id);
            saveFavoriteIds(next);
            return next;
        });
    };

    const applyTemplate = (tpl: TemplateItem) => {
        const result = buildTemplateApplyResult(tpl, state, config.themePresets);
        if (!result.applied) {
            dispatch({ type: 'SHOW_TOAST', message: result.message, isError: true });
            return;
        }
        dispatch({
            type: 'APPLY_LAYOUTS',
            layouts: result.layouts,
            globalSettings: result.globalSettings,
            tabSettings: result.tabSettings,
            customCss: result.customCss,
            activeTab: result.activeTab,
        });
        dispatch({ type: 'SHOW_TOAST', message: result.message });
        closeModal();
    };

    const openPreview = (tpl: TemplateItem) => {
        setActiveTemplate(tpl);
        setViewMode('preview');
    };

    const openConfirm = (tpl: TemplateItem) => {
        setActiveTemplate(tpl);
        setViewMode('confirm');
    };

    const backToGallery = () => {
        setActiveTemplate(null);
        setViewMode('gallery');
    };

    return (
        <div className="ld-modal-backdrop ld-gallery-backdrop" onClick={closeModal}>
            <div className="ld-gallery" onClick={(e) => e.stopPropagation()} role="dialog" aria-modal="true" aria-label="テンプレートギャラリー">
                {viewMode === 'confirm' && activeTemplate !== null && impact !== null ? (
                    <ConfirmView
                        template={activeTemplate}
                        impact={impact}
                        onBack={() => setViewMode(activeTemplate !== null ? 'preview' : 'gallery')}
                        onConfirm={() => applyTemplate(activeTemplate)}
                        onClose={closeModal}
                    />
                ) : viewMode === 'preview' && activeTemplate !== null ? (
                    <PreviewView
                        template={activeTemplate}
                        impact={impact}
                        isFavorite={favoriteIds.has(String(activeTemplate.id || ''))}
                        onToggleFavorite={() => toggleFavorite(String(activeTemplate.id || ''))}
                        onBack={backToGallery}
                        onChoose={() => setViewMode('confirm')}
                        onClose={closeModal}
                    />
                ) : (
                    <GalleryView
                        templates={filtered}
                        totalCount={templates.length}
                        category={category}
                        categoryKeys={categoryKeys}
                        scope={scope}
                        keyword={keyword}
                        favoritesOnly={favoritesOnly}
                        favoriteIds={favoriteIds}
                        onCategory={setCategory}
                        onScope={setScope}
                        onKeyword={setKeyword}
                        onFavoritesOnly={() => setFavoritesOnly((v) => !v)}
                        onToggleFavorite={toggleFavorite}
                        onPreview={openPreview}
                        onChoose={openConfirm}
                        onClose={closeModal}
                    />
                )}
            </div>
        </div>
    );
}

function GalleryView({
    templates,
    totalCount,
    category,
    categoryKeys,
    scope,
    keyword,
    favoritesOnly,
    favoriteIds,
    onCategory,
    onScope,
    onKeyword,
    onFavoritesOnly,
    onToggleFavorite,
    onPreview,
    onChoose,
    onClose,
}: {
    templates: TemplateItem[];
    totalCount: number;
    category: string;
    categoryKeys: string[];
    scope: 'all' | 'tab' | 'super';
    keyword: string;
    favoritesOnly: boolean;
    favoriteIds: Set<string>;
    onCategory: (v: string) => void;
    onScope: (v: 'all' | 'tab' | 'super') => void;
    onKeyword: (v: string) => void;
    onFavoritesOnly: () => void;
    onToggleFavorite: (id: string) => void;
    onPreview: (tpl: TemplateItem) => void;
    onChoose: (tpl: TemplateItem) => void;
    onClose: () => void;
}) {
    return (
        <>
            <header className="ld-gallery__top">
                <div className="ld-gallery__brand">
                    <span className="ld-gallery__brand-mark" aria-hidden>
                        <i className="fas fa-layer-group" />
                    </span>
                    <div>
                        <strong>LIFF Studio</strong>
                        <span>デザインギャラリー</span>
                    </div>
                </div>

                <nav className="ld-gallery__cats" aria-label="カテゴリ">
                    <button type="button" className={category === 'all' ? 'is-active' : ''} onClick={() => onCategory('all')}>
                        すべて
                    </button>
                    {categoryKeys.map((key) => (
                        <button
                            key={key}
                            type="button"
                            className={category === key ? 'is-active' : ''}
                            onClick={() => onCategory(key)}
                        >
                            {categoryLabel(key)}
                        </button>
                    ))}
                </nav>

                <div className="ld-gallery__top-actions">
                    <button
                        type="button"
                        className={`ld-gallery__fav-toggle${favoritesOnly ? ' is-active' : ''}`}
                        onClick={onFavoritesOnly}
                        aria-pressed={favoritesOnly}
                        title="お気に入りのみ"
                    >
                        <i className={`${favoritesOnly ? 'fas' : 'far'} fa-heart`} />
                    </button>
                    <button type="button" className="ld-gallery__close" onClick={onClose} aria-label="閉じる">
                        <i className="fas fa-times" />
                    </button>
                </div>
            </header>

            <div className="ld-gallery__toolbar">
                <div className="ld-gallery__scope">
                    <button type="button" className={scope === 'all' ? 'is-active' : ''} onClick={() => onScope('all')}>
                        すべて
                    </button>
                    <button type="button" className={scope === 'tab' ? 'is-active' : ''} onClick={() => onScope('tab')}>
                        タブ用
                    </button>
                    <button type="button" className={scope === 'super' ? 'is-active' : ''} onClick={() => onScope('super')}>
                        全画面パターン
                    </button>
                </div>
                <div className="ld-gallery__search">
                    <i className="fas fa-search" />
                    <input
                        type="search"
                        value={keyword}
                        onChange={(e) => onKeyword(e.target.value)}
                        placeholder="業種・用途で検索（カフェ / 予約 / 会員）"
                    />
                </div>
                <div className="ld-gallery__count">
                    {templates.length} / {totalCount}
                </div>
            </div>

            <div className="ld-gallery__body">
                {templates.length === 0 ? (
                    <div className="ld-gallery__empty">
                        <i className="fas fa-search" />
                        <p>条件に一致するテンプレートがありません。</p>
                    </div>
                ) : (
                    <div className="ld-gallery__grid">
                        {templates.map((tpl, i) => {
                            const id = String(tpl.id || i);
                            const accent = galleryAccentFor(tpl);
                            const tags = templateTags(tpl);
                            const isFav = favoriteIds.has(String(tpl.id || ''));
                            return (
                                <article key={id} className="ld-gallery-card">
                                    <div className="ld-gallery-card__head">
                                        <div
                                            className="ld-gallery-card__icon"
                                            style={{ background: accent.soft, color: accent.color }}
                                        >
                                            <i className={`fas ${accent.icon}`} />
                                        </div>
                                        <div className="ld-gallery-card__titles">
                                            <strong>{templateDisplayName(tpl, i)}</strong>
                                            <span>{paletteLabelFor(tpl)}</span>
                                        </div>
                                        <button
                                            type="button"
                                            className={`ld-gallery-card__heart${isFav ? ' is-active' : ''}`}
                                            onClick={() => toggleFavoriteSafe(tpl.id, onToggleFavorite)}
                                            aria-label="お気に入り"
                                        >
                                            <i className={`${isFav ? 'fas' : 'far'} fa-heart`} />
                                        </button>
                                    </div>

                                    <button type="button" className="ld-gallery-card__mock" onClick={() => onPreview(tpl)}>
                                        <TemplatePhoneMock template={tpl} />
                                    </button>

                                    <div className="ld-gallery-card__tags">
                                        {tags.map((tag) => (
                                            <span key={tag}>{tag}</span>
                                        ))}
                                    </div>

                                    <div className="ld-gallery-card__actions">
                                        <button type="button" className="ld-gallery-card__preview" onClick={() => onPreview(tpl)}>
                                            <i className="fas fa-eye" />
                                            Preview
                                        </button>
                                        <button type="button" className="ld-gallery-card__choose" onClick={() => onChoose(tpl)}>
                                            Choose
                                            <i className="fas fa-arrow-right" />
                                        </button>
                                    </div>
                                </article>
                            );
                        })}
                    </div>
                )}
            </div>
        </>
    );
}

function toggleFavoriteSafe(id: string | undefined, onToggleFavorite: (id: string) => void) {
    if (id !== undefined && id !== '') onToggleFavorite(id);
}

function PreviewView({
    template,
    impact,
    isFavorite,
    onToggleFavorite,
    onBack,
    onChoose,
    onClose,
}: {
    template: TemplateItem;
    impact: TemplateImpact | null;
    isFavorite: boolean;
    onToggleFavorite: () => void;
    onBack: () => void;
    onChoose: () => void;
    onClose: () => void;
}) {
    const accent = galleryAccentFor(template);
    const blocks = previewBlocksFor(template);
    const cat = CATEGORY_ACCENTS[templateCategoryKey(template)] || CATEGORY_ACCENTS.other;

    return (
        <div className="ld-gallery-preview">
            <header className="ld-gallery-preview__head">
                <button type="button" className="ld-gallery-preview__back" onClick={onBack}>
                    <i className="fas fa-arrow-left" />
                    ギャラリーへ
                </button>
                <div className="ld-gallery-preview__title">
                    <div className="ld-gallery-card__icon" style={{ background: cat.soft, color: cat.color }}>
                        <i className={`fas ${cat.icon}`} />
                    </div>
                    <div>
                        <strong>{templateDisplayName(template)}</strong>
                        <span>{paletteLabelFor(template)}</span>
                    </div>
                </div>
                <div className="ld-gallery__top-actions">
                    <button
                        type="button"
                        className={`ld-gallery__fav-toggle${isFavorite ? ' is-active' : ''}`}
                        onClick={onToggleFavorite}
                    >
                        <i className={`${isFavorite ? 'fas' : 'far'} fa-heart`} />
                    </button>
                    <button type="button" className="ld-gallery__close" onClick={onClose} aria-label="閉じる">
                        <i className="fas fa-times" />
                    </button>
                </div>
            </header>

            <div className="ld-gallery-preview__body">
                <div className="ld-gallery-preview__phone">
                    <TemplatePhoneMock template={template} size="large" />
                </div>
                <aside className="ld-gallery-preview__meta">
                    <p>{template.description || 'プロ品質のレイアウトをワンクリックで適用できます。'}</p>
                    <dl>
                        <div>
                            <dt>適用先</dt>
                            <dd>{impact?.isAllScreens ? '全画面' : `「${impact?.targetLabel || ''}」のみ`}</dd>
                        </div>
                        <div>
                            <dt>ブロック数</dt>
                            <dd>{blocks.length} 要素{impact?.isAllScreens ? `（全タブ合計 ${impact.newBlockCount}）` : ''}</dd>
                        </div>
                        <div>
                            <dt>カテゴリ</dt>
                            <dd>{categoryLabel(templateCategoryKey(template))}</dd>
                        </div>
                        <div>
                            <dt>パレット</dt>
                            <dd>
                                <span className="ld-gallery-preview__swatch" style={{ background: accent.color }} />
                                {paletteLabelFor(template)}
                            </dd>
                        </div>
                    </dl>
                    <div className="ld-gallery-card__tags">
                        {templateTags(template).map((tag) => (
                            <span key={tag}>{tag}</span>
                        ))}
                    </div>
                    <div className="ld-gallery-preview__actions">
                        <button type="button" className="ld-gallery-card__preview" onClick={onBack}>
                            戻る
                        </button>
                        <button type="button" className="ld-gallery-card__choose" onClick={onChoose}>
                            Choose
                            <i className="fas fa-arrow-right" />
                        </button>
                    </div>
                </aside>
            </div>
        </div>
    );
}

function ConfirmView({
    template,
    impact,
    onBack,
    onConfirm,
    onClose,
}: {
    template: TemplateItem;
    impact: TemplateImpact;
    onBack: () => void;
    onConfirm: () => void;
    onClose: () => void;
}) {
    return (
        <div className="ld-gallery-confirm">
            <header className="ld-gallery-preview__head">
                <button type="button" className="ld-gallery-preview__back" onClick={onBack}>
                    <i className="fas fa-arrow-left" />
                    戻る
                </button>
                <h2>テンプレートの適用確認</h2>
                <button type="button" className="ld-gallery__close" onClick={onClose} aria-label="閉じる">
                    <i className="fas fa-times" />
                </button>
            </header>
            <div className="ld-gallery-confirm__body">
                <div className="ld-gallery-confirm__visual">
                    <TemplatePhoneMock template={template} />
                </div>
                <div className="ld-template-confirm">
                    <p className="ld-template-confirm__lead">
                        <strong>{templateDisplayName(template)}</strong>
                        を適用すると、現在の内容が上書きされます。
                    </p>
                    <dl className="ld-template-confirm__list">
                        <div>
                            <dt>適用先</dt>
                            <dd>{impact.isAllScreens ? '全画面' : `「${impact.targetLabel}」のみ`}</dd>
                        </div>
                        <div>
                            <dt>ブロック数</dt>
                            <dd>
                                {impact.currentBlockCount} 個 → {impact.newBlockCount} 個に置き換え
                            </dd>
                        </div>
                        {impact.isAllScreens ? (
                            <div>
                                <dt>対象画面</dt>
                                <dd>{impact.screenLabels.join(' / ')}</dd>
                            </div>
                        ) : null}
                        {impact.appliesTheme ? (
                            <div>
                                <dt>テーマ</dt>
                                <dd>テンプレートのテーマ設定も反映されます</dd>
                            </div>
                        ) : null}
                        {impact.appliesTabSettings ? (
                            <div>
                                <dt>画面設定</dt>
                                <dd>テンプレートの画面設定も反映されます</dd>
                            </div>
                        ) : null}
                    </dl>
                    <p className="ld-form-hint">この操作は元に戻す（Ctrl+Z）で取り消せます。</p>
                    <div className="ld-template-confirm__actions">
                        <button type="button" className="ld-btn ld-btn--ghost" onClick={onBack}>
                            戻る
                        </button>
                        <button type="button" className="ld-btn ld-btn--primary" onClick={onConfirm}>
                            適用する
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}

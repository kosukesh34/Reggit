import React, { useEffect, useMemo, useState } from 'react';
import { useDesigner } from '../state/DesignerContext';
import type { DesignerState, LiffBlock, ThemePreset } from '../catalog/types';
import {
    buildPersonalizedPreviewTemplate,
    buildTemplateApplyResult,
    DEFAULT_TEMPLATE_SETUP,
    resolvePreferredTab,
    resolveTemplateThemeSelection,
    TEMPLATE_THEME_KEEP,
    type TemplateSetupOptions,
} from './applyTemplate';
import { TemplatePhoneMock } from './TemplatePhoneMock';
import {
    CATEGORY_ACCENTS,
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
import {
    MEMBER_CARD_CODE_TYPES,
    MEMBER_CARD_DESIGN_CHOICES,
    MEMBER_CARD_DISPLAY_TYPES,
} from '../catalog/blockCatalog';

type TemplateImpact = {
    isAllScreens: boolean;
    targetLabel: string;
    screenLabels: string[];
    currentBlockCount: number;
    newBlockCount: number;
    appliesTheme: boolean;
    appliesTabSettings: boolean;
};

type ViewMode = 'studio' | 'apply';

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
    const [viewMode, setViewMode] = useState<ViewMode>('studio');
    const [activeTemplate, setActiveTemplate] = useState<TemplateItem | null>(null);
    const [category, setCategory] = useState('all');
    const [scope, setScope] = useState<'all' | 'tab' | 'super'>('all');
    const [keyword, setKeyword] = useState('');
    const [favoritesOnly, setFavoritesOnly] = useState(false);
    const [favoriteIds, setFavoriteIds] = useState<Set<string>>(() => loadFavoriteIds());
    const [themeSelectionId, setThemeSelectionId] = useState(TEMPLATE_THEME_KEEP);
    const [setup, setSetup] = useState<TemplateSetupOptions>({ ...DEFAULT_TEMPLATE_SETUP });

    const templates = (Array.isArray(config.templates) ? config.templates : []) as TemplateItem[];
    const themePresets = (Array.isArray(config.themePresets) ? config.themePresets : []) as ThemePreset[];
    const categoryKeys = useMemo(() => collectCategoryKeys(templates), [templates]);
    const impact = useMemo(
        () => (activeTemplate !== null ? describeTemplateImpact(activeTemplate, state) : null),
        [activeTemplate, state],
    );
    const themeSelection = useMemo(
        () =>
            activeTemplate !== null
                ? resolveTemplateThemeSelection(activeTemplate, themeSelectionId, themePresets)
                : null,
        [activeTemplate, themeSelectionId, themePresets],
    );
    const livePreviewTemplate = useMemo(() => {
        if (themeSelection === null) return null;
        return buildPersonalizedPreviewTemplate(themeSelection.previewTemplate, setup);
    }, [themeSelection, setup]);

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
            ]
                .join(' ')
                .toLowerCase();
            return hay.includes(q);
        });
    }, [templates, scope, category, keyword, favoritesOnly, favoriteIds]);

    const initThemeSelection = (tpl: TemplateItem) => {
        const preset = String(tpl.theme?.theme_preset || '');
        const exists = preset !== '' && themePresets.some((p) => p.id === preset);
        setThemeSelectionId(exists ? preset : TEMPLATE_THEME_KEEP);
        const fromTheme = String(tpl.theme?.member_card_design || '');
        const cardDesign = MEMBER_CARD_DESIGN_CHOICES.some((d) => d.value === fromTheme) ? fromTheme : 'default';
        setSetup({
            ...DEFAULT_TEMPLATE_SETUP,
            shopName: String(state.globalSettings.app_display_name || ''),
            memberCardDesign: cardDesign,
            memberCardDisplayType: String(state.globalSettings.member_card_display_type || 'card'),
            memberCardCodeType: String(state.globalSettings.member_card_code_type || 'barcode'),
            fontFamily: String(tpl.theme?.font_family || state.globalSettings.font_family || ''),
            contentWidth: String(tpl.theme?.content_width || state.globalSettings.content_width || ''),
            buttonShape: String(tpl.theme?.button_shape || state.globalSettings.button_shape || ''),
            cardStyle: String(tpl.theme?.card_style || state.globalSettings.card_style || ''),
        });
    };

    const selectTemplate = (tpl: TemplateItem) => {
        setActiveTemplate(tpl);
        initThemeSelection(tpl);
    };

    useEffect(() => {
        if (!state.templateOpen) return;
        if (state.pendingTemplateId !== null && state.pendingTemplateId !== '') {
            const pending = templates.find((tpl) => String(tpl.id || '') === state.pendingTemplateId);
            if (pending !== undefined) {
                selectTemplate(pending);
                setViewMode('apply');
                dispatch({ type: 'SET_UI', patch: { pendingTemplateId: null } });
                return;
            }
        }
        if (filtered.length === 0) {
            setActiveTemplate(null);
            return;
        }
        const stillVisible =
            activeTemplate !== null
            && filtered.some((tpl) => {
                if (activeTemplate.id !== undefined && activeTemplate.id !== '') {
                    return tpl.id === activeTemplate.id;
                }
                return templateDisplayName(tpl) === templateDisplayName(activeTemplate);
            });
        if (!stillVisible) {
            selectTemplate(filtered[0]);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [state.templateOpen, state.pendingTemplateId, filtered]);

    if (!state.templateOpen) return null;

    const closeModal = () => {
        setViewMode('studio');
        setActiveTemplate(null);
        setKeyword('');
        setCategory('all');
        setScope('all');
        setFavoritesOnly(false);
        setThemeSelectionId(TEMPLATE_THEME_KEEP);
        setSetup({ ...DEFAULT_TEMPLATE_SETUP });
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
        if (setup.shopName.trim() === '') {
            dispatch({ type: 'SHOW_TOAST', message: '店舗名を入力してください', isError: true });
            return;
        }
        if (setup.memberCardDesign === '') {
            dispatch({ type: 'SHOW_TOAST', message: '会員証デザインを選んでください', isError: true });
            return;
        }
        const selection = resolveTemplateThemeSelection(tpl, themeSelectionId, themePresets);
        const result = buildTemplateApplyResult(
            tpl,
            state,
            themePresets,
            selection.themeOverride,
            setup,
        );
        if (!result.applied) {
            dispatch({ type: 'SHOW_TOAST', message: result.message, isError: true });
            return;
        }
        dispatch({
            type: 'APPLY_LAYOUTS',
            layouts: result.layouts,
            globalSettings: result.globalSettings,
            tabSettings: result.tabSettings,
            menuItems: result.menuItems,
            customCss: result.customCss,
            activeTab: result.activeTab,
        });
        const themeNote =
            selection.themeOverride !== undefined ? `（テーマ: ${selection.label}）` : '';
        dispatch({ type: 'SHOW_TOAST', message: `${result.message}${themeNote}` });
        closeModal();
    };

    const openApply = (tpl: TemplateItem) => {
        selectTemplate(tpl);
        setViewMode('apply');
    };

    return (
        <div className="ld-modal-backdrop ld-gallery-backdrop" onClick={closeModal}>
            <div
                className="ld-gallery ld-studio-shell"
                onClick={(e) => e.stopPropagation()}
                role="dialog"
                aria-modal="true"
                aria-label="テンプレートスタジオ"
            >
                {viewMode === 'apply' && activeTemplate !== null && impact !== null && themeSelection !== null && livePreviewTemplate !== null ? (
                    <ApplyView
                        template={activeTemplate}
                        previewTemplate={livePreviewTemplate}
                        impact={impact}
                        themePresets={themePresets}
                        themeSelectionId={themeSelectionId}
                        themeLabel={themeSelection.label}
                        setup={setup}
                        fontStacks={config.fontStacks || {}}
                        onSetupChange={setSetup}
                        onThemeSelect={setThemeSelectionId}
                        onBack={() => setViewMode('studio')}
                        onConfirm={() => applyTemplate(activeTemplate)}
                        onClose={closeModal}
                    />
                ) : (
                    <StudioBrowser
                        templates={filtered}
                        allTemplates={templates}
                        totalCount={templates.length}
                        category={category}
                        categoryKeys={categoryKeys}
                        scope={scope}
                        keyword={keyword}
                        favoritesOnly={favoritesOnly}
                        favoriteIds={favoriteIds}
                        activeTemplate={activeTemplate}
                        previewTemplate={livePreviewTemplate}
                        themePresets={themePresets}
                        themeSelectionId={themeSelectionId}
                        themeLabel={themeSelection?.label || ''}
                        setup={setup}
                        impact={impact}
                        fontStacks={config.fontStacks || {}}
                        onCategory={setCategory}
                        onScope={setScope}
                        onKeyword={setKeyword}
                        onFavoritesOnly={() => setFavoritesOnly((v) => !v)}
                        onToggleFavorite={toggleFavorite}
                        onSelect={selectTemplate}
                        onSetupChange={setSetup}
                        onThemeSelect={setThemeSelectionId}
                        onApply={openApply}
                        onClose={closeModal}
                    />
                )}
            </div>
        </div>
    );
}

function StudioBrowser({
    templates,
    allTemplates,
    totalCount,
    category,
    categoryKeys,
    scope,
    keyword,
    favoritesOnly,
    favoriteIds,
    activeTemplate,
    previewTemplate,
    themePresets,
    themeSelectionId,
    themeLabel,
    setup,
    impact,
    fontStacks,
    onCategory,
    onScope,
    onKeyword,
    onFavoritesOnly,
    onToggleFavorite,
    onSelect,
    onSetupChange,
    onThemeSelect,
    onApply,
    onClose,
}: {
    templates: TemplateItem[];
    allTemplates: TemplateItem[];
    totalCount: number;
    category: string;
    categoryKeys: string[];
    scope: 'all' | 'tab' | 'super';
    keyword: string;
    favoritesOnly: boolean;
    favoriteIds: Set<string>;
    activeTemplate: TemplateItem | null;
    previewTemplate: TemplateItem | null;
    themePresets: ThemePreset[];
    themeSelectionId: string;
    themeLabel: string;
    setup: TemplateSetupOptions;
    impact: TemplateImpact | null;
    fontStacks: Record<string, { label: string; base?: string; display?: string }>;
    onCategory: (v: string) => void;
    onScope: (v: 'all' | 'tab' | 'super') => void;
    onKeyword: (v: string) => void;
    onFavoritesOnly: () => void;
    onToggleFavorite: (id: string) => void;
    onSelect: (tpl: TemplateItem) => void;
    onSetupChange: (next: TemplateSetupOptions) => void;
    onThemeSelect: (id: string) => void;
    onApply: (tpl: TemplateItem) => void;
    onClose: () => void;
}) {
    const featured = featuredTemplates(allTemplates, 5);
    const activeKey =
        activeTemplate !== null
            ? String(activeTemplate.id || templateDisplayName(activeTemplate))
            : '';
    const accent = activeTemplate !== null ? galleryAccentFor(activeTemplate) : null;

    return (
        <div className="ld-studio">
            <header className="ld-studio__bar">
                <div className="ld-studio__brand">
                    <span className="ld-studio__mark" aria-hidden>
                        <i className="fas fa-swatchbook" />
                    </span>
                    <div>
                        <strong>テンプレートスタジオ</strong>
                        <span>一覧で選んで、右の端末で確認 → そのまま適用</span>
                    </div>
                </div>
                <div className="ld-studio__bar-search">
                    <i className="fas fa-search" />
                    <input
                        type="search"
                        value={keyword}
                        onChange={(e) => onKeyword(e.target.value)}
                        placeholder="カフェ / サロン / ホテル / 会員…"
                    />
                </div>
                <div className="ld-studio__bar-actions">
                    <button
                        type="button"
                        className={`ld-studio__icon-btn${favoritesOnly ? ' is-active' : ''}`}
                        onClick={onFavoritesOnly}
                        aria-pressed={favoritesOnly}
                        title="お気に入りのみ"
                    >
                        <i className={`${favoritesOnly ? 'fas' : 'far'} fa-heart`} />
                    </button>
                    <span className="ld-studio__count">{templates.length}<em>/{totalCount}</em></span>
                    <button type="button" className="ld-studio__icon-btn" onClick={onClose} aria-label="閉じる">
                        <i className="fas fa-times" />
                    </button>
                </div>
            </header>

            <div className="ld-studio__body">
                <aside className="ld-studio__rail" aria-label="絞り込み">
                    <p className="ld-studio__rail-label">表示範囲</p>
                    <div className="ld-studio__segment">
                        <button type="button" className={scope === 'all' ? 'is-active' : ''} onClick={() => onScope('all')}>すべて</button>
                        <button type="button" className={scope === 'tab' ? 'is-active' : ''} onClick={() => onScope('tab')}>画面用</button>
                        <button type="button" className={scope === 'super' ? 'is-active' : ''} onClick={() => onScope('super')}>Suite</button>
                    </div>

                    <p className="ld-studio__rail-label">カテゴリ</p>
                    <nav className="ld-studio__cats">
                        <button type="button" className={category === 'all' ? 'is-active' : ''} onClick={() => onCategory('all')}>
                            <i className="fas fa-border-all" />
                            <span>すべて</span>
                        </button>
                        {categoryKeys.map((key) => {
                            const meta = CATEGORY_ACCENTS[key] || CATEGORY_ACCENTS.other;
                            return (
                                <button
                                    key={key}
                                    type="button"
                                    className={category === key ? 'is-active' : ''}
                                    onClick={() => onCategory(key)}
                                >
                                    <i className={`fas ${meta.icon}`} style={{ color: category === key ? undefined : meta.color }} />
                                    <span>{categoryLabel(key)}</span>
                                </button>
                            );
                        })}
                    </nav>

                    {featured.length > 0 ? (
                        <>
                            <p className="ld-studio__rail-label">おすすめ</p>
                            <div className="ld-studio__featured">
                                {featured.map((tpl, i) => {
                                    const a = galleryAccentFor(tpl);
                                    const id = String(tpl.id || i);
                                    return (
                                        <button
                                            key={id || i}
                                            type="button"
                                            className={`ld-studio__featured-item${activeKey === String(tpl.id || templateDisplayName(tpl, i)) ? ' is-active' : ''}`}
                                            onClick={() => onSelect(tpl)}
                                        >
                                            <span style={{ background: a.soft, color: a.color }}>
                                                <i className={`fas ${a.icon}`} />
                                            </span>
                                            <em>{templateDisplayName(tpl, i)}</em>
                                        </button>
                                    );
                                })}
                            </div>
                        </>
                    ) : null}
                </aside>

                <section className="ld-studio__list ld-studio__gallery" aria-label="テンプレート一覧">
                    {templates.length === 0 ? (
                        <div className="ld-studio__empty">
                            <i className="fas fa-search" />
                            <p>条件に合うテンプレートがありません</p>
                            <button type="button" className="ld-btn ld-btn--soft ld-btn--xs" onClick={() => onCategory('all')}>
                                すべてを表示
                            </button>
                        </div>
                    ) : (
                        <div className="ld-studio__gallery-grid">
                            {templates.map((tpl, i) => {
                                const id = String(tpl.id || i);
                                const a = galleryAccentFor(tpl);
                                const tags = templateTags(tpl);
                                const rowKey = String(tpl.id || templateDisplayName(tpl, i));
                                const isFav = favoriteIds.has(String(tpl.id || ''));
                                const isActive = activeKey === rowKey;
                                const isSuper = isAllScreensTemplate(tpl);
                                const blockCount = previewBlocksFor(tpl).length;
                                return (
                                    <article
                                        key={id}
                                        className={`ld-gallery-card${isActive ? ' is-active' : ''}`}
                                        style={{ ['--row-accent' as string]: a.color }}
                                    >
                                        <div className="ld-gallery-card__head">
                                            <span className="ld-gallery-card__icon" style={{ background: a.soft, color: a.color }}>
                                                <i className={`fas ${a.icon}`} />
                                            </span>
                                            <div className="ld-gallery-card__titles">
                                                <strong>{templateDisplayName(tpl, i)}</strong>
                                                <span>
                                                    {paletteLabelFor(tpl)}
                                                    {' · '}
                                                    {isSuper ? '全画面Suite' : `${blockCount}ブロック`}
                                                    {' · '}
                                                    {categoryLabel(templateCategoryKey(tpl))}
                                                </span>
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
                                        <button
                                            type="button"
                                            className="ld-gallery-card__mock"
                                            onClick={() => onSelect(tpl)}
                                            aria-label={`${templateDisplayName(tpl, i)} を選択`}
                                        >
                                            <TemplatePhoneMock
                                                template={tpl}
                                                size="card"
                                                fontStacks={fontStacks}
                                            />
                                        </button>
                                        {tpl.description ? (
                                            <p className="ld-gallery-card__desc">{tpl.description}</p>
                                        ) : null}
                                        <div className="ld-gallery-card__tags">
                                            {tags.map((tag) => (
                                                <span key={tag}>{tag}</span>
                                            ))}
                                        </div>
                                        <div className="ld-gallery-card__actions">
                                            <button
                                                type="button"
                                                className="ld-gallery-card__preview"
                                                onClick={() => onSelect(tpl)}
                                            >
                                                プレビュー
                                            </button>
                                            <button
                                                type="button"
                                                className="ld-gallery-card__choose"
                                                onClick={() => onApply(tpl)}
                                            >
                                                使う
                                            </button>
                                        </div>
                                    </article>
                                );
                            })}
                        </div>
                    )}
                </section>

                <aside className="ld-studio__dock" aria-label="ライブプレビュー">
                    {activeTemplate === null || previewTemplate === null ? (
                        <div className="ld-studio__dock-empty">
                            <i className="fas fa-mobile-alt" />
                            <p>カードを選ぶと、<br />ここに実機プレビューが出ます</p>
                        </div>
                    ) : (
                        <>
                            <div className="ld-studio__dock-head">
                                <div>
                                    <strong>{templateDisplayName(activeTemplate)}</strong>
                                    <span>
                                        {themeLabel || paletteLabelFor(activeTemplate)}
                                        {impact !== null
                                            ? ` · ${impact.isAllScreens ? '全画面' : impact.targetLabel}`
                                            : ''}
                                    </span>
                                </div>
                                {accent !== null ? (
                                    <span className="ld-studio__dock-swatch" style={{ background: accent.color }} />
                                ) : null}
                            </div>

                            <div className="ld-studio__dock-phone">
                                <TemplatePhoneMock
                                    template={previewTemplate}
                                    size="large"
                                    interactive
                                    fontStacks={fontStacks}
                                />
                            </div>

                            <div className="ld-studio__dock-tools">
                                <PreviewStyleControls
                                    template={activeTemplate}
                                    themePresets={themePresets}
                                    themeSelectionId={themeSelectionId}
                                    setup={setup}
                                    fontStacks={fontStacks}
                                    onThemeSelect={onThemeSelect}
                                    onSetupChange={onSetupChange}
                                    compact
                                />
                            </div>

                            <div className="ld-studio__dock-actions">
                                <button
                                    type="button"
                                    className="ld-btn ld-btn--primary ld-btn--block"
                                    onClick={() => onApply(activeTemplate)}
                                >
                                    このテンプレで進む
                                    <i className="fas fa-arrow-right" />
                                </button>
                                <p className="ld-studio__dock-note">
                                    端末のタブやボタンで画面遷移を確認できます。次の画面で店舗名などを入力します。
                                </p>
                            </div>
                        </>
                    )}
                </aside>
            </div>
        </div>
    );
}

function toggleFavoriteSafe(id: string | undefined, onToggleFavorite: (id: string) => void) {
    if (id !== undefined && id !== '') onToggleFavorite(id);
}

function ThemePalettePicker({
    template,
    presets,
    selectionId,
    onSelect,
    compact = false,
}: {
    template: TemplateItem;
    presets: ThemePreset[];
    selectionId: string;
    onSelect: (id: string) => void;
    compact?: boolean;
}) {
    const templateAccent = galleryAccentFor(template).color;
    const templateBg = String(template.theme?.bg_color || '#fafaf9');

    return (
        <div className={`ld-apply-theme${compact ? ' ld-apply-theme--compact' : ''}`}>
            <div className="ld-apply-theme__head">
                <strong>カラーパレット</strong>
                <span>即反映</span>
            </div>
            <div className="ld-apply-theme__grid">
                <button
                    type="button"
                    className={`ld-apply-theme__card${selectionId === TEMPLATE_THEME_KEEP ? ' is-active' : ''}`}
                    onClick={() => onSelect(TEMPLATE_THEME_KEEP)}
                >
                    <span
                        className="ld-apply-theme__swatch"
                        style={{ background: `linear-gradient(135deg, ${templateBg} 52%, ${templateAccent} 52%)` }}
                    />
                    <em>標準</em>
                </button>
                {presets.map((preset) => {
                    const accent = String(preset.accent_color || preset.accent || '#1c1917');
                    const bg = String(preset.bg_color || '#fafaf9');
                    return (
                        <button
                            key={preset.id}
                            type="button"
                            className={`ld-apply-theme__card${selectionId === preset.id ? ' is-active' : ''}`}
                            onClick={() => onSelect(preset.id)}
                        >
                            <span
                                className="ld-apply-theme__swatch"
                                style={{ background: `linear-gradient(135deg, ${bg} 52%, ${accent} 52%)` }}
                            />
                            <em>{preset.label || preset.name || preset.id}</em>
                        </button>
                    );
                })}
            </div>
        </div>
    );
}

const FONT_CHOICES = [
    { value: '', label: '標準' },
    { value: 'sans', label: 'モダン' },
    { value: 'rounded', label: 'やわらか' },
    { value: 'serif', label: '明朝' },
    { value: 'serif-display', label: '見出し明朝' },
];

const WIDTH_CHOICES = [
    { value: '', label: '標準' },
    { value: 'edge', label: 'フル幅' },
    { value: 'comfy', label: 'ゆったり' },
    { value: 'narrow', label: 'ナロー' },
];

const BUTTON_CHOICES = [
    { value: '', label: '標準' },
    { value: 'pill', label: 'ピル' },
    { value: 'rounded', label: '丸み' },
    { value: 'square', label: '直角' },
];

const CARD_CHOICES = [
    { value: '', label: '標準' },
    { value: 'elevated', label: '浮き' },
    { value: 'outline', label: '枠線' },
    { value: 'flat', label: 'フラット' },
];

function ChipGroup({
    label,
    hint,
    value,
    options,
    onChange,
}: {
    label: string;
    hint?: string;
    value: string;
    options: Array<{ value: string; label: string }>;
    onChange: (v: string) => void;
}) {
    return (
        <div className="ld-apply-chips">
            <div className="ld-apply-chips__head">
                <strong>{label}</strong>
                {hint !== undefined && hint !== '' ? <span>{hint}</span> : null}
            </div>
            <div className="ld-apply-chips__row">
                {options.map((opt) => (
                    <button
                        key={opt.value === '' ? '__default' : opt.value}
                        type="button"
                        className={`ld-apply-chip${value === opt.value ? ' is-active' : ''}`}
                        onClick={() => onChange(opt.value)}
                    >
                        {opt.label}
                    </button>
                ))}
            </div>
        </div>
    );
}

function PreviewStyleControls({
    template,
    themePresets,
    themeSelectionId,
    setup,
    fontStacks,
    onThemeSelect,
    onSetupChange,
    compact = false,
}: {
    template: TemplateItem;
    themePresets: ThemePreset[];
    themeSelectionId: string;
    setup: TemplateSetupOptions;
    fontStacks: Record<string, { label: string; base?: string; display?: string }>;
    onThemeSelect: (id: string) => void;
    onSetupChange: (next: TemplateSetupOptions) => void;
    compact?: boolean;
}) {
    const patch = (partial: Partial<TemplateSetupOptions>) => onSetupChange({ ...setup, ...partial });
    const fontOptions = [
        { value: '', label: '標準' },
        ...Object.entries(fontStacks).map(([value, meta]) => ({
            value,
            label: meta.label || FONT_CHOICES.find((f) => f.value === value)?.label || value,
        })),
    ];
    const fonts = fontOptions.length > 1 ? fontOptions : FONT_CHOICES;

    return (
        <div className={`ld-preview-controls${compact ? ' ld-preview-controls--compact' : ''}`}>
            <ThemePalettePicker
                template={template}
                presets={themePresets}
                selectionId={themeSelectionId}
                onSelect={onThemeSelect}
                compact={compact}
            />
            <ChipGroup
                label="フォント"
                value={setup.fontFamily}
                options={fonts}
                onChange={(v) => patch({ fontFamily: v })}
            />
            {!compact ? (
                <>
                    <ChipGroup
                        label="コンテンツ幅"
                        value={setup.contentWidth}
                        options={WIDTH_CHOICES}
                        onChange={(v) => patch({ contentWidth: v })}
                    />
                    <div className="ld-preview-controls__row">
                        <ChipGroup
                            label="ボタン形"
                            value={setup.buttonShape}
                            options={BUTTON_CHOICES}
                            onChange={(v) => patch({ buttonShape: v })}
                        />
                        <ChipGroup
                            label="カード"
                            value={setup.cardStyle}
                            options={CARD_CHOICES}
                            onChange={(v) => patch({ cardStyle: v })}
                        />
                    </div>
                </>
            ) : (
                <div className="ld-preview-controls__row">
                    <ChipGroup
                        label="幅"
                        value={setup.contentWidth}
                        options={WIDTH_CHOICES}
                        onChange={(v) => patch({ contentWidth: v })}
                    />
                    <ChipGroup
                        label="ボタン"
                        value={setup.buttonShape}
                        options={BUTTON_CHOICES}
                        onChange={(v) => patch({ buttonShape: v })}
                    />
                </div>
            )}
        </div>
    );
}

function ApplyView({
    template,
    previewTemplate,
    impact,
    themePresets,
    themeSelectionId,
    themeLabel,
    setup,
    fontStacks,
    onSetupChange,
    onThemeSelect,
    onBack,
    onConfirm,
    onClose,
}: {
    template: TemplateItem;
    previewTemplate: TemplateItem;
    impact: TemplateImpact;
    themePresets: ThemePreset[];
    themeSelectionId: string;
    themeLabel: string;
    setup: TemplateSetupOptions;
    fontStacks: Record<string, { label: string; base?: string; display?: string }>;
    onSetupChange: (next: TemplateSetupOptions) => void;
    onThemeSelect: (id: string) => void;
    onBack: () => void;
    onConfirm: () => void;
    onClose: () => void;
}) {
    const canApply = setup.shopName.trim() !== '' && setup.memberCardDesign !== '';
    const patchSetup = (partial: Partial<TemplateSetupOptions>) => onSetupChange({ ...setup, ...partial });
    const accent = galleryAccentFor(template);

    return (
        <div className="ld-studio-apply">
            <header className="ld-studio__bar">
                <button type="button" className="ld-studio__back" onClick={onBack}>
                    <i className="fas fa-arrow-left" />
                    一覧へ
                </button>
                <div className="ld-studio__apply-title">
                    <span className="ld-studio-row__badge" style={{ background: accent.soft, color: accent.color }}>
                        <i className={`fas ${accent.icon}`} />
                    </span>
                    <div>
                        <strong>{templateDisplayName(template)}</strong>
                        <span>店舗情報を入れて適用 · {themeLabel}</span>
                    </div>
                </div>
                <button type="button" className="ld-studio__icon-btn" onClick={onClose} aria-label="閉じる">
                    <i className="fas fa-times" />
                </button>
            </header>

            <div className="ld-studio-apply__body">
                <div className="ld-studio-apply__form">
                    <section className="ld-studio-panel">
                        <h3>基本情報</h3>
                        <div className="ld-apply-setup">
                            <label className="ld-apply-setup__field">
                                <span>店舗名・アプリ名 <em>必須</em></span>
                                <input
                                    type="text"
                                    className="ld-form-input"
                                    value={setup.shopName}
                                    placeholder="例: ブルーバードカフェ"
                                    onChange={(e) => patchSetup({ shopName: e.target.value })}
                                />
                            </label>
                            <label className="ld-apply-setup__field">
                                <span>ヒーロー見出し <em>任意</em></span>
                                <input
                                    type="text"
                                    className="ld-form-input"
                                    value={setup.heroTitle}
                                    placeholder="空欄なら店舗名を使用"
                                    onChange={(e) => patchSetup({ heroTitle: e.target.value })}
                                />
                            </label>
                        </div>
                    </section>

                    <section className="ld-studio-panel">
                        <h3>見た目</h3>
                        <PreviewStyleControls
                            template={template}
                            themePresets={themePresets}
                            themeSelectionId={themeSelectionId}
                            setup={setup}
                            fontStacks={fontStacks}
                            onThemeSelect={onThemeSelect}
                            onSetupChange={onSetupChange}
                        />
                    </section>

                    <section className="ld-studio-panel">
                        <h3>会員証 <em>必須</em></h3>
                        <div className="ld-apply-setup__cards">
                            {MEMBER_CARD_DESIGN_CHOICES.map((choice) => (
                                <button
                                    key={choice.value}
                                    type="button"
                                    className={`ld-apply-setup__card${setup.memberCardDesign === choice.value ? ' is-active' : ''}`}
                                    onClick={() => patchSetup({ memberCardDesign: choice.value })}
                                >
                                    <span className={`ld-apply-setup__card-mock ld-apply-setup__card-mock--${choice.value}`} />
                                    <em>{choice.label}</em>
                                </button>
                            ))}
                        </div>
                        <div className="ld-apply-setup__row">
                            <label>
                                <span>表示形式</span>
                                <select
                                    className="ld-form-select"
                                    value={setup.memberCardDisplayType}
                                    onChange={(e) => patchSetup({ memberCardDisplayType: e.target.value })}
                                >
                                    {MEMBER_CARD_DISPLAY_TYPES.map((opt) => (
                                        <option key={opt.value} value={opt.value}>{opt.label}</option>
                                    ))}
                                </select>
                            </label>
                            <label>
                                <span>会員番号</span>
                                <select
                                    className="ld-form-select"
                                    value={setup.memberCardCodeType}
                                    onChange={(e) => patchSetup({ memberCardCodeType: e.target.value })}
                                >
                                    {MEMBER_CARD_CODE_TYPES.map((opt) => (
                                        <option key={opt.value} value={opt.value}>{opt.label}</option>
                                    ))}
                                </select>
                            </label>
                        </div>
                    </section>

                    <section className="ld-studio-panel ld-studio-panel--summary">
                        <dl>
                            <div>
                                <dt>適用先</dt>
                                <dd>{impact.isAllScreens ? '全画面' : `「${impact.targetLabel}」`}</dd>
                            </div>
                            <div>
                                <dt>ブロック</dt>
                                <dd>{impact.currentBlockCount} → {impact.newBlockCount}</dd>
                            </div>
                            <div>
                                <dt>テーマ</dt>
                                <dd>{themeLabel}</dd>
                            </div>
                        </dl>
                        {!canApply ? (
                            <p className="ld-form-hint ld-form-hint--warn">店舗名と会員証デザインが必要です</p>
                        ) : (
                            <p className="ld-form-hint">Ctrl+Z で取り消しできます</p>
                        )}
                        <div className="ld-template-confirm__actions">
                            <button type="button" className="ld-btn ld-btn--ghost" onClick={onBack}>戻る</button>
                            <button type="button" className="ld-btn ld-btn--primary" onClick={onConfirm} disabled={!canApply}>
                                設定して適用する
                            </button>
                        </div>
                    </section>
                </div>

                <div className="ld-studio-apply__preview">
                    <TemplatePhoneMock
                        template={previewTemplate}
                        size="large"
                        interactive
                        fontStacks={fontStacks}
                    />
                    <p>入力内容がすぐ反映されます。タブも切り替えできます。</p>
                </div>
            </div>
        </div>
    );
}

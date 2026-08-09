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
import { saveDesigner } from '../persistence/saveDesigner';
import { MemberCardDesignThumb, MemberCardFinishControls } from './MemberCardFinishControls';
import { CouponScreenControls } from './CouponScreenControls';

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
        const shopFromTpl = String(tpl.theme?.app_display_name || '').trim();
        const shopFromState = String(state.globalSettings.app_display_name || '').trim();
        setSetup({
            ...DEFAULT_TEMPLATE_SETUP,
            shopName: shopFromState !== '' ? shopFromState : shopFromTpl,
            memberCardDesign: cardDesign,
            memberCardDisplayType: String(state.globalSettings.member_card_display_type || 'card'),
            memberCardCodeType: String(state.globalSettings.member_card_code_type || 'barcode'),
            memberCardBgStyle: String(
                tpl.theme?.member_card_bg_style || state.globalSettings.member_card_bg_style || 'gradient',
            ),
            memberCardGloss: String(
                tpl.theme?.member_card_gloss || state.globalSettings.member_card_gloss || 'soft',
            ),
            memberCardSurface: String(
                tpl.theme?.member_card_surface || state.globalSettings.member_card_surface || 'plastic',
            ),
            couponScreenDesign: String(
                tpl.theme?.coupon_screen_design || state.globalSettings.coupon_screen_design || 'ticket',
            ),
            couponMediaStyle: String(
                tpl.theme?.coupon_media_style || state.globalSettings.coupon_media_style || 'image',
            ),
            couponShowAvailable: (tpl.theme?.coupon_show_available ?? state.globalSettings.coupon_show_available) !== false,
            couponShowHistory: (tpl.theme?.coupon_show_history ?? state.globalSettings.coupon_show_history) !== false,
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

    const applyTemplate = async (tpl: TemplateItem) => {
        if (state.saving) return;
        dispatch({ type: 'SET_SAVING', saving: true });

        const shopFallback =
            setup.shopName.trim()
            || String(tpl.theme?.app_display_name || '').trim()
            || String(state.globalSettings.app_display_name || '').trim()
            || '店舗アプリ';
        const effectiveSetup: TemplateSetupOptions = {
            ...setup,
            shopName: shopFallback,
            memberCardDesign: setup.memberCardDesign !== '' ? setup.memberCardDesign : 'default',
        };
        const selection = resolveTemplateThemeSelection(tpl, themeSelectionId, themePresets);
        const result = buildTemplateApplyResult(
            tpl,
            state,
            themePresets,
            selection.themeOverride,
            effectiveSetup,
        );
        if (!result.applied) {
            dispatch({ type: 'SET_SAVING', saving: false });
            dispatch({ type: 'SHOW_TOAST', message: result.message, isError: true });
            return;
        }
        const nextState = {
            ...state,
            layouts: result.layouts,
            globalSettings: result.globalSettings ?? state.globalSettings,
            tabSettings: result.tabSettings ?? state.tabSettings,
            menuItems: result.menuItems !== undefined ? result.menuItems : state.menuItems,
            customCss: result.customCss !== undefined ? result.customCss : state.customCss,
            activeTab: result.activeTab !== undefined ? result.activeTab : state.activeTab,
        } as DesignerState;

        dispatch({
            type: 'APPLY_LAYOUTS',
            layouts: nextState.layouts,
            globalSettings: nextState.globalSettings,
            tabSettings: nextState.tabSettings,
            menuItems: nextState.menuItems,
            customCss: nextState.customCss,
            activeTab: nextState.activeTab,
        });

        const formAction = String(
            config.formAction
            || (document.getElementById('liffDesignerForm') as HTMLFormElement | null)?.action
            || '',
        ).trim();
        const csrfToken = String(
            config.csrfToken
            || (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content
            || (document.querySelector('input[name="_token"]') as HTMLInputElement | null)?.value
            || '',
        ).trim();
        const themeNote =
            selection.themeOverride !== undefined ? `（テーマ: ${selection.label}）` : '';

        const finishUi = () => {
            closeModal();
            // 適用後は詳細編集へ直接入る（中間のプレビュー専用画面は挟まない）
            dispatch({ type: 'SET_EDITOR_MODE', mode: 'advanced' });
            dispatch({ type: 'SET_UI', patch: { sidebarOpen: true } });
            dispatch({ type: 'SET_RIGHT_MODE', mode: 'edit' });
        };

        if (formAction === '' || csrfToken === '') {
            dispatch({ type: 'SET_SAVING', saving: false });
            dispatch({
                type: 'SHOW_TOAST',
                message: 'テンプレは適用しましたが、保存設定が見つかりません。ページを再読み込みして「保存」を押してください',
                isError: true,
            });
            finishUi();
            return;
        }

        try {
            const saved = await saveDesigner(nextState, formAction, csrfToken);
            dispatch({ type: 'MARK_SAVED' });
            dispatch({ type: 'SHOW_TOAST', message: `${result.message}${themeNote} · ${saved}` });
            finishUi();
        } catch (err: unknown) {
            dispatch({ type: 'SET_SAVING', saving: false });
            const message = err instanceof Error ? err.message : '保存に失敗しました';
            dispatch({
                type: 'SHOW_TOAST',
                message: `テンプレは画面に適用済みです。保存に失敗: ${message}`,
                isError: true,
            });
            // 保存失敗時はモーダルを閉じず、再実行できるようにする
        }
    };

    const openApply = (tpl: TemplateItem) => {
        selectTemplate(tpl);
        setViewMode('apply');
    };

    // ApplyView を開き直すたびに先頭ステップへ戻すためのキー
    const applySessionKey = activeTemplate !== null
        ? String(activeTemplate.id || templateDisplayName(activeTemplate))
        : '';

    return (
        <div
            className="ld-modal-backdrop ld-gallery-backdrop"
            onClick={() => {
                if (!state.saving) closeModal();
            }}
        >
            <div
                className="ld-gallery ld-studio-shell"
                onClick={(e) => e.stopPropagation()}
                role="dialog"
                aria-modal="true"
                aria-label="テンプレート一覧"
                aria-busy={state.saving || undefined}
            >
                {viewMode === 'apply' && activeTemplate !== null && impact !== null && themeSelection !== null && livePreviewTemplate !== null ? (
                    <ApplyView
                        key={applySessionKey}
                        template={activeTemplate}
                        previewTemplate={livePreviewTemplate}
                        impact={impact}
                        themePresets={themePresets}
                        themeSelectionId={themeSelectionId}
                        themeLabel={themeSelection.label}
                        setup={setup}
                        fontStacks={config.fontStacks || {}}
                        isApplying={state.saving}
                        onSetupChange={setSetup}
                        onThemeSelect={setThemeSelectionId}
                        onBack={() => {
                            if (!state.saving) setViewMode('studio');
                        }}
                        onConfirm={() => {
                            void applyTemplate(activeTemplate);
                        }}
                        onClose={() => {
                            if (!state.saving) closeModal();
                        }}
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
                        fontStacks={config.fontStacks || {}}
                        onCategory={setCategory}
                        onScope={setScope}
                        onKeyword={setKeyword}
                        onFavoritesOnly={() => setFavoritesOnly((v) => !v)}
                        onToggleFavorite={toggleFavorite}
                        onSelect={selectTemplate}
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
    fontStacks,
    onCategory,
    onScope,
    onKeyword,
    onFavoritesOnly,
    onToggleFavorite,
    onSelect,
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
    fontStacks: Record<string, { label: string; base?: string; display?: string }>;
    onCategory: (v: string) => void;
    onScope: (v: 'all' | 'tab' | 'super') => void;
    onKeyword: (v: string) => void;
    onFavoritesOnly: () => void;
    onToggleFavorite: (id: string) => void;
    onSelect: (tpl: TemplateItem) => void;
    onApply: (tpl: TemplateItem) => void;
    onClose: () => void;
}) {
    const featured = featuredTemplates(allTemplates, 5);
    const activeKey =
        activeTemplate !== null
            ? String(activeTemplate.id || templateDisplayName(activeTemplate))
            : '';

    return (
        <div className="ld-studio ld-studio--gallery-only">
            <header className="ld-studio__bar">
                <div className="ld-studio__brand">
                    <span className="ld-studio__mark" aria-hidden>
                        <i className="fas fa-swatchbook" />
                    </span>
                    <div>
                        <strong>テンプレートを選ぶ</strong>
                        <span>カードを見比べて「使う」を押してください</span>
                    </div>
                </div>
                <div className="ld-studio__bar-search">
                    <i className="fas fa-search" />
                    <input
                        type="search"
                        value={keyword}
                        onChange={(e) => onKeyword(e.target.value)}
                        placeholder="カフェ / サロン / 注文 / スタンプ…"
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

            <div className="ld-studio__filters" aria-label="絞り込み">
                <div className="ld-studio__segment ld-studio__segment--inline">
                    <button type="button" className={scope === 'all' ? 'is-active' : ''} onClick={() => onScope('all')}>すべて</button>
                    <button type="button" className={scope === 'tab' ? 'is-active' : ''} onClick={() => onScope('tab')}>画面用</button>
                    <button type="button" className={scope === 'super' ? 'is-active' : ''} onClick={() => onScope('super')}>全画面</button>
                </div>
                <nav className="ld-studio__chips">
                    <button type="button" className={category === 'all' ? 'is-active' : ''} onClick={() => onCategory('all')}>
                        すべて
                    </button>
                    {categoryKeys.map((key) => {
                        const meta = CATEGORY_ACCENTS[key] || CATEGORY_ACCENTS.other;
                        return (
                            <button
                                key={key}
                                type="button"
                                className={category === key ? 'is-active' : ''}
                                onClick={() => onCategory(key)}
                                style={category === key ? undefined : { color: meta.color }}
                            >
                                <i className={`fas ${meta.icon}`} />
                                {categoryLabel(key)}
                            </button>
                        );
                    })}
                </nav>
                {featured.length > 0 ? (
                    <div className="ld-studio__featured-row">
                        {featured.map((tpl, i) => {
                            const a = galleryAccentFor(tpl);
                            const id = String(tpl.id || i);
                            return (
                                <button
                                    key={id || i}
                                    type="button"
                                    className={`ld-studio__featured-pill${activeKey === String(tpl.id || templateDisplayName(tpl, i)) ? ' is-active' : ''}`}
                                    style={{ background: a.soft, color: a.color }}
                                    onClick={() => onApply(tpl)}
                                >
                                    <i className={`fas ${a.icon}`} />
                                    {templateDisplayName(tpl, i)}
                                </button>
                            );
                        })}
                    </div>
                ) : null}
            </div>

            <div className="ld-studio__body">
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
                                        style={{ ['--row-accent' as string]: a.color, ['--row-accent-soft' as string]: a.soft }}
                                    >
                                        <div className="ld-gallery-card__mock">
                                            <TemplatePhoneMock
                                                template={tpl}
                                                size="card"
                                                interactive
                                                fontStacks={fontStacks}
                                            />
                                        </div>
                                        <div className="ld-gallery-card__head">
                                            <span className="ld-gallery-card__icon" style={{ background: a.soft, color: a.color }}>
                                                <i className={`fas ${a.icon}`} />
                                            </span>
                                            <div className="ld-gallery-card__titles">
                                                <strong>{templateDisplayName(tpl, i)}</strong>
                                                <span>
                                                    {paletteLabelFor(tpl)}
                                                    {' · '}
                                                    {isSuper ? '全画面' : `${blockCount}ブロック`}
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
                                                className="ld-gallery-card__choose ld-gallery-card__choose--solo"
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

    const rows = [
        { id: TEMPLATE_THEME_KEEP, label: '標準', bg: templateBg, accent: templateAccent },
        ...presets.map((preset) => ({
            id: preset.id,
            label: String(preset.label || preset.name || preset.id),
            bg: String(preset.bg_color || '#fafaf9'),
            accent: String(preset.accent_color || preset.accent || '#1c1917'),
        })),
    ];

    return (
        <div className={`ld-apply-theme${compact ? ' ld-apply-theme--compact' : ''}`}>
            <div className="ld-apply-theme__head">
                <strong>カラーパレット</strong>
                <span>即反映</span>
            </div>
            <div className={compact ? 'ld-apply-theme__scroll' : 'ld-apply-theme__grid'}>
                {rows.map((row) => (
                    <button
                        key={row.id}
                        type="button"
                        className={`ld-apply-theme__card${selectionId === row.id ? ' is-active' : ''}`}
                        onClick={() => onSelect(row.id)}
                        title={row.label}
                    >
                        <span
                            className="ld-apply-theme__swatch"
                            style={{ background: `linear-gradient(135deg, ${row.bg} 52%, ${row.accent} 52%)` }}
                        />
                        <em>{row.label}</em>
                    </button>
                ))}
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

const APPLY_STEPS = [
    { id: 'look', label: '見た目', hint: '色・フォント' },
    { id: 'member', label: '会員証', hint: 'レイアウト' },
    { id: 'coupon', label: 'クーポン', hint: '一覧の見せ方' },
] as const;

function ApplyView({
    template,
    previewTemplate,
    impact,
    themePresets,
    themeSelectionId,
    themeLabel,
    setup,
    fontStacks,
    isApplying,
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
    isApplying: boolean;
    onSetupChange: (next: TemplateSetupOptions) => void;
    onThemeSelect: (id: string) => void;
    onBack: () => void;
    onConfirm: () => void;
    onClose: () => void;
}) {
    const [step, setStep] = useState(0);
    const patchSetup = (partial: Partial<TemplateSetupOptions>) => {
        if (isApplying) return;
        onSetupChange({ ...setup, ...partial });
    };
    const accent = galleryAccentFor(template);
    const shopName = setup.shopName.trim();
    const hasShopName = shopName !== '';
    const lastStep = APPLY_STEPS.length - 1;
    const current = APPLY_STEPS[step] || APPLY_STEPS[0];
    const preferredPreviewTab =
        current.id === 'member' ? 'member' : current.id === 'coupon' ? 'coupons' : undefined;

    return (
        <div className={`ld-studio-apply ld-studio-apply--wizard${isApplying ? ' is-busy' : ''}`}>
            <header className="ld-studio__bar">
                <button type="button" className="ld-studio__back" onClick={onBack} disabled={isApplying}>
                    <i className="fas fa-arrow-left" />
                    一覧へ
                </button>
                <div className="ld-studio__apply-title">
                    <span className="ld-studio-row__badge" style={{ background: accent.soft, color: accent.color }}>
                        <i className={`fas ${accent.icon}`} />
                    </span>
                    <div>
                        <strong>{templateDisplayName(template)}</strong>
                        <span>{`ステップ ${step + 1}/${APPLY_STEPS.length} · ${current.label}`}</span>
                    </div>
                </div>
                <div className="ld-studio__apply-actions">
                    {step === lastStep ? (
                        <button
                            type="button"
                            className="ld-btn ld-btn--primary ld-studio__apply-save"
                            onClick={() => onConfirm()}
                            disabled={isApplying}
                        >
                            {isApplying ? (
                                <>
                                    <i className="fas fa-spinner fa-spin" aria-hidden />
                                    保存中…
                                </>
                            ) : (
                                '適用して保存'
                            )}
                        </button>
                    ) : null}
                    <button
                        type="button"
                        className="ld-studio__icon-btn"
                        onClick={onClose}
                        aria-label="閉じる"
                        disabled={isApplying}
                    >
                        <i className="fas fa-times" />
                    </button>
                </div>
            </header>

            <div className="ld-studio-apply__body">
                <aside className="ld-studio-apply__form ld-apply-wizard">
                    <ol className="ld-apply-wizard__steps" aria-label="設定ステップ">
                        {APPLY_STEPS.map((item, index) => {
                            const done = index < step;
                            const active = index === step;
                            return (
                                <li key={item.id} className={`ld-apply-wizard__step${done ? ' is-done' : ''}${active ? ' is-active' : ''}`}>
                                    <button
                                        type="button"
                                        className="ld-apply-wizard__step-btn"
                                        onClick={() => {
                                            if (!isApplying) setStep(index);
                                        }}
                                        aria-current={active ? 'step' : undefined}
                                        disabled={isApplying}
                                    >
                                        <span className="ld-apply-wizard__dot" aria-hidden>
                                            {done ? <i className="fas fa-check" /> : index + 1}
                                        </span>
                                        <span className="ld-apply-wizard__step-copy">
                                            <strong>{item.label}</strong>
                                            <em>{item.hint}</em>
                                        </span>
                                    </button>
                                </li>
                            );
                        })}
                    </ol>

                    <div className="ld-apply-wizard__scroll">
                        {hasShopName ? (
                            <p className="ld-apply-wizard__shop">
                                <i className="fas fa-store" />
                                店舗名 <strong>{shopName}</strong>
                                <span>を使用</span>
                            </p>
                        ) : (
                            <label className="ld-apply-wizard__shop-field">
                                <span>店舗名が未設定です</span>
                                <input
                                    type="text"
                                    className="ld-form-input"
                                    value={setup.shopName}
                                    placeholder="例: ブルーバードカフェ"
                                    onChange={(e) => patchSetup({ shopName: e.target.value })}
                                />
                            </label>
                        )}

                        <div className="ld-apply-wizard__panel">
                            {current.id === 'look' ? (
                                <>
                                    <h3>見た目を選ぶ</h3>
                                    <p className="ld-apply-wizard__lead">右側プレビューにすぐ反映されます</p>
                                    <PreviewStyleControls
                                        template={template}
                                        themePresets={themePresets}
                                        themeSelectionId={themeSelectionId}
                                        setup={setup}
                                        fontStacks={fontStacks}
                                        onThemeSelect={onThemeSelect}
                                        onSetupChange={onSetupChange}
                                        compact
                                    />
                                </>
                            ) : null}

                            {current.id === 'member' ? (
                                <>
                                    <h3>会員証の形</h3>
                                    <p className="ld-apply-wizard__lead">レイアウトと番号表示だけ決めます</p>
                                    <div className="ld-apply-setup__cards ld-apply-setup__cards--compact">
                                        {MEMBER_CARD_DESIGN_CHOICES.map((choice) => (
                                            <button
                                                key={choice.value}
                                                type="button"
                                                className={`ld-apply-setup__card${setup.memberCardDesign === choice.value ? ' is-active' : ''}`}
                                                onClick={() => patchSetup({ memberCardDesign: choice.value })}
                                            >
                                                <MemberCardDesignThumb design={choice.value} />
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
                                    <MemberCardFinishControls
                                        bgStyle={setup.memberCardBgStyle}
                                        gloss={setup.memberCardGloss}
                                        surface={setup.memberCardSurface}
                                        onBgStyle={(v) => patchSetup({ memberCardBgStyle: v })}
                                        onGloss={(v) => patchSetup({ memberCardGloss: v })}
                                        onSurface={(v) => patchSetup({ memberCardSurface: v })}
                                    />
                                </>
                            ) : null}

                            {current.id === 'coupon' ? (
                                <>
                                    <h3>クーポン一覧</h3>
                                    <p className="ld-apply-wizard__lead">画像の有無と、未使用／利用済みの表示</p>
                                    <CouponScreenControls
                                        design={setup.couponScreenDesign}
                                        mediaStyle={setup.couponMediaStyle}
                                        showAvailable={setup.couponShowAvailable}
                                        showHistory={setup.couponShowHistory}
                                        onDesign={(v) => patchSetup({ couponScreenDesign: v })}
                                        onMediaStyle={(v) => patchSetup({ couponMediaStyle: v })}
                                        onShowAvailable={(v) => patchSetup({ couponShowAvailable: v })}
                                        onShowHistory={(v) => patchSetup({ couponShowHistory: v })}
                                    />
                                </>
                            ) : null}
                        </div>
                    </div>

                    <div className="ld-apply-wizard__footer">
                        <button
                            type="button"
                            className="ld-btn ld-btn--ghost"
                            disabled={isApplying}
                            onClick={() => {
                                if (isApplying) return;
                                if (step === 0) onBack();
                                else setStep((s) => Math.max(0, s - 1));
                            }}
                        >
                            {step === 0 ? '一覧へ' : '戻る'}
                        </button>
                        {step < lastStep ? (
                            <button
                                type="button"
                                className="ld-btn ld-btn--primary"
                                disabled={isApplying}
                                onClick={() => {
                                    if (!isApplying) setStep((s) => Math.min(lastStep, s + 1));
                                }}
                            >
                                次へ
                            </button>
                        ) : (
                            <button
                                type="button"
                                className="ld-btn ld-btn--primary ld-apply-wizard__save"
                                disabled={isApplying}
                                onClick={() => onConfirm()}
                            >
                                {isApplying ? (
                                    <>
                                        <i className="fas fa-spinner fa-spin" aria-hidden />
                                        保存中…
                                    </>
                                ) : (
                                    '適用して保存'
                                )}
                            </button>
                        )}
                    </div>
                </aside>

                <div className="ld-studio-apply__preview">
                    <TemplatePhoneMock
                        template={previewTemplate}
                        size="large"
                        interactive
                        preferredTab={preferredPreviewTab}
                        fontStacks={fontStacks}
                    />
                    <p>
                        {impact.isAllScreens ? '全画面' : impact.targetLabel}
                        {' · '}
                        {impact.newBlockCount}ブロック · {themeLabel}
                    </p>
                </div>
            </div>
        </div>
    );
}


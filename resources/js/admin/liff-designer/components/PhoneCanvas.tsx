import React, { useEffect, useRef, useState } from 'react';
import { DETAIL_PAGE_LABELS, DETAIL_PAGES, TAB_LABELS, findBlockMeta, isLinkMenuTab, normalizeBlock } from '../catalog/blockCatalog';
import { BlockPreview } from '../blocks/BlockPreview';
import {
    DEVICE_SHELL_HEIGHT,
    PhoneAppHeader,
    PhoneAppMain,
    PhoneAppTabs,
    PhoneDeviceFrame,
} from './PhoneAppShell';
import { useDesigner } from '../state/DesignerContext';
import type { LiffBlock } from '../catalog/types';
import {
    contentPadding,
    frameSystemClasses,
    resolvePhoneBodyStyle,
    sectionGap,
    screenTabStyleClass,
} from '../utils/globalDesign';
import {
    featuredTemplates,
    galleryAccentFor,
    templateDisplayName,
    type TemplateItem,
} from './templateGalleryMeta';

function resolveDropIndex(clientY: number, element: HTMLElement, index: number, blockCount: number): number {
    const rect = element.getBoundingClientRect();
    const mid = rect.top + rect.height / 2;
    const at = clientY < mid ? index : index + 1;
    return Math.max(0, Math.min(at, blockCount));
}

const ZOOM_PRESETS = [75, 100, 125] as const;

export function PhoneCanvas() {
    const { state, dispatch, config } = useDesigner();
    const isAdvanced = state.editorMode === 'advanced';
    const blocks = state.layouts[state.activeTab] || [];
    const tabSettings = state.tabSettings[state.activeTab] || {};
    const [dragIndex, setDragIndex] = useState<number | null>(null);
    const [dropAt, setDropAt] = useState<number | null>(null);
    const [isPaletteDrag, setIsPaletteDrag] = useState(false);
    const stageRef = useRef<HTMLDivElement>(null);
    const didAutoFitZoom = useRef(false);
    const friendAddUrl = config.lineFriendAddUrl || '';

    const bodyStyle = resolvePhoneBodyStyle(tabSettings, state.globalSettings);
    const padding = contentPadding(tabSettings, state.globalSettings);
    const gap = sectionGap(state.globalSettings);
    const tabStyleClass = screenTabStyleClass(state.globalSettings);
    const systemClass = frameSystemClasses(state.globalSettings);
    const enabledNav = state.menuItems.filter((m) => m.enabled);
    const isDetailPage = DETAIL_PAGES.includes(state.activeTab);
    const shopTitle =
        String(state.globalSettings.app_display_name || '').trim()
        || document.title.replace(/\s*[|\-].*$/, '').trim()
        || '店舗アプリ';
    const suggestedTemplates = featuredTemplates(
        (Array.isArray(config.templates) ? config.templates : []) as TemplateItem[],
        4,
    );
    const activeLabel = state.menuItems.find((m) => m.tab === state.activeTab)?.label
        || DETAIL_PAGE_LABELS[state.activeTab]
        || TAB_LABELS[state.activeTab]
        || state.activeTab;
    const requireMembership = state.globalSettings.require_membership !== false;

    useEffect(() => {
        const stage = stageRef.current;
        if (stage === null) return;

        const fitOnce = () => {
            if (didAutoFitZoom.current) return;
            const available = stage.clientHeight - 64;
            if (available <= 0) return;
            const nextZoom = Math.floor((available / DEVICE_SHELL_HEIGHT) * 100);
            const clamped = Math.min(90, Math.max(55, nextZoom));
            didAutoFitZoom.current = true;
            dispatch({ type: 'SET_ZOOM', zoom: clamped });
        };

        const raf = window.requestAnimationFrame(() => {
            fitOnce();
            window.requestAnimationFrame(fitOnce);
        });
        const observer = new ResizeObserver(() => {
            if (!didAutoFitZoom.current) fitOnce();
        });
        observer.observe(stage);
        return () => {
            window.cancelAnimationFrame(raf);
            observer.disconnect();
        };
    }, [dispatch]);

    const finishDrag = () => {
        setDragIndex(null);
        setDropAt(null);
        setIsPaletteDrag(false);
    };

    const onDropAt = (index: number, e: React.DragEvent) => {
        e.preventDefault();
        const type = e.dataTransfer.getData('application/ld-block-type');
        if (type !== '') {
            const block = normalizeBlock({ type }, { n: Date.now() % 100000 });
            dispatch({ type: 'ADD_BLOCK', block, index });
            finishDrag();
            return;
        }
        if (dragIndex !== null && dragIndex !== index) {
            const to = index > dragIndex ? index - 1 : index;
            dispatch({ type: 'MOVE_BLOCK', from: dragIndex, to });
        }
        finishDrag();
    };

    const onBlockClick = (e: React.MouseEvent, blockId: string) => {
        e.stopPropagation();
        const navEl = (e.target as HTMLElement).closest('[data-preview-nav]');
        if (navEl !== null) {
            const tab = navEl.getAttribute('data-preview-nav');
            if (tab !== null && tab !== '') {
                dispatch({ type: 'SET_ACTIVE_TAB', tab });
                return;
            }
        }
        if (isAdvanced) {
            dispatch({ type: 'SELECT_BLOCK', id: blockId });
            return;
        }
        dispatch({ type: 'OPEN_QUICK_EDIT', id: blockId });
    };

    return (
        <div className="ld-editor">
            <div className="ld-editor__context">
                <div className="ld-editor__context-main">
                    <strong>{activeLabel}</strong>
                    <span>{blocks.length} ブロック</span>
                    {state.dirty ? <span className="ld-editor__dirty-dot" title="未保存" /> : null}
                    <span
                        className="ld-editor__policy-chip"
                        title={
                            requireMembership
                                ? '本番ではクーポン・スタンプ・会員証などは登録後のみ'
                                : '本番ではゲストでも閲覧可。会員登録は任意'
                        }
                    >
                        本番: 会員{requireMembership ? '必須' : '任意'}
                    </span>
                </div>
                <div className="ld-editor__context-actions">
                    <div className="ld-editor__preview-mode-wrap">
                        <span className="ld-editor__preview-mode-label">プレビュー</span>
                        <div className="ld-bg-style-tabs ld-editor__preview-mode" role="group" aria-label="プレビューの見え方">
                            <button
                                type="button"
                                className={`ld-bg-style-tab${!state.previewAsGuest ? ' is-active' : ''}`}
                                onClick={() => dispatch({ type: 'SET_PREVIEW_AS_GUEST', value: false })}
                                title="会員としてログインした見え方"
                            >
                                会員表示
                            </button>
                            <button
                                type="button"
                                className={`ld-bg-style-tab${state.previewAsGuest ? ' is-active' : ''}`}
                                onClick={() => dispatch({ type: 'SET_PREVIEW_AS_GUEST', value: true })}
                                title="未登録ゲストの見え方（会員ブロックは登録導線に）"
                            >
                                ゲスト表示
                            </button>
                        </div>
                    </div>
                    <div className="ld-editor__zoom">
                        {ZOOM_PRESETS.map((z) => (
                            <button
                                key={z}
                                type="button"
                                className={`ld-editor__zoom-btn${state.zoom === z ? ' is-active' : ''}`}
                                onClick={() => dispatch({ type: 'SET_ZOOM', zoom: z })}
                            >
                                {z}%
                            </button>
                        ))}
                        <input
                            type="range"
                            min={60}
                            max={125}
                            value={state.zoom}
                            onChange={(e) => dispatch({ type: 'SET_ZOOM', zoom: Number(e.target.value) })}
                            aria-label="ズーム"
                        />
                    </div>
                </div>
            </div>
            <div className="ld-editor__stage" ref={stageRef}>
                <PhoneDeviceFrame
                    className={`${systemClass}${state.previewAsGuest ? ' ld-phone--guest-preview' : ''}`}
                    size="canvas"
                    zoom={state.zoom}
                    screenStyle={bodyStyle}
                    screenClassName={tabStyleClass}
                >
                    <PhoneAppHeader
                        title={shopTitle}
                        friendAddUrl={friendAddUrl}
                        showCart={enabledNav.some((m) => m.tab === 'products' || m.tab === 'orders')}
                        onAccountClick={() => dispatch({ type: 'SET_ACTIVE_TAB', tab: 'member' })}
                        onCartClick={() => dispatch({ type: 'SET_ACTIVE_TAB', tab: 'products' })}
                    />
                    <PhoneAppMain
                        key={state.activeTab}
                        className="ld-phone-app-main--tab-enter"
                        padding={padding}
                        gap={gap}
                        onClick={() => dispatch({ type: 'SELECT_BLOCK', id: null, openEdit: false })}
                        onDragOver={isAdvanced ? (e) => {
                            if (e.dataTransfer.types.includes('application/ld-block-type')) {
                                e.preventDefault();
                                setIsPaletteDrag(true);
                                if (blocks.length === 0) setDropAt(0);
                            }
                        } : undefined}
                        onDrop={isAdvanced ? (e) => {
                            if (blocks.length === 0) {
                                const type = e.dataTransfer.getData('application/ld-block-type');
                                if (type !== '') {
                                    e.preventDefault();
                                    const block = normalizeBlock({ type }, { n: Date.now() % 100000 });
                                    dispatch({ type: 'ADD_BLOCK', block });
                                }
                            }
                            finishDrag();
                        } : undefined}
                    >
                        {isLinkMenuTab(state.activeTab) ? (
                            <div className="ld-empty-canvas">
                                <div className="ld-empty-canvas__badge">外部ページ</div>
                                <i className="fas fa-globe ld-empty-canvas__icon" />
                                <p>{activeLabel}</p>
                                <p className="ld-empty-canvas__hint">
                                    このタブは設定したURLのWebページを表示します。URLと表示方法は「画面一覧」で変更できます。
                                </p>
                            </div>
                        ) : blocks.length === 0 ? (
                            <div className="ld-empty-canvas">
                                {Object.values(state.layouts).some((rows) => Array.isArray(rows) && rows.length > 0) ? (
                                    <>
                                        <div className="ld-empty-canvas__badge">空の画面</div>
                                        <i className="fas fa-layer-group ld-empty-canvas__icon" />
                                        <p>「{activeLabel}」にはまだブロックがありません</p>
                                        <p className="ld-empty-canvas__hint">
                                            この画面用のテンプレートを当てるか、ブロックを追加してください。他のタブの内容はここには表示されません。
                                        </p>
                                        <div className="ld-empty-canvas__actions">
                                            <button
                                                type="button"
                                                className="ld-btn ld-btn--primary ld-btn--xs"
                                                onClick={() => dispatch({ type: 'SET_LEFT_STUDIO_TAB', tab: 'templates' })}
                                            >
                                                テンプレを当てる
                                            </button>
                                            {isAdvanced ? (
                                                <button
                                                    type="button"
                                                    className="ld-btn ld-btn--outline ld-btn--xs"
                                                    onClick={() => dispatch({ type: 'SET_LEFT_STUDIO_TAB', tab: 'blocks' })}
                                                >
                                                    ブロックを追加
                                                </button>
                                            ) : null}
                                        </div>
                                    </>
                                ) : (
                                    <>
                                        <div className="ld-empty-canvas__badge">スタート</div>
                                        <i className="fas fa-mobile-alt ld-empty-canvas__icon" />
                                        <p>顧客アプリの画面を組み立てましょう</p>
                                        <p className="ld-empty-canvas__hint">
                                            テンプレートから始めるか、ブロックを追加してください
                                        </p>
                                        <div className="ld-empty-canvas__actions">
                                            <button
                                                type="button"
                                                className="ld-btn ld-btn--primary ld-btn--xs"
                                                onClick={() => dispatch({ type: 'SET_LEFT_STUDIO_TAB', tab: 'templates' })}
                                            >
                                                テンプレから始める
                                            </button>
                                            {isAdvanced ? (
                                                <button
                                                    type="button"
                                                    className="ld-btn ld-btn--outline ld-btn--xs"
                                                    onClick={() => dispatch({ type: 'SET_LEFT_STUDIO_TAB', tab: 'blocks' })}
                                                >
                                                    ブロックを追加
                                                </button>
                                            ) : null}
                                        </div>
                                        {suggestedTemplates.length > 0 ? (
                                            <div className="ld-empty-canvas__suggest">
                                                <span className="ld-empty-canvas__suggest-label">おすすめ</span>
                                                <div className="ld-empty-canvas__chips">
                                                    {suggestedTemplates.map((tpl, i) => {
                                                        const accent = galleryAccentFor(tpl);
                                                        return (
                                                            <button
                                                                key={String(tpl.id || i)}
                                                                type="button"
                                                                className="ld-empty-canvas__chip"
                                                                style={{ borderColor: `${accent.color}33`, background: accent.soft, color: accent.color }}
                                                                onClick={() => dispatch({
                                                                    type: 'SET_UI',
                                                                    patch: {
                                                                        templateOpen: true,
                                                                        pendingTemplateId: tpl.id !== undefined ? String(tpl.id) : null,
                                                                        leftStudioTab: 'templates',
                                                                    },
                                                                })}
                                                            >
                                                                <i className={`fas ${accent.icon}`} />
                                                                {templateDisplayName(tpl, i)}
                                                            </button>
                                                        );
                                                    })}
                                                </div>
                                            </div>
                                        ) : null}
                                    </>
                                )}
                            </div>
                        ) : (
                            <>
                                {state.previewAsGuest ? (
                                    <div className="ld-guest-preview-banner">
                                        ゲスト表示プレビュー
                                        {requireMembership
                                            ? ' · 会員向けブロックは登録導線に置き換わります'
                                            : ' · 会員登録は任意の設定です'}
                                    </div>
                                ) : null}
                                {blocks.map((block: LiffBlock, index) => {
                                    const meta = findBlockMeta(block.type);
                                    const isSelected = isAdvanced && state.selectedId === block.id;
                                    const showDropLine = isAdvanced && dropAt === index && (dragIndex !== null || isPaletteDrag);
                                    return (
                                        <React.Fragment key={block.id}>
                                            {showDropLine ? <div className="ld-drop-line" aria-hidden="true" /> : null}
                                            <div
                                                className={`ld-block ld-block--${block.type}${block.design ? ` ld-block--${block.type}-design-${String(block.design)}` : ''}${isSelected ? ' is-selected' : ''}${isAdvanced && dragIndex === index ? ' is-dragging' : ''}${!isAdvanced ? ' is-tappable' : ''}`}
                                                draggable={isAdvanced}
                                                onDragStart={isAdvanced ? () => setDragIndex(index) : undefined}
                                                onDragOver={isAdvanced ? (e) => {
                                                    e.preventDefault();
                                                    setDropAt(resolveDropIndex(e.clientY, e.currentTarget, index, blocks.length));
                                                } : undefined}
                                                onDrop={isAdvanced ? (e) => onDropAt(resolveDropIndex(e.clientY, e.currentTarget, index, blocks.length), e) : undefined}
                                                onDragEnd={isAdvanced ? finishDrag : undefined}
                                                onClick={(e) => onBlockClick(e, block.id)}
                                            >
                                                {isAdvanced ? (
                                                    <div className="ld-block__handle" title="ドラッグして並べ替え">
                                                        <i className="fas fa-grip-vertical" />
                                                    </div>
                                                ) : null}
                                                {isSelected ? (
                                                    <div className="ld-block__chrome">
                                                        <span className="ld-block__type">
                                                            <i className={`fas ${meta?.icon || 'fa-cube'}`} />
                                                            {meta?.label || block.type}
                                                        </span>
                                                        <div className="ld-block__toolbar">
                                                            <button type="button" className="ld-block__toolbar-btn" title="複製" onClick={(e) => { e.stopPropagation(); dispatch({ type: 'DUPLICATE_BLOCK', id: block.id }); }}>
                                                                <i className="fas fa-copy" />
                                                            </button>
                                                            <button type="button" className="ld-block__toolbar-btn" title="上へ" onClick={(e) => { e.stopPropagation(); if (index > 0) dispatch({ type: 'MOVE_BLOCK', from: index, to: index - 1 }); }}>
                                                                <i className="fas fa-arrow-up" />
                                                            </button>
                                                            <button type="button" className="ld-block__toolbar-btn" title="下へ" onClick={(e) => { e.stopPropagation(); if (index < blocks.length - 1) dispatch({ type: 'MOVE_BLOCK', from: index, to: index + 1 }); }}>
                                                                <i className="fas fa-arrow-down" />
                                                            </button>
                                                            <button type="button" className="ld-block__toolbar-btn ld-block__toolbar-btn--danger" title="削除" onClick={(e) => { e.stopPropagation(); dispatch({ type: 'DELETE_BLOCK', id: block.id }); }}>
                                                                <i className="fas fa-trash" />
                                                            </button>
                                                        </div>
                                                    </div>
                                                ) : !isAdvanced ? (
                                                    <div className="ld-block__tap-hint">
                                                        <i className="fas fa-pen" /> タップして編集
                                                    </div>
                                                ) : (
                                                    <div className="ld-block__hover-label">
                                                        <i className={`fas ${meta?.icon || 'fa-cube'}`} />
                                                        {meta?.label || block.type}
                                                    </div>
                                                )}
                                                <BlockPreview
                                                    block={block}
                                                    friendAddUrl={friendAddUrl}
                                                    shopName={String(state.globalSettings.app_display_name || '').trim() || undefined}
                                                    asGuest={state.previewAsGuest}
                                                    memberCardDesign={String(state.globalSettings.member_card_design || '')}
                                                />
                                            </div>
                                        </React.Fragment>
                                    );
                                })}
                                {isAdvanced && dropAt === blocks.length && (dragIndex !== null || isPaletteDrag) ? (
                                    <div className="ld-drop-line" aria-hidden="true" />
                                ) : null}
                                {isAdvanced ? (
                                    <div
                                        className="ld-drop-zone-end"
                                        onDragOver={(e) => {
                                            e.preventDefault();
                                            setDropAt(blocks.length);
                                        }}
                                        onDrop={(e) => onDropAt(blocks.length, e)}
                                    />
                                ) : null}
                            </>
                        )}
                    </PhoneAppMain>
                    {isDetailPage ? null : (
                        <PhoneAppTabs
                            items={enabledNav}
                            activeTab={state.activeTab}
                            onSelect={(tab) => dispatch({ type: 'SET_ACTIVE_TAB', tab })}
                        />
                    )}
                </PhoneDeviceFrame>
            </div>
        </div>
    );
}

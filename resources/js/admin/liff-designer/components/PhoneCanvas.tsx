import React, { useState } from 'react';
import { findBlockMeta, normalizeBlock } from '../catalog/blockCatalog';
import { BlockPreview } from '../blocks/BlockPreview';
import { BlockQuickEditSheet } from './BlockQuickEditSheet';
import { PhoneAppHeader, PhoneAppMain, PhoneAppTabs, PhoneDeviceFrame } from './PhoneAppShell';
import { useDesigner } from '../state/DesignerContext';
import type { LiffBlock } from '../catalog/types';
import {
    contentPadding,
    frameSystemClasses,
    resolvePhoneBodyStyle,
    sectionGap,
    screenTabStyleClass,
} from '../utils/globalDesign';

function resolveDropIndex(clientY: number, element: HTMLElement, index: number, blockCount: number): number {
    const rect = element.getBoundingClientRect();
    const mid = rect.top + rect.height / 2;
    const at = clientY < mid ? index : index + 1;
    return Math.max(0, Math.min(at, blockCount));
}

export function PhoneCanvas() {
    const { state, dispatch, config } = useDesigner();
    const isAdvanced = state.editorMode === 'advanced';
    const blocks = state.layouts[state.activeTab] || [];
    const menuItem = state.menuItems.find((m) => m.tab === state.activeTab);
    const tabSettings = state.tabSettings[state.activeTab] || {};
    const [dragIndex, setDragIndex] = useState<number | null>(null);
    const [dropAt, setDropAt] = useState<number | null>(null);
    const [isPaletteDrag, setIsPaletteDrag] = useState(false);
    const quickEditBlock = state.quickEditBlockId !== null
        ? blocks.find((b) => b.id === state.quickEditBlockId) || null
        : null;
    const friendAddUrl = config.lineFriendAddUrl || '';

    const bodyStyle = resolvePhoneBodyStyle(tabSettings, state.globalSettings);
    const padding = contentPadding(tabSettings, state.globalSettings);
    const gap = sectionGap(state.globalSettings);
    const tabStyleClass = screenTabStyleClass(state.globalSettings);
    const systemClass = frameSystemClasses(state.globalSettings);
    const enabledNav = state.menuItems.filter((m) => m.enabled);
    const shopTitle = document.title.replace(/\s*[|\-].*$/, '').trim() || '店舗アプリ';

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
        if (isAdvanced) {
            dispatch({ type: 'SELECT_BLOCK', id: blockId });
            return;
        }
        dispatch({ type: 'OPEN_QUICK_EDIT', id: blockId });
    };

    return (
        <div className="ld-editor">
            <div className="ld-editor__toolbar">
                <div className="ld-editor__meta">
                    <span className="ld-editor__meta-label">Canvas</span>
                    <div className="ld-editor__meta-row">
                        <strong>{menuItem?.label || state.activeTab}</strong>
                        <span className="ld-editor__meta-pill">{blocks.length} blocks</span>
                    </div>
                    {!isAdvanced ? <p className="ld-editor__hint">ブロックをタップして内容を編集</p> : null}
                </div>
                <div className="ld-editor__toolbar-right">
                    {isAdvanced ? (
                        <button
                            type="button"
                            className="ld-btn ld-btn--soft ld-btn--xs"
                            onClick={() => dispatch({ type: 'SET_RIGHT_MODE', mode: 'add' })}
                        >
                            <i className="fas fa-plus" /> ブロック追加
                        </button>
                    ) : null}
                    <div className="ld-editor__zoom">
                        <button type="button" className="ld-icon-btn ld-icon-btn--sm" onClick={() => dispatch({ type: 'SET_ZOOM', zoom: state.zoom - 10 })} aria-label="縮小">
                            <i className="fas fa-minus" />
                        </button>
                        <input
                            type="range"
                            className="ld-form-range ld-form-range--zoom"
                            min={60}
                            max={120}
                            value={state.zoom}
                            onChange={(e) => dispatch({ type: 'SET_ZOOM', zoom: Number(e.target.value) })}
                        />
                        <button type="button" className="ld-icon-btn ld-icon-btn--sm" onClick={() => dispatch({ type: 'SET_ZOOM', zoom: state.zoom + 10 })} aria-label="拡大">
                            <i className="fas fa-plus" />
                        </button>
                        <span className="ld-editor__zoom-value">{state.zoom}%</span>
                    </div>
                </div>
            </div>

            <div className="ld-editor__stage">
                <PhoneDeviceFrame
                    className={systemClass}
                    size="canvas"
                    zoom={state.zoom}
                    screenStyle={bodyStyle}
                    screenClassName={tabStyleClass}
                >
                    <PhoneAppHeader title={shopTitle} friendAddUrl={friendAddUrl} />
                    <PhoneAppMain
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
                        {blocks.length === 0 ? (
                            <div className="ld-empty-canvas">
                                <i className="fas fa-layer-group ld-empty-canvas__icon" />
                                <p>この画面にはまだブロックがありません</p>
                                <p className="ld-empty-canvas__hint">
                                    {isAdvanced ? '右のパネルからブロックを追加するか、テンプレートを選んでください' : 'トップバーのテンプレートから画面を作成してください'}
                                </p>
                                <div className="ld-empty-canvas__actions">
                                    <button type="button" className="ld-btn ld-btn--primary ld-btn--xs" onClick={() => dispatch({ type: 'SET_UI', patch: { templateOpen: true } })}>
                                        テンプレートを選ぶ
                                    </button>
                                    {isAdvanced ? (
                                        <button type="button" className="ld-btn ld-btn--outline ld-btn--xs" onClick={() => dispatch({ type: 'SET_RIGHT_MODE', mode: 'add' })}>
                                            ブロックを追加
                                        </button>
                                    ) : null}
                                </div>
                            </div>
                        ) : (
                            <>
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
                                                <BlockPreview block={block} friendAddUrl={friendAddUrl} />
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
                    <PhoneAppTabs
                        items={enabledNav}
                        activeTab={state.activeTab}
                        onSelect={(tab) => dispatch({ type: 'SET_ACTIVE_TAB', tab })}
                    />
                </PhoneDeviceFrame>
            </div>

            {quickEditBlock !== null ? (
                <BlockQuickEditSheet
                    block={quickEditBlock}
                    friendAddUrl={friendAddUrl}
                    onChange={(patch) => dispatch({ type: 'UPDATE_BLOCK', id: quickEditBlock.id, patch })}
                    onClose={() => dispatch({ type: 'CLOSE_QUICK_EDIT' })}
                />
            ) : null}
        </div>
    );
}

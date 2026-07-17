import React from 'react';
import { findBlockMeta } from '../catalog/blockCatalog';
import { useDesigner } from '../state/DesignerContext';

export function LayersPanel() {
    const { state, dispatch } = useDesigner();
    const blocks = state.layouts[state.activeTab] || [];

    if (blocks.length === 0) {
        return (
            <div className="ld-sidebar__section ld-studio-panel-body">
                <div className="ld-sidebar__title">レイヤー</div>
                <p className="ld-sidebar__lead ld-sidebar__lead--muted">この画面にブロックがありません</p>
                <button
                    type="button"
                    className="ld-btn ld-btn--soft ld-btn--block"
                    onClick={() => dispatch({ type: 'SET_LEFT_STUDIO_TAB', tab: 'blocks' })}
                >
                    <i className="fas fa-plus" /> ブロックを追加
                </button>
            </div>
        );
    }

    return (
        <div className="ld-sidebar__section ld-studio-panel-body">
            <div className="ld-sidebar__title">レイヤー</div>
            <p className="ld-sidebar__lead">{blocks.length} ブロック</p>
            <ul className="ld-layers-list">
                {blocks.map((block, index) => {
                    const meta = findBlockMeta(block.type);
                    const isActive = state.selectedId === block.id;
                    return (
                        <li key={block.id} className={`ld-layers-item${isActive ? ' is-active' : ''}`}>
                            <button
                                type="button"
                                className="ld-layers-item__main"
                                onClick={() => dispatch({ type: 'SELECT_BLOCK', id: block.id })}
                            >
                                <span className="ld-layers-item__icon">
                                    <i className={`fas ${meta?.icon || 'fa-cube'}`} />
                                </span>
                                <span className="ld-layers-item__label">{meta?.label || block.type}</span>
                            </button>
                            <div className="ld-layers-item__actions">
                                <button
                                    type="button"
                                    className="ld-icon-btn ld-icon-btn--sm"
                                    title="上へ"
                                    disabled={index === 0}
                                    onClick={() => dispatch({ type: 'MOVE_BLOCK', from: index, to: index - 1 })}
                                >
                                    <i className="fas fa-arrow-up" />
                                </button>
                                <button
                                    type="button"
                                    className="ld-icon-btn ld-icon-btn--sm"
                                    title="下へ"
                                    disabled={index === blocks.length - 1}
                                    onClick={() => dispatch({ type: 'MOVE_BLOCK', from: index, to: index + 1 })}
                                >
                                    <i className="fas fa-arrow-down" />
                                </button>
                                <button
                                    type="button"
                                    className="ld-icon-btn ld-icon-btn--sm"
                                    title="複製"
                                    onClick={() => dispatch({ type: 'DUPLICATE_BLOCK', id: block.id })}
                                >
                                    <i className="fas fa-copy" />
                                </button>
                                <button
                                    type="button"
                                    className="ld-icon-btn ld-icon-btn--sm"
                                    title="削除"
                                    onClick={() => dispatch({ type: 'DELETE_BLOCK', id: block.id })}
                                >
                                    <i className="fas fa-trash" />
                                </button>
                            </div>
                        </li>
                    );
                })}
            </ul>
        </div>
    );
}

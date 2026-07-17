import React from 'react';
import { BLOCK_CATEGORIES, BLOCK_TYPES, normalizeBlock } from '../catalog/blockCatalog';
import { useDesigner } from '../state/DesignerContext';

export function BlockPalette() {
    const { state, dispatch } = useDesigner();

    const filtered = BLOCK_TYPES.filter((bt) => {
        if (state.paletteCategory !== 'all' && bt.category !== state.paletteCategory) return false;
        const q = state.paletteSearch.trim().toLowerCase();
        if (q === '') return true;
        return bt.label.toLowerCase().includes(q) || bt.desc.toLowerCase().includes(q) || bt.type.includes(q);
    });

    return (
        <div className="ld-sidebar__section ld-sidebar__section--palette ld-studio-panel-body">
            <div className="ld-sidebar__title">ブロック</div>
            <p className="ld-sidebar__lead">クリックで追加、ドラッグでキャンバスに配置</p>
            <div className="ld-palette-controls">
                <input
                    type="text"
                    className="ld-form-input"
                    placeholder="ヒーロー / 会員 / クーポン..."
                    value={state.paletteSearch}
                    onChange={(e) => dispatch({ type: 'SET_PALETTE_SEARCH', value: e.target.value })}
                />
                <div className="ld-palette-categories">
                    {BLOCK_CATEGORIES.map((c) => (
                        <button
                            key={c.id}
                            type="button"
                            className={`ld-palette-cat${state.paletteCategory === c.id ? ' is-active' : ''}`}
                            onClick={() => dispatch({ type: 'SET_PALETTE_CATEGORY', value: c.id })}
                        >
                            {c.label}
                        </button>
                    ))}
                </div>
            </div>
            <div className="ld-block-palette">
                {filtered.length === 0 ? (
                    <div className="ld-palette-empty">
                        <i className="fas fa-search" />
                        <p>該当するブロックがありません</p>
                    </div>
                ) : filtered.map((bt) => (
                    <button
                        key={bt.type}
                        type="button"
                        className="ld-palette-item"
                        draggable
                        onDragStart={(e) => {
                            e.dataTransfer.setData('application/ld-block-type', bt.type);
                            e.dataTransfer.effectAllowed = 'copy';
                        }}
                        onClick={() => {
                            const block = normalizeBlock({ type: bt.type }, { n: Date.now() % 100000 });
                            dispatch({ type: 'ADD_BLOCK', block });
                        }}
                    >
                        <span className="ld-palette-item__icon"><i className={`fas ${bt.icon}`} /></span>
                        <span className="ld-palette-item__text">
                            <span className="ld-palette-item__label">{bt.label}</span>
                            <span className="ld-palette-item__desc">{bt.desc}</span>
                        </span>
                    </button>
                ))}
            </div>
        </div>
    );
}

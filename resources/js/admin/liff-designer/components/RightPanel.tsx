import React from 'react';
import { BLOCK_CATEGORIES, BLOCK_TYPES, findBlockMeta, normalizeBlock } from '../catalog/blockCatalog';
import { BlockSettingsEditor } from '../blocks/BlockSettingsEditor';
import { SimpleTabPanel } from './SimpleTabPanel';
import { useDesigner } from '../state/DesignerContext';
import { ColorField, ImageUrlField, RangeField, SelectField } from './fields/FormFields';

function AdvancedPanel() {
    const { state, dispatch, config } = useDesigner();
    const selected = (state.layouts[state.activeTab] || []).find((b) => b.id === state.selectedId) || null;
    const tabSettings = state.tabSettings[state.activeTab] || {};
    const friendAddUrl = config.lineFriendAddUrl || '';

    const filtered = BLOCK_TYPES.filter((bt) => {
        if (state.paletteCategory !== 'all' && bt.category !== state.paletteCategory) return false;
        const q = state.paletteSearch.trim().toLowerCase();
        if (q === '') return true;
        return bt.label.toLowerCase().includes(q) || bt.desc.toLowerCase().includes(q) || bt.type.includes(q);
    });

    return (
        <div className="ld-sidebar__body">
            {state.rightMode === 'add' ? (
                <div className="ld-sidebar__section ld-sidebar__section--palette">
                    <div className="ld-sidebar__title">Blocks</div>
                    <p className="ld-sidebar__lead">クリックで追加、ドラッグで配置</p>
                    <div className="ld-palette-controls">
                        <input
                            type="text"
                            className="ld-form-input"
                            placeholder="名前で検索..."
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
            ) : null}

            {state.rightMode === 'edit' ? (
                <div className="ld-sidebar__section ld-sidebar__section--settings">
                    {selected !== null ? (
                        <BlockSettingsEditor
                            block={selected}
                            friendAddUrl={friendAddUrl}
                            onChange={(patch) => dispatch({ type: 'UPDATE_BLOCK', id: selected.id, patch })}
                            onReplace={(block) => dispatch({ type: 'REPLACE_BLOCK', id: selected.id, block })}
                            onDelete={() => dispatch({ type: 'DELETE_BLOCK', id: selected.id })}
                        />
                    ) : (
                        <>
                            <div className="ld-sidebar__title">画面設定</div>
                            <p className="ld-sidebar__lead">ブロック未選択時は、このタブ全体の見た目を調整できます。</p>
                            <ColorField
                                label="背景色"
                                value={String(tabSettings.background_color || '#fafaf9')}
                                onChange={(v) => dispatch({ type: 'UPDATE_TAB_SETTINGS', patch: { background_color: v } })}
                            />
                            <ImageUrlField
                                label="背景画像"
                                value={String(tabSettings.background_image || '')}
                                onChange={(v) => dispatch({ type: 'UPDATE_TAB_SETTINGS', patch: { background_image: v } })}
                            />
                            <SelectField
                                label="背景画像の表示"
                                value={String(tabSettings.background_size || 'cover')}
                                options={[
                                    { value: 'cover', label: 'カバー' },
                                    { value: 'contain', label: '全体表示' },
                                    { value: 'auto', label: 'オリジナル' },
                                ]}
                                onChange={(v) => dispatch({ type: 'UPDATE_TAB_SETTINGS', patch: { background_size: v } })}
                            />
                            <RangeField
                                label="コンテンツ余白"
                                value={Number(tabSettings.content_padding ?? state.globalSettings.content_padding ?? 18)}
                                min={8}
                                max={40}
                                onChange={(v) => dispatch({ type: 'UPDATE_TAB_SETTINGS', patch: { content_padding: v } })}
                            />
                            <button
                                type="button"
                                className="ld-btn ld-btn--outline ld-btn--block ld-theme-open-btn"
                                onClick={() => dispatch({ type: 'SET_UI', patch: { themeOpen: true } })}
                            >
                                <i className="fas fa-swatchbook" /> テーマを変更
                            </button>
                        </>
                    )}
                </div>
            ) : null}
        </div>
    );
}

function AdvancedChrome() {
    const { state, dispatch } = useDesigner();
    const selected = (state.layouts[state.activeTab] || []).find((b) => b.id === state.selectedId) || null;
    const selectedMeta = selected !== null ? findBlockMeta(selected.type) : null;

    return (
        <>
            {selected !== null && state.rightMode !== 'edit' ? (
                <div className="ld-panel-hint">
                    <span>
                        <i className={`fas ${selectedMeta?.icon || 'fa-cube'}`} />
                        {selectedMeta?.label || selected.type} を選択中
                    </span>
                    <button type="button" className="ld-btn ld-btn--primary ld-btn--xs" onClick={() => dispatch({ type: 'SET_RIGHT_MODE', mode: 'edit' })}>
                        設定を開く
                    </button>
                </div>
            ) : null}

            <div className="ld-mode-tabs ld-mode-tabs--dual">
                {([
                    { id: 'add', label: '追加', icon: 'fa-plus' },
                    { id: 'edit', label: '編集', icon: 'fa-sliders-h', badge: selected !== null },
                ] as const).map((tab) => (
                    <button
                        key={tab.id}
                        type="button"
                        className={`ld-mode-tab${state.rightMode === tab.id ? ' is-active' : ''}`}
                        onClick={() => dispatch({ type: 'SET_RIGHT_MODE', mode: tab.id })}
                    >
                        <i className={`fas ${tab.icon}`} />
                        {tab.label}
                        {'badge' in tab && tab.badge ? <span className="ld-mode-tab__dot" /> : null}
                    </button>
                ))}
            </div>
        </>
    );
}

export function RightPanel() {
    const { state, dispatch } = useDesigner();

    return (
        <aside className={`ld-sidebar ld-sidebar--light${state.sidebarOpen ? ' is-open' : ''}`}>
            <div className="ld-sidebar__chrome">
                <div>
                    <div className="ld-sidebar__chrome-title">Inspector</div>
                    <p className="ld-sidebar__chrome-sub">
                        {state.editorMode === 'simple' ? '画面の見た目を調整' : 'ブロックと画面設定'}
                    </p>
                </div>
                <button
                    type="button"
                    className="ld-icon-btn ld-icon-btn--sm"
                    onClick={() => dispatch({ type: 'SET_UI', patch: { themeOpen: true } })}
                    title="テーマ"
                >
                    <i className="fas fa-swatchbook" />
                </button>
            </div>
            {state.editorMode === 'advanced' ? <AdvancedChrome /> : null}
            <div className="ld-sidebar__scroll">
                {state.editorMode === 'simple' ? (
                    <div className="ld-sidebar__body">
                        <SimpleTabPanel />
                    </div>
                ) : (
                    <AdvancedPanel />
                )}
            </div>
            <button type="button" className="ld-drawer-close ld-mobile-panel-btn" onClick={() => dispatch({ type: 'SET_UI', patch: { sidebarOpen: false } })}>
                閉じる
            </button>
        </aside>
    );
}

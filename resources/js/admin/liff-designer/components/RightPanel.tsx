import React from 'react';
import { findBlockMeta } from '../catalog/blockCatalog';
import { BlockSettingsEditor } from '../blocks/BlockSettingsEditor';
import { BlockQuickEditPanel } from './BlockQuickEditSheet';
import { LayersPanel } from './LayersPanel';
import { SimpleTabPanel } from './SimpleTabPanel';
import { ThemePanel } from './ThemePanel';
import { useDesigner } from '../state/DesignerContext';
import { ColorField, ImageUrlField, RangeField, SelectField } from './fields/FormFields';
import type { RightStudioTab } from '../catalog/types';

function ScreenBackgroundEditor() {
    const { state, dispatch } = useDesigner();
    const tabSettings = state.tabSettings[state.activeTab] || {};

    return (
        <>
            <div className="ld-sidebar__title">画面設定</div>
            <p className="ld-sidebar__lead">ブロック未選択時は、このタブ全体の見た目を調整できます。</p>
            <p className="ld-sidebar__lead ld-sidebar__lead--muted">背景を空にするとアプリ全体の背景が使われます。</p>
            <ColorField
                label="背景色"
                value={String(tabSettings.background_color || state.globalSettings.bg_color || '#f9fafb')}
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
            <button
                type="button"
                className="ld-btn ld-btn--ghost ld-btn--block"
                onClick={() => dispatch({ type: 'UPDATE_TAB_SETTINGS', patch: { background_color: '', background_image: '' } })}
            >
                この画面の背景をクリア（全体を使う）
            </button>
            <button
                type="button"
                className="ld-btn ld-btn--soft ld-btn--block"
                onClick={() => {
                    const color = String(tabSettings.background_color || state.globalSettings.bg_color || '#f9fafb');
                    dispatch({ type: 'UPDATE_GLOBAL', patch: { background_style: 'solid', bg_color: color } });
                    dispatch({ type: 'APPLY_GLOBAL_BACKGROUND' });
                    dispatch({ type: 'SHOW_TOAST', message: 'この背景色をアプリ全体に適用しました' });
                }}
            >
                <i className="fas fa-fill-drip" /> この背景を全体に適用
            </button>
            <RangeField
                label="コンテンツ余白"
                value={Number(tabSettings.content_padding ?? state.globalSettings.content_padding ?? 18)}
                min={8}
                max={40}
                onChange={(v) => dispatch({ type: 'UPDATE_TAB_SETTINGS', patch: { content_padding: v } })}
            />
        </>
    );
}

function EditPanel() {
    const { state, dispatch, config } = useDesigner();
    const isSimple = state.editorMode === 'simple';
    const friendAddUrl = config.lineFriendAddUrl || '';
    const selected = (state.layouts[state.activeTab] || []).find((b) => b.id === state.selectedId) || null;
    const quickEditBlock = isSimple && state.quickEditBlockId !== null
        ? (state.layouts[state.activeTab] || []).find((b) => b.id === state.quickEditBlockId) || null
        : null;

    if (isSimple) {
        if (quickEditBlock !== null) {
            return (
                <BlockQuickEditPanel
                    block={quickEditBlock}
                    friendAddUrl={friendAddUrl}
                    onChange={(patch) => dispatch({ type: 'UPDATE_BLOCK', id: quickEditBlock.id, patch })}
                    onClose={() => dispatch({ type: 'CLOSE_QUICK_EDIT' })}
                />
            );
        }
        return <SimpleTabPanel />;
    }

    if (selected !== null) {
        return (
            <BlockSettingsEditor
                block={selected}
                friendAddUrl={friendAddUrl}
                onChange={(patch) => dispatch({ type: 'UPDATE_BLOCK', id: selected.id, patch })}
                onReplace={(block) => dispatch({ type: 'REPLACE_BLOCK', id: selected.id, block })}
                onDelete={() => dispatch({ type: 'DELETE_BLOCK', id: selected.id })}
            />
        );
    }

    return <ScreenBackgroundEditor />;
}

const RIGHT_TABS: Array<{ id: RightStudioTab; label: string; icon: string }> = [
    { id: 'edit', label: '編集', icon: 'fa-sliders-h' },
    { id: 'theme', label: 'テーマ', icon: 'fa-swatchbook' },
    { id: 'layers', label: 'レイヤー', icon: 'fa-layer-group' },
];

export function RightPanel() {
    const { state, dispatch } = useDesigner();
    const selected = (state.layouts[state.activeTab] || []).find((b) => b.id === state.selectedId) || null;
    const selectedMeta = selected !== null ? findBlockMeta(selected.type) : null;
    const isSimple = state.editorMode === 'simple';
    const tab = state.rightStudioTab;

    return (
        <aside className={`ld-sidebar ld-sidebar--light ld-studio-right${state.sidebarOpen ? ' is-open' : ''}`}>
            <div className="ld-sidebar__chrome">
                <div>
                    <div className="ld-sidebar__chrome-title">設定パネル</div>
                    <p className="ld-sidebar__chrome-sub">
                        {selected !== null
                            ? `${selectedMeta?.label || selected.type} を編集`
                            : isSimple
                                ? '画面の見た目を調整'
                                : 'ブロックとテーマ設定'}
                    </p>
                </div>
                <div className="ld-panel-head-actions">
                    <button
                        type="button"
                        className="ld-icon-btn ld-icon-btn--sm ld-panel-close"
                        onClick={() => dispatch({ type: 'SET_UI', patch: { sidebarOpen: false } })}
                        title="閉じる"
                        aria-label="設定パネルを閉じる"
                    >
                        <i className="fas fa-times" />
                    </button>
                </div>
            </div>

            <div className="ld-studio-tabs ld-studio-tabs--right" role="tablist" aria-label="右パネル">
                {RIGHT_TABS.map((item) => (
                    <button
                        key={item.id}
                        type="button"
                        role="tab"
                        aria-selected={tab === item.id}
                        className={`ld-studio-tab${tab === item.id ? ' is-active' : ''}`}
                        onClick={() => dispatch({ type: 'SET_RIGHT_STUDIO_TAB', tab: item.id })}
                    >
                        <i className={`fas ${item.icon}`} />
                        <span>{item.label}</span>
                        {item.id === 'edit' && selected !== null ? <span className="ld-mode-tab__dot" /> : null}
                    </button>
                ))}
            </div>

            <div className="ld-sidebar__scroll">
                <div className="ld-sidebar__body">
                    {tab === 'edit' ? (
                        <div className="ld-sidebar__section ld-sidebar__section--settings">
                            <EditPanel />
                        </div>
                    ) : null}
                    {tab === 'theme' ? <ThemePanel /> : null}
                    {tab === 'layers' ? <LayersPanel /> : null}
                </div>
            </div>
        </aside>
    );
}

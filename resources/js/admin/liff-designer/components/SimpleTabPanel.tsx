import React from 'react';
import { useDesigner } from '../state/DesignerContext';
import { ColorField, ImageUrlField, RangeField, SelectField } from './fields/FormFields';

export function SimpleTabPanel() {
    const { state, dispatch } = useDesigner();
    const tabSettings = state.tabSettings[state.activeTab] || {};
    const menuItem = state.menuItems.find((m) => m.tab === state.activeTab);

    return (
        <div className="ld-sidebar__section">
            <div className="ld-sidebar__title">画面設定</div>
            <p className="ld-sidebar__lead">
                編集中: <strong>{menuItem?.label || state.activeTab}</strong>
            </p>
            <p className="ld-sidebar__lead ld-sidebar__lead--muted">
                下部タブの表示・非表示・順番・名前は<strong>左の画面一覧</strong>で変更します。ここでは選択中の画面の見た目だけを設定します。
            </p>
            <p className="ld-sidebar__subsection">この画面の背景</p>
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
                value={Number(tabSettings.content_padding ?? 18)}
                min={8}
                max={40}
                onChange={(v) => dispatch({ type: 'UPDATE_TAB_SETTINGS', patch: { content_padding: v } })}
            />
            <p className="ld-sidebar__subsection">アプリ全体</p>
            <button
                type="button"
                className="ld-btn ld-btn--outline ld-btn--block ld-theme-open-btn"
                onClick={() => dispatch({ type: 'SET_UI', patch: { themeOpen: true } })}
            >
                <i className="fas fa-swatchbook" /> テーマを変更
            </button>
        </div>
    );
}

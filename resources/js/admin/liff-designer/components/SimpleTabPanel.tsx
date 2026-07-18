import React from 'react';
import { useDesigner } from '../state/DesignerContext';
import { DETAIL_PAGE_LABELS, DETAIL_PAGES, TAB_LABELS } from '../catalog/blockCatalog';
import { ColorField, ImageUrlField, RangeField, SelectField } from './fields/FormFields';

export function SimpleTabPanel() {
    const { state, dispatch } = useDesigner();
    const tabSettings = state.tabSettings[state.activeTab] || {};
    const menuItem = state.menuItems.find((m) => m.tab === state.activeTab);
    const isDetailPage = DETAIL_PAGES.includes(state.activeTab);
    const screenLabel = menuItem?.label
        || DETAIL_PAGE_LABELS[state.activeTab]
        || TAB_LABELS[state.activeTab]
        || state.activeTab;

    return (
        <div className="ld-sidebar__section">
            <div className="ld-sidebar__title">画面設定</div>
            <p className="ld-sidebar__lead">
                編集中: <strong>{screenLabel}</strong>
            </p>
            {isDetailPage ? (
                <p className="ld-sidebar__lead ld-sidebar__lead--muted">
                    詳細ページの枠（見出し・バナーなど）をノーコードで編集できます。本文・画像は管理画面のお知らせから反映されます。
                </p>
            ) : (
                <p className="ld-sidebar__lead ld-sidebar__lead--muted">
                    左の Screens で表示・順番・名前を変更。ここでは見た目だけを設定します。
                </p>
            )}
            <p className="ld-sidebar__subsection">この画面の背景</p>
            <p className="ld-sidebar__lead ld-sidebar__lead--muted">
                未設定のときはアプリ全体の背景が使われます。色を選ぶとこの画面だけ上書きされます。
            </p>
            {String(tabSettings.background_color || '') === '' ? (
                <>
                    <p className="ld-form-hint">
                        現在: 全体背景（{String(state.globalSettings.bg_color || '#f9fafb')}）を使用中
                    </p>
                    <ColorField
                        label="この画面だけ上書き"
                        value={String(state.globalSettings.bg_color || '#f9fafb')}
                        onChange={(v) => dispatch({ type: 'UPDATE_TAB_SETTINGS', patch: { background_color: v } })}
                    />
                </>
            ) : (
                <ColorField
                    label="背景色（この画面）"
                    value={String(tabSettings.background_color)}
                    onChange={(v) => dispatch({ type: 'UPDATE_TAB_SETTINGS', patch: { background_color: v } })}
                />
            )}
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
            <p className="ld-sidebar__subsection">アプリの動作（本番）</p>
            <div className="ld-bg-style-tabs" role="group" aria-label="会員登録ポリシー">
                <button
                    type="button"
                    className={`ld-bg-style-tab${state.globalSettings.require_membership !== false ? ' is-active' : ''}`}
                    onClick={() => dispatch({ type: 'UPDATE_GLOBAL', patch: { require_membership: true } })}
                >
                    会員必須
                </button>
                <button
                    type="button"
                    className={`ld-bg-style-tab${state.globalSettings.require_membership === false ? ' is-active' : ''}`}
                    onClick={() => dispatch({ type: 'UPDATE_GLOBAL', patch: { require_membership: false } })}
                >
                    会員任意
                </button>
            </div>
            <p className="ld-form-hint">
                {state.globalSettings.require_membership !== false
                    ? '本番は登録後のみ会員機能が使えます。見え方確認はキャンバスの「ゲスト表示」を使います。'
                    : '本番はゲスト閲覧可。見え方確認はキャンバスの「会員表示 / ゲスト表示」です。'}
            </p>
            <p className="ld-sidebar__subsection">アプリ全体</p>
            <button
                type="button"
                className="ld-btn ld-btn--outline ld-btn--block ld-theme-open-btn"
                onClick={() => dispatch({ type: 'SET_RIGHT_STUDIO_TAB', tab: 'theme' })}
            >
                <i className="fas fa-swatchbook" /> テーマを変更
            </button>
        </div>
    );
}

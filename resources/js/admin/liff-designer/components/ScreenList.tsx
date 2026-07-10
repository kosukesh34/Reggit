import React from 'react';
import { TAB_LABELS, TAB_ICONS } from '../catalog/blockCatalog';
import { useTabFeatureMap } from './tabHelpers';
import { useDesigner } from '../state/DesignerContext';

export function ScreenList() {
    const { state, dispatch, config } = useDesigner();
    const featureMap = useTabFeatureMap();
    const menuByTab = Object.fromEntries(state.menuItems.map((m) => [m.tab, m]));

    const renderTab = (tab: string, item: { label: string; icon: string; enabled: boolean }, editable: boolean) => {
        const featureKey = featureMap[tab];
        const featureOff = featureKey !== undefined && config.featureFlags?.[featureKey] === false;
        const count = (state.layouts[tab] || []).length;
        const isActive = state.activeTab === tab;

        return (
            <div
                key={tab}
                className={`ld-tab-card${isActive ? ' is-active' : ''}${item.enabled ? '' : ' is-hidden-tab'}${featureOff ? ' is-feature-off' : ''}`}
            >
                <button
                    type="button"
                    className="ld-tab-card__main"
                    onClick={() => dispatch({ type: 'SET_ACTIVE_TAB', tab })}
                >
                    <span className="ld-tab-card__icon">
                        <i className={`fas ${item.icon}`} />
                    </span>
                    <span className="ld-tab-card__info">
                        {editable ? (
                            <input
                                className="ld-tab-card__name"
                                value={item.label}
                                onClick={(e) => e.stopPropagation()}
                                onChange={(e) => dispatch({ type: 'UPDATE_MENU_ITEM', tab, patch: { label: e.target.value } })}
                            />
                        ) : (
                            <span className="ld-tab-card__name ld-tab-card__name--static">{item.label}</span>
                        )}
                        <span className="ld-tab-card__meta">
                            {count} ブロック
                            {!editable ? ' · タブ非表示' : null}
                            {featureOff ? ' · 機能OFF' : null}
                        </span>
                    </span>
                </button>
                {editable ? (
                    <div className="ld-tab-card__actions">
                        <button type="button" title="上へ" onClick={() => dispatch({ type: 'MOVE_MENU_ITEM', tab, delta: -1 })}>
                            <i className="fas fa-chevron-up" />
                        </button>
                        <button type="button" title="下へ" onClick={() => dispatch({ type: 'MOVE_MENU_ITEM', tab, delta: 1 })}>
                            <i className="fas fa-chevron-down" />
                        </button>
                        <button
                            type="button"
                            title={item.enabled ? 'タブを非表示' : 'タブを表示'}
                            className={item.enabled ? '' : 'is-off'}
                            onClick={() => dispatch({ type: 'UPDATE_MENU_ITEM', tab, patch: { enabled: !item.enabled } })}
                        >
                            <i className={`fas ${item.enabled ? 'fa-eye' : 'fa-eye-slash'}`} />
                        </button>
                    </div>
                ) : null}
            </div>
        );
    };

    return (
        <aside className={`ld-screens ld-screens--light${state.screensOpen ? ' is-open' : ''}`}>
            <div className="ld-screens__head">
                <div className="ld-screens__title">画面一覧</div>
                <p className="ld-screens__hint">タップで編集画面を切り替え。目のアイコンで表示/非表示、矢印で順番を変更できます。</p>
            </div>
            <div className="ld-screens__list">
                {state.menuItems.map((item) => renderTab(item.tab, item, true))}
                {menuByTab.announcements === undefined ? renderTab('announcements', {
                    label: TAB_LABELS.announcements,
                    icon: TAB_ICONS.announcements,
                    enabled: true,
                }, false) : null}
            </div>
            <button type="button" className="ld-drawer-close ld-mobile-panel-btn" onClick={() => dispatch({ type: 'SET_UI', patch: { screensOpen: false } })}>
                閉じる
            </button>
        </aside>
    );
}

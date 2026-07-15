import React, { useEffect, useLayoutEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { TAB_LABELS, TAB_ICONS } from '../catalog/blockCatalog';
import { useTabFeatureMap } from './tabHelpers';
import { useDesigner } from '../state/DesignerContext';

type MenuPos = { top: number; right: number };

export function ScreenList() {
    const { state, dispatch, config } = useDesigner();
    const featureMap = useTabFeatureMap();
    const menuByTab = Object.fromEntries(state.menuItems.map((m) => [m.tab, m]));
    const enabledCount = state.menuItems.filter((m) => m.enabled).length;
    const [menuOpenTab, setMenuOpenTab] = useState<string | null>(null);
    const [menuPos, setMenuPos] = useState<MenuPos | null>(null);
    const [renamingTab, setRenamingTab] = useState<string | null>(null);
    const menuRef = useRef<HTMLDivElement>(null);
    const moreBtnRefs = useRef<Record<string, HTMLButtonElement | null>>({});
    const renameInputRef = useRef<HTMLInputElement>(null);

    const closeMenu = () => {
        setMenuOpenTab(null);
        setMenuPos(null);
    };

    useLayoutEffect(() => {
        if (menuOpenTab === null) return;
        const btn = moreBtnRefs.current[menuOpenTab];
        if (btn === null || btn === undefined) return;
        const rect = btn.getBoundingClientRect();
        setMenuPos({ top: rect.bottom + 4, right: window.innerWidth - rect.right });
    }, [menuOpenTab]);

    useEffect(() => {
        if (menuOpenTab === null) return;
        const onPointerDown = (e: PointerEvent) => {
            const target = e.target as Node | null;
            if (target === null) return;
            const btn = moreBtnRefs.current[menuOpenTab];
            if (btn !== null && btn !== undefined && btn.contains(target)) return;
            if (menuRef.current !== null && menuRef.current.contains(target)) return;
            closeMenu();
        };
        const onKeyDown = (e: KeyboardEvent) => {
            if (e.key === 'Escape') closeMenu();
        };
        const onScroll = () => closeMenu();
        document.addEventListener('pointerdown', onPointerDown);
        document.addEventListener('keydown', onKeyDown);
        window.addEventListener('scroll', onScroll, true);
        window.addEventListener('resize', onScroll);
        return () => {
            document.removeEventListener('pointerdown', onPointerDown);
            document.removeEventListener('keydown', onKeyDown);
            window.removeEventListener('scroll', onScroll, true);
            window.removeEventListener('resize', onScroll);
        };
    }, [menuOpenTab]);

    useEffect(() => {
        if (renamingTab === null) return;
        renameInputRef.current?.focus();
        renameInputRef.current?.select();
    }, [renamingTab]);

    const startRename = (tab: string) => {
        closeMenu();
        setRenamingTab(tab);
    };

    const finishRename = () => {
        setRenamingTab(null);
    };

    const openItem = menuOpenTab !== null
        ? state.menuItems.find((m) => m.tab === menuOpenTab) ?? null
        : null;
    const openTabIndex = menuOpenTab !== null
        ? state.menuItems.findIndex((m) => m.tab === menuOpenTab)
        : -1;
    const canMoveUp = openTabIndex > 0;
    const canMoveDown = openTabIndex >= 0 && openTabIndex < state.menuItems.length - 1;

    const renderTab = (tab: string, item: { label: string; icon: string; enabled: boolean }, editable: boolean) => {
        const featureKey = featureMap[tab];
        const featureOff = featureKey !== undefined && config.featureFlags?.[featureKey] === false;
        const count = (state.layouts[tab] || []).length;
        const isActive = state.activeTab === tab;
        const isRenaming = renamingTab === tab;
        const isMenuOpen = menuOpenTab === tab;

        return (
            <div
                key={tab}
                className={`ld-tab-card${isActive ? ' is-active' : ''}${item.enabled ? '' : ' is-hidden-tab'}${featureOff ? ' is-feature-off' : ''}`}
            >
                <button
                    type="button"
                    className="ld-tab-card__main"
                    onClick={() => {
                        if (isRenaming) return;
                        dispatch({ type: 'SET_ACTIVE_TAB', tab });
                    }}
                >
                    <span className="ld-tab-card__icon">
                        <i className={`fas ${item.icon}`} />
                    </span>
                    <span className="ld-tab-card__info">
                        {editable && isRenaming ? (
                            <input
                                ref={renameInputRef}
                                className="ld-tab-card__name ld-tab-card__name--editing"
                                value={item.label}
                                onClick={(e) => e.stopPropagation()}
                                onChange={(e) => dispatch({ type: 'UPDATE_MENU_ITEM', tab, patch: { label: e.target.value } })}
                                onBlur={finishRename}
                                onKeyDown={(e) => {
                                    if (e.key === 'Enter' || e.key === 'Escape') {
                                        e.preventDefault();
                                        finishRename();
                                    }
                                }}
                                aria-label="タブ名を編集"
                            />
                        ) : (
                            <span className="ld-tab-card__name ld-tab-card__name--static">{item.label}</span>
                        )}
                        <span className="ld-tab-card__meta">
                            {count} ブロック
                            {!editable ? ' · 固定' : null}
                            {featureOff ? ' · OFF' : null}
                            {!item.enabled && editable ? ' · 非表示' : null}
                        </span>
                    </span>
                    {isActive ? <span className="ld-tab-card__active-dot" aria-hidden /> : null}
                </button>
                {editable ? (
                    <div className="ld-tab-card__menu-wrap">
                        <button
                            type="button"
                            ref={(el) => {
                                moreBtnRefs.current[tab] = el;
                            }}
                            className={`ld-tab-card__more${isMenuOpen ? ' is-open' : ''}`}
                            title="メニュー"
                            aria-label={`${item.label}のメニュー`}
                            aria-haspopup="menu"
                            aria-expanded={isMenuOpen}
                            onClick={(e) => {
                                e.stopPropagation();
                                setMenuOpenTab((prev) => (prev === tab ? null : tab));
                            }}
                        >
                            <i className="fas fa-ellipsis-v" />
                        </button>
                    </div>
                ) : null}
            </div>
        );
    };

    return (
        <aside className={`ld-screens ld-screens--light${state.screensOpen ? ' is-open' : ''}`}>
            <div className="ld-screens__head">
                <div className="ld-screens__head-row">
                    <div>
                        <div className="ld-screens__title">画面一覧</div>
                        <p className="ld-screens__hint">表示する画面と順番を管理</p>
                    </div>
                    <div className="ld-panel-head-actions">
                        <span className="ld-screens__count">{enabledCount} 表示中</span>
                        <button
                            type="button"
                            className="ld-icon-btn ld-icon-btn--sm ld-panel-close ld-screens__close-mobile"
                            onClick={() => dispatch({ type: 'SET_UI', patch: { screensOpen: false } })}
                            title="閉じる"
                            aria-label="画面一覧を閉じる"
                        >
                            <i className="fas fa-times" />
                        </button>
                    </div>
                </div>
            </div>
            <div className="ld-screens__list">
                {state.menuItems.map((item) => renderTab(item.tab, item, true))}
                {menuByTab.announcements === undefined ? renderTab('announcements', {
                    label: TAB_LABELS.announcements,
                    icon: TAB_ICONS.announcements,
                    enabled: true,
                }, false) : null}
            </div>
            <div className="ld-screens__footer">
                <button type="button" className="ld-btn ld-btn--soft ld-btn--block" onClick={() => dispatch({ type: 'SET_UI', patch: { templateOpen: true } })}>
                    <i className="fas fa-th-large" />
                    テンプレートギャラリー
                </button>
            </div>
            {menuOpenTab !== null && openItem !== null && menuPos !== null
                ? createPortal(
                    <div
                        ref={menuRef}
                        className="ld-tab-card__menu"
                        role="menu"
                        style={{ position: 'fixed', top: menuPos.top, right: menuPos.right, left: 'auto' }}
                    >
                        <button
                            type="button"
                            role="menuitem"
                            onClick={(e) => {
                                e.stopPropagation();
                                startRename(menuOpenTab);
                            }}
                        >
                            <i className="fas fa-pen" />
                            名前を変更
                        </button>
                        <button
                            type="button"
                            role="menuitem"
                            disabled={!canMoveUp}
                            onClick={(e) => {
                                e.stopPropagation();
                                dispatch({ type: 'MOVE_MENU_ITEM', tab: menuOpenTab, delta: -1 });
                                closeMenu();
                            }}
                        >
                            <i className="fas fa-chevron-up" />
                            上へ移動
                        </button>
                        <button
                            type="button"
                            role="menuitem"
                            disabled={!canMoveDown}
                            onClick={(e) => {
                                e.stopPropagation();
                                dispatch({ type: 'MOVE_MENU_ITEM', tab: menuOpenTab, delta: 1 });
                                closeMenu();
                            }}
                        >
                            <i className="fas fa-chevron-down" />
                            下へ移動
                        </button>
                        <button
                            type="button"
                            role="menuitem"
                            onClick={(e) => {
                                e.stopPropagation();
                                dispatch({ type: 'UPDATE_MENU_ITEM', tab: menuOpenTab, patch: { enabled: !openItem.enabled } });
                                closeMenu();
                            }}
                        >
                            <i className={`fas ${openItem.enabled ? 'fa-eye-slash' : 'fa-eye'}`} />
                            {openItem.enabled ? 'タブを非表示' : 'タブを表示'}
                        </button>
                    </div>,
                    document.body,
                )
                : null}
        </aside>
    );
}

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { canRedo, canUndo } from '../state/designerReducer';
import { useDesigner } from '../state/DesignerContext';
import { saveDesigner } from '../persistence/saveDesigner';
import { exportDesignerJson } from '../persistence/serialize';
import { normalizeBlock, normalizeMenuItems, TAB_LABELS, DETAIL_PAGE_LABELS, defaultGlobalSettings, defaultTabSettings, createEmptyLayouts } from '../catalog/blockCatalog';
import type { DesignerSnapshot } from '../catalog/types';
import { PANEL_PRESETS, applyPanelPreset, detectActivePreset, type PanelPresetId } from '../utils/panelLayout';

export function Topbar() {
    const { state, dispatch, config } = useDesigner();
    const importRef = useRef<HTMLInputElement>(null);
    const moreRef = useRef<HTMLDivElement>(null);
    const [panelPreset, setPanelPreset] = useState<PanelPresetId>('standard');
    const isAdvanced = state.editorMode === 'advanced';
    const activeLabel = state.menuItems.find((m) => m.tab === state.activeTab)?.label
        || DETAIL_PAGE_LABELS[state.activeTab]
        || TAB_LABELS[state.activeTab]
        || state.activeTab;

    useEffect(() => {
        const root = document.querySelector('.ld.ld-react') as HTMLElement | null;
        if (root !== null) {
            setPanelPreset(detectActivePreset(root));
        }
    }, []);

    useEffect(() => {
        if (!state.moreOpen) return undefined;
        const onPointerDown = (e: MouseEvent) => {
            if (moreRef.current !== null && !moreRef.current.contains(e.target as Node)) {
                dispatch({ type: 'SET_UI', patch: { moreOpen: false } });
            }
        };
        document.addEventListener('mousedown', onPointerDown);
        return () => document.removeEventListener('mousedown', onPointerDown);
    }, [state.moreOpen, dispatch]);

    const save = useCallback(async () => {
        const formAction = config.formAction;
        const csrfToken = config.csrfToken || (document.querySelector('input[name="_token"]') as HTMLInputElement | null)?.value;
        if (formAction === undefined || formAction === '' || csrfToken === undefined || csrfToken === '') {
            dispatch({ type: 'SHOW_TOAST', message: '保存設定が見つかりません', isError: true });
            return;
        }
        dispatch({ type: 'SET_SAVING', saving: true });
        try {
            const message = await saveDesigner(state, formAction, csrfToken);
            dispatch({ type: 'MARK_SAVED' });
            dispatch({ type: 'SHOW_TOAST', message });
        } catch (err) {
            dispatch({ type: 'SET_SAVING', saving: false });
            const message = err instanceof Error ? err.message : '保存に失敗しました';
            dispatch({ type: 'SHOW_TOAST', message, isError: true });
        }
    }, [state, config, dispatch]);

    const onImport = (file: File | null) => {
        if (file === null) return;
        const reader = new FileReader();
        reader.onload = () => {
            try {
                const data = JSON.parse(String(reader.result || '{}')) as {
                    layouts?: Record<string, unknown[]>;
                    tab_settings?: Record<string, unknown>;
                    menu_items?: unknown[];
                    custom_css?: string;
                };
                if (data.layouts === undefined || typeof data.layouts !== 'object') {
                    throw new Error('layouts がありません');
                }
                const idCounter = { n: Date.now() % 100000 };
                const layouts = createEmptyLayouts();
                Object.keys(TAB_LABELS).forEach((key) => {
                    const rows = Array.isArray(data.layouts?.[key]) ? data.layouts![key] : [];
                    layouts[key] = rows.map((item) => normalizeBlock((item || {}) as Record<string, unknown>, idCounter));
                });
                const tabSettings: Record<string, Record<string, unknown>> = {};
                Object.keys(TAB_LABELS).forEach((key) => {
                    tabSettings[key] = { ...defaultTabSettings(), ...((data.tab_settings?.[key] as Record<string, unknown>) || {}) };
                });
                const globalSettings = {
                    ...defaultGlobalSettings(),
                    ...((data.tab_settings?._global as Record<string, unknown>) || {}),
                };
                const snapshot: DesignerSnapshot = {
                    layouts,
                    tabSettings,
                    globalSettings,
                    menuItems: normalizeMenuItems(data.menu_items),
                    customCss: typeof data.custom_css === 'string' ? data.custom_css : state.customCss,
                };
                dispatch({ type: 'IMPORT_SNAPSHOT', snapshot });
                dispatch({ type: 'SHOW_TOAST', message: 'JSONを読み込みました' });
            } catch (err) {
                const message = err instanceof Error ? err.message : '不明なエラー';
                dispatch({ type: 'SHOW_TOAST', message: `読み込み失敗: ${message}`, isError: true });
            }
        };
        reader.readAsText(file);
    };

    const onPanelPreset = (presetId: PanelPresetId) => {
        const root = document.querySelector('.ld.ld-react') as HTMLElement | null;
        if (root === null) return;
        applyPanelPreset(root, presetId);
        setPanelPreset(presetId);
    };

    return (
        <header className="ld-topbar">
            <div className="ld-topbar__left">
                <a href={config.backUrl || '#'} className="ld-topbar__back" title="設定に戻る">
                    <i className="fas fa-arrow-left" />
                </a>
                <div className="ld-topbar__brand">
                    <span className="ld-topbar__mark" aria-hidden>
                        <i className="fas fa-layer-group" />
                    </span>
                    <div className="ld-topbar__brand-text">
                        <strong>LIFF Studio</strong>
                        <span>顧客アプリデザイナー</span>
                    </div>
                </div>
                <div className="ld-topbar__context">
                    <span className="ld-topbar__context-label">編集中</span>
                    <strong>{activeLabel}</strong>
                    {state.dirty ? <span className="ld-dirty-badge">未保存</span> : <span className="ld-saved-badge">保存済み</span>}
                </div>
            </div>

            <div className="ld-topbar__center">
                <div className="ld-topbar__mode">
                    <button
                        type="button"
                        className={!isAdvanced ? 'is-active' : ''}
                        onClick={() => dispatch({ type: 'SET_EDITOR_MODE', mode: 'simple' })}
                    >
                        かんたん
                    </button>
                    <button
                        type="button"
                        className={isAdvanced ? 'is-active' : ''}
                        onClick={() => dispatch({ type: 'SET_EDITOR_MODE', mode: 'advanced' })}
                    >
                        詳細編集
                    </button>
                </div>
            </div>

            <div className="ld-topbar__actions">
                <div className="ld-topbar__group ld-topbar__group--panels">
                    <button
                        type="button"
                        className={`ld-icon-btn ld-icon-btn--screens-mobile${state.screensOpen ? ' is-active' : ''}`}
                        onClick={() => dispatch({ type: 'SET_UI', patch: { screensOpen: !state.screensOpen } })}
                        title={state.screensOpen ? '画面一覧を閉じる' : '画面一覧を開く'}
                        aria-pressed={state.screensOpen}
                    >
                        <i className="fas fa-th-list" />
                    </button>
                    <button
                        type="button"
                        className={`ld-icon-btn${state.sidebarOpen ? ' is-active' : ''}`}
                        onClick={() => dispatch({ type: 'SET_UI', patch: { sidebarOpen: !state.sidebarOpen } })}
                        title={state.sidebarOpen ? '設定パネルを閉じる' : '設定パネルを開く'}
                        aria-pressed={state.sidebarOpen}
                    >
                        <i className="fas fa-sliders-h" />
                    </button>
                </div>

                {isAdvanced ? (
                    <div className="ld-topbar__group ld-topbar__layout ld-topbar__layout--desktop">
                        {(Object.keys(PANEL_PRESETS) as PanelPresetId[]).map((id) => (
                            <button
                                key={id}
                                type="button"
                                className={`ld-chip-btn${panelPreset === id ? ' is-active' : ''}`}
                                onClick={() => onPanelPreset(id)}
                                title={`パネル幅: ${PANEL_PRESETS[id].label}`}
                            >
                                {PANEL_PRESETS[id].label}
                            </button>
                        ))}
                    </div>
                ) : null}

                <button type="button" className="ld-btn ld-btn--soft" onClick={() => dispatch({ type: 'SET_UI', patch: { templateOpen: true } })}>
                    <i className="fas fa-th-large" />
                    テンプレート
                </button>
                <button type="button" className="ld-btn ld-btn--soft" onClick={() => dispatch({ type: 'SET_UI', patch: { previewOpen: true } })}>
                    <i className="fas fa-eye" />
                    プレビュー
                </button>

                {isAdvanced ? (
                    <div className="ld-topbar__group">
                        <button type="button" className="ld-icon-btn" disabled={!canUndo(state)} onClick={() => dispatch({ type: 'UNDO' })} title="元に戻す (Ctrl+Z)">
                            <i className="fas fa-undo" />
                        </button>
                        <button type="button" className="ld-icon-btn" disabled={!canRedo(state)} onClick={() => dispatch({ type: 'REDO' })} title="やり直す (Ctrl+Shift+Z)">
                            <i className="fas fa-redo" />
                        </button>
                        <div className="ld-more" ref={moreRef}>
                            <button type="button" className="ld-icon-btn" onClick={() => dispatch({ type: 'SET_UI', patch: { moreOpen: !state.moreOpen } })} title="その他">
                                <i className="fas fa-ellipsis-h" />
                            </button>
                            {state.moreOpen ? (
                                <div className="ld-more__menu">
                                    <button type="button" onClick={() => { exportDesignerJson(state); dispatch({ type: 'SET_UI', patch: { moreOpen: false } }); }}>
                                        <i className="fas fa-download" /> JSON書き出し
                                    </button>
                                    <button type="button" onClick={() => { importRef.current?.click(); dispatch({ type: 'SET_UI', patch: { moreOpen: false } }); }}>
                                        <i className="fas fa-upload" /> JSON読み込み
                                    </button>
                                    <button type="button" onClick={() => { dispatch({ type: 'SET_UI', patch: { themeOpen: true, moreOpen: false } }); }}>
                                        <i className="fas fa-swatchbook" /> テーマを変更
                                    </button>
                                </div>
                            ) : null}
                        </div>
                    </div>
                ) : null}

                <button type="button" className="ld-icon-btn" onClick={() => dispatch({ type: 'SET_UI', patch: { helpOpen: true } })} title="使い方">
                    <i className="fas fa-question" />
                </button>

                <button type="button" className="ld-btn ld-btn--primary ld-topbar__save" disabled={state.saving} onClick={() => { void save(); }} title="保存 (Ctrl+S)">
                    <i className={`fas ${state.saving ? 'fa-spinner fa-spin' : 'fa-check'}`} />
                    {state.saving ? '保存中…' : '保存'}
                </button>
            </div>
            <input ref={importRef} type="file" accept="application/json" hidden onChange={(e) => onImport(e.target.files?.[0] || null)} />
        </header>
    );
}

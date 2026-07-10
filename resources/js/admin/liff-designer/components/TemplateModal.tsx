import React, { useMemo, useState } from 'react';
import { normalizeBlock } from '../catalog/blockCatalog';
import { useDesigner } from '../state/DesignerContext';
import type { DesignerState, LiffBlock } from '../catalog/types';

type TemplateItem = {
    id?: string;
    name?: string;
    label?: string;
    description?: string;
    category?: string;
    scope?: 'tab' | 'all' | string;
    tab?: string;
    blocks?: unknown[];
    layouts?: Record<string, unknown[]>;
    theme?: Record<string, unknown>;
    tab_settings?: Record<string, unknown>;
};

type TemplateImpact = {
    isAllScreens: boolean;
    targetLabel: string;
    screenLabels: string[];
    currentBlockCount: number;
    newBlockCount: number;
    appliesTheme: boolean;
    appliesTabSettings: boolean;
};

function countBlocksInLayouts(layouts: Record<string, LiffBlock[]>): number {
    return Object.values(layouts).reduce((sum, rows) => sum + rows.length, 0);
}

function describeTemplateImpact(tpl: TemplateItem, state: DesignerState): TemplateImpact {
    const menuByTab = Object.fromEntries(state.menuItems.map((m) => [m.tab, m]));
    const isAllScreens = tpl.scope === 'all' || (tpl.layouts !== undefined && typeof tpl.layouts === 'object');

    if (isAllScreens && tpl.layouts !== undefined && typeof tpl.layouts === 'object') {
        const tabs = Object.keys(tpl.layouts);
        const newBlockCount = tabs.reduce((sum, tab) => {
            const rows = Array.isArray(tpl.layouts?.[tab]) ? tpl.layouts[tab] : [];
            return sum + rows.length;
        }, 0);
        return {
            isAllScreens: true,
            targetLabel: '全画面',
            screenLabels: tabs.map((tab) => menuByTab[tab]?.label || tab),
            currentBlockCount: countBlocksInLayouts(state.layouts),
            newBlockCount,
            appliesTheme: tpl.theme !== undefined && typeof tpl.theme === 'object',
            appliesTabSettings: tpl.tab_settings !== undefined && typeof tpl.tab_settings === 'object',
        };
    }

    const tab = state.activeTab;
    const rows = Array.isArray(tpl.blocks) ? tpl.blocks : [];
    return {
        isAllScreens: false,
        targetLabel: menuByTab[tab]?.label || tab,
        screenLabels: [menuByTab[tab]?.label || tab],
        currentBlockCount: (state.layouts[tab] || []).length,
        newBlockCount: rows.length,
        appliesTheme: false,
        appliesTabSettings: false,
    };
}

export function TemplateModal() {
    const { state, dispatch, config } = useDesigner();
    const [pendingTemplate, setPendingTemplate] = useState<TemplateItem | null>(null);

    const templates = (Array.isArray(config.templates) ? config.templates : []) as TemplateItem[];
    const pendingImpact = useMemo(
        () => (pendingTemplate !== null ? describeTemplateImpact(pendingTemplate, state) : null),
        [pendingTemplate, state],
    );

    if (!state.templateOpen) return null;

    const closeModal = () => {
        setPendingTemplate(null);
        dispatch({ type: 'SET_UI', patch: { templateOpen: false } });
    };

    const applyTemplate = (tpl: TemplateItem) => {
        const idCounter = { n: Date.now() % 100000 };
        if (tpl.layouts !== undefined && typeof tpl.layouts === 'object') {
            const layouts: Record<string, LiffBlock[]> = { ...state.layouts };
            Object.keys(tpl.layouts).forEach((tab) => {
                const rows = Array.isArray(tpl.layouts?.[tab]) ? tpl.layouts![tab] : [];
                layouts[tab] = rows.map((item) => normalizeBlock((item || {}) as Record<string, unknown>, idCounter));
            });
            dispatch({
                type: 'APPLY_LAYOUTS',
                layouts,
                globalSettings: tpl.theme ? { ...state.globalSettings, ...tpl.theme } : undefined,
                tabSettings: tpl.tab_settings
                    ? { ...state.tabSettings, ...(tpl.tab_settings as Record<string, Record<string, unknown>>) }
                    : undefined,
            });
            dispatch({ type: 'SHOW_TOAST', message: `テンプレート「${tpl.label || tpl.name || '未命名'}」を全画面に適用しました` });
        } else {
            const rows = Array.isArray(tpl.blocks) ? tpl.blocks : [];
            const blocks = rows.map((item) => normalizeBlock((item || {}) as Record<string, unknown>, idCounter));
            dispatch({
                type: 'APPLY_LAYOUTS',
                layouts: { ...state.layouts, [state.activeTab]: blocks },
            });
            dispatch({ type: 'SHOW_TOAST', message: `テンプレートを「${state.activeTab}」に適用しました` });
        }
        setPendingTemplate(null);
        dispatch({ type: 'SET_UI', patch: { templateOpen: false } });
    };

    const confirmApply = () => {
        if (pendingTemplate !== null) {
            applyTemplate(pendingTemplate);
        }
    };

    return (
        <div className="ld-modal-backdrop" onClick={closeModal}>
            <div className="ld-modal ld-modal--wide" onClick={(e) => e.stopPropagation()}>
                <div className="ld-modal__head">
                    <h2>{pendingTemplate !== null ? 'テンプレートの適用確認' : 'テンプレート'}</h2>
                    <button type="button" className="ld-btn ld-btn--ghost" onClick={closeModal}>
                        <i className="fas fa-times" />
                    </button>
                </div>
                <div className="ld-modal__body">
                    {pendingTemplate !== null && pendingImpact !== null ? (
                        <div className="ld-template-confirm">
                            <p className="ld-template-confirm__lead">
                                <strong>{pendingTemplate.label || pendingTemplate.name || 'テンプレート'}</strong>
                                を適用すると、現在の内容が上書きされます。
                            </p>
                            <dl className="ld-template-confirm__list">
                                <div>
                                    <dt>適用先</dt>
                                    <dd>{pendingImpact.isAllScreens ? '全画面' : `「${pendingImpact.targetLabel}」のみ`}</dd>
                                </div>
                                <div>
                                    <dt>ブロック数</dt>
                                    <dd>
                                        {pendingImpact.currentBlockCount} 個 → {pendingImpact.newBlockCount} 個に置き換え
                                    </dd>
                                </div>
                                {pendingImpact.isAllScreens ? (
                                    <div>
                                        <dt>対象画面</dt>
                                        <dd>{pendingImpact.screenLabels.join(' / ')}</dd>
                                    </div>
                                ) : null}
                                {pendingImpact.appliesTheme ? (
                                    <div>
                                        <dt>テーマ</dt>
                                        <dd>テンプレートのテーマ設定も反映されます</dd>
                                    </div>
                                ) : null}
                                {pendingImpact.appliesTabSettings ? (
                                    <div>
                                        <dt>画面設定</dt>
                                        <dd>テンプレートの画面設定も反映されます</dd>
                                    </div>
                                ) : null}
                            </dl>
                            <p className="ld-form-hint">この操作は元に戻す（Ctrl+Z）で取り消せます。</p>
                            <div className="ld-template-confirm__actions">
                                <button type="button" className="ld-btn ld-btn--ghost" onClick={() => setPendingTemplate(null)}>
                                    戻る
                                </button>
                                <button type="button" className="ld-btn ld-btn--primary" onClick={confirmApply}>
                                    適用する
                                </button>
                            </div>
                        </div>
                    ) : templates.length === 0 ? (
                        <p className="ld-form-hint">利用可能なテンプレートがありません。</p>
                    ) : (
                        <div className="ld-template-grid">
                            {templates.map((tpl, i) => (
                                <button
                                    key={tpl.id || String(i)}
                                    type="button"
                                    className="ld-template-card"
                                    onClick={() => setPendingTemplate(tpl)}
                                >
                                    <strong>{tpl.label || tpl.name || `テンプレート ${i + 1}`}</strong>
                                    {tpl.description ? <span>{tpl.description}</span> : null}
                                    <em>{tpl.scope === 'all' || tpl.layouts ? '全画面' : '現在のタブ'}</em>
                                </button>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}

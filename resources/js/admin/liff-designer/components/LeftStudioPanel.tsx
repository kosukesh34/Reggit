import React from 'react';
import type { LeftStudioTab } from '../catalog/types';
import { useDesigner } from '../state/DesignerContext';
import { BlockPalette } from './BlockPalette';
import { ScreenList } from './ScreenList';
import { TemplateGallery } from './TemplateGallery';

const LEFT_TABS: Array<{ id: LeftStudioTab; label: string; icon: string }> = [
    { id: 'screens', label: '画面', icon: 'fa-mobile-alt' },
    { id: 'templates', label: 'テンプレ', icon: 'fa-th-large' },
    { id: 'blocks', label: 'ブロック', icon: 'fa-cube' },
];

export function LeftStudioPanel() {
    const { state, dispatch } = useDesigner();
    const tab = state.leftStudioTab;
    const isAdvanced = state.editorMode === 'advanced';

    return (
        <aside className={`ld-screens ld-studio-left${state.screensOpen ? ' is-open' : ''}`}>
            <div className="ld-studio-tabs" role="tablist" aria-label="左パネル">
                {LEFT_TABS.map((item) => {
                    if (item.id === 'blocks' && !isAdvanced) return null;
                    return (
                        <button
                            key={item.id}
                            type="button"
                            role="tab"
                            aria-selected={tab === item.id}
                            className={`ld-studio-tab${tab === item.id ? ' is-active' : ''}`}
                            onClick={() => dispatch({ type: 'SET_LEFT_STUDIO_TAB', tab: item.id })}
                        >
                            <i className={`fas ${item.icon}`} />
                            <span>{item.label}</span>
                        </button>
                    );
                })}
                <button
                    type="button"
                    className="ld-icon-btn ld-icon-btn--sm ld-panel-close ld-screens__close-mobile"
                    onClick={() => dispatch({ type: 'SET_UI', patch: { screensOpen: false } })}
                    title="閉じる"
                    aria-label="左パネルを閉じる"
                >
                    <i className="fas fa-times" />
                </button>
            </div>
            <div className="ld-studio-left__body">
                {tab === 'screens' ? <ScreenList embedded /> : null}
                {tab === 'templates' ? <TemplateGallery compact /> : null}
                {tab === 'blocks' && isAdvanced ? <BlockPalette /> : null}
            </div>
        </aside>
    );
}

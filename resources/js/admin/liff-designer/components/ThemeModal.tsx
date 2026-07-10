import React from 'react';
import { ThemePanel } from './ThemePanel';
import { useDesigner } from '../state/DesignerContext';

export function ThemeModal() {
    const { state, dispatch } = useDesigner();
    if (!state.themeOpen) return null;

    return (
        <div className="ld-modal-backdrop" onClick={() => dispatch({ type: 'SET_UI', patch: { themeOpen: false } })}>
            <div className="ld-modal ld-modal--wide ld-modal--theme" onClick={(e) => e.stopPropagation()}>
                <div className="ld-modal__head">
                    <h2><i className="fas fa-swatchbook" /> テーマを変更</h2>
                    <button type="button" className="ld-btn ld-btn--ghost" onClick={() => dispatch({ type: 'SET_UI', patch: { themeOpen: false } })}>
                        <i className="fas fa-times" />
                    </button>
                </div>
                <div className="ld-modal__body">
                    <ThemePanel />
                </div>
            </div>
        </div>
    );
}

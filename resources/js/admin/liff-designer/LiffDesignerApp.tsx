import React, { useEffect } from 'react';
import type { DesignerConfig } from './catalog/types';
import { DesignerProvider, useDesigner } from './state/DesignerContext';
import { Topbar } from './components/Topbar';
import { ScreenList } from './components/ScreenList';
import { PhoneCanvas } from './components/PhoneCanvas';
import { RightPanel } from './components/RightPanel';
import { TemplateModal } from './components/TemplateModal';
import { ThemeModal } from './components/ThemeModal';
import { PreviewOverlay } from './components/PreviewOverlay';
import { HelpModal } from './components/HelpModal';
import { OnboardingOverlay } from './components/OnboardingOverlay';
import { saveDesigner } from './persistence/saveDesigner';
import { applyPanelWidths, loadSavedPanelWidths } from './utils/panelLayout';
import { ensureLiffDesignerModules } from './ensureModules';
import './modules/panelResizer';

function Toast() {
    const { state, dispatch } = useDesigner();
    useEffect(() => {
        if (state.toast === null) return undefined;
        const timer = window.setTimeout(() => dispatch({ type: 'CLEAR_TOAST' }), 3000);
        return () => window.clearTimeout(timer);
    }, [state.toast, dispatch]);
    if (state.toast === null) return null;
    return (
        <div className={`ld-toast ld-toast--visible${state.toast.isError ? ' ld-toast--error' : ' ld-toast--success'}`}>
            <i className={`fas ${state.toast.isError ? 'fa-exclamation-circle' : 'fa-check-circle'}`} />
            {state.toast.message}
        </div>
    );
}

function LegacyNotes() {
    const { state, dispatch } = useDesigner();
    useEffect(() => {
        if (state.legacyNotes.length === 0) return;
        dispatch({ type: 'SHOW_TOAST', message: state.legacyNotes[0] });
        dispatch({ type: 'CLEAR_LEGACY_NOTES' });
    }, [state.legacyNotes, dispatch]);
    return null;
}

function DirtyGuard() {
    const { state } = useDesigner();
    useEffect(() => {
        const onBeforeUnload = (e: BeforeUnloadEvent) => {
            if (!state.dirty) return;
            e.preventDefault();
            e.returnValue = '';
        };
        window.addEventListener('beforeunload', onBeforeUnload);
        return () => window.removeEventListener('beforeunload', onBeforeUnload);
    }, [state.dirty]);
    return null;
}

function PanelBootstrap() {
    useEffect(() => {
        const root = document.querySelector('.ld.ld-react') as HTMLElement | null;
        if (root !== null) {
            applyPanelWidths(root, loadSavedPanelWidths());
        }
        const resizer = ensureLiffDesignerModules().panelResizer;
        if (resizer !== undefined && typeof resizer.bindPanelResizer === 'function') {
            resizer.bindPanelResizer.call(resizer);
        }
    }, []);
    return null;
}

function KeyboardShortcuts() {
    const { state, dispatch, config } = useDesigner();
    useEffect(() => {
        const onKey = (e: KeyboardEvent) => {
            const meta = e.metaKey || e.ctrlKey;
            if (meta && e.key.toLowerCase() === 'z') {
                if (state.editorMode !== 'advanced') return;
                e.preventDefault();
                dispatch({ type: e.shiftKey ? 'REDO' : 'UNDO' });
            }
            if (meta && e.key.toLowerCase() === 's') {
                e.preventDefault();
                const formAction = config.formAction;
                const csrfToken = config.csrfToken || (document.querySelector('input[name="_token"]') as HTMLInputElement | null)?.value;
                if (formAction === undefined || formAction === '' || csrfToken === undefined || csrfToken === '') return;
                dispatch({ type: 'SET_SAVING', saving: true });
                saveDesigner(state, formAction, csrfToken)
                    .then((message) => {
                        dispatch({ type: 'MARK_SAVED' });
                        dispatch({ type: 'SHOW_TOAST', message });
                    })
                    .catch((err: unknown) => {
                        dispatch({ type: 'SET_SAVING', saving: false });
                        const message = err instanceof Error ? err.message : '保存に失敗しました';
                        dispatch({ type: 'SHOW_TOAST', message, isError: true });
                    });
            }
            if (e.key === 'Escape') {
                dispatch({
                    type: 'SET_UI',
                    patch: {
                        moreOpen: false,
                        templateOpen: false,
                        themeOpen: false,
                        previewOpen: false,
                        helpOpen: false,
                        screensOpen: false,
                        sidebarOpen: false,
                    },
                });
                dispatch({ type: 'CLOSE_QUICK_EDIT' });
                if (state.editorMode === 'advanced' && state.selectedId !== null) {
                    dispatch({ type: 'SELECT_BLOCK', id: null, openEdit: false });
                }
            }
            if (state.editorMode === 'advanced' && (e.key === 'Delete' || e.key === 'Backspace') && state.selectedId !== null) {
                const tag = (e.target as HTMLElement)?.tagName;
                if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') return;
                e.preventDefault();
                dispatch({ type: 'DELETE_BLOCK', id: state.selectedId });
            }
        };
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    }, [state, dispatch, config]);
    return null;
}

function DesignerShell() {
    const { state, dispatch } = useDesigner();
    const backdropOpen = state.screensOpen || state.sidebarOpen;

    return (
        <div className={`ld ld-react${state.editorMode === 'simple' ? ' ld--simple' : ' ld--advanced'}`}>
            <Topbar />
            <div className="ld-body">
                <ScreenList />
                <div className="ld-resizer ld-resizer--left" data-resizer="left" aria-hidden="true" />
                <PhoneCanvas />
                <div className="ld-resizer ld-resizer--right" data-resizer="right" aria-hidden="true" />
                <RightPanel />
            </div>
            {backdropOpen ? (
                <div
                    className="ld-drawer-backdrop"
                    onClick={() => dispatch({ type: 'SET_UI', patch: { screensOpen: false, sidebarOpen: false } })}
                />
            ) : null}
            <TemplateModal />
            <ThemeModal />
            <PreviewOverlay />
            <HelpModal open={state.helpOpen} onClose={() => dispatch({ type: 'SET_UI', patch: { helpOpen: false } })} />
            <OnboardingOverlay />
            <Toast />
            <LegacyNotes />
            <DirtyGuard />
            <PanelBootstrap />
            <KeyboardShortcuts />
            {state.customCss ? <style>{state.customCss}</style> : null}
        </div>
    );
}

export function LiffDesignerApp({ config }: { config: DesignerConfig }) {
    return (
        <DesignerProvider config={config}>
            <DesignerShell />
        </DesignerProvider>
    );
}

export function readDesignerConfigFromDom(): DesignerConfig {
    const el = document.getElementById('ldDesignerConfig');
    if (el?.textContent) {
        try {
            return JSON.parse(el.textContent) as DesignerConfig;
        } catch (error) {
            // eslint-disable-next-line no-console
            console.error('LIFF Designer config parse failed:', error);
        }
    }
    return {};
}

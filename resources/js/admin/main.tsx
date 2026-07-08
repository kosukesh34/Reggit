import React from 'react';
import '../../css/app.css';
import { createRoot } from 'react-dom/client';
import { AdminSidebar } from './components/AdminSidebar';
import { loadLegacyAdminScripts } from './legacy/loadLegacyAdminScripts';
import { useEffect } from 'react';

type LdDesignerApi = {
    bindPanelDrawers?: () => void;
    bindPanelResizer?: () => void;
    shouldShowOnboarding?: () => boolean;
    showOnboarding?: () => void;
    showHelpOverlay?: () => void;
};

function showLdToast(message: string, isError = false, durationMs = 3000): void {
    const toast = document.getElementById('ldToast');
    if (toast === null) {
        return;
    }

    toast.innerHTML = `<i class="fas ${isError ? 'fa-exclamation-circle' : 'fa-check-circle'}"></i> ${message}`;
    toast.className = `ld-toast${isError ? ' ld-toast--error' : ' ld-toast--success'}`;
    requestAnimationFrame(() => {
        toast.classList.add('ld-toast--visible');
    });
    window.clearTimeout(window.__ldToastTimer);
    window.__ldToastTimer = window.setTimeout(() => {
        toast.classList.remove('ld-toast--visible');
    }, durationMs);
}

function initializeLiffDesigner(): void {
    if (typeof window.LiffDesigner === 'undefined') {
        showLdToast('デザイナーの読み込みに失敗しました。ページを再読み込みしてください。', true);
        return;
    }

    const configEl = document.getElementById('ldDesignerConfig');
    let config: Record<string, unknown> = {};
    if (configEl?.textContent) {
        try {
            config = JSON.parse(configEl.textContent);
        } catch (error) {
            // eslint-disable-next-line no-console
            console.error('LIFF Designer config parse failed:', error);
            showLdToast('デザイナー設定の読み込みに失敗しました。ページを再読み込みしてください。', true);
            return;
        }
    }

    let designer: LdDesignerApi;
    try {
        designer = new window.LiffDesigner(config) as LdDesignerApi;
        window.__ldDesigner = designer;
    } catch (error) {
        // eslint-disable-next-line no-console
        console.error('LIFF Designer init failed:', error);
        showLdToast('デザイナーの読み込みに失敗しました。ページを再読み込みしてください。', true);
        return;
    }

    try {
        designer.bindPanelDrawers?.();
        designer.bindPanelResizer?.();
    } catch (error) {
        // eslint-disable-next-line no-console
        console.warn('LIFF Designer panel init failed:', error);
    }

    if (designer.shouldShowOnboarding?.() === true) {
        window.setTimeout(() => {
            designer.showOnboarding?.();
        }, 300);
    }

    const helpBtn = document.getElementById('helpBtn');
    helpBtn?.addEventListener('click', () => {
        designer.showHelpOverlay?.();
    });

    const settingsToggle = document.getElementById('liffSettingsToggle');
    settingsToggle?.addEventListener('click', (event) => {
        const target = event.target as HTMLElement | null;
        if (target?.closest('input, textarea, select')) {
            return;
        }
        settingsToggle.classList.toggle('is-open');
    });

    const form = document.getElementById('liffDesignerForm');
    form?.addEventListener('submit', () => {
        window.setTimeout(() => {
            showLdToast('デザインを保存しました', false);
        }, 100);
    });

    window.__ldShowToast = showLdToast;
}

function AdminBootstrap(): null {
    useEffect(() => {
        const bootstrap = async (): Promise<void> => {
            try {
                await loadLegacyAdminScripts();
                if (document.body.dataset.pageKind === 'liff-designer') {
                    initializeLiffDesigner();
                }
            } catch (error) {
                // eslint-disable-next-line no-console
                console.error('[admin-bootstrap] failed:', error);
            }
        };
        void bootstrap();
    }, []);

    return null;
}

const mount = document.getElementById('react-admin-root');
if (mount !== null) {
    createRoot(mount).render(
        <>
            <AdminBootstrap />
            <AdminSidebar />
        </>,
    );
}

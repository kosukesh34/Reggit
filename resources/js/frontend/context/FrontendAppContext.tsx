import React, { useEffect, useSyncExternalStore } from 'react';
import { frontendStore } from './frontendStore';

export function useFrontendApp() {
    const currentTab = useSyncExternalStore(
        frontendStore.subscribe,
        frontendStore.getCurrentTab,
        frontendStore.getCurrentTab,
    );

    return {
        apiClient: frontendStore.apiClient,
        pageRouter: frontendStore.pageRouter,
        currentTab,
        switchTab: frontendStore.switchTab,
    };
}

/** Provider は不要（複数 React root 間で frontendStore を共有） */
export function FrontendAppProvider({ children }: { children: React.ReactNode }): JSX.Element {
    return <>{children}</>;
}

export function useLiffHashSync(): void {
    const { switchTab, pageRouter } = useFrontendApp();
    useEffect(() => {
        const onHash = () => {
            const hash = (window.location.hash || '').replace(/^#/, '');
            if (hash === '' || hash.includes('/')) {
                return;
            }
            switchTab(hash.split('?')[0] ?? 'home');
        };
        window.addEventListener('hashchange', onHash);
        const onBack = (e: Event) => {
            const target = e.target as HTMLElement | null;
            if (target?.closest?.('[data-liff-page-back]')) {
                e.preventDefault();
                pageRouter.closePage(true);
            }
        };
        document.addEventListener('click', onBack);
        return () => {
            window.removeEventListener('hashchange', onHash);
            document.removeEventListener('click', onBack);
        };
    }, [switchTab, pageRouter]);
}

import { useCallback, useEffect, useState } from 'react';
import { primaryForTab } from '../demo/navigation';
import { isDeepLiffHash } from '../navigation/LiffPageRouter';

function tabFromHash(): string {
    const hash = (window.location.hash || '').replace(/^#/, '');
    if (hash === '' || hash.includes('/')) {
        return 'home';
    }
    return hash.split('?')[0] ?? 'home';
}

function readActiveTab(): string {
    if (typeof window.app?.currentTab === 'string' && window.app.currentTab !== '') {
        return window.app.currentTab;
    }
    return tabFromHash();
}

export type LiffRouter = {
    activeTab: string;
    switchTab: (tab: string) => void;
};

/**
 * タブ / hash の React 側の真実源。
 * 互換のため window.app.switchTab があれば委譲し、なければ hash を更新する。
 */
export function useLiffRouter(): LiffRouter {
    const [activeTab, setActiveTab] = useState(readActiveTab);

    useEffect(() => {
        const sync = (): void => {
            setActiveTab(readActiveTab());
        };
        window.addEventListener('hashchange', sync);
        window.addEventListener('ios-tab-changed', sync);
        return () => {
            window.removeEventListener('hashchange', sync);
            window.removeEventListener('ios-tab-changed', sync);
        };
    }, []);

    const switchTab = useCallback((tab: string) => {
        const app = window.app;
        if (app !== undefined && app !== null && typeof app.switchTab === 'function') {
            void app.switchTab(tab);
            setActiveTab(tab);
            return;
        }
        if (app !== undefined && app !== null) {
            app.currentTab = tab;
        }
        const primary = primaryForTab(tab);
        document.body.classList.toggle('liff-lottery-ui', tab === 'designlab');
        document.querySelectorAll('.tab-content').forEach((el) => {
            el.classList.toggle('active', el.id === `${tab}Tab`);
        });
        document.querySelectorAll('.main-tab-btn').forEach((btn) => {
            const el = btn as HTMLElement;
            el.classList.toggle('active', el.dataset.tab === tab);
        });
        if (!isDeepLiffHash(window.location.hash || '')) {
            const next = `#${tab}`;
            if (window.location.hash !== next) {
                window.history.replaceState(null, '', next);
            }
        }
        window.dispatchEvent(new CustomEvent('ios-tab-changed', { detail: { tab, primary } }));
        setActiveTab(tab);
    }, []);

    return { activeTab, switchTab };
}

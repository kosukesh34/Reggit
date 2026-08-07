import { ApiClient } from '../../shared/utils/apiClient';
import { AppConstants } from '../../shared/constants';
import { LiffPageRouter } from '../navigation/LiffPageRouter';

type Listener = () => void;

let currentTab = (() => {
    const hash = (typeof window !== 'undefined' ? window.location.hash : '').replace(/^#/, '');
    if (hash !== '' && !hash.includes('/')) {
        return hash.split('?')[0] ?? AppConstants.TABS.HOME;
    }
    return AppConstants.TABS.HOME;
})();

const listeners = new Set<Listener>();
const apiClient = new ApiClient();
const pageRouter = new LiffPageRouter();

function notify(): void {
    listeners.forEach((listener) => listener());
}

export const frontendStore = {
    apiClient,
    pageRouter,
    getCurrentTab(): string {
        return currentTab;
    },
    switchTab(tab: string): void {
        const next = tab !== '' ? tab : AppConstants.TABS.HOME;
        currentTab = next;
        if (typeof window !== 'undefined') {
            window.location.hash = next;
            document.querySelectorAll('.tab-content').forEach((el) => {
                el.classList.toggle('active', el.id === `${next}Tab`);
            });
            document.querySelectorAll('.main-tab-btn').forEach((el) => {
                const btn = el as HTMLElement;
                const isActive = btn.dataset.tab === next || btn.dataset.primaryTab === next;
                btn.classList.toggle('active', isActive);
            });
            window.dispatchEvent(new CustomEvent('ios-tab-changed', { detail: { tab: next } }));
        }
        notify();
    },
    subscribe(listener: Listener): () => void {
        listeners.add(listener);
        return () => {
            listeners.delete(listener);
        };
    },
};

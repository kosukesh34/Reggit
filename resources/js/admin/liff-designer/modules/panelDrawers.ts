import { ensureLiffDesignerModules } from '../ensureModules';

/**
 * 狭い画面で左右パネルをドロワー表示（3カラムレイアウトは維持）
 */
export const panelDrawers = {
    bindPanelDrawers(this: unknown): void {
        const root = document.querySelector('.ld');
        if (root === null) {
            return;
        }

        let backdrop = root.querySelector('.ld-drawer-backdrop') as HTMLElement | null;
        if (backdrop === null) {
            backdrop = document.createElement('div');
            backdrop.className = 'ld-drawer-backdrop';
            backdrop.hidden = true;
            root.appendChild(backdrop);
        }

        const close = (): void => {
            root.classList.remove('ld-screens-open', 'ld-sidebar-open');
            backdrop.hidden = true;
        };

        const open = (panel: 'screens' | 'blocks'): void => {
            const isOpen = panel === 'screens'
                ? root.classList.contains('ld-screens-open')
                : root.classList.contains('ld-sidebar-open');
            if (isOpen) {
                close();
                return;
            }
            root.classList.remove('ld-screens-open', 'ld-sidebar-open');
            root.classList.add(panel === 'screens' ? 'ld-screens-open' : 'ld-sidebar-open');
            backdrop.hidden = false;
        };

        const screensBtn = document.getElementById('openScreensDrawer');
        const blocksBtn = document.getElementById('openBlocksDrawer');
        if (screensBtn !== null) {
            screensBtn.addEventListener('click', () => open('screens'));
        }
        if (blocksBtn !== null) {
            blocksBtn.addEventListener('click', () => open('blocks'));
        }
        backdrop.addEventListener('click', close);
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                close();
            }
        });
    },
};

ensureLiffDesignerModules().panelDrawers = panelDrawers;

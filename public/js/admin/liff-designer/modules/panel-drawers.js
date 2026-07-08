window.LiffDesignerModules = window.LiffDesignerModules || {};

/**
 * 狭い画面で左右パネルをドロワー表示（3カラムレイアウトは維持）
 */
window.LiffDesignerModules.panelDrawers = {
    bindPanelDrawers() {
        const root = document.querySelector('.ld');
        if (!root) return;

        let backdrop = root.querySelector('.ld-drawer-backdrop');
        if (!backdrop) {
            backdrop = document.createElement('div');
            backdrop.className = 'ld-drawer-backdrop';
            backdrop.hidden = true;
            root.appendChild(backdrop);
        }

        const close = () => {
            root.classList.remove('ld-screens-open', 'ld-sidebar-open');
            backdrop.hidden = true;
        };

        const open = (panel) => {
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
        if (screensBtn) screensBtn.addEventListener('click', () => open('screens'));
        if (blocksBtn) blocksBtn.addEventListener('click', () => open('blocks'));
        backdrop.addEventListener('click', close);
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') close();
        });
    },
};

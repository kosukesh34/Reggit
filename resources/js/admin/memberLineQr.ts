import { QRCodeCompat } from '../shared/utils/qrCodeCompat';

function renderMemberLineQr(root: HTMLElement): void {
    const url = (root.dataset.url || '').trim();
    if (url === '') {
        return;
    }
    if (root.dataset.qrMounted === '1') {
        return;
    }
    root.innerHTML = '';
    // eslint-disable-next-line no-new
    new QRCodeCompat(root, {
        text: url,
        width: 180,
        height: 180,
    });
    root.dataset.qrMounted = '1';
}

export function mountMemberLineQr(): void {
    const toggle = document.getElementById('member-line-qr-toggle');
    const panel = document.getElementById('member-line-qr-panel');
    const root = document.getElementById('member-line-qr');
    if (toggle === null || panel === null || root === null) {
        return;
    }

    toggle.addEventListener('click', () => {
        const isOpen = !panel.hasAttribute('hidden');
        if (isOpen) {
            panel.setAttribute('hidden', '');
            toggle.setAttribute('aria-expanded', 'false');
            toggle.textContent = 'QRを表示';
            return;
        }
        panel.removeAttribute('hidden');
        toggle.setAttribute('aria-expanded', 'true');
        toggle.textContent = 'QRを隠す';
        renderMemberLineQr(root);
    });
}

mountMemberLineQr();

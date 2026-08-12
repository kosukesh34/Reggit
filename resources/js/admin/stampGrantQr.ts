import { QRCodeCompat } from '../shared/utils/qrCodeCompat';

export function mountStampGrantQr(): void {
    const root = document.querySelector<HTMLElement>('[data-stamp-grant-qr]');
    const input = document.getElementById('stampGrantUrl') as HTMLTextAreaElement | null;
    const copy = document.querySelector<HTMLButtonElement>('[data-copy-stamp-grant-url]');
    const url = root?.dataset.url?.trim() ?? '';
    if (root === null || url === '') return;

    // eslint-disable-next-line no-new
    new QRCodeCompat(root, { text: url, width: 320, height: 320, colorDark: '#243f38' });
    copy?.addEventListener('click', () => {
        void navigator.clipboard.writeText(input?.value ?? url).then(() => {
            copy.innerHTML = '<i class="fas fa-check"></i> コピーしました';
            window.setTimeout(() => { copy.innerHTML = '<i class="fas fa-copy"></i> URLをコピー'; }, 1800);
        });
    });
}

mountStampGrantQr();

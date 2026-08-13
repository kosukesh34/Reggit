import { QRCodeCompat } from '../shared/utils/qrCodeCompat';

export function mountStampGrantQr(): void {
    document.querySelectorAll<HTMLElement>('[data-stamp-grant-qr]').forEach((root) => {
        const url = root.dataset.url?.trim() ?? '';
        if (url === '') {
            return;
        }
        // eslint-disable-next-line no-new
        new QRCodeCompat(root, { text: url, width: 240, height: 240, colorDark: '#243f38' });
    });

    document.querySelectorAll<HTMLButtonElement>('[data-copy-stamp-grant-url]').forEach((copy) => {
        copy.addEventListener('click', () => {
            const targetId = copy.dataset.copyTarget ?? '';
            const input = targetId !== '' ? document.getElementById(targetId) as HTMLTextAreaElement | null : null;
            const url = input?.value.trim() ?? '';
            if (url === '') {
                return;
            }
            void navigator.clipboard.writeText(url).then(() => {
                copy.innerHTML = '<i class="fas fa-check"></i> コピーしました';
                window.setTimeout(() => { copy.innerHTML = '<i class="fas fa-copy"></i> URLをコピー'; }, 1800);
            });
        });
    });
}

mountStampGrantQr();

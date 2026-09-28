import QRCodeLib from 'qrcode';
import { QRCodeCompat } from '../shared/utils/qrCodeCompat';

async function downloadQrPng(url: string, filename: string): Promise<void> {
    const canvas = document.createElement('canvas');
    await QRCodeLib.toCanvas(canvas, url, {
        width: 480,
        color: { dark: '#243f38', light: '#ffffff' },
    });
    const link = document.createElement('a');
    link.href = canvas.toDataURL('image/png');
    link.download = filename;
    link.click();
}

export function mountStampGrantQr(): void {
    document.querySelectorAll<HTMLElement>('[data-stamp-grant-qr]').forEach((root) => {
        const url = root.dataset.url?.trim() ?? '';
        if (url === '') {
            return;
        }

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

    document.querySelectorAll<HTMLButtonElement>('[data-download-stamp-grant-qr]').forEach((button) => {
        button.addEventListener('click', () => {
            const url = button.dataset.url?.trim() ?? '';
            const filename = button.dataset.filename?.trim() || 'stamp-grant-qr.png';
            if (url === '') {
                return;
            }
            void downloadQrPng(url, filename).then(() => {
                const original = button.innerHTML;
                button.innerHTML = '<i class="fas fa-check"></i> ダウンロードしました';
                window.setTimeout(() => { button.innerHTML = original; }, 1800);
            });
        });
    });

    document.querySelectorAll<HTMLElement>('[data-stamp-grant-limit]').forEach((root) => {
        const toggle = root.querySelector<HTMLInputElement>('[data-stamp-grant-daily-toggle]');
        const hidden = root.querySelector<HTMLInputElement>('[data-stamp-grant-limit-value]');
        const other = root.querySelector<HTMLElement>('[data-stamp-grant-other]');
        const quotaInput = root.querySelector<HTMLInputElement>('[data-stamp-grant-quota]');
        if (toggle === null || hidden === null || other === null) {
            return;
        }
        const selectedOther = (): string => {
            const checked = other.querySelector<HTMLInputElement>('[data-stamp-grant-other-limit]:checked');
            return checked?.value ?? 'member';
        };
        const sync = (): void => {
            const selected = toggle.checked ? 'daily' : selectedOther();
            other.hidden = toggle.checked;
            hidden.value = selected;
            if (quotaInput !== null) {
                quotaInput.disabled = selected !== 'once';
            }
        };
        toggle.addEventListener('change', sync);
        other.querySelectorAll<HTMLInputElement>('[data-stamp-grant-other-limit]').forEach((radio) => {
            radio.addEventListener('change', sync);
        });
        quotaInput?.addEventListener('focus', () => {
            const onceRadio = other.querySelector<HTMLInputElement>('[data-stamp-grant-other-limit][value="once"]');
            if (onceRadio !== null && onceRadio.checked === false) {
                onceRadio.checked = true;
                sync();
            }
        });
        sync();
    });
}

mountStampGrantQr();

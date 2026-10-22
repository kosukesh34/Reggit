import QRCode from 'qrcode';

async function mountIssuedQr(root: HTMLElement): Promise<void> {
    const card = root.closest('.qr-card, .card');
    const button = card?.querySelector<HTMLButtonElement>('[data-save-issued-qr]');
    const error = card?.querySelector<HTMLElement>('[data-qr-error]');
    const url = root.dataset.url;
    if (!url || !button) return;
    try {
        const canvas = document.createElement('canvas');
        canvas.setAttribute('aria-label', root.getAttribute('aria-label') || 'QRコード');
        await QRCode.toCanvas(canvas, url, { width: 240, margin: 4, errorCorrectionLevel: 'M' });
        root.replaceChildren(canvas);
        button.disabled = false;
        button.addEventListener('click', () => {
            const anchor = document.createElement('a');
            anchor.download = root.dataset.filename || 'qr-code.png';
            anchor.href = canvas.toDataURL('image/png');
            anchor.click();
        });
    } catch {
        if (error) {
            error.textContent = 'QRコードを生成できませんでした。URLの長さや形式を確認してください。';
            error.hidden = false;
        }
    }
}

document.querySelectorAll<HTMLElement>('[data-issued-qr]').forEach(root => { void mountIssuedQr(root); });

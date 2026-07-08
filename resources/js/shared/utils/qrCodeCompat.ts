import QRCodeLib from 'qrcode';

type QRCodeOptions = {
    text: string;
    width?: number;
    height?: number;
    colorDark?: string;
    colorLight?: string;
};

export class QRCodeCompat {
    container: HTMLElement;
    options: QRCodeOptions;

    constructor(container: HTMLElement, options: QRCodeOptions) {
        this.container = container;
        this.options = options;
        this.render(options.text);
    }

    makeCode(text: string): void {
        this.render(text);
    }

    render(text: string): void {
        const width = this.options.width || this.options.height || 120;
        const canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = width;
        canvas.setAttribute('aria-label', 'QR code');
        this.container.innerHTML = '';
        this.container.appendChild(canvas);

        void QRCodeLib.toCanvas(canvas, text, {
            width,
            color: {
                dark: this.options.colorDark || '#000000',
                light: this.options.colorLight || '#ffffff',
            },
        });
    }
}

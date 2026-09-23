type ToastType = 'success' | 'error' | 'warning' | 'info';

export class ToastManager {
    static show(message: string, type: ToastType = 'info', duration = 3000): void {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;

        const icon = this.getIcon(type);
        toast.setAttribute('role', type === 'error' ? 'alert' : 'status');
        const iconElement = document.createElement('i');
        iconElement.className = icon;
        iconElement.setAttribute('aria-hidden', 'true');
        const text = document.createElement('span');
        text.textContent = message;
        toast.append(iconElement, text);

        document.body.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('show');
        }, 10);

        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }

    static getIcon(type: ToastType): string {
        const icons: Record<ToastType, string> = {
            success: 'fas fa-check-circle',
            error: 'fas fa-exclamation-circle',
            warning: 'fas fa-exclamation-triangle',
            info: 'fas fa-info-circle',
        };
        return icons[type] || icons.info;
    }

    static success(message: string, duration?: number): void {
        this.show(message, 'success', duration);
    }

    static error(message: string, duration?: number): void {
        this.show(message, 'error', duration);
    }

    static warning(message: string, duration?: number): void {
        this.show(message, 'warning', duration);
    }

    static info(message: string, duration?: number): void {
        this.show(message, 'info', duration);
    }
}

export class ConfirmDialog {
    static async show(message: string, title = '確認'): Promise<boolean> {
        return new Promise((resolve) => {
            const overlay = document.createElement('div');
            overlay.className = 'confirm-dialog';

            overlay.innerHTML = `
                <div class="confirm-dialog-content">
                    <div class="confirm-dialog-title">
                    </div>
                    <div class="confirm-dialog-message"></div>
                    <div class="confirm-dialog-actions">
                        <button class="btn btn-secondary" data-action="cancel">
                            <i class="fas fa-times"></i> キャンセル
                        </button>
                        <button class="btn btn-primary" data-action="confirm">
                            <i class="fas fa-check"></i> 確認
                        </button>
                    </div>
                </div>
            `;

            overlay.setAttribute('role', 'dialog');
            overlay.setAttribute('aria-modal', 'true');
            overlay.setAttribute('aria-label', title);
            overlay.querySelector('.confirm-dialog-title')!.textContent = title;
            overlay.querySelector('.confirm-dialog-message')!.textContent = message;
            const opener = document.activeElement instanceof HTMLElement ? document.activeElement : null;
            const close = (confirmed: boolean): void => {
                overlay.remove();
                opener?.focus();
                resolve(confirmed);
            };
            document.body.appendChild(overlay);
            const cancel = overlay.querySelector<HTMLButtonElement>('[data-action="cancel"]')!;
            const confirm = overlay.querySelector<HTMLButtonElement>('[data-action="confirm"]')!;
            cancel.focus();
            overlay.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    event.preventDefault();
                    close(false);
                } else if (event.key === 'Tab') {
                    event.preventDefault();
                    (document.activeElement === cancel ? confirm : cancel).focus();
                }
            });

            overlay.querySelector('[data-action="confirm"]')?.addEventListener('click', () => {
                close(true);
            });

            overlay.querySelector('[data-action="cancel"]')?.addEventListener('click', () => {
                close(false);
            });

            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    close(false);
                }
            });
        });
    }
}

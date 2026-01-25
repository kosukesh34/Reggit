class ConfirmDialog {
    static async show(message, title = '確認') {
        return new Promise((resolve) => {
            const overlay = document.createElement('div');
            overlay.className = 'confirm-dialog';
            
            overlay.innerHTML = `
                <div class="confirm-dialog-content">
                    <div class="confirm-dialog-title">
                        <i class="fas fa-question-circle"></i> ${title}
                    </div>
                    <div class="confirm-dialog-message">${message}</div>
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
            
            document.body.appendChild(overlay);
            
            overlay.querySelector('[data-action="confirm"]').addEventListener('click', () => {
                overlay.remove();
                resolve(true);
            });
            
            overlay.querySelector('[data-action="cancel"]').addEventListener('click', () => {
                overlay.remove();
                resolve(false);
            });
            
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    overlay.remove();
                    resolve(false);
                }
            });
        });
    }
}


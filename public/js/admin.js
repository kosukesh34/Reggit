document.addEventListener('DOMContentLoaded', () => {
    initDeleteConfirmations();
    initTooltips();
    initFormValidation();
    initLoadingStates();
});

function initDeleteConfirmations() {
    document.querySelectorAll('.delete-form').forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const itemName = form.dataset.itemName || 'この項目';
            const confirmed = await ConfirmDialog.show(
                `「${itemName}」を削除してもよろしいですか？\nこの操作は取り消せません。`,
                '削除の確認'
            );
            if (confirmed) {
                form.submit();
            }
        });
    });
}

function initTooltips() {
    document.querySelectorAll('[data-tooltip]').forEach(element => {
        element.addEventListener('mouseenter', (e) => {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = element.dataset.tooltip;
            document.body.appendChild(tooltip);
            
            const rect = element.getBoundingClientRect();
            const tooltipRect = tooltip.getBoundingClientRect();
            
            let top = rect.top - tooltipRect.height - 8;
            let left = rect.left + (rect.width / 2) - (tooltipRect.width / 2);
            
            if (top < 0) {
                top = rect.bottom + 8;
            }
            
            if (left < 0) {
                left = 8;
            } else if (left + tooltipRect.width > window.innerWidth) {
                left = window.innerWidth - tooltipRect.width - 8;
            }
            
            tooltip.style.top = `${top}px`;
            tooltip.style.left = `${left}px`;
            
            setTimeout(() => tooltip.classList.add('show'), 10);
            
            element._tooltip = tooltip;
        });
        
        element.addEventListener('mouseleave', () => {
            if (element._tooltip) {
                element._tooltip.remove();
                element._tooltip = null;
            }
        });
    });
}

function initFormValidation() {
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', (e) => {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn && !form.checkValidity()) {
                e.preventDefault();
                ToastManager.error('入力内容を確認してください');
                form.reportValidity();
            } else if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 処理中...';
            }
        });
    });
}

function initLoadingStates() {
    document.querySelectorAll('a.btn, button.btn').forEach(btn => {
        if (btn.href || btn.type === 'submit') {
            btn.addEventListener('click', function() {
                if (!this.disabled && !this.classList.contains('no-loading')) {
                    const originalHTML = this.innerHTML;
                    this.disabled = true;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 処理中...';
                    
                    setTimeout(() => {
                        this.disabled = false;
                        this.innerHTML = originalHTML;
                    }, 3000);
                }
            });
        }
    });
}

const ToastManager = {
    show(message, type = 'info', duration = 3000) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        
        const icon = this.getIcon(type);
        toast.innerHTML = `
            <i class="${icon}"></i>
            <span>${message}</span>
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => toast.classList.add('show'), 10);
        
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    },
    
    getIcon(type) {
        const icons = {
            success: 'fas fa-check-circle',
            error: 'fas fa-exclamation-circle',
            warning: 'fas fa-exclamation-triangle',
            info: 'fas fa-info-circle',
        };
        return icons[type] || icons.info;
    },
    
    success(message, duration) {
        this.show(message, 'success', duration);
    },
    
    error(message, duration) {
        this.show(message, 'error', duration);
    },
    
    warning(message, duration) {
        this.show(message, 'warning', duration);
    },
    
    info(message, duration) {
        this.show(message, 'info', duration);
    }
};

const ConfirmDialog = {
    async show(message, title = '確認') {
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
};


import { ConfirmDialog } from '../shared/utils/confirmDialog';
import { ToastManager } from '../shared/utils/toast';

type TooltipHost = HTMLElement & { _tooltip?: HTMLElement | null };

function preventZoom(): void {
    document.addEventListener('gesturestart', ((e: Event) => {
        e.preventDefault();
    }) as EventListener);
    document.addEventListener('gesturechange', ((e: Event) => {
        e.preventDefault();
    }) as EventListener);
    document.addEventListener('gestureend', ((e: Event) => {
        e.preventDefault();
    }) as EventListener);
    document.addEventListener('touchstart', ((e: TouchEvent) => {
        if (e.touches.length > 1) {
            e.preventDefault();
        }
    }) as EventListener, { passive: false });
    document.addEventListener('touchmove', ((e: TouchEvent) => {
        if (e.touches.length > 1) {
            e.preventDefault();
        }
    }) as EventListener, { passive: false });
    document.addEventListener('touchend', ((e: TouchEvent) => {
        if (e.touches.length > 1) {
            e.preventDefault();
        }
    }) as EventListener, { passive: false });
}

function initDeleteConfirmations(): void {
    document.querySelectorAll('.delete-form').forEach((formEl) => {
        const form = formEl as HTMLFormElement;
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const itemName = form.dataset.itemName || 'この項目';
            const confirmed = await ConfirmDialog.show(
                `「${itemName}」を削除してもよろしいですか？\nこの操作は取り消せません。`,
                '削除の確認',
            );
            if (confirmed) {
                form.submit();
            }
        });
    });
}

function initTooltips(): void {
    document.querySelectorAll('[data-tooltip]').forEach((el) => {
        const element = el as TooltipHost;
        element.addEventListener('mouseenter', () => {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = element.dataset.tooltip || '';
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

function initFormValidation(): void {
    document.querySelectorAll('form').forEach((formEl) => {
        const form = formEl as HTMLFormElement;
        form.addEventListener('submit', (e) => {
            // 削除確認ダイアログ待ちのフォームはここでは触らない
            if (form.classList.contains('delete-form')) {
                return;
            }
            if (e.defaultPrevented) {
                return;
            }
            const submitBtn = form.querySelector('button[type="submit"]:not(.no-loading), input[type="submit"]:not(.no-loading)') as HTMLButtonElement | null;
            if (submitBtn && !form.checkValidity()) {
                e.preventDefault();
                ToastManager.error('入力内容を確認してください');
                form.reportValidity();
                return;
            }
            // click 時ではなく submit 確定後に無効化（click で disabled にすると送信自体がキャンセルされる）
            if (submitBtn && !submitBtn.disabled) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 処理中...';
            }
        });
    });
}

function initLoadingStates(): void {
    // 送信ボタンの loading 表示は initFormValidation の submit 時に行う。
    // click で disabled にするとブラウザがフォーム送信をキャンセルするため、ここには置かない。
}

function initSidebarCollapsibleGroups(): void {
    const groups = document.querySelectorAll('.sidebar-nav .nav-group');
    if (!groups.length) return;

    const nav = document.querySelector('.sidebar-nav') as HTMLElement | null;
    if (nav) {
        const savedScroll = sessionStorage.getItem('adminSidebarScroll');
        if (savedScroll) {
            nav.scrollTop = parseInt(savedScroll, 10);
        }
        nav.addEventListener('scroll', () => {
            sessionStorage.setItem('adminSidebarScroll', String(nav.scrollTop));
        });
    }

    groups.forEach((groupEl) => {
        const group = groupEl as HTMLElement;
        const toggle = group.querySelector('.nav-group-label') as HTMLElement | null;
        if (!toggle) return;

        group.classList.add('is-collapsible');
        toggle.setAttribute('role', 'button');
        toggle.setAttribute('tabindex', '0');

        const hasActive = !!group.querySelector('.nav-item.active');
        if (!hasActive) {
            group.classList.add('is-collapsed');
        }
        toggle.setAttribute('aria-expanded', String(!group.classList.contains('is-collapsed')));

        const toggleGroup = (): void => {
            group.classList.toggle('is-collapsed');
            toggle.setAttribute('aria-expanded', String(!group.classList.contains('is-collapsed')));
        };

        toggle.addEventListener('click', toggleGroup);
        toggle.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter' && event.key !== ' ') return;
            event.preventDefault();
            toggleGroup();
        });
    });
}

export function bootstrapAdminCommon(): void {
    preventZoom();

    const run = (): void => {
        initDeleteConfirmations();
        initTooltips();
        initFormValidation();
        initLoadingStates();
        initSidebarCollapsibleGroups();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run);
    } else {
        run();
    }
}

bootstrapAdminCommon();

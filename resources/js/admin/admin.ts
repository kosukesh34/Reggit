import { ConfirmDialog } from '../shared/utils/confirmDialog';
import { ToastManager } from '../shared/utils/toast';
import {
    formsHaveUnsavedChanges,
    serializeForm,
    shouldTrackUnsavedForm,
    UNSAVED_LEAVE_MESSAGE,
} from './unsavedFormGuard';

type TooltipHost = HTMLElement & { _tooltip?: HTMLElement | null };

let isLeavingAdminPage = false;

function markAdminPageLeaving(): void {
    isLeavingAdminPage = true;
}

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

function showTooltip(element: TooltipHost): void {
    if (element._tooltip) element._tooltip.remove();
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip';
    tooltip.setAttribute('role', 'tooltip');
    tooltip.textContent = element.dataset.tooltip || '';
    document.body.appendChild(tooltip);

    const rect = element.getBoundingClientRect();
    const tooltipRect = tooltip.getBoundingClientRect();
    let top = rect.top - tooltipRect.height - 8;
    let left = rect.left;
    if (top < 8) top = rect.bottom + 8;
    if (left + tooltipRect.width > window.innerWidth - 8) {
        left = Math.max(8, window.innerWidth - tooltipRect.width - 8);
    }
    tooltip.style.top = `${top}px`;
    tooltip.style.left = `${left}px`;
    tooltip.classList.add('show');
    element._tooltip = tooltip;
}

function hideTooltip(element: TooltipHost): void {
    element._tooltip?.remove();
    element._tooltip = null;
}

function initTooltips(): void {
    document.querySelectorAll<HTMLElement>('[data-tooltip]').forEach((el) => {
        const element = el as TooltipHost;
        if (!element.hasAttribute('tabindex') && element.tagName !== 'BUTTON' && element.tagName !== 'A') {
            element.tabIndex = 0;
        }
        element.addEventListener('mouseenter', () => showTooltip(element));
        element.addEventListener('mouseleave', () => hideTooltip(element));
        element.addEventListener('focus', () => showTooltip(element));
        element.addEventListener('blur', () => hideTooltip(element));
    });
}

function showAdminSavingOverlay(): void {
    if (document.getElementById('adminSavingOverlay')) return;
    const overlay = document.createElement('div');
    overlay.id = 'adminSavingOverlay';
    overlay.className = 'loading-overlay';
    overlay.setAttribute('role', 'status');
    overlay.setAttribute('aria-live', 'polite');
    overlay.innerHTML = '<div class="admin-saving"><div class="loading-spinner"></div><p>保存中</p></div>';
    document.body.appendChild(overlay);
}

function resolveSubmitControl(form: HTMLFormElement, event: Event): HTMLButtonElement | HTMLInputElement | null {
    const submitter = 'submitter' in event ? (event as SubmitEvent).submitter : null;
    if (submitter instanceof HTMLButtonElement || submitter instanceof HTMLInputElement) {
        return submitter;
    }
    return form.querySelector('button[type="submit"], input[type="submit"]');
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
            if (form.hasAttribute('data-no-loading')) {
                return;
            }
            const submitBtn = resolveSubmitControl(form, e);
            if (submitBtn?.classList.contains('no-loading')) {
                return;
            }
            if (submitBtn !== null && !form.checkValidity()) {
                e.preventDefault();
                ToastManager.error('入力内容を確認してください');
                form.reportValidity();
                return;
            }
            // 確認画面など、後から登録された submit ハンドラーのキャンセルも確認する。
            setTimeout(() => {
                if (e.defaultPrevented) return;
                markAdminPageLeaving();
                if (submitBtn instanceof HTMLButtonElement && !submitBtn.disabled) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 処理中...';
                } else if (submitBtn instanceof HTMLInputElement && !submitBtn.disabled) {
                    submitBtn.disabled = true;
                    submitBtn.value = '処理中...';
                }
                showAdminSavingOverlay();
            });
        });
    });
}

function initUnsavedChangesWarning(): void {
    const forms = Array.from(document.querySelectorAll('form')).filter(
        (form): form is HTMLFormElement => form instanceof HTMLFormElement && shouldTrackUnsavedForm(form),
    );
    if (forms.length === 0) {
        return;
    }

    const savedSnapshots = forms.map((form) => serializeForm(form));
    // IP行の追加など、inputイベント前のDOM変更も拾う
    let isDirty = false;

    const hasUnsavedChanges = (): boolean => (
        isDirty || formsHaveUnsavedChanges(forms, savedSnapshots)
    );

    const syncDirtyFromForms = (): void => {
        if (formsHaveUnsavedChanges(forms, savedSnapshots)) {
            isDirty = true;
        }
    };

    forms.forEach((form) => {
        form.addEventListener('input', syncDirtyFromForms);
        form.addEventListener('change', syncDirtyFromForms);
        const observer = new MutationObserver(syncDirtyFromForms);
        observer.observe(form, { childList: true, subtree: true });

        form.addEventListener('submit', () => {
            if (!form.checkValidity()) {
                return;
            }
            markAdminPageLeaving();
        });
    });

    window.addEventListener('beforeunload', (event) => {
        if (isLeavingAdminPage || !hasUnsavedChanges()) {
            return;
        }
        event.preventDefault();
        event.returnValue = '';
    });

    document.addEventListener('click', (event) => {
        if (isLeavingAdminPage || event.button !== 0
            || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }
        const target = event.target;
        if (!(target instanceof Element)) {
            return;
        }
        const link = target.closest('a');
        if (!(link instanceof HTMLAnchorElement) || link.target === '_blank' || link.href === '') {
            return;
        }
        let nextUrl: URL;
        try {
            nextUrl = new URL(link.href);
        } catch {
            return;
        }
        if (nextUrl.origin !== window.location.origin
            || (nextUrl.pathname === window.location.pathname && nextUrl.search === window.location.search)) {
            return;
        }
        if (!hasUnsavedChanges()) {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();
        void ConfirmDialog.show(UNSAVED_LEAVE_MESSAGE, '確認').then((confirmed) => {
            if (!confirmed) {
                return;
            }
            markAdminPageLeaving();
            window.location.href = link.href;
        });
    }, true);
}

function initStoreSwitcher(): void {
    const root = document.querySelector('[data-store-switcher]');
    if (!(root instanceof HTMLDetailsElement)) {
        return;
    }
    const search = root.querySelector<HTMLInputElement>('.sidebar-store-switcher-search');
    const empty = root.querySelector<HTMLElement>('.sidebar-store-switcher-empty');
    const items = Array.from(root.querySelectorAll<HTMLElement>('.sidebar-store-switcher-item'));
    if (search === null || empty === null) {
        return;
    }

    const applyFilter = (): void => {
        const query = search.value.trim().toLowerCase();
        let visibleCount = 0;
        items.forEach((item) => {
            const haystack = (item.dataset.storeSearch ?? item.textContent ?? '').toLowerCase();
            const isMatch = query === '' || haystack.includes(query);
            item.hidden = !isMatch;
            if (isMatch) {
                visibleCount += 1;
            }
        });
        empty.hidden = visibleCount !== 0;
    };

    root.addEventListener('toggle', () => {
        if (!root.open) {
            search.value = '';
            applyFilter();
            return;
        }
        search.focus();
    });
    search.addEventListener('input', applyFilter);
    document.addEventListener('click', (event) => {
        if (!root.open || !(event.target instanceof Node) || root.contains(event.target)) {
            return;
        }
        root.open = false;
    });
    root.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }
        if (search.value !== '') {
            search.value = '';
            applyFilter();
            event.preventDefault();
            return;
        }
        root.open = false;
    });
}

function initSidebarScrollRestore(): void {
    const nav = document.querySelector('.sidebar-nav') as HTMLElement | null;
    if (nav === null) {
        return;
    }

    const savedScroll = sessionStorage.getItem('adminSidebarScroll');
    if (savedScroll !== null) {
        nav.scrollTop = parseInt(savedScroll, 10);
    }
    nav.addEventListener('scroll', () => {
        sessionStorage.setItem('adminSidebarScroll', String(nav.scrollTop));
    });
}

function initImageUploads(): void {
    document.querySelectorAll<HTMLElement>('[data-image-upload]').forEach((root) => {
        const fieldId = root.dataset.imageUpload;
        if (fieldId === undefined || fieldId === '') {
            return;
        }
        const fileInput = document.getElementById(fieldId);
        const dropzone = document.getElementById(`${fieldId}_dropzone`);
        const preview = document.getElementById(`${fieldId}_preview`);
        const previewImg = document.getElementById(`${fieldId}_preview_img`) as HTMLImageElement | null;
        const clearBtn = document.getElementById(`${fieldId}_clear`);
        const clearFlag = document.getElementById(`${fieldId}_clear_flag`) as HTMLInputElement | null;
        if (!(fileInput instanceof HTMLInputElement) || dropzone === null) {
            return;
        }

        const showPreview = (src: string): void => {
            if (preview === null || previewImg === null) {
                return;
            }
            previewImg.src = src;
            preview.hidden = src === '';
        };

        const readFile = (file: File | undefined): void => {
            if (file === undefined || !file.type.startsWith('image/')) {
                return;
            }
            if (file.size > 2 * 1024 * 1024) {
                window.alert('画像サイズは2MB以下にしてください');
                return;
            }
            if (clearFlag !== null) {
                clearFlag.value = '0';
            }
            const reader = new FileReader();
            reader.onload = () => {
                showPreview(typeof reader.result === 'string' ? reader.result : '');
                fileInput.dispatchEvent(new Event('admin-image-preview', { bubbles: true }));
            };
            reader.readAsDataURL(file);
        };

        dropzone.addEventListener('click', () => {
            fileInput.click();
        });
        dropzone.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                fileInput.click();
            }
        });
        dropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropzone.classList.add('is-dragover');
        });
        dropzone.addEventListener('dragleave', () => {
            dropzone.classList.remove('is-dragover');
        });
        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.classList.remove('is-dragover');
            const dropped = e.dataTransfer?.files[0];
            if (dropped === undefined) {
                return;
            }
            const transfer = new DataTransfer();
            transfer.items.add(dropped);
            fileInput.files = transfer.files;
            readFile(dropped);
        });
        fileInput.addEventListener('change', () => {
            const selected = fileInput.files?.[0];
            if (selected !== undefined) {
                readFile(selected);
            }
        });
        if (clearBtn !== null) {
            clearBtn.addEventListener('click', () => {
                fileInput.value = '';
                if (clearFlag !== null) {
                    clearFlag.value = '1';
                }
                showPreview('');
                fileInput.dispatchEvent(new Event('admin-image-preview', { bubbles: true }));
            });
        }
    });
}

export function bootstrapAdminCommon(): void {
    preventZoom();

    const run = (): void => {
        initDeleteConfirmations();
        initTooltips();
        initFormValidation();
        initUnsavedChangesWarning();
        initStoreSwitcher();
        initSidebarScrollRestore();
        initImageUploads();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run);
    } else {
        run();
    }
}

bootstrapAdminCommon();

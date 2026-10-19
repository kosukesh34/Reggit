export const UNSAVED_LEAVE_MESSAGE = '保存していない変更があります。このページを離れますか？';

export function serializeForm(form: HTMLFormElement): string {
    const params = new URLSearchParams();
    new FormData(form).forEach((value, key) => {
        params.append(key, typeof value === 'string' ? value : value.name);
    });
    return params.toString();
}

export function isSettingsLikeAction(action: string): boolean {
    const normalized = action.toLowerCase();
    return normalized.includes('/settings/')
        || normalized.includes('login-security')
        || normalized.includes('change-password')
        || normalized.includes('/rfm/settings');
}

export function shouldTrackUnsavedForm(form: HTMLFormElement): boolean {
    if (
        form.hasAttribute('data-no-unsaved-warn')
        || form.classList.contains('no-unsaved-warn')
        || form.classList.contains('delete-form')
    ) {
        return false;
    }

    if (form.hasAttribute('data-unsaved-warn')) {
        return true;
    }

    if (form.closest('.content-body') === null) {
        return false;
    }

    const method = (form.getAttribute('method') || 'get').toLowerCase();
    if (method === 'get') {
        return false;
    }

    if (!isSettingsLikeAction(form.getAttribute('action') || '')) {
        return false;
    }

    // checkbox / radio も含める（IP未登録時のログイン設定など）
    return form.querySelector(
        'input:not([type="hidden"]):not([type="submit"]):not([type="button"]):not([type="reset"]), select, textarea',
    ) !== null;
}

export function formsHaveUnsavedChanges(
    forms: readonly HTMLFormElement[],
    savedSnapshots: readonly string[],
): boolean {
    return forms.some((form, index) => serializeForm(form) !== savedSnapshots[index]);
}

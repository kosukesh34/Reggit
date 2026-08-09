import type { DesignerState } from '../catalog/types';
import { buildSavePayload } from './serialize';

function readXsrfToken(): string {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
    if (match === null || match[1] === undefined) {
        return '';
    }
    try {
        return decodeURIComponent(match[1]);
    } catch {
        return match[1];
    }
}

export async function saveDesigner(state: DesignerState, formAction: string, csrfToken: string): Promise<string> {
    if (formAction === '' || csrfToken === '') {
        throw new Error('保存設定が見つかりません。ページを再読み込みしてください');
    }

    const payload = buildSavePayload(state);
    const body = new FormData();
    body.append('_token', csrfToken);
    body.append('layouts_json', payload.layouts_json);
    body.append('detail_layouts_json', payload.detail_layouts_json);
    body.append('tab_settings_json', payload.tab_settings_json);
    body.append('menu_items_json', payload.menu_items_json);
    body.append('custom_css', payload.custom_css);

    const headers: Record<string, string> = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken,
    };
    const xsrf = readXsrfToken();
    if (xsrf !== '') {
        headers['X-XSRF-TOKEN'] = xsrf;
    }

    let response: Response;
    try {
        response = await fetch(formAction, {
            method: 'POST',
            body,
            headers,
            credentials: 'same-origin',
        });
    } catch {
        throw new Error('保存リクエストに失敗しました。通信環境を確認してください');
    }

    const contentType = response.headers.get('content-type') || '';
    const isJson = contentType.includes('application/json');
    let data: { message?: string; errors?: Record<string, string[]> } = {};
    if (isJson) {
        try {
            data = await response.json() as { message?: string; errors?: Record<string, string[]> };
        } catch {
            data = {};
        }
    }

    if (!response.ok) {
        let message = `保存に失敗しました（${response.status}）`;
        if (typeof data.message === 'string' && data.message !== '') {
            message = data.message;
        } else if (data.errors !== undefined) {
            const first = Object.values(data.errors)[0];
            if (Array.isArray(first) && first[0] !== undefined) {
                message = first[0];
            }
        } else if (response.status === 419) {
            message = 'セッションの有効期限が切れました。ページを再読み込みしてください';
        } else if (!isJson) {
            message = '保存に失敗しました。ログイン状態を確認して再読み込みしてください';
        }
        throw new Error(message);
    }

    if (!isJson) {
        throw new Error('保存応答が不正です。ページを再読み込みして再度お試しください');
    }

    return typeof data.message === 'string' && data.message !== '' ? data.message : '保存しました';
}

import type { DesignerState } from '../catalog/types';
import { buildSavePayload } from './serialize';

export async function saveDesigner(state: DesignerState, formAction: string, csrfToken: string): Promise<string> {
    const payload = buildSavePayload(state);
    const body = new FormData();
    body.append('_token', csrfToken);
    body.append('layouts_json', payload.layouts_json);
    body.append('tab_settings_json', payload.tab_settings_json);
    body.append('menu_items_json', payload.menu_items_json);
    body.append('custom_css', payload.custom_css);

    const response = await fetch(formAction, {
        method: 'POST',
        body,
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    });

    if (!response.ok) {
        let message = '保存に失敗しました';
        try {
            const data = await response.json() as { message?: string; errors?: Record<string, string[]> };
            if (typeof data.message === 'string' && data.message !== '') {
                message = data.message;
            } else if (data.errors !== undefined) {
                const first = Object.values(data.errors)[0];
                if (Array.isArray(first) && first[0] !== undefined) {
                    message = first[0];
                }
            }
        } catch {
            /* ignore */
        }
        throw new Error(message);
    }

    const data = await response.json() as { message?: string };
    return typeof data.message === 'string' && data.message !== '' ? data.message : '保存しました';
}

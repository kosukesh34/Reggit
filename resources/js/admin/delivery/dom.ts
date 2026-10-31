/** 配信画面の要素を ID で読み書きする小さな補助。要素がない画面ではそのまま何もしない。 */

export const field = <T extends HTMLElement>(id: string): T | null => document.getElementById(id) as T | null;

export function setText(id: string, value: string): void {
    const element = field<HTMLElement>(id);
    if (element) element.textContent = value;
}

export function fieldValue(id: string): string {
    return (field<HTMLInputElement | HTMLSelectElement>(id)?.value || '').trim();
}

export function selectedOptionText(id: string): string {
    const select = field<HTMLSelectElement>(id);
    if (!select || select.value === '') return '';
    return select.selectedOptions[0]?.textContent?.trim() || select.value;
}

export function formattedNumber(value: string): string {
    const number = Number(value);
    return Number.isFinite(number) ? number.toLocaleString('ja-JP') : value;
}

export function checkedValues(name: string): string[] {
    return Array.from(document.querySelectorAll<HTMLInputElement>(`input[name="${name}"]:checked`))
        .map((input) => input.value.trim())
        .filter((value) => value !== '');
}

export function checkedOptionLabels(name: string): string[] {
    return Array.from(document.querySelectorAll<HTMLInputElement>(`input[name="${name}"]:checked`))
        .map((input) => input.dataset.label?.trim() || input.value)
        .filter((value) => value !== '');
}

export function setBlockEnabled(block: HTMLElement | null, enabled: boolean): void {
    if (!block) return;
    block.hidden = !enabled;
    block.querySelectorAll<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>('input[name], select[name], textarea[name]')
        .forEach((control) => {
            control.disabled = !enabled;
        });
}

export function formatDateTime(value: string): string {
    if (!value) return '日時を選択';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '日時を選択';
    return date.toLocaleString('ja-JP', {
        year: 'numeric', month: 'numeric', day: 'numeric', hour: '2-digit', minute: '2-digit',
    });
}

/** 管理画面の Ajax GET。失敗時は例外にして呼び出し側の表示に任せる */
export async function fetchJson<T>(url: string): Promise<T> {
    const response = await fetch(url, {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
    });
    if (!response.ok) throw new Error(`request failed: ${response.status}`);
    return await response.json() as T;
}

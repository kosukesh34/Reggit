export function pointQrTokenFromUrl(href: string): string {
    try {
        const url = new URL(href);
        const hash = url.hash.includes('?') ? url.hash.slice(url.hash.indexOf('?') + 1) : '';
        const rawState = url.searchParams.get('liff.state') || url.searchParams.get('state') || '';
        const state = rawState.includes('?') ? rawState.slice(rawState.indexOf('?') + 1) : rawState;
        const token = url.searchParams.get('point_code')
            || new URLSearchParams(state).get('point_code')
            || new URLSearchParams(hash).get('point_code') || '';
        return /^[0-9a-f]{48}$/.test(token) ? token : '';
    } catch {
        return '';
    }
}

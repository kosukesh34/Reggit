/** スタンプ付与QRトークンの URL / sessionStorage 抽出。LIFF初期化とスタンプ画面で共有する。 */

export const PENDING_STAMP_GRANT_KEY = 'reggit_pending_stamp_grant';

export const STAMP_GRANT_CODE_RE = /^[0-9a-f]{12}$/i;

export function isStampGrantCode(value: string): boolean {
    return STAMP_GRANT_CODE_RE.test(value.trim());
}

export function extractStampGrantCodeFromParams(params: URLSearchParams): string | null {
    const token = params.get('g');
    if (token === null || !isStampGrantCode(token)) {
        return null;
    }
    return token.trim().toLowerCase();
}

export function extractStampGrantCodeFromString(value: string | null | undefined): string | null {
    if (value === null || value === undefined) {
        return null;
    }
    const normalized = value.startsWith('#') ? value.slice(1) : value.trim();
    if (normalized === '') {
        return null;
    }
    if (isStampGrantCode(normalized)) {
        return normalized.toLowerCase();
    }
    const fromPath = normalized.match(/\/g\/([0-9a-f]{12})(?:[/?#]|$)/i);
    if (fromPath !== null && fromPath[1] !== undefined) {
        return fromPath[1].toLowerCase();
    }
    try {
        const full = new URL(normalized);
        const fromQuery = extractStampGrantCodeFromParams(full.searchParams);
        if (fromQuery !== null) {
            return fromQuery;
        }
        const fromLiffState = extractStampGrantCodeFromEncodedParam(full.searchParams.get('liff.state'))
            ?? extractStampGrantCodeFromEncodedParam(full.searchParams.get('state'));
        if (fromLiffState !== null) {
            return fromLiffState;
        }
        const hashQuery = full.hash.includes('?') ? full.hash.slice(full.hash.indexOf('?') + 1) : '';
        return extractStampGrantCodeFromParams(new URLSearchParams(hashQuery));
    } catch {
        // relative query or plain query
    }
    const query = normalized.includes('?') ? normalized.slice(normalized.indexOf('?') + 1) : normalized;
    const params = new URLSearchParams(query);
    return extractStampGrantCodeFromParams(params)
        ?? extractStampGrantCodeFromEncodedParam(params.get('liff.state'));
}

export function extractStampGrantCodeFromEncodedParam(raw: string | null | undefined): string | null {
    if (raw === null || raw === undefined || raw === '') {
        return null;
    }
    let decoded = raw;
    for (let i = 0; i < 2; i += 1) {
        try {
            const next = decodeURIComponent(decoded);
            if (next === decoded) {
                break;
            }
            decoded = next;
        } catch {
            break;
        }
        const fromDecoded = extractStampGrantCodeFromString(decoded);
        if (fromDecoded !== null) {
            return fromDecoded;
        }
    }
    return extractStampGrantCodeFromString(raw);
}

export function extractStampGrantCodeFromLocation(locationLike: Location): string | null {
    const params = new URLSearchParams(locationLike.search || '');
    const direct = extractStampGrantCodeFromParams(params);
    if (direct !== null) {
        return direct;
    }
    return extractStampGrantCodeFromString(locationLike.hash || '')
        ?? extractStampGrantCodeFromEncodedParam(params.get('liff.state'))
        ?? extractStampGrantCodeFromEncodedParam(params.get('state'));
}

export function persistStampGrantCode(token: string): void {
    if (typeof window === 'undefined' || token === '') {
        return;
    }
    try {
        window.sessionStorage.setItem(PENDING_STAMP_GRANT_KEY, token);
    } catch {
        // ignore
    }
}

export function getPersistedStampGrantCode(): string | null {
    if (typeof window === 'undefined') {
        return null;
    }
    try {
        const token = window.sessionStorage.getItem(PENDING_STAMP_GRANT_KEY);
        return token !== null && isStampGrantCode(token) ? token.toLowerCase() : null;
    } catch {
        return null;
    }
}

export function clearPersistedStampGrantCode(): void {
    if (typeof window === 'undefined') {
        return;
    }
    try {
        window.sessionStorage.removeItem(PENDING_STAMP_GRANT_KEY);
    } catch {
        // ignore
    }
}

export function getStampGrantCodeFromUrl(): string | null {
    if (typeof window === 'undefined') {
        return null;
    }
    const fromLocation = extractStampGrantCodeFromLocation(window.location);
    if (fromLocation !== null) {
        persistStampGrantCode(fromLocation);
        return fromLocation;
    }
    return getPersistedStampGrantCode();
}

export function clearStampGrantCodeFromUrl(): void {
    if (typeof window === 'undefined' || window.history === undefined) {
        return;
    }
    const url = new URL(window.location.href);
    url.searchParams.delete('g');
    window.history.replaceState({}, '', url.toString());
    clearPersistedStampGrantCode();
}

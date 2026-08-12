/** 順番待ち QR トークンの URL / sessionStorage 抽出。LIFF・Manager・React で共有する。 */

export const PENDING_QUEUE_TOKEN_KEY = 'mobile_order_pending_queue_token';

export function extractQueueTokenFromString(value: string | null | undefined): string | null {
    if (value === null || value === undefined) {
        return null;
    }
    const normalized = value.startsWith('#') ? value.slice(1) : value.trim();
    if (normalized === '') {
        return null;
    }
    try {
        const full = new URL(normalized);
        const token = full.searchParams.get('queue_token') || full.searchParams.get('queue_id');
        if (token !== null && token !== '') {
            return token;
        }
    } catch {
        // relative query or plain query
    }
    const query = normalized.includes('?') ? normalized.split('?')[1] : normalized;
    const params = new URLSearchParams(query);
    const token = params.get('queue_token') || params.get('queue_id');
    return token !== null && token !== '' ? token : null;
}

export function extractQueueTokenFromEncodedParam(raw: string | null | undefined): string | null {
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
        const fromDecoded = extractQueueTokenFromString(decoded);
        if (fromDecoded !== null) {
            return fromDecoded;
        }
    }
    return extractQueueTokenFromString(raw);
}

export function extractQueueTokenFromLocation(locationLike: Location): string | null {
    const params = new URLSearchParams(locationLike.search || '');
    const direct = params.get('queue_token') || params.get('queue_id');
    if (direct !== null && direct !== '') {
        return direct;
    }
    return extractQueueTokenFromString(locationLike.hash || '')
        ?? extractQueueTokenFromEncodedParam(params.get('liff.state'))
        ?? extractQueueTokenFromEncodedParam(params.get('state'))
        ?? extractQueueTokenFromEncodedParam(params.get('liffRedirectUri'));
}

export function persistQueueToken(token: string): void {
    if (typeof window === 'undefined' || token === '') {
        return;
    }
    try {
        window.sessionStorage.setItem(PENDING_QUEUE_TOKEN_KEY, token);
    } catch {
        // ignore
    }
}

export function getPersistedQueueToken(): string | null {
    if (typeof window === 'undefined') {
        return null;
    }
    try {
        return window.sessionStorage.getItem(PENDING_QUEUE_TOKEN_KEY);
    } catch {
        return null;
    }
}

export function clearPersistedQueueToken(): void {
    if (typeof window === 'undefined') {
        return;
    }
    try {
        window.sessionStorage.removeItem(PENDING_QUEUE_TOKEN_KEY);
    } catch {
        // ignore
    }
}

export function getQueueTokenFromUrl(): string | null {
    if (typeof window === 'undefined') {
        return null;
    }
    const fromLocation = extractQueueTokenFromLocation(window.location);
    if (fromLocation !== null) {
        persistQueueToken(fromLocation);
        return fromLocation;
    }
    return getPersistedQueueToken();
}

export function clearQueueTokenFromUrl(): void {
    if (typeof window === 'undefined' || window.history === undefined) {
        return;
    }
    const url = new URL(window.location.href);
    url.searchParams.delete('queue_token');
    url.searchParams.delete('queue_id');
    url.searchParams.delete('state');
    url.searchParams.delete('liff.state');
    url.searchParams.delete('liffRedirectUri');
    window.history.replaceState({}, '', url.toString());
    clearPersistedQueueToken();
}

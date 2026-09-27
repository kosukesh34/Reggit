export type WalletReturnTarget = 'line' | 'app' | 'web';

export interface LocationLike {
    href?: string;
    origin?: string;
}

export function buildLiffEntryUrl(liffId: string, projectKey: unknown): string | null {
    const normalizedLiffId = liffId.trim();
    if (!normalizedLiffId) return null;

    const liffState = new URLSearchParams({
        project_key: projectKey == null ? '' : String(projectKey).trim(),
    }).toString();

    return `https://liff.line.me/${normalizedLiffId}?${new URLSearchParams({ 'liff.state': liffState })}`;
}

export function resolveAppOrigin(location: LocationLike | null, apiBase: unknown): string {
    if (location?.origin && location.origin !== 'null') {
        return location.origin;
    }

    if (typeof apiBase !== 'string' || (!apiBase.startsWith('http://') && !apiBase.startsWith('https://'))) {
        return '';
    }

    try {
        return new URL(apiBase).origin;
    } catch {
        return '';
    }
}

export function resolveApiUrl(path: unknown, origin: string): string {
    const value = typeof path === 'string' ? path : String(path ?? '');
    if (value.startsWith('http://') || value.startsWith('https://')) {
        return value;
    }

    const normalizedPath = value.startsWith('/') ? value : `/${value}`;
    return origin ? `${origin}${normalizedPath}` : normalizedPath;
}

export function isValidHttpUrl(url: unknown): url is string {
    if (typeof url !== 'string' || url === '') return false;

    try {
        const parsed = new URL(url);
        return parsed.protocol === 'http:' || parsed.protocol === 'https:';
    } catch {
        return false;
    }
}

export function normalizeExternalUrl(url: unknown, baseHref?: string): string | null {
    if (!isValidHttpUrl(url)) return null;

    try {
        const parsed = new URL(url, baseHref);
        const isLocal = parsed.hostname === 'localhost' || parsed.hostname === '127.0.0.1';
        if (!isLocal && parsed.protocol === 'http:') {
            parsed.protocol = 'https:';
        }
        return parsed.href;
    } catch {
        return null;
    }
}

export function resolveGoogleWalletOpenUrl(issueUrl: unknown, baseHref?: string): string | null {
    const normalized = normalizeExternalUrl(issueUrl, baseHref);
    if (!normalized) return null;

    try {
        const parsed = new URL(normalized);
        if (parsed.hostname === 'pay.google.com' && parsed.pathname.startsWith('/gp/v/save/')) {
            return parsed.href;
        }
        if (parsed.pathname.startsWith('/google-wallet/save/')) {
            return parsed.href;
        }
    } catch {
        return null;
    }

    return normalized;
}

export function formatWalletErrorMessage(error: unknown, fallback?: string): string {
    const raw = error instanceof Error
        ? error.message
        : typeof error === 'object' && error !== null && 'message' in error
            ? String(error.message)
            : '';

    if (/pattern/i.test(raw)) {
        return 'Safari で Wallet を開けませんでした。LINE の設定で外部ブラウザを許可するか、しばらくしてから再度お試しください。';
    }
    if (/外部ブラウザ|Wallet Pass の URL/i.test(raw)) {
        return raw;
    }
    return raw || fallback || '処理に失敗しました';
}

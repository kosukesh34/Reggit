import { AppConstants } from '../constants';

type RequestOptions = {
    method?: string;
    body?: unknown;
    headers?: Record<string, string>;
};

type ApiSuccess<T = unknown> = { success: true; data: T };
type ApiFailure = { success: false; error: string };
export type ApiResult<T = unknown> = ApiSuccess<T> | ApiFailure;

export class ApiClient {
    baseHeaders: Record<string, string>;

    constructor() {
        this.baseHeaders = { ...AppConstants.HEADERS.JSON };
    }

    static get LINE_USER_ID_STORAGE_KEY(): string {
        return 'mobile_order_line_user_id';
    }

    static getLineUserIdStorageKey(projectKey?: string): string {
        const pk =
            projectKey
            || (typeof window !== 'undefined' ? window.__PROJECT_KEY__ : undefined)
            || (typeof document !== 'undefined'
                ? document.querySelector('meta[name="project-key"]')?.getAttribute('content') || ''
                : '')
            || 'default';
        return `${ApiClient.LINE_USER_ID_STORAGE_KEY}_${pk}`;
    }

    getLiffTokenLineUserId(): string | null {
        if (typeof window === 'undefined' || typeof window.liff === 'undefined') {
            return null;
        }
        try {
            if (typeof window.liff.isLoggedIn === 'function' && !window.liff.isLoggedIn()) {
                return null;
            }
            const decoded =
                typeof window.liff.getDecodedIDToken === 'function'
                    ? window.liff.getDecodedIDToken()
                    : null;
            const sub = decoded && decoded.sub ? String(decoded.sub).trim() : '';
            return /^U[0-9a-f]{32}$/i.test(sub) ? sub : null;
        } catch (_e) {
            return null;
        }
    }

    debugLog(eventName: string, payload?: Record<string, unknown>): void {
        if (typeof window !== 'undefined' && typeof window.__clientDebugLog === 'function') {
            try {
                window.__clientDebugLog(eventName, payload || {});
            } catch (_e) {
                // ignore
            }
        }
    }

    resolveLineUserId(): string | null {
        if (typeof window === 'undefined') {
            return null;
        }

        const liffTokenId = this.getLiffTokenLineUserId();
        if (liffTokenId) {
            if (window.__LINE_USER_ID__ && window.__LINE_USER_ID__ !== liffTokenId) {
                this.debugLog('line_user_id_mismatch_cleared', {
                    previous_tail: String(window.__LINE_USER_ID__).slice(-4),
                    token_tail: liffTokenId.slice(-4),
                });
            }
            window.__LINE_USER_ID__ = liffTokenId;
            this.persistLineUserId(liffTokenId);
            return liffTokenId;
        }

        if (window.__LINE_USER_ID__) {
            const current = String(window.__LINE_USER_ID__).trim();
            if (/^U[0-9a-f]{32}$/i.test(current)) {
                this.persistLineUserId(current);
                return current;
            }
        }

        if (window.__IS_LIFF__) {
            return null;
        }

        const cached = this.getPersistedLineUserId();
        if (cached) {
            window.__LINE_USER_ID__ = cached;
            return cached;
        }

        return null;
    }

    persistLineUserId(lineUserId: string): void {
        if (typeof window === 'undefined' || !window.localStorage) {
            return;
        }
        const normalized = String(lineUserId || '').trim();
        if (!/^U[0-9a-f]{32}$/i.test(normalized)) {
            return;
        }
        try {
            window.localStorage.setItem(ApiClient.getLineUserIdStorageKey(), normalized);
            window.localStorage.removeItem(ApiClient.LINE_USER_ID_STORAGE_KEY);
        } catch (_e) {
            // ignore
        }
    }

    clearPersistedLineUserId(): void {
        if (typeof window === 'undefined' || !window.localStorage) {
            return;
        }
        try {
            window.localStorage.removeItem(ApiClient.getLineUserIdStorageKey());
            window.localStorage.removeItem(ApiClient.LINE_USER_ID_STORAGE_KEY);
        } catch (_e) {
            // ignore
        }
    }

    getPersistedLineUserId(): string | null {
        if (typeof window === 'undefined' || !window.localStorage) {
            return null;
        }
        try {
            const scoped = String(window.localStorage.getItem(ApiClient.getLineUserIdStorageKey()) || '').trim();
            if (/^U[0-9a-f]{32}$/i.test(scoped)) {
                return scoped;
            }
            const legacy = String(window.localStorage.getItem(ApiClient.LINE_USER_ID_STORAGE_KEY) || '').trim();
            return /^U[0-9a-f]{32}$/i.test(legacy) ? legacy : null;
        } catch (_e) {
            return null;
        }
    }

    async request(endpoint: string, options: RequestOptions = {}): Promise<ApiResult> {
        const {
            method = AppConstants.HTTP_METHODS.GET,
            body = null,
            headers = {},
        } = options;

        const requestHeaders: Record<string, string> = {
            ...this.baseHeaders,
            ...headers,
        };
        const projectKey =
            typeof window !== 'undefined' && window.__PROJECT_KEY__
                ? window.__PROJECT_KEY__
                : typeof document !== 'undefined'
                    ? document.querySelector('meta[name="project-key"]')?.getAttribute('content')
                    : null;
        if (projectKey) {
            requestHeaders['X-Project-Key'] = projectKey;
        }
        if (typeof window !== 'undefined' && window.__API_TOKEN__) {
            requestHeaders.Authorization = `Bearer ${window.__API_TOKEN__}`;
        }
        const lineUserId = this.resolveLineUserId();
        if (lineUserId) {
            requestHeaders['X-Line-User-Id'] = lineUserId;
        }
        this.debugLog('api_identity', {
            endpoint,
            method,
            has_line_user_id: !!lineUserId,
            line_user_tail: lineUserId ? String(lineUserId).slice(-4) : '',
            has_device_id: !!(
                typeof window !== 'undefined'
                && window.__MOBILE_DEVICE_INFO__
                && window.__MOBILE_DEVICE_INFO__.device_identifier
            ),
        });
        if (typeof window !== 'undefined' && window.__MOBILE_DEVICE_INFO__) {
            const deviceIdentifier = String(window.__MOBILE_DEVICE_INFO__.device_identifier || '').trim();
            if (deviceIdentifier) {
                requestHeaders['X-Device-Identifier'] = deviceIdentifier;
            }
        }
        requestHeaders['ngrok-skip-browser-warning'] = 'true';

        const config: RequestInit = {
            method,
            headers: requestHeaders,
            credentials: 'same-origin',
        };

        if (body) {
            config.body = typeof body === 'string' ? body : JSON.stringify(body);
        }

        const url =
            typeof window !== 'undefined'
            && window.location
            && endpoint.startsWith('/')
            && !endpoint.startsWith('//')
                ? window.location.origin + endpoint
                : endpoint;

        try {
            const response = await fetch(url, config);

            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes(AppConstants.CONTENT_TYPES.JSON)) {
                const text = await response.text();
                this.debugLog('api_non_json', {
                    endpoint,
                    method,
                    status: response.status,
                    contentType: contentType || '',
                    bodyHead: text ? String(text).slice(0, 300) : '',
                });
                throw new Error(`Invalid response format. Expected JSON, got: ${contentType}`);
            }

            const data = await response.json();

            if (!response.ok) {
                this.debugLog('api_http_error', {
                    endpoint,
                    method,
                    status: response.status,
                    message: data && (data.error || data.message) ? String(data.error || data.message) : '',
                });
                throw new Error(data.error || data.message || `HTTP error! status: ${response.status}`);
            }

            return { success: true, data };
        } catch (error) {
            const message = error instanceof Error ? error.message : 'An error occurred';
            this.debugLog('api_request_failed', {
                endpoint,
                method,
                message: message || 'unknown',
            });
            // eslint-disable-next-line no-console
            console.error(`API request failed: ${endpoint}`, error);
            return { success: false, error: message || 'An error occurred' };
        }
    }

    async get(endpoint: string, options: RequestOptions = {}): Promise<ApiResult> {
        return this.request(endpoint, { ...options, method: AppConstants.HTTP_METHODS.GET });
    }

    async post(endpoint: string, body: unknown, options: RequestOptions = {}): Promise<ApiResult> {
        return this.request(endpoint, { ...options, method: AppConstants.HTTP_METHODS.POST, body });
    }

    async put(endpoint: string, body: unknown, options: RequestOptions = {}): Promise<ApiResult> {
        return this.request(endpoint, { ...options, method: AppConstants.HTTP_METHODS.PUT, body });
    }
}

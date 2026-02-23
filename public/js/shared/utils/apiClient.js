class ApiClient {
    constructor() {
        this.baseHeaders = AppConstants.HEADERS.JSON;
    }

    resolveLineUserId() {
        if (typeof window === 'undefined') return null;
        if (window.__LINE_USER_ID__) return window.__LINE_USER_ID__;

        if (typeof window.liff !== 'undefined') {
            try {
                if (typeof liff.isLoggedIn === 'function' && liff.isLoggedIn()) {
                    const decoded = typeof liff.getDecodedIDToken === 'function'
                        ? liff.getDecodedIDToken()
                        : null;
                    const sub = decoded && decoded.sub ? String(decoded.sub) : null;
                    if (sub) {
                        window.__LINE_USER_ID__ = sub;
                        return sub;
                    }
                }
            } catch (e) {
                // LIFF未初期化中などの例外は無視
            }
        }

        return null;
    }

    async request(endpoint, options = {}) {
        const {
            method = AppConstants.HTTP_METHODS.GET,
            body = null,
            headers = {},
        } = options;

        const requestHeaders = {
            ...this.baseHeaders,
            ...headers,
        };
        const projectKey = (typeof window !== 'undefined' && window.__PROJECT_KEY__)
            ? window.__PROJECT_KEY__
            : (typeof document !== 'undefined'
                ? document.querySelector('meta[name="project-key"]')?.getAttribute('content')
                : null);
        if (projectKey) {
            requestHeaders['X-Project-Key'] = projectKey;
        }
        if (typeof window !== 'undefined' && window.__API_TOKEN__) {
            requestHeaders['Authorization'] = `Bearer ${window.__API_TOKEN__}`;
        }
        const lineUserId = this.resolveLineUserId();
        if (lineUserId) {
            requestHeaders['X-Line-User-Id'] = lineUserId;
        }

        const config = {
            method,
            headers: requestHeaders,
        };

        if (body) {
            config.body = typeof body === 'string' ? body : JSON.stringify(body);
        }

        try {
            const response = await fetch(endpoint, config);
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes(AppConstants.CONTENT_TYPES.JSON)) {
                const text = await response.text();
                throw new Error(`Invalid response format. Expected JSON, got: ${contentType}`);
            }

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || data.message || `HTTP error! status: ${response.status}`);
            }

            return { success: true, data };
        } catch (error) {
            console.error(`API request failed: ${endpoint}`, error);
            return { success: false, error: error.message || 'An error occurred' };
        }
    }

    async get(endpoint, options = {}) {
        return this.request(endpoint, { ...options, method: AppConstants.HTTP_METHODS.GET });
    }

    async post(endpoint, body, options = {}) {
        return this.request(endpoint, { ...options, method: AppConstants.HTTP_METHODS.POST, body });
    }

    async put(endpoint, body, options = {}) {
        return this.request(endpoint, { ...options, method: AppConstants.HTTP_METHODS.PUT, body });
    }
}

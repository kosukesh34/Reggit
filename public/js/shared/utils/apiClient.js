class ApiClient {
    constructor() {
        this.baseHeaders = AppConstants.HEADERS.JSON;
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
        if (typeof window !== 'undefined' && window.__LINE_USER_ID__) {
            requestHeaders['X-Line-User-Id'] = window.__LINE_USER_ID__;
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

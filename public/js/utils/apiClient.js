class ApiClient {
    constructor() {
        this.baseHeaders = AppConstants.HEADERS.JSON;
    }

    async request(endpoint, options = {}) {
        const {
            method = AppConstants.HTTP_METHODS.GET,
            body = null,
            headers = {},
            userId = null,
        } = options;

        const requestHeaders = {
            ...this.baseHeaders,
            ...headers,
        };

        if (userId) {
            requestHeaders['X-Line-User-Id'] = userId;
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
}


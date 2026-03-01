class DeviceManager {
    constructor(apiClient) {
        this.apiClient = apiClient;
        this.lastPayloadHash = '';
    }

    isMobileApp() {
        return typeof window !== 'undefined' && window.__MOBILE_APP_MODE__ === true;
    }

    init() {
        this.installNativeBridge();
        if (!this.isMobileApp()) {
            return;
        }
        this.registerDevice();
        if (typeof window !== 'undefined' && window.__MOBILE_PENDING_PUSH_TOKEN__) {
            this.updatePushToken(window.__MOBILE_PENDING_PUSH_TOKEN__);
        }
        // ネイティブの注入が onNavigationFinished で遅れる場合に備え、遅延リトライ
        this._scheduleRegisterRetries();
        this._listenForNativeInjection();
    }

    _scheduleRegisterRetries() {
        if (typeof window === 'undefined' || this._registerRetriesScheduled) return;
        this._registerRetriesScheduled = true;
        [300, 800].forEach((delayMs) => {
            setTimeout(() => {
                if (this.isMobileApp() && this.getPayload()) {
                    this.registerDevice();
                }
            }, delayMs);
        });
    }

    _listenForNativeInjection() {
        if (typeof window === 'undefined') return;
        document.addEventListener('mobile-device-info-injected', () => {
            if (this.isMobileApp()) {
                this.registerDevice();
            }
        });
    }

    installNativeBridge() {
        if (typeof window === 'undefined') return;

        window.__nativeSetPushToken = (token) => {
            const normalizedToken = token == null ? '' : String(token).trim();
            window.__MOBILE_PENDING_PUSH_TOKEN__ = normalizedToken || null;
            if (!window.__MOBILE_DEVICE_INFO__ || typeof window.__MOBILE_DEVICE_INFO__ !== 'object') {
                window.__MOBILE_DEVICE_INFO__ = {};
            }
            window.__MOBILE_DEVICE_INFO__.push_token = normalizedToken || null;
            this.updatePushToken(normalizedToken || null);
        };
    }

    updatePushToken(pushToken) {
        if (typeof window === 'undefined') return;
        if (!window.__MOBILE_DEVICE_INFO__ || typeof window.__MOBILE_DEVICE_INFO__ !== 'object') {
            window.__MOBILE_DEVICE_INFO__ = {};
        }
        window.__MOBILE_DEVICE_INFO__.push_token = pushToken;
        this.registerDevice();
    }

    getPayload() {
        if (typeof window === 'undefined') return null;
        const info = window.__MOBILE_DEVICE_INFO__;
        if (!info || typeof info !== 'object') return null;

        const deviceIdentifier = String(info.device_identifier || '').trim();
        if (!deviceIdentifier) return null;

        const platformRaw = String(info.platform || 'unknown').toLowerCase();
        const platform = ['ios', 'android', 'web'].indexOf(platformRaw) >= 0 ? platformRaw : 'unknown';

        return {
            platform: platform,
            device_identifier: deviceIdentifier,
            push_token: info.push_token ? String(info.push_token) : null,
            os_version: info.os_version ? String(info.os_version) : null,
            app_version: info.app_version ? String(info.app_version) : null,
        };
    }

    registerDevice() {
        const payload = this.getPayload();
        if (!payload) return;

        const payloadHash = JSON.stringify(payload);
        if (payloadHash === this.lastPayloadHash) {
            return;
        }
        this.lastPayloadHash = payloadHash;

        this.apiClient.post(
            AppConstants.API_ENDPOINTS.DEVICE_REGISTER,
            payload,
            { headers: { 'X-Device-Identifier': payload.device_identifier } }
        ).then((result) => {
            if (!result || result.success !== true) {
                this.lastPayloadHash = '';
            }
        }).catch(() => {
            this.lastPayloadHash = '';
        });
    }
}


class DeviceManager {
    constructor(apiClient) {
        this.apiClient = apiClient;
        this.lastPayloadHash = '';
    }

    isMobileApp() {
        return typeof window !== 'undefined' && window.__MOBILE_APP_MODE__ === true;
    }

    /**
     * ネイティブアプリ起動時、端末に紐づく会員（line_user_id）をAPIで取得し __LINE_USER_ID__ を復元する。
     * 毎回起動で会員情報が消えないようにする。Promise を返すので app.init で await する。
     */
    restoreSessionIfNeeded() {
        if (typeof window === 'undefined' || !this.isMobileApp()) {
            return Promise.resolve();
        }
        this.installNativeBridge();
        const deviceId = window.__MOBILE_DEVICE_INFO__ && String(window.__MOBILE_DEVICE_INFO__.device_identifier || '').trim();
        if (deviceId) {
            return this._fetchAndRestoreSession(deviceId);
        }
        return new Promise((resolve) => {
            const onInjected = () => {
                document.removeEventListener('mobile-device-info-injected', onInjected);
                clearTimeout(timeout);
                const id = window.__MOBILE_DEVICE_INFO__ && String(window.__MOBILE_DEVICE_INFO__.device_identifier || '').trim();
                if (id) {
                    this._fetchAndRestoreSession(id).then(resolve).catch(() => resolve());
                } else {
                    resolve();
                }
            };
            document.addEventListener('mobile-device-info-injected', onInjected);
            const timeout = setTimeout(() => {
                document.removeEventListener('mobile-device-info-injected', onInjected);
                resolve();
            }, 2500);
        });
    }

    _fetchAndRestoreSession(deviceIdentifier) {
        const apiBase = (typeof window !== 'undefined' && window.__API_BASE__) ? window.__API_BASE__ : '/api';
        const projectKey = (typeof window !== 'undefined' && window.__PROJECT_KEY__) ? window.__PROJECT_KEY__ : 'default';
        const url = apiBase + '/devices/session';
        const headers = {
            'X-Device-Identifier': deviceIdentifier,
            'X-Project-Key': projectKey,
            'Content-Type': 'application/json',
        };
        return fetch(url, { method: 'GET', headers, credentials: 'same-origin' })
            .then((res) => res.json())
            .then((data) => {
                const lineUserId = data && data.line_user_id && String(data.line_user_id).trim();
                if (lineUserId && /^U[0-9a-f]{32}$/i.test(lineUserId)) {
                    window.__LINE_USER_ID__ = lineUserId;
                    if (this.apiClient && typeof this.apiClient.persistLineUserId === 'function') {
                        this.apiClient.persistLineUserId(lineUserId);
                    }
                }
            })
            .catch(() => {});
    }

    init() {
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
        // 初回・ネイティブ注入直後のほか、OneSignal トークンが遅れて届く場合（例: Android 2s/5s）にも送信する
        [300, 800, 2500, 5500].forEach((delayMs) => {
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
            const normalizedToken = token === null || typeof token === 'undefined' ? '' : String(token).trim();
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

import { ApiClient } from '../../shared/utils/apiClient';
import { AppConstants } from '../../shared/constants';

type DevicePayload = {
    platform: string;
    device_identifier: string;
    push_token: string | null;
    os_version: string | null;
    app_version: string | null;
};

type MobileDeviceInfo = {
    device_identifier?: string;
    platform?: string;
    push_token?: string | null;
    os_version?: string | null;
    app_version?: string | null;
};

export class DeviceManager {
    apiClient: ApiClient;
    lastPayloadHash: string;
    _registerRetriesScheduled: boolean;

    constructor(apiClient: ApiClient) {
        this.apiClient = apiClient;
        this.lastPayloadHash = '';
        this._registerRetriesScheduled = false;
    }

    isMobileApp(): boolean {
        return typeof window !== 'undefined' && window.__MOBILE_APP_MODE__ === true;
    }

    restoreSessionIfNeeded(): Promise<void> {
        if (typeof window === 'undefined' || !this.isMobileApp()) {
            return Promise.resolve();
        }
        this.installNativeBridge();
        const deviceId =
            window.__MOBILE_DEVICE_INFO__
            && String(window.__MOBILE_DEVICE_INFO__.device_identifier || '').trim();
        if (deviceId) {
            return this._fetchAndRestoreSession(deviceId);
        }
        return new Promise((resolve) => {
            const onInjected = (): void => {
                document.removeEventListener('mobile-device-info-injected', onInjected);
                clearTimeout(timeout);
                const id =
                    window.__MOBILE_DEVICE_INFO__
                    && String(window.__MOBILE_DEVICE_INFO__.device_identifier || '').trim();
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

    _fetchAndRestoreSession(deviceIdentifier: string): Promise<void> {
        const apiBase =
            typeof window !== 'undefined' && window.__API_BASE__ ? window.__API_BASE__ : '/api';
        const projectKey =
            typeof window !== 'undefined' && window.__PROJECT_KEY__
                ? window.__PROJECT_KEY__
                : '';
        const url = apiBase + '/devices/session';
        const headers = {
            'X-Device-Identifier': deviceIdentifier,
            'X-Device-Token': ApiClient.getDeviceToken(deviceIdentifier),
            'X-Project-Key': projectKey,
            'Content-Type': 'application/json',
        };
        return fetch(url, { method: 'GET', headers, credentials: 'same-origin' })
            .then((res) => res.json())
            .then((data: { line_user_id?: string }) => {
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

    init(): void {
        if (!this.isMobileApp()) {
            return;
        }
        this.registerDevice();
        if (typeof window !== 'undefined' && window.__MOBILE_PENDING_PUSH_TOKEN__) {
            this.updatePushToken(window.__MOBILE_PENDING_PUSH_TOKEN__);
        }
        this._scheduleRegisterRetries();
        this._listenForNativeInjection();
    }

    _scheduleRegisterRetries(): void {
        if (typeof window === 'undefined' || this._registerRetriesScheduled) {
            return;
        }
        this._registerRetriesScheduled = true;
        [300, 800, 2500, 5500].forEach((delayMs) => {
            setTimeout(() => {
                if (this.isMobileApp() && this.getPayload()) {
                    this.registerDevice();
                }
            }, delayMs);
        });
    }

    _listenForNativeInjection(): void {
        if (typeof window === 'undefined') {
            return;
        }
        document.addEventListener('mobile-device-info-injected', () => {
            if (this.isMobileApp()) {
                this.registerDevice();
            }
        });
    }

    installNativeBridge(): void {
        if (typeof window === 'undefined') {
            return;
        }

        window.__nativeSetPushToken = (token: string | null | undefined) => {
            const normalizedToken = token == null ? '' : String(token).trim();
            window.__MOBILE_PENDING_PUSH_TOKEN__ = normalizedToken || null;
            if (!window.__MOBILE_DEVICE_INFO__ || typeof window.__MOBILE_DEVICE_INFO__ !== 'object') {
                window.__MOBILE_DEVICE_INFO__ = {};
            }
            window.__MOBILE_DEVICE_INFO__.push_token = normalizedToken || null;
            this.updatePushToken(normalizedToken || null);
        };
    }

    updatePushToken(pushToken: string | null): void {
        if (typeof window === 'undefined') {
            return;
        }
        if (!window.__MOBILE_DEVICE_INFO__ || typeof window.__MOBILE_DEVICE_INFO__ !== 'object') {
            window.__MOBILE_DEVICE_INFO__ = {};
        }
        window.__MOBILE_DEVICE_INFO__.push_token = pushToken;
        this.registerDevice();
    }

    getPayload(): DevicePayload | null {
        if (typeof window === 'undefined') {
            return null;
        }
        const info = window.__MOBILE_DEVICE_INFO__ as MobileDeviceInfo | undefined;
        if (!info || typeof info !== 'object') {
            return null;
        }

        const deviceIdentifier = String(info.device_identifier || '').trim();
        if (!deviceIdentifier) {
            return null;
        }

        const platformRaw = String(info.platform || 'unknown').toLowerCase();
        const platform = ['ios', 'android', 'web'].indexOf(platformRaw) >= 0 ? platformRaw : 'unknown';

        return {
            platform,
            device_identifier: deviceIdentifier,
            push_token: info.push_token ? String(info.push_token) : null,
            os_version: info.os_version ? String(info.os_version) : null,
            app_version: info.app_version ? String(info.app_version) : null,
        };
    }

    registerDevice(): void {
        const payload = this.getPayload();
        if (!payload) {
            return;
        }

        const payloadHash = JSON.stringify(payload);
        if (payloadHash === this.lastPayloadHash) {
            return;
        }
        this.lastPayloadHash = payloadHash;

        this.apiClient
            .post(AppConstants.API_ENDPOINTS.DEVICE_REGISTER, payload, {
                headers: { 'X-Device-Identifier': payload.device_identifier },
            })
            .then((result) => {
                if (!result || result.success !== true) {
                    this.lastPayloadHash = '';
                } else {
                    const data = result.data as { device_token?: string };
                    if (typeof data.device_token === 'string') {
                        ApiClient.saveDeviceToken(payload.device_identifier, data.device_token);
                    }
                }
            })
            .catch(() => {
                this.lastPayloadHash = '';
            });
    }
}

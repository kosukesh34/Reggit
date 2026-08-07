/** LIFF / ネイティブアプリの identity 準備 */

function isMobileAppMode(): boolean {
    return typeof window !== 'undefined' && window.__MOBILE_APP_MODE__ === true;
}

async function waitForNativeDeviceInfo(maxWaitMs: number): Promise<void> {
    if (typeof window === 'undefined' || !isMobileAppMode()) {
        return;
    }
    const start = Date.now();
    while (Date.now() - start < maxWaitMs) {
        const deviceId = window.__MOBILE_DEVICE_INFO__
            && String(window.__MOBILE_DEVICE_INFO__.device_identifier || '').trim();
        if (deviceId) {
            return;
        }
        await new Promise((resolve) => setTimeout(resolve, 50));
    }
}

/**
 * API 呼び出し前の identity 準備。
 * @returns true = API へ進める / false = サンプル表示へフォールバック
 */
export async function ensureLiffIdentityForLoad(options?: {
    waitNativeDevice?: boolean;
}): Promise<boolean> {
    if (isMobileAppMode()) {
        if (options?.waitNativeDevice === true) {
            await waitForNativeDeviceInfo(2500);
        }
        return true;
    }

    if (typeof window !== 'undefined' && window.__IS_LIFF__) {
        return window.__LINE_USER_ID__ !== null && window.__LINE_USER_ID__ !== undefined && window.__LINE_USER_ID__ !== '';
    }

    return true;
}

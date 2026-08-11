/** LIFF / ネイティブアプリの identity 準備（Manager 間で共有） */

declare const MemberManager: {
    isMobileAppMode: () => boolean;
};

type MemberManagerLike = {
    ensureIdentityReady?: (timeoutMs: number) => Promise<void>;
    resolveLineUserIdForClient?: () => Promise<unknown>;
    hasAuthenticatedIdentity?: () => boolean;
};

function getMemberManager(): MemberManagerLike | null {
    if (typeof window === 'undefined' || window.app == null) {
        return null;
    }
    const app = window.app as { memberManager?: MemberManagerLike };
    return app.memberManager ?? null;
}

async function waitForNativeDeviceInfo(maxWaitMs: number): Promise<void> {
    if (typeof window === 'undefined'
        || typeof MemberManager === 'undefined'
        || !MemberManager.isMobileAppMode()) {
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
    const memberManager = getMemberManager();
    if (memberManager === null) {
        return true;
    }

    if (typeof MemberManager !== 'undefined' && MemberManager.isMobileAppMode()) {
        if (options?.waitNativeDevice === true) {
            await waitForNativeDeviceInfo(2500);
        }
        if (typeof memberManager.ensureIdentityReady === 'function') {
            await memberManager.ensureIdentityReady(2500);
        }
        return true;
    }

    if (typeof memberManager.ensureIdentityReady === 'function') {
        await memberManager.ensureIdentityReady(2500);
    } else if (typeof memberManager.resolveLineUserIdForClient === 'function') {
        await memberManager.resolveLineUserIdForClient();
    }

    if (typeof window !== 'undefined' && window.__IS_LIFF__) {
        return !!window.__LINE_USER_ID__;
    }

    if (typeof memberManager.hasAuthenticatedIdentity === 'function') {
        return memberManager.hasAuthenticatedIdentity();
    }
    return !!(typeof window !== 'undefined' && window.__LINE_USER_ID__);
}

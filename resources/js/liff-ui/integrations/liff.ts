import { ApiClient } from '../../shared/utils/apiClient';

/** Reggit 既存の LIFF 初期化に寄せる。SDK 二重読込はしない。 */
export async function initializeLiff(): Promise<typeof window.liff | null> {
  if (typeof window === 'undefined') {
    return null;
  }
  if (window.liff && typeof window.liff.isLoggedIn === 'function') {
    return window.liff;
  }
  return null;
}

export async function getLiffIdentity() {
  const liff = await initializeLiff();
  const api = new ApiClient();
  const lineUserId = api.resolveLineUserId();
  if (liff && typeof liff.isLoggedIn === 'function' && liff.isLoggedIn()) {
    try {
      const profile = typeof liff.getProfile === 'function' ? await liff.getProfile() : undefined;
      const accessToken = typeof liff.getAccessToken === 'function' ? liff.getAccessToken() : null;
      return {
        accessToken: accessToken ?? undefined,
        profile,
        inClient: typeof liff.isInClient === 'function' ? liff.isInClient() : false,
        lineUserId: lineUserId ?? profile?.userId,
      };
    } catch {
      return { accessToken: undefined, profile: undefined, inClient: false, lineUserId };
    }
  }
  return {
    accessToken: undefined,
    profile: undefined,
    inClient: false,
    lineUserId,
  };
}

/**
 * アプリ全体の会員登録ポリシー。
 * `_global.require_membership !== false` のとき必須（デフォルト true = 既存挙動）。
 */
export function isMembershipRequired(): boolean {
    const settings =
        typeof window !== 'undefined' && window.__LIFF_TAB_SETTINGS__
            ? window.__LIFF_TAB_SETTINGS__
            : null;
    const global = settings && typeof settings._global === 'object' && settings._global !== null
        ? settings._global
        : null;
    if (global === null) {
        return true;
    }
    return global.require_membership !== false;
}

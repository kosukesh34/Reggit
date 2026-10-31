import { AppConstants } from '../../shared/constants';
import {
    buildLiffEntryUrl as policyBuildLiffEntryUrl,
    normalizeExternalUrl as policyNormalizeExternalUrl,
    resolveApiUrl as policyResolveApiUrl,
    resolveAppOrigin as policyResolveAppOrigin,
    resolveGoogleWalletOpenUrl as policyResolveGoogleWalletOpenUrl,
} from './walletUrlPolicy';

/** 端末・LIFF・ネイティブアプリの判定と、Wallet 追加に使う URL の解決。 */

declare const liff: any;

export function resolveLiffOs() {
    if (typeof window !== 'undefined' && window.__LIFF_OS__) {
        return window.__LIFF_OS__;
    }
    if (typeof window !== 'undefined' && window.__IS_LIFF__ && typeof liff !== 'undefined' && typeof liff.getOS === 'function') {
        try {
            const os = liff.getOS();
            if (os === 'ios' || os === 'android') {
                window.__LIFF_OS__ = os;
                return os;
            }
        } catch (e) { /* ignore */ }
    }
    return null;
}

export function isIOS() {
    if (resolveLiffOs() === 'ios') return true;
    if (typeof navigator === 'undefined') return false;
    return /iPad|iPhone|iPod/.test(navigator.userAgent) ||
        (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
}

export function isAndroid() {
    if (resolveLiffOs() === 'android') return true;
    if (typeof navigator === 'undefined') return false;
    return /Android/.test(navigator.userAgent);
}

export function isWalletPassEnabled() {
    return typeof window !== 'undefined' && window.__WALLET_PASS_ENABLED__ === true;
}

export function isGoogleWalletEnabled() {
    return typeof window !== 'undefined' && window.__GOOGLE_WALLET_ENABLED__ === true;
}

export function isMobileAppMode() {
    return typeof window !== 'undefined' && window.__MOBILE_APP_MODE__ === true;
}

/** ネイティブアプリ時は __MOBILE_DEVICE_INFO__.platform を優先。ブラウザ時は null。 */
export function getNativePlatform() {
    if (typeof window === 'undefined' || !window.__MOBILE_APP_MODE__ || !window.__MOBILE_DEVICE_INFO__) return null;
    const p = window.__MOBILE_DEVICE_INFO__.platform;
    return (p === 'ios' || p === 'android') ? p : null;
}

/**
 * Wallet 追加用中間ページの return_url と return_target を返す。
 * LINE の場合は liff.line.me 形式、ネイティブアプリの場合はアプリスキームを返す。
 * @returns {{ returnUrl: string, returnTarget: 'line'|'app'|'web' }}
 */
export function getWalletReturnParams() {
    if (typeof window !== 'undefined' && window.__IS_LIFF__ === true) {
        const projectKey = (typeof window !== 'undefined' && window.__PROJECT_KEY__)
            ? String(window.__PROJECT_KEY__)
            : '';
        const liffUrl = buildLiffEntryUrl(projectKey);
        if (liffUrl) {
            return { returnUrl: liffUrl, returnTarget: 'line' };
        }
        const currentHref = window.location && window.location.href ? window.location.href : '';
        return { returnUrl: currentHref, returnTarget: 'line' };
    }
    if (isMobileAppMode()) {
        const scheme = (typeof AppConstants !== 'undefined' && AppConstants.WALLET_RETURN_APP_SCHEME)
            ? AppConstants.WALLET_RETURN_APP_SCHEME
            : 'reggit://';
        return { returnUrl: scheme, returnTarget: 'app' };
    }
    const currentHref = typeof window !== 'undefined' && window.location && window.location.href ? window.location.href : '';
    return { returnUrl: currentHref, returnTarget: 'web' };
}

export function getLineLiffIdFromMeta() {
    if (typeof document === 'undefined') return '';
    const meta = document.querySelector('meta[name="line-liff-id"]');
    const metaEl = meta as HTMLMetaElement | null;
    return metaEl && metaEl.content ? String(metaEl.content).trim() : '';
}

/** Wallet 追加後に LINE アプリへ戻るための LIFF エントリ URL */
export function buildLiffEntryUrl(projectKey) {
    return policyBuildLiffEntryUrl(getLineLiffIdFromMeta(), projectKey);
}

/** LIFF / WebView でも使えるアプリのオリジン（https://example.com） */
export function resolveAppOrigin() {
    const apiBase = (typeof window !== 'undefined' && window.__API_BASE__) ? window.__API_BASE__ : '/api';
    const location = typeof window !== 'undefined' ? window.location : null;
    return policyResolveAppOrigin(location, apiBase);
}

/** API パス（/api/...）を絶対 URL にする */
export function resolveApiUrl(path) {
    return policyResolveApiUrl(path, resolveAppOrigin());
}

/** LIFF / Safari 向けに https を優先（ngrok 等で http が混ざるのを防ぐ） */
export function normalizeExternalUrl(url) {
    const baseHref = typeof window !== 'undefined' ? window.location.href : undefined;
    return policyNormalizeExternalUrl(url, baseHref);
}

/**
 * Google Save URL を外部ブラウザで開く URL に正規化する。
 * サーバー短縮 URL（/google-wallet/save/…）と pay.google.com 直リンクの両方に対応。
 */
export function resolveGoogleWalletOpenUrl(issueUrl) {
    const baseHref = typeof window !== 'undefined' ? window.location.href : undefined;
    return policyResolveGoogleWalletOpenUrl(issueUrl, baseHref);
}

export function isDesignerMemberPreview() {
    if (typeof window === 'undefined') return false;
    const params = new URLSearchParams(window.location.search || '');
    if (params.get('designer_live_preview') !== '1') return false;
    const source = params.get('designer_source');
    return source === 'member' || source === 'member_card' || source === 'profile';
}

/** Apple Wallet ボタンを表示すべきか（iOS またはネイティブ iOS アプリかつ有効時） */
export function shouldShowAppleWallet() {
    if (!isWalletPassEnabled()) return false;
    if (isDesignerMemberPreview()) return true;
    const nativePlatform = getNativePlatform();
    if (nativePlatform === 'ios') return true;
    if (nativePlatform === 'android') return false;
    return isIOS();
}

/** Google Wallet ボタンを表示すべきか（Android またはネイティブ Android アプリかつ有効時） */
export function shouldShowGoogleWallet() {
    if (!isGoogleWalletEnabled()) return false;
    if (isDesignerMemberPreview()) return true;
    const nativePlatform = getNativePlatform();
    if (nativePlatform === 'android') return true;
    if (nativePlatform === 'ios') return false;
    return isAndroid();
}

/** ネイティブ iOS で PassKit 追加が使えるか */
export function canUseNativeAppleWalletPass() {
    return isMobileAppMode()
        && getNativePlatform() === 'ios'
        && typeof window.__nativeAddAppleWalletPass__ === 'function';
}

/** ネイティブアプリで Safari / Chrome 経由の Wallet 追加が使えるか */
export function canUseNativeExternalWallet() {
    return isMobileAppMode()
        && typeof window.__nativeOpenExternalUrl__ === 'function';
}

export function isWalletAddUrl(url) {
    if (!url || typeof url !== 'string') return false;
    try {
        const parsed = new URL(url, typeof window !== 'undefined' && window.location ? window.location.href : undefined);
        return parsed.pathname === '/wallet-add';
    } catch (e) {
        return false;
    }
}

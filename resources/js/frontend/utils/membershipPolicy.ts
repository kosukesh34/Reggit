/**
 * アプリ全体の会員登録ポリシー。
 * window.__MEMBER_REGISTRATION_MODE__ を優先する。
 * - optional: ゲスト可能。ログイン時に会員を自動作成
 * - popup: 起動時にログイン／登録ポップアップ
 * - prompt: 起動時に登録画面（任意）
 * - force: 会員登録必須
 */
export type MemberRegistrationMode = 'optional' | 'popup' | 'prompt' | 'force';

export function getMemberRegistrationMode(): MemberRegistrationMode {
    const raw =
        typeof window !== 'undefined' && window.__MEMBER_REGISTRATION_MODE__
            ? String(window.__MEMBER_REGISTRATION_MODE__)
            : '';
    if (raw === 'prompt' || raw === 'force' || raw === 'optional' || raw === 'popup') {
        return raw;
    }
    return 'optional';
}

export function syncMemberRegistrationModeFromPayload(payload: Record<string, unknown> | null | undefined): void {
    if (typeof window === 'undefined' || payload === null || payload === undefined) {
        return;
    }
    const raw = payload.member_registration_mode;
    if (raw === 'prompt' || raw === 'force' || raw === 'optional' || raw === 'popup') {
        window.__MEMBER_REGISTRATION_MODE__ = raw;
    }
}

export function shouldShowRegistrationForm(): boolean {
    const mode = getMemberRegistrationMode();
    if (mode === 'force') {
        return true;
    }
    if (mode === 'prompt') {
        return !isRegistrationFormDismissed();
    }
    return false;
}

export function shouldShowStartupLoginPopup(): boolean {
    return getMemberRegistrationMode() === 'popup' && !isRegistrationFormDismissed();
}

export function shouldAutoCreateMember(): boolean {
    return getMemberRegistrationMode() === 'optional';
}

export function isRegistrationFormDismissed(): boolean {
    if (typeof window === 'undefined') {
        return false;
    }
    try {
        return window.sessionStorage.getItem('member_registration_prompt_dismissed') === '1';
    } catch (e) {
        return false;
    }
}

export function isMembershipRequired(): boolean {
    return getMemberRegistrationMode() === 'force';
}

export function getGuestRegisterMessage(): { title: string; subtitle: string; buttonLabel: string; buttonClass: string } {
    const mode = getMemberRegistrationMode();
    if (mode === 'force') {
        return {
            title: '会員パスの発行が必要です',
            subtitle: '発行すると、アプリの機能をご利用いただけます。',
            buttonLabel: '会員パスを発行',
            buttonClass: 'btn-primary',
        };
    }
    if (mode === 'prompt' || mode === 'popup') {
        return {
            title: '会員パスを発行できます',
            subtitle: '発行すると、ポイントやクーポンを店舗で使えます。',
            buttonLabel: '会員パスを発行',
            buttonClass: 'btn-primary',
        };
    }
    return {
        title: 'ゲストで閲覧中',
        subtitle: '会員パスを発行すると、ポイントやクーポンを店舗で使えます。',
        buttonLabel: '任意で発行',
        buttonClass: 'btn-secondary',
    };
}

/**
 * アプリ全体の会員登録ポリシー。
 * window.__MEMBER_REGISTRATION_MODE__ を優先する。
 * - optional: 未会員でも利用可
 * - prompt: 登録を促すが閉じられる
 * - force: 会員登録必須
 */
export type MemberRegistrationMode = 'optional' | 'prompt' | 'force';

export function getMemberRegistrationMode(): MemberRegistrationMode {
    const raw =
        typeof window !== 'undefined' && window.__MEMBER_REGISTRATION_MODE__
            ? String(window.__MEMBER_REGISTRATION_MODE__)
            : '';
    if (raw === 'prompt' || raw === 'force' || raw === 'optional') {
        return raw;
    }
    return 'optional';
}

export function syncMemberRegistrationModeFromPayload(payload: Record<string, unknown> | null | undefined): void {
    if (typeof window === 'undefined' || payload === null || payload === undefined) {
        return;
    }
    const raw = payload.member_registration_mode;
    if (raw === 'prompt' || raw === 'force' || raw === 'optional') {
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
            title: '会員登録が必要です。',
            subtitle: '登録するとアプリのすべての機能をご利用いただけます。',
            buttonLabel: '会員登録する',
            buttonClass: 'btn-primary',
        };
    }
    if (mode === 'prompt') {
        return {
            title: '会員登録をおすすめします。',
            subtitle: '登録するとポイント・クーポン・予約履歴などがご利用いただけます。',
            buttonLabel: '会員登録する',
            buttonClass: 'btn-primary',
        };
    }
    return {
        title: '会員登録は任意です。',
        subtitle: '登録するとポイント・クーポン・予約履歴を利用できます。',
        buttonLabel: '任意で会員登録',
        buttonClass: 'btn-secondary',
    };
}

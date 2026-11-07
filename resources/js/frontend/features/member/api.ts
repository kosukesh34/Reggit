import { AppConstants } from '../../../shared/constants';

export type MemberPayload = Record<string, unknown> & {
    is_member?: boolean;
    is_sample_preview?: boolean;
    show_member_card_for_guest?: boolean;
    points?: number;
    member?: { member_number?: string; points?: number; user?: { name?: string } } | null;
    user?: { name?: string } | null;
    current_rank_label?: string;
    current_rank_icon?: string | null;
    current_rank_color?: string | null;
    next_rank_label?: string | null;
    points_to_next_rank?: number | null;
    days_to_next_rank?: number | null;
    days_required_for_next_rank?: number | null;
    rank_up_history?: Array<{ from_rank_label?: string; to_rank_label?: string; achieved_at?: string }>;
    points_expiry?: { expires_at_label?: string; points_expiring?: number } | null;
    current_rank?: string;
    show_points_display?: boolean;
    show_rank_progress?: boolean;
    rank_basis?: string;
    rank_aggregation_type?: string;
    rank_metric?: number;
    member_card_design?: string;
    member_card_logo_url?: string | null;
    member_card_display_type?: string;
    member_card_code_type?: string;
    profile_field_settings?: unknown[];
    profile?: Record<string, unknown>;
    profile_image_enabled?: boolean;
};

type MemberManagerLike = {
    memberData?: MemberPayload | null;
    _registerFullScreen?: boolean;
    _forcedRegister?: boolean;
    ensureIdentityReady?: (ms?: number) => Promise<unknown>;
    getMemberData?: () => MemberPayload | null;
    fetchMemberContext?: (options?: { force?: boolean }) => Promise<MemberPayload | null>;
    canAutoRegister?: () => boolean;
    autoRegister?: () => Promise<void>;
    navigateToRegisterPage?: () => void;
    openRegisterFullScreen?: (options?: { forced?: boolean; skipHistory?: boolean }) => Promise<void>;
    _setupAddToWallet?: () => void;
};

export function getMemberManager(): MemberManagerLike | undefined {
    return (window.app as { memberManager?: MemberManagerLike } | undefined)?.memberManager;
}

export function startLineLogin(): void {
    const liffSdk = typeof window !== 'undefined' ? window.liff : undefined;
    if (window.__IS_LIFF__ === true && liffSdk !== undefined && typeof liffSdk.login === 'function') {
        liffSdk.login();
        return;
    }
    openRegister(false);
}

export function openRegister(forced = false): void {
    if (typeof window !== 'undefined' && window.__REGGIT_LIFF_UI__ === true) {
        document.dispatchEvent(new CustomEvent('liff-ui-navigate', {
            detail: { path: '/membership' },
        }));
        window.setTimeout(() => {
            const mm = getMemberManager();
            if (mm !== undefined && typeof mm.openRegisterFullScreen === 'function') {
                void mm.openRegisterFullScreen({ forced, skipHistory: true });
            }
        }, 80);
        return;
    }
    const mm = getMemberManager();
    if (typeof window.app?.switchTab === 'function' && window.app.currentTab !== AppConstants.TABS.MEMBER) {
        window.app.switchTab(AppConstants.TABS.MEMBER);
    }
    if (mm !== undefined && typeof mm.navigateToRegisterPage === 'function') {
        mm.navigateToRegisterPage();
    }
}

// Wallet ボタンの表示判定は旧画面（MemberManager）と同じものを使う
export { shouldShowAppleWallet, shouldShowGoogleWallet } from '../../member/walletEnvironment';

export function walletPassUrl(): string {
    const projectKey = typeof window !== 'undefined' && window.__PROJECT_KEY__ ? window.__PROJECT_KEY__ : '';
    return `${AppConstants.API_ENDPOINTS.MEMBER_WALLET_PASS}?project_key=${encodeURIComponent(projectKey)}`;
}

/** Reuse the authenticated, prefetched LINE/native Wallet flow for the React card. */
export function setupWalletButtons(): void {
    getMemberManager()?._setupAddToWallet?.();
}

import { AppConstants } from '../../../shared/constants';
import { DomHelper } from '../../../shared/utils/domHelper';
import { isDemoDataMode } from '../../demo/demoMode';
import { buildBuiltinSampleMemberData } from '../../demo/sampleData';
import {
    getGuestRegisterMessage,
    getMemberRegistrationMode,
    isMembershipRequired,
    shouldShowRegistrationForm,
    syncMemberRegistrationModeFromPayload,
} from '../../utils/membershipPolicy';

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
    member_card_design?: string;
    member_card_logo_url?: string | null;
    member_card_display_type?: string;
    member_card_code_type?: string;
    profile_field_settings?: unknown[];
    profile?: Record<string, unknown>;
};

export type MemberLoadResult =
    | { kind: 'loading' }
    | { kind: 'login' }
    | { kind: 'guest_nocard' }
    | { kind: 'guest_prompt' }
    | { kind: 'register'; forced: boolean }
    | { kind: 'member'; data: MemberPayload }
    | { kind: 'sample'; data: MemberPayload }
    | { kind: 'error'; message: string };

type MemberManagerLike = {
    memberData?: MemberPayload | null;
    _registerFullScreen?: boolean;
    _forcedRegister?: boolean;
    _walletSummaryLast?: { pending_lottery_count?: number } | null;
    canUseMemberFeature?: () => boolean;
    ensureIdentityReady?: (ms?: number) => Promise<unknown>;
    resolveLineUserIdForClient?: () => Promise<unknown>;
    getMemberData?: () => MemberPayload | null;
    setMemberData?: (data: MemberPayload) => void;
    fetchMemberContext?: (options?: { force?: boolean }) => Promise<MemberPayload | null>;
    _fetchMemberContextWithIdentityRetry?: (options?: { force?: boolean }) => Promise<MemberPayload>;
    ensureGuestMemberContext?: () => Promise<unknown>;
    canAutoRegister?: () => boolean;
    autoRegister?: () => Promise<void>;
    navigateToRegisterPage?: () => void;
    openRegisterFullScreen?: (options?: { forced?: boolean; skipHistory?: boolean }) => Promise<void>;
    openProfileEdit?: () => void;
    closeMemberScreen?: () => void;
    closeRegisterFullScreen?: () => void;
    closeProfileEdit?: () => void;
    paintRegisterInto?: (container: HTMLElement, options?: { forced?: boolean }) => Promise<void>;
    paintProfileEditInto?: (container: HTMLElement) => Promise<void>;
    saveRegisterForm?: (e: Event) => void | Promise<void>;
    saveProfileEdit?: (e: Event) => void | Promise<void>;
    _setupPostalCodeLookup?: (container: HTMLElement) => void;
    _syncMemberScreenOverlay?: () => void;
    bindMemberCardEffects?: (container: HTMLElement, data: MemberPayload, codeId: string, isQR: boolean, isSheet: boolean) => void;
    refreshWalletSummaryOnLaunch?: () => void;
    preload?: (arg?: unknown) => void;
    _registerLiffIdentityRetry?: () => void;
    isForcedRegistrationActive?: () => boolean;
    isRegisterFullScreenActive?: () => boolean;
};

export function getMemberManager(): MemberManagerLike | undefined {
    return (window.app as { memberManager?: MemberManagerLike } | undefined)?.memberManager;
}

export function getSampleMemberData(): MemberPayload {
    const app = window.app as { getDesignerSampleMemberData?: () => MemberPayload } | undefined;
    const sample = typeof app?.getDesignerSampleMemberData === 'function'
        ? app.getDesignerSampleMemberData()
        : buildBuiltinSampleMemberData();
    return { ...sample, is_sample_preview: true };
}

export function guestRegisterCopy(): ReturnType<typeof getGuestRegisterMessage> {
    return getGuestRegisterMessage();
}

export function resolveCardDesign(data: MemberPayload, container: HTMLElement | null): {
    design: string;
    displayType: string;
    codeType: string;
    accent: string;
    bgStyle: string;
    logoUrl: string | null;
} {
    const availableDesigns = ['default', 'logo_top', 'logo_center', 'logo_header', 'compact', 'split_banner', 'minimal_line', 'emblem'];
    const block = container?.closest('[data-card-design]') as HTMLElement | null;
    const blockOverride = block ? String(block.getAttribute('data-card-design') || '') : '';
    let design = DomHelper.getLiffGlobalSetting('member_card_design', '')
        || String(data.member_card_design || 'default');
    if (blockOverride && availableDesigns.includes(blockOverride)) {
        design = blockOverride;
    }
    if (!availableDesigns.includes(design)) {
        design = 'default';
    }
    const displayType = DomHelper.getLiffGlobalSetting('member_card_display_type', '')
        || String(data.member_card_display_type || 'card');
    const codeType = DomHelper.getLiffGlobalSetting('member_card_code_type', '')
        || String(data.member_card_code_type || 'barcode');
    const accent = DomHelper.getLiffGlobalSetting('member_card_accent_color', '');
    const bgStyleRaw = DomHelper.getLiffGlobalSetting('member_card_bg_style', '') || 'gradient';
    const bgStyle = ['solid', 'gradient', 'image'].includes(bgStyleRaw) ? bgStyleRaw : 'gradient';
    return {
        design,
        displayType,
        codeType,
        accent,
        bgStyle,
        logoUrl: data.member_card_logo_url ? String(data.member_card_logo_url) : null,
    };
}

export async function loadMemberView(): Promise<MemberLoadResult> {
    const mm = getMemberManager();
    if (mm === undefined) {
        return { kind: 'error', message: '会員情報の読み込みに失敗しました' };
    }

    if (typeof window !== 'undefined' && window.__IS_DESIGNER_EMBED_PREVIEW__ === true) {
        const payload = window.__DESIGNER_PREVIEW_MEMBER_PAYLOAD__ as MemberPayload | undefined;
        if (payload && payload.is_member === true) {
            return { kind: 'member', data: payload };
        }
        return { kind: 'sample', data: getSampleMemberData() };
    }

    if (mm._registerFullScreen === true && !(mm.memberData && mm.memberData.is_member === true)) {
        return { kind: 'register', forced: mm._forcedRegister === true };
    }

    if (typeof window !== 'undefined' && window.__IS_LIFF__) {
        if (typeof mm.ensureIdentityReady === 'function') {
            await mm.ensureIdentityReady();
        }
    } else if (!window.__LINE_USER_ID__ && typeof mm.resolveLineUserIdForClient === 'function') {
        await mm.resolveLineUserIdForClient();
    }

    if (typeof mm.canUseMemberFeature === 'function' && mm.canUseMemberFeature() !== true) {
        if (typeof window !== 'undefined' && window.__IS_LIFF__) {
            if (typeof mm._registerLiffIdentityRetry === 'function') {
                mm._registerLiffIdentityRetry();
            }
            return { kind: 'loading' };
        }
        if (!isMembershipRequired()) {
            return { kind: 'sample', data: getSampleMemberData() };
        }
        return { kind: 'login' };
    }

    if (isDemoDataMode()) {
        return { kind: 'sample', data: getSampleMemberData() };
    }

    const cached = typeof mm.getMemberData === 'function' ? mm.getMemberData() : mm.memberData;
    if (cached && cached.is_member === true) {
        if (typeof mm.refreshWalletSummaryOnLaunch === 'function') {
            mm.refreshWalletSummaryOnLaunch();
        }
        if (typeof mm.preload === 'function') {
            mm.preload();
        }
        return { kind: 'member', data: cached };
    }

    try {
        const payload = typeof mm._fetchMemberContextWithIdentityRetry === 'function'
            ? await mm._fetchMemberContextWithIdentityRetry({ force: true })
            : (typeof mm.fetchMemberContext === 'function'
                ? await mm.fetchMemberContext({ force: true })
                : null);
        if (payload === null || payload === undefined) {
            return { kind: 'error', message: '会員情報の読み込みに失敗しました' };
        }
        syncMemberRegistrationModeFromPayload(payload);
        if (payload.is_member === true) {
            if (typeof mm.setMemberData === 'function') {
                mm.setMemberData(payload);
            }
            if (typeof mm.refreshWalletSummaryOnLaunch === 'function') {
                mm.refreshWalletSummaryOnLaunch();
            }
            if (typeof mm.preload === 'function') {
                mm.preload();
            }
            return { kind: 'member', data: payload };
        }

        if (shouldShowRegistrationForm()) {
            return { kind: 'register', forced: getMemberRegistrationMode() === 'force' };
        }
        if (payload.show_member_card_for_guest === false && isMembershipRequired()) {
            return { kind: 'guest_nocard' };
        }
        if (typeof mm.canAutoRegister === 'function' && mm.canAutoRegister() === true && typeof mm.autoRegister === 'function') {
            await mm.autoRegister();
            const after = typeof mm.getMemberData === 'function' ? mm.getMemberData() : null;
            if (after && after.is_member === true) {
                return { kind: 'member', data: after };
            }
        }
        if (!isMembershipRequired()) {
            return { kind: 'sample', data: getSampleMemberData() };
        }
        return { kind: 'guest_prompt' };
    } catch (error) {
        const message = error instanceof Error ? error.message : '会員情報の読み込みに失敗しました';
        if (cached && cached.is_member === true) {
            return { kind: 'member', data: cached };
        }
        return { kind: 'error', message };
    }
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
                return;
            }
            document.dispatchEvent(new CustomEvent('liff-member-open-register', { detail: { forced } }));
        }, 80);
        return;
    }
    const mm = getMemberManager();
    if (typeof window.app?.switchTab === 'function' && window.app.currentTab !== AppConstants.TABS.MEMBER) {
        window.app.switchTab(AppConstants.TABS.MEMBER);
    }
    if (mm !== undefined && typeof mm.navigateToRegisterPage === 'function') {
        mm.navigateToRegisterPage();
        return;
    }
    document.dispatchEvent(new CustomEvent('liff-member-open-register', { detail: { forced } }));
}

export function openProfileEdit(): void {
    if (typeof window !== 'undefined' && window.__REGGIT_LIFF_UI__ === true) {
        document.dispatchEvent(new CustomEvent('liff-ui-navigate', {
            detail: { path: '/membership' },
        }));
        window.setTimeout(() => {
            document.dispatchEvent(new CustomEvent('liff-member-open-edit'));
        }, 80);
        return;
    }
    document.dispatchEvent(new CustomEvent('liff-member-open-edit'));
}

export function shouldShowAppleWallet(): boolean {
    // MemberManager の static。インスタンス constructor 経由で参照する。
    const mgr = getMemberManager() as { constructor?: { shouldShowAppleWallet?: () => boolean } } | undefined;
    if (typeof mgr?.constructor?.shouldShowAppleWallet === 'function') {
        return mgr.constructor.shouldShowAppleWallet() === true;
    }
    return false;
}

export function shouldShowGoogleWallet(): boolean {
    const mgr = getMemberManager() as { constructor?: { shouldShowGoogleWallet?: () => boolean } } | undefined;
    if (typeof mgr?.constructor?.shouldShowGoogleWallet === 'function') {
        return mgr.constructor.shouldShowGoogleWallet() === true;
    }
    return false;
}

export function walletPassUrl(): string {
    const projectKey = typeof window !== 'undefined' && window.__PROJECT_KEY__ ? window.__PROJECT_KEY__ : '';
    return `${AppConstants.API_ENDPOINTS.MEMBERS_WALLET_PASS}?project_key=${encodeURIComponent(projectKey)}`;
}

export function pendingLotteryCount(): number {
    const mm = getMemberManager();
    return mm?._walletSummaryLast?.pending_lottery_count ?? 0;
}

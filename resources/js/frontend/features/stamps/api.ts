import { ApiClient } from '../../../shared/utils/apiClient';
import { AppConstants } from '../../../shared/constants';
import { DomHelper } from '../../../shared/utils/domHelper';
import { isDemoDataMode } from '../../demo/demoMode';
import { ensureLiffIdentityForLoad } from '../../demo/ensureIdentity';
import { buildBuiltinSampleStampCards } from '../../demo/sampleData';
import { isMembershipRequired } from '../../utils/membershipPolicy';

export type StampRewardMilestone = {
    stamp_count?: number | string;
    description?: string;
};

export type StampCard = {
    id?: number | string;
    current_stamps?: number;
    required_stamps?: number;
    completed_at?: string | null;
    layout_style?: string;
    image_url?: string | null;
    stamp_images?: Record<string, string> | null;
    stamp_empty_images?: Record<string, string> | null;
    member_stamp_valid_until?: string | null;
    valid_until?: string | null;
    reward_milestones?: StampRewardMilestone[];
    name?: string;
    reward_description?: string | null;
    is_sample_preview?: boolean;
    cycle_mode?: string;
    cycle_count?: number;
    is_archived?: boolean;
    next_stamp_card_name?: string | null;
};

export type StampsLoadResult =
    | { kind: 'demo'; cards: StampCard[]; defaultImage: string | null }
    | { kind: 'guest' }
    | { kind: 'member_required'; message: string }
    | { kind: 'ok'; cards: StampCard[]; defaultImage: string | null }
    | { kind: 'error'; message: string };

export type StampDesignSettings = {
    screenDesign: string;
    slotStyle: string;
    filledStyle: string;
    accentColor: string;
};

const STAMP_SCREEN_DESIGNS = ['grid_card', 'wallet', 'circle_board', 'minimal'];
const STAMP_SLOT_STYLES = ['circle', 'rounded', 'square'];
const STAMP_FILLED_STYLES = ['image', 'solid', 'icon'];

function client(): ApiClient {
    const app = window.app as { apiClient?: ApiClient } | undefined;
    return app?.apiClient ?? new ApiClient();
}

export function getStampDesignSettings(): StampDesignSettings {
    const block = document.querySelector('.builder-data-block[data-source="stamps"]') as HTMLElement | null;
    const readBlock = (attr: string): string => (block ? String(block.getAttribute(attr) || '') : '');
    const readGlobal = (key: string, fallback: string): string => DomHelper.getLiffGlobalSetting(key, fallback);

    const screenDesign = readBlock('data-stamp-design') || readGlobal('stamp_screen_design', 'grid_card');
    const slotStyle = readBlock('data-stamp-slot-style') || readGlobal('stamp_slot_style', 'circle');
    const filledStyle = readBlock('data-stamp-filled-style') || readGlobal('stamp_filled_style', 'image');
    const accentColor = readBlock('data-stamp-accent-color') || readGlobal('stamp_accent_color', '');

    return {
        screenDesign: STAMP_SCREEN_DESIGNS.includes(screenDesign) ? screenDesign : 'grid_card',
        slotStyle: STAMP_SLOT_STYLES.includes(slotStyle) ? slotStyle : 'circle',
        filledStyle: STAMP_FILLED_STYLES.includes(filledStyle) ? filledStyle : 'image',
        accentColor,
    };
}

function sampleCards(): StampCard[] {
    const app = window.app as { getDesignerSampleStampCards?: () => StampCard[] } | undefined;
    const cards = typeof app?.getDesignerSampleStampCards === 'function'
        ? app.getDesignerSampleStampCards()
        : buildBuiltinSampleStampCards();
    return cards.map((card) => ({ ...card, is_sample_preview: true }));
}

export async function loadStamps(): Promise<StampsLoadResult> {
    if (isDemoDataMode()) {
        return { kind: 'demo', cards: sampleCards(), defaultImage: null };
    }

    const canCallApi = await ensureLiffIdentityForLoad();
    if (!canCallApi) {
        if (isDemoDataMode() || !isMembershipRequired()) {
            return { kind: 'demo', cards: sampleCards(), defaultImage: null };
        }
        return { kind: 'guest' };
    }

    try {
        const result = await client().get(AppConstants.API_ENDPOINTS.STAMPS);
        if (result.success !== true) {
            return { kind: 'error', message: result.error || 'スタンプの読み込みに失敗しました' };
        }
        const data = result.data as {
            stamp_cards?: StampCard[];
            default_image_url?: string | null;
            message?: string | null;
        };
        const cards = data && data.stamp_cards ? data.stamp_cards : [];
        const defaultImage = data && data.default_image_url ? data.default_image_url : null;
        const message = data && data.message ? data.message : null;
        if ((!cards || cards.length === 0) && message) {
            if (isDemoDataMode() || !isMembershipRequired()) {
                return { kind: 'demo', cards: sampleCards(), defaultImage: null };
            }
            return { kind: 'member_required', message };
        }
        return {
            kind: 'ok',
            cards: cards.map((card, index) => ({ ...card, id: card.id ?? index })),
            defaultImage,
        };
    } catch (error) {
        const message = error instanceof Error ? error.message : 'スタンプの読み込みに失敗しました';
        return { kind: 'error', message };
    }
}

export function formatValidUntil(dateLike?: string | null): string | null {
    if (!dateLike) {
        return null;
    }
    const d = new Date(dateLike);
    if (Number.isNaN(d.getTime())) {
        return null;
    }
    return d.toLocaleDateString('ja-JP', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    });
}

export function openStampDetail(id: number | string, cards: StampCard[], defaultImage: string | null): void {
    const mgr = window.app?.stampsManager as {
        openDetail?: (id: number | string) => void | Promise<void>;
        primeCache?: (cards: StampCard[], defaultImage?: string | null) => void;
    } | undefined;
    if (typeof mgr?.primeCache === 'function') {
        mgr.primeCache(cards, defaultImage);
    }
    if (typeof mgr?.openDetail === 'function') {
        void mgr.openDetail(id);
    }
}

export function switchToMemberTab(): void {
    if (typeof window.app?.switchTab === 'function') {
        window.app.switchTab(AppConstants.TABS.MEMBER);
    }
}

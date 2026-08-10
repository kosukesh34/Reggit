import { isDemoDataMode } from '../../demo/demoMode';
import { readDemoWonCoupons, type DemoCoupon } from '../../demo/demoCoupons';

export type LotteryEntitlement = {
    id: number;
    reward_type: string;
    expires_at: string | null;
};

export type LotteryState = {
    gacha_enabled: boolean;
    scratch_enabled: boolean;
    entitlements: LotteryEntitlement[];
    prize_labels: { gacha: string[]; scratch: string[] };
};

type DesignLabManagerLike = {
    prepareForReact?: () => Promise<LotteryState | null>;
    getLotteryState?: () => LotteryState | null;
    hydrateFromReact?: (container: HTMLElement, options?: { scratchInit?: boolean }) => void;
    initScratchFromReact?: (container: HTMLElement) => void;
    resetScratchFromReact?: (container: HTMLElement) => void;
};

export function getDesignLabManager(): DesignLabManagerLike | undefined {
    return (window.app as { designLabManager?: DesignLabManagerLike } | undefined)?.designLabManager;
}

export async function prepareLottery(): Promise<LotteryState | null> {
    const mgr = getDesignLabManager();
    if (mgr !== undefined && typeof mgr.prepareForReact === 'function') {
        return mgr.prepareForReact();
    }
    return null;
}

export function chipLabel(pending: boolean): string {
    if (isDemoDataMode()) {
        return '体験';
    }
    return pending ? 'プレイ可' : '権利なし';
}

export function hasPending(state: LotteryState | null, type: 'gacha' | 'scratch'): boolean {
    if (isDemoDataMode()) {
        return true;
    }
    if (state === null) {
        return false;
    }
    return state.entitlements.some((row) => row.reward_type === type);
}

export function showGachaSection(state: LotteryState | null): boolean {
    return isDemoDataMode() || state?.gacha_enabled === true;
}

export function showScratchSection(state: LotteryState | null): boolean {
    return isDemoDataMode() || state?.scratch_enabled === true;
}

export function wonCouponsForDisplay(): DemoCoupon[] | null {
    if (!isDemoDataMode()) {
        return null;
    }
    return readDemoWonCoupons();
}

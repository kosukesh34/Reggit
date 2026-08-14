import { isDemoDataMode } from '../../demo/demoMode';

export type CouponRecord = {
    id: number | string;
    coupon_name?: string;
    description?: string;
    status?: string;
    design_template?: string;
    theme_color?: string;
    valid_from?: string;
    valid_until?: string;
    issued_at?: string;
    used_at?: string;
    image_url?: string;
    image_fit?: string;
    code?: string;
    use_display?: string;
    [key: string]: unknown;
};

export function normalizeCouponStatus(status: unknown): string {
    const value = status ? String(status) : 'available';
    if (value === 'unused') {
        return 'available';
    }
    if (value === 'upcoming' || value === 'available' || value === 'used' || value === 'expired') {
        return value;
    }
    return 'available';
}

export function normalizeCouponImageFit(imageFit: unknown): 'cover' | 'contain' {
    return imageFit === 'contain' ? 'contain' : 'cover';
}

export function formatCouponDate(value: string | undefined): string {
    if (value === undefined || value === '') {
        return '';
    }
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return '';
    }
    return date.toLocaleDateString('ja-JP', { year: 'numeric', month: 'short', day: 'numeric' });
}

export function getDaysUntil(value: string | undefined): number | null {
    if (value === undefined || value === '') {
        return null;
    }
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return null;
    }
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    date.setHours(0, 0, 0, 0);
    return Math.ceil((date.getTime() - today.getTime()) / (24 * 60 * 60 * 1000));
}

export function isRecentlyIssued(issuedAt: string | undefined): boolean {
    if (issuedAt === undefined || issuedAt === '') {
        return false;
    }
    const issued = new Date(issuedAt);
    if (Number.isNaN(issued.getTime())) {
        return false;
    }
    const diffMs = Date.now() - issued.getTime();
    return diffMs >= 0 && diffMs <= 72 * 60 * 60 * 1000;
}

export function normalizeUseDisplay(useDisplay: unknown, code: string): string {
    if (isDemoDataMode() && code !== '') {
        return 'barcode';
    }
    const display = (useDisplay && String(useDisplay).trim()) || 'barcode';
    if (display === 'none' && code !== '') {
        return 'barcode';
    }
    return display;
}

export function sortVisibleCoupons(coupons: CouponRecord[]): CouponRecord[] {
    return [...coupons].sort((a, b) => {
        const weight: Record<string, number> = { available: 0, upcoming: 1 };
        const statusA = weight[normalizeCouponStatus(a.status)] || 0;
        const statusB = weight[normalizeCouponStatus(b.status)] || 0;
        if (statusA !== statusB) {
            return statusA - statusB;
        }
        const dateA = a.valid_until || a.valid_from || a.issued_at || '';
        const dateB = b.valid_until || b.valid_from || b.issued_at || '';
        return String(dateA).localeCompare(String(dateB));
    });
}

export function sortHistoryCoupons(coupons: CouponRecord[]): CouponRecord[] {
    return [...coupons].sort((a, b) => {
        const dateA = a.used_at || a.valid_until || a.issued_at || '';
        const dateB = b.used_at || b.valid_until || b.issued_at || '';
        return String(dateB).localeCompare(String(dateA));
    });
}

export function groupCoupons(coupons: CouponRecord[]): {
    available: CouponRecord[];
    upcoming: CouponRecord[];
    used: CouponRecord[];
    expired: CouponRecord[];
} {
    const groups = { available: [] as CouponRecord[], upcoming: [] as CouponRecord[], used: [] as CouponRecord[], expired: [] as CouponRecord[] };
    coupons.forEach((cp) => {
        const status = normalizeCouponStatus(cp.status);
        if (status === 'available' || status === 'upcoming' || status === 'used' || status === 'expired') {
            groups[status].push(cp);
        } else {
            groups.available.push(cp);
        }
    });
    return groups;
}

export function shouldShowLotteryEntry(): boolean {
    if (isDemoDataMode()) {
        return true;
    }
    const flags = window.__FEATURE_FLAGS__;
    if (flags === null || typeof flags !== 'object') {
        return false;
    }
    return Boolean(flags.feature_gacha_enabled) || Boolean(flags.feature_scratch_enabled);
}

export function lotteryFlags(): { showGacha: boolean; showScratch: boolean } {
    const flags = window.__FEATURE_FLAGS__;
    return {
        showGacha: isDemoDataMode()
            || (flags !== null && typeof flags === 'object' && Boolean(flags.feature_gacha_enabled)),
        showScratch: isDemoDataMode()
            || (flags !== null && typeof flags === 'object' && Boolean(flags.feature_scratch_enabled)),
    };
}

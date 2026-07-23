/** デモ用スクラッチ／ガチャで獲得したクーポン（localStorage） */

export type DemoCoupon = {
    id: string;
    coupon_name: string;
    description: string;
    status: string;
    valid_until: string;
    issued_at: string;
    image_url: string;
    code: string;
    use_display: string;
    is_sample_preview: true;
    source: 'scratch' | 'gacha';
};

const STORAGE_KEY = 'liff_demo_won_coupons';
const USED_STORAGE_KEY = 'liff_demo_used_coupon_ids';

export const DEMO_COUPON_PRIZES: Array<{
    coupon_name: string;
    description: string;
    image_url: string;
}> = [
    {
        coupon_name: 'ドリンク1杯無料',
        description: 'お好きなドリンク1杯を無料でご提供します',
        image_url: '/images/liff/coupon-drink.jpg',
    },
    {
        coupon_name: 'デザート20%OFF',
        description: 'デザートメニューが20%割引になります',
        image_url: '/images/liff/coupon-dessert.jpg',
    },
    {
        coupon_name: 'ポイント 50pt プレゼント',
        description: '次回のご利用時に50ポイントを付与します',
        image_url: '/images/liff/coupon-drink.jpg',
    },
];

export function readDemoWonCoupons(): DemoCoupon[] {
    try {
        const raw = window.localStorage.getItem(STORAGE_KEY);
        if (raw === null || raw === '') {
            return [];
        }
        const parsed = JSON.parse(raw);
        if (!Array.isArray(parsed)) {
            return [];
        }
        return parsed.map((item, index) => {
            const coupon = item as DemoCoupon;
            const code = (coupon.code && String(coupon.code).trim())
                ? String(coupon.code).trim()
                : `DEMO-${String(coupon.id || index).slice(-8)}`;
            return {
                ...coupon,
                description: String(coupon.description || '').replace(/（デモ）/g, ''),
                code,
                use_display: 'barcode',
                is_sample_preview: true,
            };
        });
    } catch {
        return [];
    }
}

export function readDemoUsedCouponIds(): string[] {
    try {
        const raw = window.localStorage.getItem(USED_STORAGE_KEY);
        if (raw === null || raw === '') {
            return [];
        }
        const parsed = JSON.parse(raw);
        return Array.isArray(parsed) ? parsed.map((id) => String(id)) : [];
    } catch {
        return [];
    }
}

export function markDemoCouponUsed(id: string): void {
    const next = Array.from(new Set([...readDemoUsedCouponIds(), String(id)])).slice(0, 50);
    try {
        window.localStorage.setItem(USED_STORAGE_KEY, JSON.stringify(next));
    } catch {
        // ignore
    }
    const won = readDemoWonCoupons().map((coupon) => (
        String(coupon.id) === String(id)
            ? { ...coupon, status: 'used' }
            : coupon
    ));
    try {
        window.localStorage.setItem(STORAGE_KEY, JSON.stringify(won));
    } catch {
        // ignore
    }
}

export function applyDemoCouponUsageState<T extends { id?: string | number; status?: string }>(coupons: T[]): T[] {
    const usedIds = new Set(readDemoUsedCouponIds());
    return coupons.map((coupon) => {
        if (coupon.id == null || !usedIds.has(String(coupon.id))) {
            return coupon;
        }
        return { ...coupon, status: 'used' };
    });
}

export function saveDemoWonCoupon(
    prize: (typeof DEMO_COUPON_PRIZES)[number],
    source: 'scratch' | 'gacha',
): DemoCoupon {
    const until = new Date();
    until.setDate(until.getDate() + 14);
    const coupon: DemoCoupon = {
        id: `demo-${source}-${Date.now()}`,
        coupon_name: prize.coupon_name,
        description: prize.description,
        status: 'available',
        valid_until: until.toISOString(),
        issued_at: new Date().toISOString(),
        image_url: prize.image_url,
        code: `DEMO-${Date.now().toString().slice(-8)}`,
        use_display: 'barcode',
        is_sample_preview: true,
        source,
    };
    const next = [coupon, ...readDemoWonCoupons()].slice(0, 20);
    try {
        window.localStorage.setItem(STORAGE_KEY, JSON.stringify(next));
    } catch {
        // ignore quota
    }
    return coupon;
}

export function pickRandomPrize(): (typeof DEMO_COUPON_PRIZES)[number] {
    const index = Math.floor(Math.random() * DEMO_COUPON_PRIZES.length);
    return DEMO_COUPON_PRIZES[index] ?? DEMO_COUPON_PRIZES[0];
}

export function isDemoCouponId(id: string | number | null | undefined): boolean {
    if (id == null) {
        return false;
    }
    const value = String(id);
    return value.startsWith('sample-coupon-') || value.startsWith('demo-');
}

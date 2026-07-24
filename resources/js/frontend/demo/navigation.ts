/** /liff 固定デモUI用のタブ IA と機能フラグ判定 */

import { isDemoDataMode } from './demoMode';

export type PrimaryTab = 'home' | 'products' | 'member' | 'queue';

export type FeatureFlags = Record<string, boolean>;

const FLAG = {
    home: 'feature_home_enabled',
    products: 'feature_orders_enabled',
    orders: 'feature_orders_enabled',
    stamps: 'feature_stamps_enabled',
    coupons: 'feature_coupons_enabled',
    queue: 'feature_queue_enabled',
    reservations: 'feature_reservations_enabled',
    member: 'feature_members_enabled',
    gallery: 'feature_gallery_enabled',
    announcements: 'feature_announcements_enabled',
    gacha: 'feature_gacha_enabled',
    scratch: 'feature_scratch_enabled',
} as const;

/** 旧タブ → 下部タブのグループ */
export const TAB_TO_PRIMARY: Record<string, PrimaryTab> = {
    home: 'home',
    products: 'products',
    orders: 'home',
    stamps: 'member',
    coupons: 'member',
    member: 'member',
    designlab: 'member',
    queue: 'queue',
    reservations: 'queue',
    gallery: 'home',
    announcements: 'home',
};

export function readFeatureFlags(): FeatureFlags {
    // 固定UIの体験（未ログイン）では全機能導線を見せる
    if (isDemoDataMode()) {
        return {
            feature_home_enabled: true,
            feature_orders_enabled: true,
            feature_stamps_enabled: true,
            feature_coupons_enabled: true,
            feature_queue_enabled: true,
            feature_reservations_enabled: true,
            feature_members_enabled: true,
            feature_gallery_enabled: true,
            feature_announcements_enabled: true,
            feature_gacha_enabled: true,
            feature_scratch_enabled: true,
        };
    }
    const flags = window.__FEATURE_FLAGS__;
    if (flags !== null && typeof flags === 'object') {
        return flags;
    }
    return {};
}

export function isFeatureEnabled(tab: keyof typeof FLAG, flags?: FeatureFlags): boolean {
    const source = flags ?? readFeatureFlags();
    const key = FLAG[tab];
    if (key === undefined) {
        return true;
    }
    const value = source[key];
    return value !== false;
}

export type BottomTabItem = {
    primary: PrimaryTab;
    tab: string;
    label: string;
};

/** 機能フラグから下部4タブを組み立てる（無効なグループは除外） */
export function buildBottomTabs(flags?: FeatureFlags): BottomTabItem[] {
    const source = flags ?? readFeatureFlags();
    const items: BottomTabItem[] = [];

    if (isFeatureEnabled('home', source) || isFeatureEnabled('announcements', source) || isFeatureEnabled('gallery', source) || isFeatureEnabled('orders', source)) {
        items.push({ primary: 'home', tab: 'home', label: 'ホーム' });
    }
    if (isFeatureEnabled('products', source)) {
        items.push({ primary: 'products', tab: 'products', label: 'オーダー' });
    }
    if (isFeatureEnabled('member', source) || isFeatureEnabled('stamps', source) || isFeatureEnabled('coupons', source)) {
        const tab = isFeatureEnabled('member', source)
            ? 'member'
            : (isFeatureEnabled('stamps', source) ? 'stamps' : 'coupons');
        items.push({ primary: 'member', tab, label: 'カード' });
    }
    if (isFeatureEnabled('queue', source) || isFeatureEnabled('reservations', source)) {
        const tab = isFeatureEnabled('queue', source) ? 'queue' : 'reservations';
        items.push({ primary: 'queue', tab, label: '来店' });
    }

    if (items.length === 0) {
        items.push({ primary: 'home', tab: 'home', label: 'ホーム' });
    }
    return items;
}

export type SegmentItem = { tab: string; label: string };

export function buildCardSegments(flags?: FeatureFlags): SegmentItem[] {
    const source = flags ?? readFeatureFlags();
    const items: SegmentItem[] = [];
    if (isFeatureEnabled('member', source)) {
        items.push({ tab: 'member', label: '会員証' });
    }
    if (isFeatureEnabled('stamps', source)) {
        items.push({ tab: 'stamps', label: 'スタンプ' });
    }
    if (isFeatureEnabled('coupons', source)) {
        items.push({ tab: 'coupons', label: 'クーポン' });
    }
    if (isFeatureEnabled('gacha', source) || isFeatureEnabled('scratch', source) || isDemoDataMode()) {
        items.push({ tab: 'designlab', label: 'あそび' });
    }
    return items;
}

export function buildVisitSegments(flags?: FeatureFlags): SegmentItem[] {
    const source = flags ?? readFeatureFlags();
    const items: SegmentItem[] = [];
    if (isFeatureEnabled('queue', source)) {
        items.push({ tab: 'queue', label: '順番待ち' });
    }
    if (isFeatureEnabled('reservations', source)) {
        items.push({ tab: 'reservations', label: '予約' });
    }
    return items;
}

export function primaryForTab(tab: string): PrimaryTab | null {
    if (TAB_TO_PRIMARY[tab] !== undefined) {
        return TAB_TO_PRIMARY[tab];
    }
    if (/^link_[0-9]+$/.test(tab)) {
        return 'home';
    }
    return null;
}

/** 旧ハッシュ別名（表示用ラベルは別途） */
export const HASH_TAB_ALIASES: Record<string, string> = {
    order: 'products',
    card: 'member',
    visit: 'queue',
    more: 'home',
};

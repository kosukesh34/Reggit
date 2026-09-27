import type { ParsedLiffHash } from './LiffPageRouter';

const ALLOWED_TABS = new Set([
    'home', 'products', 'orders', 'stamps', 'coupons', 'designlab', 'queue',
    'member', 'profile', 'reservations', 'gallery', 'announcements',
]);

const TAB_ALIASES: Record<string, string> = {
    order: 'products',
    card: 'member',
    profile: 'profile',
    visit: 'queue',
    more: 'home',
};

const PREVIEW_TABS: Record<string, string> = {
    products: 'products', orders: 'orders', stamps: 'stamps', coupons: 'coupons',
    queue: 'queue', reservations: 'reservations', member: 'member',
    member_card: 'member', profile: 'profile', gallery: 'gallery',
    announcements: 'announcements',
};

export function resolveAppTab(raw: string | null): string | null {
    if (!raw) return null;
    const tab = TAB_ALIASES[raw] ?? raw;
    return ALLOWED_TABS.has(tab) || /^link_[0-9]+$/.test(tab) ? tab : null;
}

export function resolveTabFromSearch(search: string): string | null {
    const params = new URLSearchParams(search || '');
    const direct = resolveAppTab(params.get('tab'));
    if (direct) return direct;
    const state = params.get('liff.state');
    if (!state) return null;
    const normalized = state.startsWith('?') ? state.slice(1) : state;
    return resolveAppTab(new URLSearchParams(normalized).get('tab'));
}

export function resolveTabFromParsedHash(parsed: ParsedLiffHash): string | null {
    const detailTabs: Partial<Record<ParsedLiffHash['kind'], string>> = {
        announcement: 'announcements', product: 'products', order: 'products',
        coupon: 'coupons', gallery: 'gallery', stamp: 'stamps',
        reservation: 'reservations',
    };
    return parsed.kind === 'tab' ? resolveAppTab(parsed.tab) : detailTabs[parsed.kind] ?? null;
}

export function normalizeOrderMode(value: unknown): 'mobile_only' | 'in_store_only' | 'both' {
    return value === 'mobile_only' || value === 'in_store_only' || value === 'both' ? value : 'both';
}

export function resolveDesignerPreview(search: string): { source: string | null; embed: boolean; sample: boolean } {
    const params = new URLSearchParams(search || '');
    const enabled = params.get('designer_live_preview') === '1';
    const source = params.get('designer_source');
    return {
        source: enabled && source && Object.prototype.hasOwnProperty.call(PREVIEW_TABS, source) ? source : null,
        embed: params.get('designer_embed') === '1',
        sample: params.get('designer_sample') === '1',
    };
}

export function tabForDesignerPreview(source: string): string {
    return PREVIEW_TABS[source] ?? 'home';
}

/** 旧 ReggitApp.switchTab のタブ名 → liff-ui HashRouter パス */

const TAB_PATHS: Record<string, string> = {
  home: '/home',
  member: '/membership',
  membership: '/membership',
  designlab: '/designlab',
  coupons: '/coupon',
  coupon: '/coupon',
  stamps: '/stamp',
  stamp: '/stamp',
  queue: '/queue',
  reservations: '/reservation',
  reservation: '/reservation',
  products: '/menu',
  orders: '/orderHistory',
  announcements: '/news',
  gallery: '/gallery',
  more: '/more',
};

export function pathForLegacyTab(tab: string): string | null {
  const normalized = String(tab || '').trim();
  if (normalized === '') {
    return null;
  }
  return TAB_PATHS[normalized] ?? `/${normalized}`;
}

export const LIFF_UI_NAVIGATE = 'liff-ui-navigate';

export type LiffUiNavigateDetail = {
  path: string;
  replace?: boolean;
};

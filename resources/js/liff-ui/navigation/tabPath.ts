import { getQueueTokenFromUrl } from '../../shared/utils/queueToken';
import { getStampGrantCodeFromUrl } from '../../shared/utils/stampGrantToken';

/** 旧 ReggitApp.switchTab のタブ名 → liff-ui HashRouter パス */

const TAB_PATHS: Record<string, string> = {
  home: '/home',
  member: '/membership',
  membership: '/membership',
  profile: '/profile',
  designlab: '/designlab',
  coupons: '/coupon',
  coupon: '/coupon',
  stamps: '/stamp',
  stamp: '/stamp',
  queue: '/queue',
  reservations: '/reservation',
  reservation: '/reservation',
  products: '/menu',
  menu: '/menu',
  orders: '/orderHistory',
  orderHistory: '/orderHistory',
  announcements: '/news',
  news: '/news',
  gallery: '/gallery',
  more: '/more',
  link_1: '/link_1',
  link_2: '/link_2',
  link_3: '/link_3',
  card: '/membership',
  visit: '/queue',
  order: '/menu',
};

const PATH_TO_LEGACY_TAB: Record<string, string> = {
  '/home': 'home',
  '/membership': 'member',
  '/profile': 'profile',
  '/designlab': 'designlab',
  '/coupon': 'coupons',
  '/stamp': 'stamps',
  '/queue': 'queue',
  '/reservation': 'reservations',
  '/menu': 'products',
  '/orderHistory': 'orders',
  '/news': 'announcements',
  '/gallery': 'gallery',
  '/more': 'more',
  '/link_1': 'link_1',
  '/link_2': 'link_2',
  '/link_3': 'link_3',
};

function pathForQueryTab(raw: string | null): string | null {
  if (raw === null || raw === '') {
    return null;
  }
  return TAB_PATHS[raw] ?? null;
}

function tabParamFromEncoded(raw: string | null): string | null {
  if (raw === null || raw === '') {
    return null;
  }
  let decoded = raw;
  try {
    decoded = decodeURIComponent(raw);
  } catch {
    decoded = raw;
  }
  const normalized = decoded.startsWith('?') ? decoded.slice(1) : decoded;
  try {
    const asUrl = new URL(normalized);
    return pathForQueryTab(asUrl.searchParams.get('tab'));
  } catch {
    // query string
  }
  const query = normalized.includes('?') ? normalized.split('?')[1] : normalized;
  return pathForQueryTab(new URLSearchParams(query).get('tab'));
}

/** クエリ / liff.state / ネイティブ指定 / 待ち順トークンから初期パスを決める */
export function resolveInitialHashPath(): string {
  if (typeof window === 'undefined') {
    return '/home';
  }
  const params = new URLSearchParams(window.location.search || '');
  const fromQuery = pathForQueryTab(params.get('tab'));
  if (fromQuery !== null) {
    return fromQuery;
  }
  const fromLiffState = tabParamFromEncoded(params.get('liff.state'))
    ?? tabParamFromEncoded(params.get('state'));
  if (fromLiffState !== null) {
    return fromLiffState;
  }
  const nativeTab = window.__NATIVE_TARGET_TAB__ ?? window.__NATIVE_PENDING_TAB__;
  if (typeof nativeTab === 'string' && nativeTab !== '') {
    const fromNative = pathForQueryTab(nativeTab);
    if (fromNative !== null) {
      return fromNative;
    }
  }
  if (getQueueTokenFromUrl() !== null) {
    return '/queue';
  }
  if (getStampGrantCodeFromUrl() !== null) {
    return '/stamp';
  }
  return '/home';
}

/** HashRouter が解釈する前に旧 `#member` 形式を `#/membership` へ直す */
export function normalizeLegacyLocationHash(): void {
  if (typeof window === 'undefined') {
    return;
  }
  const raw = String(window.location.hash || '');
  const base = `${window.location.pathname}${window.location.search}`;
  if (raw === '' || raw === '#' || raw === '#/') {
    window.history.replaceState(null, '', `${base}#${resolveInitialHashPath()}`);
    return;
  }

  const withoutHash = raw.replace(/^#/, '');
  const [pathPart, ...queryParts] = withoutHash.split('?');
  const query = queryParts.length > 0 ? `?${queryParts.join('?')}` : '';
  const key = String(pathPart || '').replace(/^\//, '').trim();
  if (key === '') {
    window.history.replaceState(null, '', `${base}#${resolveInitialHashPath()}${query}`);
    return;
  }

  const mapped = TAB_PATHS[key];
  if (mapped === undefined) {
    return;
  }
  const nextHash = `#${mapped}${query}`;
  if (raw !== nextHash) {
    window.history.replaceState(null, '', `${base}${nextHash}`);
  }
}

export function pathForLegacyTab(tab: string): string | null {
  const normalized = String(tab || '').trim().replace(/^\//, '');
  if (normalized === '') {
    return null;
  }
  return TAB_PATHS[normalized] ?? `/${normalized}`;
}

/** HashRouter の pathname → 旧 currentTab 名（Managers 互換） */
export function legacyTabForPath(pathname: string): string | null {
  const normalized = String(pathname || '').split('?')[0] || '';
  if (normalized === '' || normalized === '/') {
    return 'home';
  }
  if (PATH_TO_LEGACY_TAB[normalized] !== undefined) {
    return PATH_TO_LEGACY_TAB[normalized];
  }
  const asTab = normalized.replace(/^\//, '');
  if (TAB_PATHS[asTab] !== undefined) {
    return asTab === 'membership' || asTab === 'card' ? 'member' : asTab;
  }
  return null;
}

export const LIFF_UI_NAVIGATE = 'liff-ui-navigate';

export type LiffUiNavigateDetail = {
  path: string;
  replace?: boolean;
};

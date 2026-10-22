import { createContext, useContext, useEffect, useMemo, useRef, useState, type PropsWithChildren } from 'react';

export type NativeTab = {
  key: string;
  label: string;
  icon?: string | null;
  sf_symbol?: string | null;
  url?: string | null;
};

export type HomeGalleryItem = { id: number; image_url: string; title: string; description?: string; mode: "detail" | "webview" | "external"; url?: string };

export type NativeConfig = {
  project_key?: string;
  project_name?: string;
  tabs?: NativeTab[];
  navigation?: {
    header?: NativeTab[];
    footer?: NativeTab[];
    hamburger?: NativeTab[];
  };
  theme?: { primary_color?: string };
  native_chrome_colors?: Record<string, string>;
  header?: {
    image_url?: string;
    logo_url?: string;
    background_color?: string;
    text?: string;
  };
  home?: {
    layout?: "legacy" | "gallery" | "member_news" | "buttons_gallery";
    image_limit?: number | null;
    gallery_items?: HomeGalleryItem[];
    image_url?: string;
    image_urls?: string[];
    menu_layout?: string;
    show_member_card?: boolean;
  };
  products?: { display_layout?: string };
  shop_shipping_fee?: number;
  shop_shipping_fees_by_prefecture?: Record<string, number>;
  shop_prefectures?: string[];
  member_screen?: {
    style?: "classic" | "wallet" | "minimal" | "ticket";
    show_rank_progress?: boolean;
    show_point_history?: boolean;
    show_coupon_activity?: boolean;
  };
};

const fallbackTabs: NativeTab[] = [
  { key: 'home', label: 'ホーム' },
  { key: 'member', label: '会員証' },
  { key: 'products', label: '注文' },
  { key: 'coupons', label: 'クーポン' },
  { key: 'stamps', label: 'スタンプ' },
];

const fallbackConfig: NativeConfig = {
  project_name: 'Reggit',
  tabs: fallbackTabs,
  home: { image_urls: [], menu_layout: 'grid_3x2' },
  theme: { primary_color: '#173F35' },
  header: { background_color: '#FFFFFF' },
};

type NativeConfigState = { config: NativeConfig; loading: boolean };
const NativeConfigContext = createContext<NativeConfigState>({ config: fallbackConfig, loading: true });
const configRequests = new Map<string, Promise<NativeConfig>>();

function projectKey(): string {
  return typeof window !== 'undefined' && window.__PROJECT_KEY__
    ? String(window.__PROJECT_KEY__)
    : 'default';
}

function localMediaUrl(value: string | undefined): string {
  if (!value || typeof window === 'undefined') return value ?? '';
  try {
    const url = new URL(value, window.location.origin);
    if ((url.hostname === 'localhost' || url.hostname === '127.0.0.1') && url.pathname.startsWith('/storage/')) {
      return `${window.location.origin}${url.pathname}${url.search}`;
    }
  } catch {
    return value;
  }
  return value;
}

function normalizeConfig(config: NativeConfig): NativeConfig {
  return {
    ...fallbackConfig,
    ...config,
    tabs: config.tabs?.length ? config.tabs : fallbackTabs,
    header: {
      ...fallbackConfig.header,
      ...config.header,
      image_url: localMediaUrl(config.header?.image_url),
      logo_url: localMediaUrl(config.header?.logo_url),
    },
    home: {
      ...fallbackConfig.home,
      ...config.home,
      image_url: localMediaUrl(config.home?.image_url),
      image_urls: config.home?.image_urls?.map(localMediaUrl) ?? [],
    },
  };
}

function loadNativeConfig(key: string): Promise<NativeConfig> {
  const cached = configRequests.get(key);
  if (cached) return cached;
  const selectedStoreId = new URLSearchParams(window.location.search).get("store_id");
  const request = fetch(`/api/v1/app-config?project_key=${encodeURIComponent(key)}${selectedStoreId ? `&store_id=${encodeURIComponent(selectedStoreId)}` : ""}`, {
    headers: { Accept: 'application/json' },
    credentials: 'same-origin',
  })
    .then((response) => {
      if (!response.ok) throw new Error(`app-config: ${response.status}`);
      return response.json() as Promise<NativeConfig>;
    })
    .then(normalizeConfig)
    .catch(() => fallbackConfig);
  configRequests.set(key, request);
  return request;
}

export function NativeConfigProvider({ children, initialConfig }: PropsWithChildren<{ initialConfig?: NativeConfig }>) {
  const key = useMemo(projectKey, []);
  const receivedHomePreview = useRef(false);
  const [state, setState] = useState<NativeConfigState>({ config: initialConfig ? normalizeConfig(initialConfig) : fallbackConfig, loading: !initialConfig });

  useEffect(() => {
    if (initialConfig) return;
    let mounted = true;
    void loadNativeConfig(key).then((config) => {
      if (mounted && !receivedHomePreview.current) setState({ config, loading: false });
    });
    return () => { mounted = false; };
  }, [key, initialConfig]);

  useEffect(() => {
    if (window.__IS_DESIGNER_EMBED_PREVIEW__ !== true || window.parent === window) return;
    const receive = (event: MessageEvent) => {
      if (event.origin !== window.location.origin || event.source !== window.parent || event.data?.type !== 'reggit:home-form-preview:update') return;
      const incoming = event.data.config as NativeConfig | undefined;
      if (incoming && typeof incoming === 'object') {
        receivedHomePreview.current = true;
        setState({ config: normalizeConfig(incoming), loading: false });
      }
    };
    const receiveDraft = (event: Event) => {
      const incoming = (event as CustomEvent<NativeConfig>).detail;
      if (!incoming || typeof incoming !== 'object') return;
      receivedHomePreview.current = true;
      setState({ config: normalizeConfig(incoming), loading: false });
    };
    window.addEventListener('reggit:home-preview-draft', receiveDraft);
    window.addEventListener('message', receive);
    window.parent.postMessage({ type: 'reggit:home-form-preview:ready' }, window.location.origin);
    return () => {
      window.removeEventListener('message', receive);
      window.removeEventListener('reggit:home-preview-draft', receiveDraft);
    };
  }, []);

  return <NativeConfigContext.Provider value={state}>{children}</NativeConfigContext.Provider>;
}

export function useNativeConfig(): NativeConfigState {
  return useContext(NativeConfigContext);
}

const routes: Record<string, string> = {
  home: '/home',
  member: '/membership',
  membership: '/membership',
  profile: '/profile',
  products: '/mobileOrder',
  mobileOrder: '/mobileOrder',
  dineInOrder: '/dineInOrder',
  orders: '/orderHistory',
  orderHistory: '/orderHistory',
  coupons: '/coupon',
  coupon: '/coupon',
  stamps: '/stamp',
  stamp: '/stamp',
  queue: '/queue',
  reservations: '/reservation',
  reservation: '/reservation',
  gallery: '/gallery',
  announcements: '/news',
  news: '/news',
  terms: '/terms',
  privacy: '/privacy',
  transfer: '/more/transfer',
  withdraw: '/more/withdraw',
};

export function nativeTabPath(key: string): string {
  return routes[key] ?? `/${key}`;
}

export function nativeTabHref(tab: NativeTab): string {
  return tab.url?.trim() || `#${nativeTabPath(tab.key)}`;
}

const icons: Record<string, string> = {
  home: 'fa-house',
  member: 'fa-id-card',
  membership: 'fa-id-card',
  profile: 'fa-user-pen',
  profileEdit: 'fa-user-pen',
  lottery: 'fa-dice',
  storeSearch: 'fa-location-dot',
  products: 'fa-bag-shopping',
  mobileOrder: 'fa-bag-shopping',
  dineInOrder: 'fa-utensils',
  orders: 'fa-list-ol',
  coupons: 'fa-ticket',
  coupon: 'fa-ticket',
  stamps: 'fa-star',
  stamp: 'fa-star',
  queue: 'fa-list-ol',
  reservations: 'fa-calendar-days',
  gallery: 'fa-images',
  announcements: 'fa-bullhorn',
  news: 'fa-bell',
  terms: 'fa-file-lines',
  privacy: 'fa-shield-halved',
  transfer: 'fa-link',
  store: 'fa-location-dot',
};

const sfIcons: Record<string, string> = {
  'house.fill': 'fa-house',
  'person.crop.circle.fill': 'fa-user',
  bag: 'fa-bag-shopping',
  'bag.fill': 'fa-bag-shopping',
  'cart.fill': 'fa-cart-shopping',
  'fork.knife': 'fa-utensils',
  'list.clipboard.fill': 'fa-list-ol',
  'ticket.fill': 'fa-ticket',
  'star.circle.fill': 'fa-star',
  'star.fill': 'fa-star',
  'number.circle.fill': 'fa-list-ol',
  calendar: 'fa-calendar-days',
  'photo.on.rectangle': 'fa-images',
  'bell.fill': 'fa-bell',
  'doc.text.fill': 'fa-file-lines',
  'hand.raised.fill': 'fa-shield-halved',
  link: 'fa-link',
  'shippingbox.fill': 'fa-box',
  sparkles: 'fa-wand-magic-sparkles',
};

export function nativeTabIconClass(tab: NativeTab): string {
  const customIcon = tab.icon?.trim();
  const name = customIcon?.startsWith('fa-') && !(tab.key !== 'home' && ['fa-home', 'fa-house'].includes(customIcon))
    ? customIcon
    : tab.key === "products" && ["", "fork.knife", "fork.knife.circle", "cart.fill"].includes(tab.sf_symbol ?? "") ? "fa-bag-shopping" : sfIcons[tab.sf_symbol ?? ''] ?? icons[tab.key] ?? 'fa-circle';
  return `fa-solid ${name}`;
}

import { appConfig } from './appConfig';
import { isLinkTabType, type FeatureConfig, type FeatureType, type QuickAction, type StoreAppConfig, type TabConfig, type TabType } from '../types/app';
import { createDefaultCustomization } from './customization';
import type { AppCustomizationPatch } from '../types/customization';

function headerTitle(): string {
  const header = window.__FRONTEND_HEADER__;
  if (header && typeof header === 'object' && typeof (header as { title?: string }).title === 'string') {
    const title = (header as { title: string }).title.trim();
    if (title !== '') {
      return title;
    }
  }
  return document.title || '店舗アプリ';
}

const featureFlagMap: Partial<Record<FeatureType, string>> = {
  membership: 'feature_members_enabled',
  point: 'feature_members_enabled',
  mobileOrder: 'feature_orders_enabled',
  dineInOrder: 'feature_orders_enabled',
  menu: 'feature_orders_enabled',
  purchaseHistory: 'feature_orders_enabled',
  orderHistory: 'feature_orders_enabled',
  reservation: 'feature_reservations_enabled',
  queue: 'feature_queue_enabled',
  coupon: 'feature_coupons_enabled',
  stamp: 'feature_stamps_enabled',
  gallery: 'feature_gallery_enabled',
  news: 'feature_announcements_enabled',
};

function buildFeatures(): FeatureConfig {
  const flags = window.__FEATURE_FLAGS__ ?? {};
  const orderMode = String(window.__ORDER_MODE__ ?? 'both');
  const enabled = (feature: FeatureType): boolean => {
    const key = featureFlagMap[feature];
    return key === undefined ? false : flags[key] !== false;
  };

  return {
    membership: enabled('membership'),
    mobileOrder: enabled('mobileOrder') && orderMode !== 'in_store_only',
    dineInOrder: enabled('dineInOrder') && orderMode !== 'mobile_only',
    reservation: enabled('reservation'),
    queue: enabled('queue'),
    coupon: enabled('coupon'),
    stamp: enabled('stamp'),
    point: enabled('point'),
    menu: enabled('menu'),
    gallery: enabled('gallery'),
    news: enabled('news'),
    storeSearch: false,
    purchaseHistory: enabled('purchaseHistory'),
    orderHistory: enabled('orderHistory'),
    favorite: false,
    ticket: false,
    subscription: false,
  };
}

const menuTabMap: Record<string, TabType | undefined> = {
  home: 'home',
  products: 'mobileOrder',
  member: 'membership',
  coupons: 'coupon',
  stamps: 'stamp',
  queue: 'queue',
  reservations: 'reservation',
  announcements: 'news',
};

const tabFeatureMap: Partial<Record<TabType, FeatureType>> = {
  membership: 'membership',
  mobileOrder: 'mobileOrder',
  dineInOrder: 'dineInOrder',
  reservation: 'reservation',
  queue: 'queue',
  coupon: 'coupon',
  stamp: 'stamp',
  point: 'point',
  menu: 'menu',
  news: 'news',
};

function buildTabs(features: FeatureConfig, homeEnabled: boolean): TabConfig[] {
  const configuredMenu = Array.isArray(window.__FRONTEND_MENU__) ? window.__FRONTEND_MENU__ : [];
  const seen = new Set<TabType>();
  const candidates: Array<{ type: TabType | undefined; label?: string; url?: string }> = configuredMenu.map((item) => ({
    type: isLinkTabType(item.tab)
      ? item.tab
      : (item.tab === 'products' && !features.mobileOrder && features.dineInOrder
        ? 'dineInOrder' as const
        : menuTabMap[item.tab]),
    label: item.label,
    url: typeof item.url === 'string' ? item.url : undefined,
  }));

  if (candidates.length === 0) {
    candidates.push(
      { type: homeEnabled ? 'home' : undefined, label: 'ホーム' },
      { type: features.mobileOrder ? 'mobileOrder' : features.dineInOrder ? 'dineInOrder' : undefined, label: 'オーダー' },
      { type: features.membership ? 'membership' : undefined, label: '会員証' },
      { type: features.queue ? 'queue' : features.reservation ? 'reservation' : undefined, label: '来店' },
    );
  }

  const tabs: TabConfig[] = [];
  candidates.forEach((candidate) => {
    const type = candidate.type;
    const feature = type === undefined ? undefined : tabFeatureMap[type];
    if (type === undefined || seen.has(type) || (type === 'home' && !homeEnabled) || (feature !== undefined && !features[feature])) {
      return;
    }
    seen.add(type);
    tabs.push({
      id: type,
      type,
      label: candidate.label?.trim() || (type === 'home' ? 'ホーム' : (isLinkTabType(type) ? '外部サイト' : 'サービス')),
      url: candidate.url,
      enabled: true,
      order: tabs.length + 1,
    });
  });

  if (homeEnabled && !seen.has('home')) {
    tabs.unshift({ id: 'home', type: 'home', label: 'ホーム', enabled: true, order: 0 });
  }
  if (!seen.has('more')) {
    tabs.push({ id: 'more', type: 'more', label: 'その他', enabled: true, order: tabs.length + 1 });
  }

  return tabs.slice(0, 6).map((tab, index) => ({ ...tab, order: index + 1 }));
}

const quickActionIconMap: Partial<Record<FeatureType, string>> = {
  mobileOrder: 'cup',
  dineInOrder: 'cup',
  membership: 'coins',
  coupon: 'ticket',
  stamp: 'stamp',
  queue: 'calendar',
  reservation: 'calendar',
  gallery: 'image',
  news: 'image',
};

function buildQuickActions(tabs: TabConfig[], features: FeatureConfig): QuickAction[] {
  const actions: QuickAction[] = [];
  tabs.forEach((tab) => {
    const feature = tabFeatureMap[tab.type];
    if (feature === undefined || !features[feature] || actions.some((item) => item.feature === feature)) {
      return;
    }
    actions.push({
      id: feature,
      feature,
      label: tab.label,
      icon: quickActionIconMap[feature] ?? 'cup',
      order: actions.length + 1,
    });
  });
  return actions.slice(0, 4);
}

function primaryFeature(features: FeatureConfig): FeatureType {
  const candidates: FeatureType[] = ['membership', 'mobileOrder', 'dineInOrder', 'queue', 'reservation', 'coupon'];
  return candidates.find((feature) => features[feature]) ?? 'membership';
}

/** 現在の Reggit プロジェクトを DesignMock の StoreAppConfig に写す */
export function buildProjectStore(): StoreAppConfig {
  const projectKey = typeof window !== 'undefined' && window.__PROJECT_KEY__
    ? String(window.__PROJECT_KEY__)
    : 'default';
  const template = appConfig.stores[0];
  const name = headerTitle();
  const features = buildFeatures();
  const tabs = buildTabs(features, window.__FEATURE_FLAGS__?.feature_home_enabled !== false);
  const quickActions = buildQuickActions(tabs, features);
  const primaryActionFeature = primaryFeature(features);

  if (template === undefined) {
    return {
      storeId: projectKey,
      storeName: name,
      brandName: name,
      logoText: name.slice(0, 1).toUpperCase(),
      themeId: 'minimal-light',
      features,
      tabs,
      quickActions,
      membershipCard: {
        templateId: 'classic',
        backgroundType: 'gradient',
        gradientFrom: '#1F3A5F',
        gradientTo: '#0F1C2E',
        textColor: '#FFFFFF',
        logoVisible: true,
        memberNameVisible: true,
        memberNumberVisible: true,
        rankVisible: true,
        pointVisible: true,
        barcodeVisible: true,
        borderRadius: 20,
        shadow: 'medium',
      },
      heroTitle: name,
      heroSubtitle: '会員アプリ',
      home: {
        heroImage: '',
        heroEyebrow: 'Welcome',
        statusLabel: '営業中',
        primaryActionLabel: primaryActionFeature === 'membership' ? '会員証を開く' : 'サービスを開く',
        primaryActionFeature,
        featuredImage: '',
        featuredTitle: '',
        featuredDescription: '',
        featuredActionLabel: '',
        featuredFeature: 'coupon',
      },
    };
  }

  return {
    ...template,
    storeId: projectKey,
    storeName: name,
    brandName: name,
    logoText: name.slice(0, 1).toUpperCase() || template.logoText,
    heroTitle: name,
    heroSubtitle: template.heroSubtitle,
    features,
    tabs,
    quickActions,
    home: {
      ...template.home,
      primaryActionFeature,
      primaryActionLabel: primaryActionFeature === 'membership' ? '会員証を開く' : 'サービスを開く',
    },
  };
}

export function readServerCustomizationPatch(_storeId: string): AppCustomizationPatch | undefined {
  const settings = window.__LIFF_TAB_SETTINGS__;
  if (settings === undefined || settings === null || typeof settings !== 'object') {
    return undefined;
  }
  const global = (settings as { _global?: Record<string, unknown> })._global;
  if (global === undefined || typeof global !== 'object' || global === null) {
    return undefined;
  }
  const raw = global.app_customization;
  if (raw === undefined || raw === null) {
    if (typeof global.products_group_by_category === 'boolean') {
      return { order: { groupByCategory: global.products_group_by_category } };
    }
    return undefined;
  }
  const withLegacyOrderSetting = (patch: AppCustomizationPatch): AppCustomizationPatch => ({
    ...patch,
    order: {
      ...patch.order,
      ...(patch.order?.groupByCategory === undefined && typeof global.products_group_by_category === 'boolean'
        ? { groupByCategory: global.products_group_by_category }
        : {}),
    },
  });
  if (typeof raw === 'string') {
    try {
      const parsed = JSON.parse(raw) as AppCustomizationPatch;
      return withLegacyOrderSetting(parsed);
    } catch {
      return undefined;
    }
  }
  if (typeof raw === 'object') {
    return withLegacyOrderSetting(raw as AppCustomizationPatch);
  }
  return undefined;
}

export function defaultCustomizationForProject(): ReturnType<typeof createDefaultCustomization> {
  return createDefaultCustomization(buildProjectStore());
}

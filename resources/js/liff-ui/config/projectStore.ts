import { appConfig } from './appConfig';
import type { StoreAppConfig } from '../types/app';
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

/** 現在の Reggit プロジェクトを DesignMock の StoreAppConfig に写す */
export function buildProjectStore(): StoreAppConfig {
  const projectKey = typeof window !== 'undefined' && window.__PROJECT_KEY__
    ? String(window.__PROJECT_KEY__)
    : 'default';
  const template = appConfig.stores[0];
  const name = headerTitle();

  if (template === undefined) {
    return {
      storeId: projectKey,
      storeName: name,
      brandName: name,
      logoText: name.slice(0, 1).toUpperCase(),
      themeId: 'minimal-light',
      features: {
        membership: true,
        mobileOrder: true,
        dineInOrder: true,
        reservation: true,
        queue: true,
        coupon: true,
        stamp: true,
        point: true,
        menu: true,
        gallery: true,
        news: true,
        storeSearch: false,
        purchaseHistory: true,
        orderHistory: true,
        favorite: false,
        ticket: false,
        subscription: false,
      },
      tabs: [
        { id: 'home', type: 'home', label: 'ホーム', enabled: true, order: 1 },
        { id: 'membership', type: 'membership', label: '会員証', enabled: true, order: 2 },
        { id: 'coupon', type: 'coupon', label: 'クーポン', enabled: true, order: 3 },
        { id: 'more', type: 'more', label: 'その他', enabled: true, order: 4 },
      ],
      quickActions: [],
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
        primaryActionLabel: '会員証を開く',
        primaryActionFeature: 'membership',
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
  };
}

export function readServerCustomizationPatch(storeId: string): AppCustomizationPatch | undefined {
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
    return undefined;
  }
  if (typeof raw === 'string') {
    try {
      const parsed = JSON.parse(raw) as AppCustomizationPatch;
      return { ...parsed, storeId };
    } catch {
      return undefined;
    }
  }
  if (typeof raw === 'object') {
    return { ...(raw as AppCustomizationPatch), storeId };
  }
  return undefined;
}

export function defaultCustomizationForProject(): ReturnType<typeof createDefaultCustomization> {
  return createDefaultCustomization(buildProjectStore());
}

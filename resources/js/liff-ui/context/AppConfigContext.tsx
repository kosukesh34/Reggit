import { createContext, useContext, useEffect, useMemo, useState, type ReactNode } from 'react';
import { appConfig } from '../config/appConfig';
import { createDefaultCustomization, memberCrmNavigationTabs, MEMBER_CRM_HEADER, MEMBER_CRM_HOME, MEMBER_CRM_STORE_ID } from '../config/customization';
import { buildProjectStore, readServerCustomizationPatch } from '../config/projectStore';
import type { StoreAppConfig } from '../types/app';
import type { AppCustomization, AppCustomizationPatch, CouponStatus } from '../types/customization';

const customizationStorageKey = isDesignShowcaseMode() ? 'reggit-demo-customization-v1' : 'reggit-app-customization-v4';
const showcaseStoreStorageKey = 'reggit-showcase-store-id-v2';
const SHOWCASE_DEFAULT_STORE_ID = 'store_landing_showcase';
const experienceVersion = 16;

interface PersistedCustomizations {
  version: 3;
  experienceVersion: 16;
  byStore: Record<string, AppCustomizationPatch>;
}

interface AppConfigContextValue {
  activeStore: StoreAppConfig;
  customization: AppCustomization;
}

const AppConfigContext = createContext<AppConfigContextValue | null>(null);

function mergeCustomization(store: StoreAppConfig, patch?: AppCustomizationPatch): AppCustomization {
  const base = createDefaultCustomization(store);
  if (patch === undefined) {
    return base;
  }
  const savedDefaultStatus = patch.coupon?.defaultStatus as CouponStatus | 'used' | 'expired' | undefined;
  const appTemplateId = patch.appTemplateId ?? base.appTemplateId;
  const isMemberCrm = appTemplateId === MEMBER_CRM_STORE_ID;
  const savedLayout = patch.home?.layout;
  const isLegacyStandard = savedLayout === "standard";
  const home = isMemberCrm && isLegacyStandard
    ? { ...base.home, ...patch.home, ...MEMBER_CRM_HOME }
    : { ...base.home, ...patch.home };
  const header = isMemberCrm && isLegacyStandard
    ? { ...MEMBER_CRM_HEADER, rightButton: { ...MEMBER_CRM_HEADER.rightButton } }
    : {
      ...base.header,
      ...patch.header,
      rightButton: {
        ...base.header.rightButton,
        ...patch.header?.rightButton,
      },
    };
  const savedTabs = patch.navigation?.tabs;
  const tabs = isMemberCrm
    ? memberCrmNavigationTabs(savedTabs)
    : (savedTabs !== undefined && savedTabs.length > 0
      ? (store.storeId === 'store_landing_showcase' ? savedTabs : savedTabs.slice(0, 6))
      : base.navigation.tabs);

  return {
    ...base,
    ...patch,
    designPreset: patch.designPreset ?? base.designPreset,
    experienceId: patch.experienceId ?? base.experienceId,
    themeId: patch.themeId ?? base.themeId,
    appTemplateId,
    colors: { ...base.colors, ...patch.colors },
    appearance: { ...base.appearance, ...patch.appearance },
    brand: { ...base.brand, ...patch.brand },
    home,
    header,
    coupon: {
      ...base.coupon,
      ...patch.coupon,
      defaultStatus: savedDefaultStatus === 'used' || savedDefaultStatus === 'expired'
        ? 'ended'
        : savedDefaultStatus ?? base.coupon.defaultStatus,
    },
    order: { ...base.order, ...patch.order },
    membership: { ...base.membership, ...patch.membership },
    stamp: { ...base.stamp, ...patch.stamp },
    navigation: {
      ...base.navigation,
      ...patch.navigation,
      tabs,
      linkUrls: { ...base.navigation.linkUrls, ...patch.navigation?.linkUrls },
    },
  };
}

/** デモ用の業態ギャラリーは出さない。お客様画面は Skip と同じ構成にする。 */
export function isDesignShowcaseMode(): boolean {
  return false;
}

function readShowcaseStoreId(stores: StoreAppConfig[]): string {
  const fallback = stores.some((store) => store.storeId === SHOWCASE_DEFAULT_STORE_ID)
    ? SHOWCASE_DEFAULT_STORE_ID
    : (stores[0]?.storeId ?? 'default');
  try {
    const saved = window.localStorage.getItem(showcaseStoreStorageKey);
    if (saved !== null && stores.some((store) => store.storeId === saved)) {
      return saved;
    }
  } catch {
    return fallback;
  }
  return fallback;
}

function resolveStores(): StoreAppConfig[] {
  if (isDesignShowcaseMode()) {
    const showcase = appConfig.stores.find((store) => store.storeId === SHOWCASE_DEFAULT_STORE_ID);
    return showcase !== undefined ? [showcase] : appConfig.stores.slice(0, 1);
  }
  return [buildProjectStore()];
}

function loadInitialCustomizations(stores: StoreAppConfig[]): Record<string, AppCustomization> {
  let byStore: Record<string, AppCustomizationPatch> = {};
  try {
    const saved = window.localStorage.getItem(customizationStorageKey);
    if (saved) {
      const persisted = JSON.parse(saved) as PersistedCustomizations;
      if (persisted.version === 3 && persisted.experienceVersion === experienceVersion) {
        byStore = persisted.byStore ?? {};
      }
    }
  } catch {
    byStore = {};
  }

  const projectKey = typeof window !== 'undefined' && window.__PROJECT_KEY__
    ? String(window.__PROJECT_KEY__)
    : '';
  // ショーケース（/liff）では実店舗の保存設定をデモ店に混ぜない
  if (!isDesignShowcaseMode() && projectKey !== '') {
    const serverTarget = stores.find((store) => store.storeId === projectKey);
    if (serverTarget !== undefined) {
      const serverPatch = readServerCustomizationPatch(serverTarget.storeId);
      if (serverPatch !== undefined) {
        byStore[serverTarget.storeId] = serverPatch;
      }
    }
  }

  return Object.fromEntries(stores.map((store) => [store.storeId, mergeCustomization(store, byStore[store.storeId])]));
}

export function AppConfigProvider({ children }: { children: ReactNode }) {
  const stores = useMemo(() => resolveStores(), []);
  const isDesignerEmbedPreview = window.__IS_DESIGNER_EMBED_PREVIEW__ === true;
  const [activeStoreId] = useState(() => {
    if (isDesignShowcaseMode()) {
      return readShowcaseStoreId(stores);
    }
    return stores[0]?.storeId ?? 'default';
  });
  const [customizations, setCustomizations] = useState<Record<string, AppCustomization>>(() => loadInitialCustomizations(stores));
  useEffect(() => {
    if (isDesignerEmbedPreview) {
      return;
    }
    const payload: PersistedCustomizations = { version: 3, experienceVersion, byStore: customizations };
    try {
      window.localStorage.setItem(customizationStorageKey, JSON.stringify(payload));
    } catch {
      // Browser storage may be denied or full; keep the current customization.
    }
  }, [customizations, isDesignerEmbedPreview]);

  useEffect(() => {
    if (isDesignerEmbedPreview || !isDesignShowcaseMode()) {
      return;
    }
    try {
      window.localStorage.setItem(showcaseStoreStorageKey, activeStoreId);
    } catch {
      return;
    }
  }, [activeStoreId, isDesignerEmbedPreview]);

  useEffect(() => {
    if (!isDesignerEmbedPreview) {
      return undefined;
    }
    const previewStore = stores[0];
    if (previewStore === undefined) {
      return undefined;
    }

    const receivePreviewCustomization = (event: MessageEvent) => {
      if (event.origin !== window.location.origin || event.data?.type !== 'reggit:designer-preview:update') {
        return;
      }
      const patch = event.data.customization as AppCustomizationPatch | undefined;
      if (patch === undefined || patch === null || typeof patch !== 'object') {
        return;
      }
      const next = mergeCustomization(previewStore, patch);
      setCustomizations((current) => ({
        ...current,
        [previewStore.storeId]: next,
      }));
    };

    window.addEventListener('message', receivePreviewCustomization);
    if (window.parent !== window) {
      window.parent.postMessage({ type: 'reggit:designer-preview:ready' }, window.location.origin);
    }
    return () => window.removeEventListener('message', receivePreviewCustomization);
  }, [isDesignerEmbedPreview, stores]);

  const activeStore = useMemo(() => {
    return stores.find((store) => store.storeId === activeStoreId) ?? stores[0];
  }, [activeStoreId, stores]);

  const customization = activeStore !== undefined
    ? (customizations[activeStore.storeId] ?? createDefaultCustomization(activeStore))
    : createDefaultCustomization(buildProjectStore());
  const value = useMemo<AppConfigContextValue>(() => {
    if (activeStore === undefined) {
      throw new Error('店舗設定がありません');
    }
    return { activeStore, customization };
  }, [activeStore, customization]);

  return <AppConfigContext.Provider value={value}>{children}</AppConfigContext.Provider>;
}

export function useAppConfig() {
  const context = useContext(AppConfigContext);
  if (context === null) {
    throw new Error('useAppConfig must be used within AppConfigProvider');
  }
  return context;
}

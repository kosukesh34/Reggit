import { createContext, useContext, useEffect, useMemo, useState, type ReactNode } from 'react';
import { appConfig } from '../config/appConfig';
import { createDefaultCustomization } from '../config/customization';
import { buildProjectStore, readServerCustomizationPatch } from '../config/projectStore';
import { themeMap } from '../themes/themes';
import type { StoreAppConfig } from '../types/app';
import type { AppCustomization, AppCustomizationPatch, CouponStatus } from '../types/customization';

const customizationStorageKey = 'reggit-app-customization-v3';

interface PersistedCustomizations {
  version: 3;
  experienceVersion: 5;
  byStore: Record<string, AppCustomizationPatch>;
}

interface AppConfigContextValue {
  stores: StoreAppConfig[];
  activeStore: StoreAppConfig;
  setActiveStoreId: (storeId: string) => void;
  activeThemeId: string;
  setActiveThemeId: (themeId: string) => void;
  customization: AppCustomization;
  getStoreCustomization: (storeId: string) => AppCustomization;
  updateCustomization: (patch: AppCustomizationPatch) => void;
  resetCustomization: () => void;
}

const AppConfigContext = createContext<AppConfigContextValue | null>(null);

function mergeCustomization(store: StoreAppConfig, patch?: AppCustomizationPatch): AppCustomization {
  const base = createDefaultCustomization(store);
  if (patch === undefined) {
    return base;
  }
  const savedDefaultStatus = patch.coupon?.defaultStatus as CouponStatus | 'used' | 'expired' | undefined;

  return {
    ...base,
    ...patch,
    colors: { ...base.colors, ...patch.colors },
    appearance: { ...base.appearance, ...patch.appearance },
    home: { ...base.home, ...patch.home },
    header: { ...base.header, ...patch.header },
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
      tabs: patch.navigation?.tabs?.length ? patch.navigation.tabs.slice(0, 6) : base.navigation.tabs,
    },
  };
}

function resolveStores(isAdminStudio: boolean): StoreAppConfig[] {
  const projectStore = buildProjectStore();
  if (!isAdminStudio) {
    return [projectStore];
  }
  // 管理画面では既存モック業態も参照用に残す
  return [projectStore, ...appConfig.stores.filter((store) => store.storeId !== projectStore.storeId)];
}

function loadInitialCustomizations(stores: StoreAppConfig[]): Record<string, AppCustomization> {
  let byStore: Record<string, AppCustomizationPatch> = {};
  try {
    const saved = window.localStorage.getItem(customizationStorageKey);
    if (saved) {
      const persisted = JSON.parse(saved) as PersistedCustomizations;
      if (persisted.version === 3 && persisted.experienceVersion === 5) {
        byStore = persisted.byStore ?? {};
      }
    }
  } catch {
    byStore = {};
  }

  const projectStore = stores[0];
  if (projectStore !== undefined) {
    const serverPatch = readServerCustomizationPatch(projectStore.storeId);
    if (serverPatch !== undefined) {
      byStore[projectStore.storeId] = serverPatch;
    }
  }

  return Object.fromEntries(stores.map((store) => [store.storeId, mergeCustomization(store, byStore[store.storeId])]));
}

export function AppConfigProvider({ children, isAdminStudio = false }: { children: ReactNode; isAdminStudio?: boolean }) {
  const stores = useMemo(() => resolveStores(isAdminStudio), [isAdminStudio]);
  const [activeStoreId, setActiveStoreId] = useState(stores[0]?.storeId ?? 'default');
  const [activeThemeId, setActiveThemeId] = useState(
    themeMap[stores[0]?.themeId ?? 'minimal-light']?.id ?? 'minimal-light',
  );
  const [customizations, setCustomizations] = useState<Record<string, AppCustomization>>(() => loadInitialCustomizations(stores));

  useEffect(() => {
    const payload: PersistedCustomizations = { version: 3, experienceVersion: 5, byStore: customizations };
    window.localStorage.setItem(customizationStorageKey, JSON.stringify(payload));
  }, [customizations]);

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
    return {
      stores,
      activeStore,
      setActiveStoreId: (storeId: string) => {
        setActiveStoreId(storeId);
        const nextStore = stores.find((store) => store.storeId === storeId);
        if (nextStore !== undefined) {
          setActiveThemeId(nextStore.themeId);
        }
      },
      activeThemeId,
      setActiveThemeId,
      customization,
      getStoreCustomization: (storeId: string) => {
        const store = stores.find((item) => item.storeId === storeId) ?? activeStore;
        return customizations[storeId] ?? createDefaultCustomization(store);
      },
      updateCustomization: (patch) => {
        setCustomizations((current) => {
          const currentCustomization = current[activeStore.storeId] ?? createDefaultCustomization(activeStore);
          return {
            ...current,
            [activeStore.storeId]: {
              ...currentCustomization,
              ...patch,
              colors: { ...currentCustomization.colors, ...patch.colors },
              appearance: { ...currentCustomization.appearance, ...patch.appearance },
              home: { ...currentCustomization.home, ...patch.home },
              header: { ...currentCustomization.header, ...patch.header },
              coupon: { ...currentCustomization.coupon, ...patch.coupon },
              order: { ...currentCustomization.order, ...patch.order },
              membership: { ...currentCustomization.membership, ...patch.membership },
              stamp: { ...currentCustomization.stamp, ...patch.stamp },
              navigation: { ...currentCustomization.navigation, ...patch.navigation },
            },
          };
        });
      },
      resetCustomization: () => {
        setCustomizations((current) => ({
          ...current,
          [activeStore.storeId]: createDefaultCustomization(activeStore),
        }));
      },
    };
  }, [activeStore, activeThemeId, customization, customizations, stores]);

  return <AppConfigContext.Provider value={value}>{children}</AppConfigContext.Provider>;
}

export function useAppConfig() {
  const context = useContext(AppConfigContext);
  if (context === null) {
    throw new Error('useAppConfig must be used within AppConfigProvider');
  }
  return context;
}

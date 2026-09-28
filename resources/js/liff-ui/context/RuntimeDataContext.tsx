import { createContext, useCallback, useContext, useEffect, useMemo, useRef, useState, type ReactNode } from "react";
import { additionalStores } from "../config/additionalStores";
import { couponsByStore, memberProfileByStore, newsByStore, storeLocationsByStore, storeSummaryByStore } from "../mocks/content";
import { reggitApi } from "../services/reggitApi";
import type { NewsItem } from "../types/app";
import type {
  ContactInput,
  DataMode,
  NotificationSettings,
  OperationReceipt,
  OrderInput,
  QueueInput,
  ReservationInput,
  RuntimeBootstrap,
} from "../types/runtime";
import { useAppConfig } from "./AppConfigContext";

const MOCK_STORE_IDS = new Set([
  ...Object.keys(storeSummaryByStore),
  ...additionalStores.map((store) => store.storeId),
]);

const storageKey = typeof document !== "undefined" && document.body.dataset.appMode === "demo"
  ? "reggit-demo-runtime-data-v1" : "reggit-runtime-data-v2";

interface LocalStoreState {
  usedCouponIds: string[];
  stampCount: number;
  receipts: OperationReceipt[];
}

type LocalState = Record<string, LocalStoreState>;

interface RuntimeDataContextValue extends RuntimeBootstrap {
  mode: DataMode;
  loading: boolean;
  error?: string;
  refresh: () => Promise<void>;
  createOrder: (input: OrderInput) => Promise<OperationReceipt>;
  createReservation: (input: ReservationInput) => Promise<OperationReceipt>;
  joinQueue: (input: QueueInput) => Promise<OperationReceipt>;
  useCoupon: (couponId: string) => Promise<OperationReceipt>;
  addStamp: (staffCode: string) => Promise<OperationReceipt>;
  redeemPoints: (points: number) => Promise<OperationReceipt>;
  saveSettings: (settings: NotificationSettings) => Promise<OperationReceipt>;
  submitContact: (input: ContactInput) => Promise<OperationReceipt>;
  markAnnouncementRead: (announcementId: string) => Promise<void>;
}

const RuntimeDataContext = createContext<RuntimeDataContextValue | null>(null);

function loadLocalState(): LocalState {
  try {
    return JSON.parse(window.localStorage.getItem(storageKey) ?? "{}") as LocalState;
  } catch {
    return {};
  }
}

function createReceipt(prefix: string): OperationReceipt {
  const suffix = Math.random().toString(36).slice(2, 7).toUpperCase();
  return { reference: `${prefix}-${suffix}`, acceptedAt: new Date().toISOString(), status: "accepted" };
}

export function RuntimeDataProvider({ children }: { children: ReactNode }) {
  const { activeStore } = useAppConfig();
  const isAnnouncementFormPreview = typeof window !== 'undefined'
    && window.__IS_DESIGNER_EMBED_PREVIEW__ === true
    && window.__ANNOUNCEMENT_FORM_PREVIEW__ === true;
  const [localState, setLocalState] = useState<LocalState>(loadLocalState);
  const [remoteData, setRemoteData] = useState<RuntimeBootstrap>();
  const [announcementFormPreview, setAnnouncementFormPreview] = useState<NewsItem>();
  const isDemoPreview = typeof document !== 'undefined' && document.body.classList.contains('liff-demo-data');
  const isMockShowcaseStore = MOCK_STORE_IDS.has(activeStore.storeId);
  const usePreviewData = isDemoPreview || isMockShowcaseStore || !reggitApi.configured;
  const [mode, setMode] = useState<DataMode>(usePreviewData ? 'preview' : 'offline');
  const [loading, setLoading] = useState(reggitApi.configured && !usePreviewData);
  const [error, setError] = useState<string>();
  const refreshGeneration = useRef(0);

  const localStore = localState[activeStore.storeId] ?? { usedCouponIds: [], stampCount: 6, receipts: [] };
  const mockData = useMemo<RuntimeBootstrap>(() => {
    const fallbackId = 'store_cafe';
    const primaryStore = storeSummaryByStore[activeStore.storeId] ?? storeSummaryByStore[fallbackId];
    const member = memberProfileByStore[activeStore.storeId] ?? memberProfileByStore[fallbackId] ?? {
      name: 'ゲスト',
      number: '未発行',
      rank: 'レギュラー',
      points: 0,
      expiration: '—',
      nextRankLabel: '次のランク',
      isMember: false,
    };
    return {
      member,
      coupons: couponsByStore[activeStore.storeId] ?? couponsByStore[fallbackId] ?? [],
      news: newsByStore[activeStore.storeId] ?? newsByStore[fallbackId] ?? [],
      stores: storeLocationsByStore[activeStore.storeId]
        ?? storeLocationsByStore[fallbackId]
        ?? (primaryStore ? [primaryStore] : []),
      usedCouponIds: localStore.usedCouponIds,
      stampCount: localStore.stampCount,
      notificationCount: 2,
    };
  }, [activeStore.storeId, localStore.stampCount, localStore.usedCouponIds]);

  const refresh = useCallback(async () => {
    const generation = refreshGeneration.current + 1;
    refreshGeneration.current = generation;
    if (!reggitApi.configured || isDemoPreview || isMockShowcaseStore) {
      setRemoteData(undefined);
      setMode('preview');
      setLoading(false);
      setError(undefined);
      return;
    }
    setLoading(true);
    try {
      const data = await reggitApi.bootstrap(activeStore.storeId);
      if (generation !== refreshGeneration.current) {
        return;
      }
      setRemoteData(data);
      setMode('live');
      setError(undefined);
    } catch (caught) {
      if (generation !== refreshGeneration.current) {
        return;
      }
      setRemoteData(undefined);
      setMode('offline');
      setError(caught instanceof Error ? caught.message : '店舗データを取得できませんでした');
    } finally {
      if (generation === refreshGeneration.current) {
        setLoading(false);
      }
    }
  }, [activeStore.storeId, isDemoPreview, isMockShowcaseStore]);

  useEffect(() => {
    let cancelled = false;
    const run = (): void => {
      if (!cancelled) {
        void refresh();
      }
    };

    const onReload = (): void => {
      run();
    };
    document.addEventListener('liff-member-reload', onReload);
    document.addEventListener('liff-payment-completed', onReload);

    const waitForLiff = typeof window !== 'undefined'
      && window.__IS_LIFF__ === true
      && !window.__LINE_USER_ID__
      && window.__LIFF_READY__ !== true;
    if (waitForLiff) {
      let timer = 0;
      const onLiffReady = (): void => {
        if (timer !== 0) {
          window.clearTimeout(timer);
          timer = 0;
        }
        run();
      };
      document.addEventListener('liff-ready', onLiffReady);
      timer = window.setTimeout(run, 3000);
      return () => {
        cancelled = true;
        document.removeEventListener('liff-ready', onLiffReady);
        document.removeEventListener('liff-member-reload', onReload);
        document.removeEventListener('liff-payment-completed', onReload);
        if (timer !== 0) {
          window.clearTimeout(timer);
        }
      };
    }

    run();
    document.addEventListener('liff-ready', onReload);
    return () => {
      cancelled = true;
      document.removeEventListener('liff-ready', onReload);
      document.removeEventListener('liff-member-reload', onReload);
      document.removeEventListener('liff-payment-completed', onReload);
    };
  }, [refresh]);

  useEffect(() => {
    window.localStorage.setItem(storageKey, JSON.stringify(localState));
  }, [localState]);

  useEffect(() => {
    if (!isAnnouncementFormPreview) return undefined;
    const receiveAnnouncementPreview = (event: MessageEvent): void => {
      if (event.origin !== window.location.origin || event.data?.type !== 'reggit:announcement-form-preview:update') return;
      const incoming = event.data.announcement as Record<string, unknown> | undefined;
      if (incoming === undefined || incoming === null || typeof incoming !== 'object') return;
      setAnnouncementFormPreview({
        id: '__reggit_announcement_form_preview__',
        title: typeof incoming.title === 'string' && incoming.title.trim() !== '' ? incoming.title : 'お知らせのタイトル',
        date: typeof incoming.published_at === 'string' ? incoming.published_at : new Date().toISOString(),
        category: incoming.is_pinned === true ? '重要' : 'お知らせ',
        body: typeof incoming.body === 'string' ? incoming.body : '',
        image: typeof incoming.image_url === 'string' && incoming.image_url !== '' ? incoming.image_url : undefined,
        isRead: false,
        isPinned: incoming.is_pinned === true,
      });
    };
    window.addEventListener('message', receiveAnnouncementPreview);
    if (window.parent !== window) {
      window.parent.postMessage({ type: 'reggit:announcement-form-preview:ready' }, window.location.origin);
    }
    return () => window.removeEventListener('message', receiveAnnouncementPreview);
  }, [isAnnouncementFormPreview]);

  const updateLocal = useCallback((updater: (current: LocalStoreState) => LocalStoreState) => {
    setLocalState((current) => {
      const storeState = current[activeStore.storeId] ?? { usedCouponIds: [], stampCount: 6, receipts: [] };
      return { ...current, [activeStore.storeId]: updater(storeState) };
    });
  }, [activeStore.storeId]);

  const run = useCallback(async (live: () => Promise<OperationReceipt>, prefix: string, after?: (receipt: OperationReceipt) => void) => {
    if (reggitApi.configured && !isDemoPreview && !isMockShowcaseStore) {
      if (mode !== 'live') throw new Error('店舗システムに接続できないため、この操作は完了できません');
      const receipt = await live();
      after?.(receipt);
      await refresh();
      return receipt;
    }
    const receipt = createReceipt(prefix);
    updateLocal((current) => ({ ...current, receipts: [receipt, ...current.receipts].slice(0, 30) }));
    after?.(receipt);
    return receipt;
  }, [isDemoPreview, isMockShowcaseStore, mode, refresh, updateLocal]);

  const emptyLiveData = useMemo<RuntimeBootstrap>(() => ({
    member: {
      name: 'ゲスト',
      number: '未発行',
      rank: 'レギュラー',
      points: 0,
      expiration: '—',
      nextRankLabel: '次のランク',
      isMember: false,
    },
    coupons: [],
    news: [],
    stores: [{
      name: activeStore.storeName,
      address: '',
      openHours: '',
      access: '',
      phone: '',
    }],
    usedCouponIds: [],
    stampCount: 0,
    notificationCount: 0,
  }), [activeStore.storeName]);

  const baseData = remoteData ?? (usePreviewData ? mockData : emptyLiveData);
  const data = useMemo(
    () => announcementFormPreview === undefined
      ? baseData
      : { ...baseData, news: [announcementFormPreview], notificationCount: 1 },
    [announcementFormPreview, baseData],
  );
  // 初回ロード中はモック店舗データを画面に出さない（ユーザー困惑防止）
  const isBootstrapping = loading && remoteData === undefined && !usePreviewData;
  const markAnnouncementRead = useCallback(async (announcementId: string) => {
    if (isAnnouncementFormPreview && announcementId === '__reggit_announcement_form_preview__') return;
    if (reggitApi.configured && !isDemoPreview && !isMockShowcaseStore) {
      if (mode !== 'live') throw new Error('店舗システムに接続できないため、既読状態を保存できません');
      await reggitApi.markAnnouncementRead(announcementId);
    }
    setRemoteData((current) => current === undefined ? current : {
      ...current,
      news: current.news.map((item) => item.id === announcementId ? { ...item, isRead: true } : item),
      notificationCount: Math.max(0, current.notificationCount - (current.news.some((item) => item.id === announcementId && item.isRead !== true) ? 1 : 0)),
    });
  }, [isAnnouncementFormPreview, isDemoPreview, isMockShowcaseStore, mode]);

  const value = useMemo<RuntimeDataContextValue>(() => ({
    ...data,
    mode: isBootstrapping ? 'offline' : mode,
    loading: isBootstrapping || loading,
    error: isBootstrapping ? undefined : error,
    refresh,
    createOrder: (input) => run(() => reggitApi.createOrder(input), "ORD"),
    createReservation: (input) => run(() => reggitApi.createReservation(input), "RSV"),
    joinQueue: (input) => run(() => reggitApi.joinQueue(input), "QUE"),
    useCoupon: (couponId) => run(
      () => reggitApi.useCoupon(activeStore.storeId, couponId),
      "CPN",
      () => updateLocal((current) => ({ ...current, usedCouponIds: [...new Set([...current.usedCouponIds, couponId])] })),
    ),
    addStamp: (staffCode) => run(
      () => reggitApi.addStamp(activeStore.storeId, staffCode),
      "STP",
      () => updateLocal((current) => ({ ...current, stampCount: current.stampCount + 1 })),
    ),
    redeemPoints: (points) => run(() => reggitApi.redeemPoints(activeStore.storeId, points), "PTS"),
    saveSettings: (settings) => run(() => reggitApi.saveSettings(activeStore.storeId, settings), "SET"),
    submitContact: (input) => run(() => reggitApi.submitContact(activeStore.storeId, input), "CNT"),
    markAnnouncementRead,
  }), [activeStore.storeId, data, error, isBootstrapping, loading, markAnnouncementRead, mode, refresh, run, updateLocal]);

  return <RuntimeDataContext.Provider value={value}>{children}</RuntimeDataContext.Provider>;
}

export function useRuntimeData() {
  const context = useContext(RuntimeDataContext);
  if (!context) throw new Error("useRuntimeData must be used within RuntimeDataProvider");
  return context;
}

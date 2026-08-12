import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from "react";
import { couponsByStore, memberProfileByStore, newsByStore, storeLocationsByStore, storeSummaryByStore } from "../mocks/content";
import { reggitApi } from "../services/reggitApi";
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

const storageKey = "reggit-runtime-data-v2";

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
  const [localState, setLocalState] = useState<LocalState>(loadLocalState);
  const [remoteData, setRemoteData] = useState<RuntimeBootstrap>();
  const isDemoPreview = typeof document !== 'undefined' && document.body.classList.contains('liff-demo-data');
  const [mode, setMode] = useState<DataMode>(!reggitApi.configured || isDemoPreview ? 'preview' : 'offline');
  const [loading, setLoading] = useState(reggitApi.configured && !isDemoPreview);
  const [error, setError] = useState<string>();

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
    if (!reggitApi.configured || isDemoPreview) {
      setRemoteData(undefined);
      setMode('preview');
      setLoading(false);
      setError(undefined);
      return;
    }
    setLoading(true);
    try {
      const data = await reggitApi.bootstrap(activeStore.storeId);
      setRemoteData(data);
      setMode('live');
      setError(undefined);
    } catch (caught) {
      setRemoteData(undefined);
      setMode('offline');
      setError(caught instanceof Error ? caught.message : '店舗データを取得できませんでした');
    } finally {
      setLoading(false);
    }
  }, [activeStore.storeId, isDemoPreview]);

  useEffect(() => {
    void refresh();
  }, [refresh]);

  useEffect(() => {
    const onMemberChanged = (): void => {
      void refresh();
    };
    document.addEventListener("liff-member-reload", onMemberChanged);
    document.addEventListener("liff-payment-completed", onMemberChanged);
    return () => {
      document.removeEventListener("liff-member-reload", onMemberChanged);
      document.removeEventListener("liff-payment-completed", onMemberChanged);
    };
  }, [refresh]);

  useEffect(() => {
    window.localStorage.setItem(storageKey, JSON.stringify(localState));
  }, [localState]);

  const updateLocal = useCallback((updater: (current: LocalStoreState) => LocalStoreState) => {
    setLocalState((current) => {
      const storeState = current[activeStore.storeId] ?? { usedCouponIds: [], stampCount: 6, receipts: [] };
      return { ...current, [activeStore.storeId]: updater(storeState) };
    });
  }, [activeStore.storeId]);

  const run = useCallback(async (live: () => Promise<OperationReceipt>, prefix: string, after?: (receipt: OperationReceipt) => void) => {
    if (reggitApi.configured && !isDemoPreview) {
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
  }, [isDemoPreview, mode, refresh, updateLocal]);

  const data = remoteData ?? mockData;
  // 初回ロード中はモック店舗データを画面に出さない（ユーザー困惑防止）
  const isBootstrapping = loading && remoteData === undefined && !isDemoPreview;
  const markAnnouncementRead = useCallback(async (announcementId: string) => {
    if (reggitApi.configured && !isDemoPreview) {
      if (mode !== 'live') throw new Error('店舗システムに接続できないため、既読状態を保存できません');
      await reggitApi.markAnnouncementRead(announcementId);
    }
    setRemoteData((current) => current === undefined ? current : {
      ...current,
      news: current.news.map((item) => item.id === announcementId ? { ...item, isRead: true } : item),
      notificationCount: Math.max(0, current.notificationCount - (current.news.some((item) => item.id === announcementId && item.isRead !== true) ? 1 : 0)),
    });
  }, [isDemoPreview, mode]);

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

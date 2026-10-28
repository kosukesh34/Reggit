import { nativeRequest } from "../native/NativeResource";
import type { PromoPopup } from "../native/promoPopupPolicy";

/** ポップアップの取得元。テストや別の取得経路に差し替えられるよう関数で受け取る */
export type PromoPopupSource = () => Promise<PromoPopup[]>;

/** 端末ごとの表示済み記録（最後に表示した日） */
export interface PromoPopupSeenStore {
  lastSeen(key: string): string | null;
  markSeen(key: string, day: string): void;
}

export type PromoPopupEvent = "view" | "click" | "secondary_click" | "close";

/** 表示・タップの記録。集計用なので失敗しても画面には出さない */
export type PromoPopupEventReporter = (popupId: number, event: PromoPopupEvent) => void;

export const reportPromoPopupEvent: PromoPopupEventReporter = (popupId, event) => {
  void nativeRequest(`/api/promo-popups/${popupId}/events`, { event }).catch(() => { /* 集計できないだけ */ });
};

export const fetchPromoPopups: PromoPopupSource = async () => (await nativeRequest<{ popups?: PromoPopup[] }>("/api/promo-popups")).popups ?? [];

export function localPromoPopupSeenStore(projectKey: string): PromoPopupSeenStore {
  const storageKey = (key: string) => `reggit_promo_popup:${projectKey}:${key}`;
  return {
    lastSeen(key) {
      try { return window.localStorage.getItem(storageKey(key)); } catch { return null; }
    },
    markSeen(key, day) {
      try { window.localStorage.setItem(storageKey(key), day); } catch { /* 保存できない端末では起動ごとに表示する */ }
    },
  };
}

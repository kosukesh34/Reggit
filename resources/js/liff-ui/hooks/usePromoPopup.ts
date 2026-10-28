import { useCallback, useEffect, useState } from "react";
import { localDay, pickPopup, popupLink, popupSeenKey, type PromoPopup, type PromoPopupLink } from "../native/promoPopupPolicy";
import type { PromoPopupEventReporter, PromoPopupSeenStore, PromoPopupSource } from "../services/promoPopups";

// 「起動のたび」も同じ読み込み中に画面を移動しただけでは出し直さない
const shownThisLaunch = new Set<number>();

/** 起動時に1件だけ選び、表示済みを記録する。取得元と保存先は呼び出し側から渡す */
export function usePromoPopup(enabled: boolean, source: PromoPopupSource, store: PromoPopupSeenStore, report: PromoPopupEventReporter) {
  const [popup, setPopup] = useState<PromoPopup>();

  useEffect(() => {
    if (!enabled) return undefined;
    let cancelled = false;
    source().then((popups) => {
      const today = localDay(new Date());
      const next = pickPopup(popups, today, shownThisLaunch, (item) => store.lastSeen(popupSeenKey(item)));
      if (cancelled || next === undefined) return;
      shownThisLaunch.add(next.id);
      store.markSeen(popupSeenKey(next), today);
      setPopup(next);
      report(next.id, "view");
    }).catch(() => { /* 宣伝が出ないだけなので画面は止めない */ });
    return () => { cancelled = true; };
  }, [enabled, source, store, report]);

  const dismiss = useCallback(() => {
    if (popup) report(popup.id, "close");
    setPopup(undefined);
  }, [popup, report]);
  /** 押したボタンの遷移先を返して閉じる */
  const takeAction = useCallback((button: "primary" | "secondary"): PromoPopupLink => {
    const link = popupLink(button === "primary" ? popup?.link_url : popup?.secondary_link_url);
    // 遷移先のないボタン（「閉じる」）は、タップ率に入れず閉じたとして数える
    if (popup) report(popup.id, link.kind === "none" ? "close" : button === "primary" ? "click" : "secondary_click");
    setPopup(undefined);
    return link;
  }, [popup, report]);

  return { popup, dismiss, takeAction };
}

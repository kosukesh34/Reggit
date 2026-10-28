import { useCallback, useMemo } from "react";
import { useNavigate } from "react-router-dom";
import { PromoPopupView } from "./PromoPopupView";
import { usePromoPopup } from "../../hooks/usePromoPopup";
import { fetchPromoPopups, localPromoPopupSeenStore, reportPromoPopupEvent } from "../../services/promoPopups";
import { isDemoDataMode } from "../../../frontend/demo/demoMode";
import type { PromoPopupLink } from "../../native/promoPopupPolicy";

function openExternal(url: string): void {
  const liff = window.liff as { isInClient?: () => boolean; openWindow?: (input: { url: string; external: boolean }) => void } | undefined;
  if (liff?.isInClient?.() && liff.openWindow) liff.openWindow({ url, external: true });
  else window.open(url, "_blank", "noopener,noreferrer");
}

/** 起動時の宣伝ポップアップ。取得・判定（usePromoPopup）と見た目（PromoPopupView）をつなぎ、遷移だけを担当する。 */
export function PromoPopupHost({ enabled }: { enabled: boolean }) {
  const navigate = useNavigate();
  const store = useMemo(() => localPromoPopupSeenStore(window.__PROJECT_KEY__ ?? ""), []);
  const active = enabled && !isDemoDataMode() && window.__IS_DESIGNER_EMBED_PREVIEW__ !== true;
  const { popup, dismiss, takeAction } = usePromoPopup(active, fetchPromoPopups, store, reportPromoPopupEvent);
  const open = useCallback((link: PromoPopupLink) => {
    if (link.kind === "app") navigate(link.path);
    else if (link.kind === "external") openExternal(link.url);
  }, [navigate]);

  return popup ? <PromoPopupView popup={popup} onClose={dismiss} onAction={(button) => open(takeAction(button))} /> : null;
}

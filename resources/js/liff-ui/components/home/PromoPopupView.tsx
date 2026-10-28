import { X } from "lucide-react";
import { useEffect, useId, type ReactNode } from "react";
import { createPortal } from "react-dom";
import { CenterPopup } from "../ui/CenterPopup";
import { BottomSheet } from "../ui/BottomSheet";
import type { PromoPopup } from "../../native/promoPopupPolicy";

/** 宣伝ポップアップの見た目。形式（全画面・中央モーダル・下からシート・下部バナー）ごとに外枠だけを切り替える。 */
export function PromoPopupView({ popup, onClose, onAction }: { popup: PromoPopup; onClose: () => void; onAction: (button: "primary" | "secondary") => void }) {
  const content = <>
    {popup.image_url ? <img className="promo-popup__image" src={popup.image_url} alt="" /> : null}
    {popup.body ? <p className="promo-popup__body">{popup.body}</p> : null}
    <button type="button" className="native-button promo-popup__action" onClick={() => onAction("primary")}>{popup.button_label || (popup.link_url ? "詳しく見る" : "閉じる")}</button>
    {popup.secondary_button_label ? <button type="button" className="native-button native-button--secondary promo-popup__action promo-popup__action--secondary" onClick={() => onAction("secondary")}>{popup.secondary_button_label}</button> : null}
  </>;
  switch (popup.display_type) {
    case "modal": return <CenterPopup open onClose={onClose} title={popup.title} className="promo-popup">{content}</CenterPopup>;
    case "bottom_sheet": return <BottomSheet open onClose={onClose} title={popup.title} className="promo-popup">{content}</BottomSheet>;
    case "banner": return <PromoBanner popup={popup} onClose={onClose} onAction={onAction} />;
    default: return <PromoFullscreen title={popup.title} hasImage={Boolean(popup.image_url)} onClose={onClose}>{content}</PromoFullscreen>;
  }
}

function PromoFullscreen({ title, hasImage, onClose, children }: { title: string; hasImage: boolean; onClose: () => void; children: ReactNode }) {
  const titleId = useId();
  useEffect(() => {
    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    const onKeyDown = (event: KeyboardEvent) => { if (event.key === "Escape") onClose(); };
    window.addEventListener("keydown", onKeyDown);
    return () => { document.body.style.overflow = previousOverflow; window.removeEventListener("keydown", onKeyDown); };
  }, [onClose]);
  return createPortal(<div className={`promo-fullscreen${hasImage ? " has-image" : ""}`} role="dialog" aria-modal="true" aria-labelledby={titleId}>
    <button type="button" className="promo-fullscreen__close" aria-label="閉じる" onClick={onClose} autoFocus><X size={22} /></button>
    <div className="promo-fullscreen__panel"><h2 id={titleId}>{title}</h2>{children}</div>
  </div>, document.body);
}

function PromoBanner({ popup, onClose, onAction }: { popup: PromoPopup; onClose: () => void; onAction: (button: "primary" | "secondary") => void }) {
  // バナーは細いので、本体を押したときの1つ目のボタンだけを使う
  return createPortal(<aside className="promo-banner" aria-label="お知らせ">
    <button type="button" className="promo-banner__main" onClick={() => onAction("primary")}>
      {popup.image_url ? <img src={popup.image_url} alt="" /> : null}
      <span><strong>{popup.title}</strong>{popup.body ? <small>{popup.body}</small> : null}</span>
    </button>
    <button type="button" className="promo-banner__close" aria-label="閉じる" onClick={onClose}><X size={18} /></button>
  </aside>, document.body);
}

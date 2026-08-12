import { X } from "lucide-react";
import { useEffect, useId, type ReactNode } from "react";
import { createPortal } from "react-dom";

interface CenterPopupProps {
  open: boolean;
  onClose: () => void;
  title: string;
  children: ReactNode;
  className?: string;
}

/** 重要な内容を画面中央に表示する、モバイル向けダイアログ。 */
export function CenterPopup({ open, onClose, title, children, className = "" }: CenterPopupProps) {
  const titleId = useId();

  useEffect(() => {
    if (!open) return;

    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === "Escape") onClose();
    };
    window.addEventListener("keydown", onKeyDown);

    return () => {
      document.body.style.overflow = previousOverflow;
      window.removeEventListener("keydown", onKeyDown);
    };
  }, [onClose, open]);

  if (!open) return null;

  return createPortal(
    <div className={`center-popup${className ? ` ${className}` : ""}`} role="dialog" aria-modal="true" aria-labelledby={titleId}>
      <button className="center-popup__backdrop" type="button" aria-label="閉じる" onClick={onClose} />
      <div className="center-popup__panel">
        <div className="center-popup__header">
          <h2 id={titleId}>{title}</h2>
          <button className="center-popup__close" type="button" aria-label="閉じる" onClick={onClose}>
            <X size={19} />
          </button>
        </div>
        <div className="center-popup__body">{children}</div>
      </div>
    </div>,
    document.body,
  );
}

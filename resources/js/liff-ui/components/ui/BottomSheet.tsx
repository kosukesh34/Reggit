import { X } from "lucide-react";
import { useEffect, type ReactNode } from "react";
import { createPortal } from "react-dom";
import { Button } from "./Button";

interface BottomSheetProps {
  open: boolean;
  onClose: () => void;
  title?: string;
  children: ReactNode;
}

export function BottomSheet({ open, onClose, title, children }: BottomSheetProps) {
  useEffect(() => {
    if (!open) {
      return;
    }
    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === "Escape") {
        onClose();
      }
    };
    window.addEventListener("keydown", onKeyDown);
    return () => window.removeEventListener("keydown", onKeyDown);
  }, [onClose, open]);

  if (!open) {
    return null;
  }

  return createPortal(
    <div className="sheet" role="dialog" aria-modal="true">
      <button className="sheet__backdrop" type="button" aria-label="閉じる" onClick={onClose} />
      <div className="sheet__panel">
        <div className="sheet__handle" />
        <div className="sheet__header">
          <div>
            <div className="sheet__title">{title}</div>
          </div>
          <Button variant="ghost" size="icon" onClick={onClose} ariaLabel="閉じる">
            <X size={18} />
          </Button>
        </div>
        <div className="sheet__body">{children}</div>
      </div>
    </div>,
      document.body,
    );
}

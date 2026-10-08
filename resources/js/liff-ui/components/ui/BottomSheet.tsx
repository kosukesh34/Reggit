import { X } from "lucide-react";
import { useEffect, useRef, type ReactNode } from "react";
import { createPortal } from "react-dom";
import { Button } from "./Button";

interface BottomSheetProps {
  open: boolean;
  onClose: () => void;
  title?: string;
  children: ReactNode;
  hideHeader?: boolean;
  className?: string;
  locked?: boolean;
}

export function BottomSheet({ open, onClose, title, children, hideHeader = false, className = "", locked = false }: BottomSheetProps) {
  const panelRef = useRef<HTMLDivElement>(null);
  const closeRef = useRef(onClose);
  closeRef.current = () => { if (!locked) onClose(); };
  useEffect(() => {
    if (!open) {
      return;
    }
    const opener = document.activeElement as HTMLElement | null;
    const focusable = () => Array.from(panelRef.current?.querySelectorAll<HTMLElement>('button:not(:disabled), a[href], input:not(:disabled), select:not(:disabled), textarea:not(:disabled), [tabindex="0"]') ?? []);
    (focusable()[0] ?? panelRef.current)?.focus();
    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === "Escape") {
        event.preventDefault();
        closeRef.current();
      } else if (event.key === "Tab") {
        const elements = focusable();
        const first = elements[0];
        const last = elements[elements.length - 1];
        if (!first) {
          event.preventDefault();
        } else if (event.shiftKey && (document.activeElement === first || !panelRef.current?.contains(document.activeElement))) {
          event.preventDefault();
          last.focus();
        } else if (!event.shiftKey && (document.activeElement === last || !panelRef.current?.contains(document.activeElement))) {
          event.preventDefault();
          first.focus();
        }
      }
    };
    window.addEventListener("keydown", onKeyDown);
    return () => {
      window.removeEventListener("keydown", onKeyDown);
      opener?.focus();
    };
  }, [open]);

  if (!open) {
    return null;
  }

  return createPortal(
    <div className={`sheet ${className}`} role="dialog" aria-modal="true" aria-label={title || "詳細"}>
      <button className="sheet__backdrop" type="button" tabIndex={-1} aria-label="閉じる" disabled={locked} onClick={onClose} />
      <div className="sheet__panel" ref={panelRef} tabIndex={-1}>
        <div className="sheet__handle" />
        {hideHeader ? null : <div className="sheet__header">
          <div>
            <div className="sheet__title">{title}</div>
          </div>
          <Button variant="ghost" size="icon" onClick={onClose} ariaLabel="閉じる">
            <X size={18} />
          </Button>
        </div>}
        <div className="sheet__body">{children}</div>
      </div>
    </div>,
      document.body,
    );
}

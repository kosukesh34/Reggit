import { useEffect, useState } from "react";

/** 管理画面のプレビュー（x-admin.app-preview）から届く、保存前の入力内容。管理画面に埋め込まれていないときは常に undefined */
export function useAdminFormPreview<T>(kind: string): T | undefined {
  const [draft, setDraft] = useState<T>();

  useEffect(() => {
    if (window.__IS_DESIGNER_EMBED_PREVIEW__ !== true || window.parent === window) return undefined;
    const receive = (event: MessageEvent) => {
      if (event.origin !== window.location.origin || event.source !== window.parent || event.data?.type !== `reggit:${kind}-form-preview:update`) return;
      const incoming = event.data.draft as T | undefined;
      if (incoming !== null && typeof incoming === "object") setDraft(incoming);
    };
    window.addEventListener("message", receive);
    window.parent.postMessage({ type: `reggit:${kind}-form-preview:ready` }, window.location.origin);
    return () => window.removeEventListener("message", receive);
  }, [kind]);

  return draft;
}

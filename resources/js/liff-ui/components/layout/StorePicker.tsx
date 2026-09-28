import { useEffect, useState } from "react";

type Store = { id: number; name: string };
export function StorePicker() {
  const [stores, setStores] = useState<Store[]>([]);
  const selected = new URLSearchParams(window.location.search).get("store_id") ?? "";
  useEffect(() => {
    if (document.body.dataset.appMode === "demo" || window.__IS_DESIGNER_EMBED_PREVIEW__) return;
    const key = encodeURIComponent(String(window.__PROJECT_KEY__ ?? ""));
    const controller = new AbortController();
    fetch(`/api/v1/stores?project_key=${key}`, { signal: controller.signal, credentials: "same-origin" })
      .then((response) => response.ok ? response.json() : { stores: [] })
      .then((data: { stores: Store[] }) => setStores(data.stores ?? []))
      .catch(() => {});
    return () => controller.abort();
  }, []);
  if (stores.length < 2) return null;
  return <label style={{ display: "flex", alignItems: "center", gap: 12, padding: "12px 16px", background: "#fff", borderBottom: "1px solid #dbe3de" }}>
    <span>ご利用店舗</span>
    <select aria-label="ご利用店舗" value={selected} onChange={(event) => {
      const url = new URL(window.location.href);
      url.searchParams.set("store_id", event.target.value);
      window.location.assign(url.toString());
    }} style={{ flex: 1, padding: 8 }}>
      <option value="" disabled>店舗を選択してください</option>
      {stores.map((store) => <option value={store.id} key={store.id}>{store.name}</option>)}
    </select>
  </label>;
}

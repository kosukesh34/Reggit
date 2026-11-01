import { useState } from "react";
import { NativeState, useNativeResource } from "./NativeResource";
type Store = { id: number; name: string; address?: string; phone?: string; business_hours?: string; latitude?: number; longitude?: number };
export function NativeStorePage() {
  const key = encodeURIComponent(String(window.__PROJECT_KEY__ ?? ""));
  const resource = useNativeResource(`/api/v1/stores?project_key=${key}`, { stores: [] as Store[] });
  const [search, setSearch] = useState("");
  const list = resource.data.stores?.filter((store) => `${store.name} ${store.address}`.includes(search)) ?? [];
  return <div className="native-screen native-store"><label className="native-search"><i className="fa-solid fa-magnifying-glass" /><input placeholder="店舗を検索" value={search} onChange={(event) => setSearch(event.target.value)} /></label><NativeState loading={resource.loading} error={resource.error} empty={!list.length} title="店舗はありません" icon="fa-location-dot" reload={() => void resource.reload()}>{list.map((store) => <article className="native-store-row" key={store.id}><h2>{store.name}</h2><p>{store.address}</p>{store.business_hours ? <p>{store.business_hours}</p> : null}<div className="native-dialog-actions">{store.phone ? <a className="native-button native-button--secondary" href={`tel:${store.phone}`}>電話</a> : null}<a className="native-button" href={`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(store.latitude != null && store.longitude != null ? `${store.latitude},${store.longitude}` : store.address || store.name)}`} target="_blank" rel="noreferrer">地図で見る</a></div></article>)}</NativeState></div>;
}
export function NativeLegalPage({ kind }: { kind: "terms" | "privacy" }) {
  return <iframe className="native-legal" title={kind === "terms" ? "利用規約" : "プライバシーポリシー"} src={`/legal/${encodeURIComponent(String(window.__PROJECT_KEY__ ?? "default"))}/${kind}?embed=1`} />;
}

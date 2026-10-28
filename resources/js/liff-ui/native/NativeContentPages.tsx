import { useState } from "react";
import { Link, useSearchParams } from "react-router-dom";
import { NativeState, NativeSegments, nativeDate, useNativeResource } from "./NativeResource";
import { useRuntimeData } from "../context/RuntimeDataContext";
import { buildBuiltinSampleGallery } from "../../frontend/demo/sampleData";
import type { GalleryItem, GalleryPayload } from "../../frontend/features/gallery/api";
import type { Announcement } from "../../frontend/features/announcements/api";
import { BottomSheet } from "../components/ui/BottomSheet";
import { isDemoDataMode } from "../../frontend/demo/demoMode";
import { useNativeBack } from "./NativeNavigation";

export function NativeNewsPage() {
  const runtime = useRuntimeData();
  const sample: { announcements: Announcement[] } = { announcements: runtime.news.map((item) => ({ id: item.id, title: item.title, body: item.body, image_url: item.image, image_urls: item.images, is_read: item.isRead, is_pinned: item.isPinned, published_at: item.date })) };
  const formPreview = window.__IS_DESIGNER_EMBED_PREVIEW__ === true && window.__ANNOUNCEMENT_FORM_PREVIEW__ === true;
  const resource = useNativeResource("/api/announcements", sample, isDemoDataMode() || formPreview);
  const announcements = formPreview ? sample.announcements : resource.data.announcements;
  const [search, setSearch] = useSearchParams();
  const selected = announcements?.find((item) => String(item.id) === search.get("announcement"));
  useNativeBack(!!selected, () => setSearch({}));
  const images = (item: Announcement) => item.image_urls?.length ? item.image_urls : item.image_url ? [item.image_url] : [];
  const open = (item: Announcement) => {
    setSearch({ announcement: String(item.id) });
    if (!item.is_read) {
      void runtime.markAnnouncementRead(String(item.id)).then(() => resource.setData((data) => ({ announcements: data.announcements.map((row) => row.id === item.id ? { ...row, is_read: true } : row) }))).catch(() => {});
    }
  };
  return <div className="native-screen native-news"><NativeState loading={resource.loading} error={resource.error} empty={!announcements?.length} title="お知らせはありません" subtitle="お知らせが届くとここに表示されます" icon="fa-bullhorn" reload={() => void resource.reload()}>
    {selected ? <article className="native-news-detail"><h1>{selected.title}</h1><p className="native-caption">{nativeDate(selected.published_at)}</p>{images(selected).map((url) => <img src={url} alt="" key={url} />)}<div className="native-body-text">{selected.body}</div>{selected.action_path?.startsWith("/") ? <Link className="native-button" to={selected.action_path}>{selected.action_label || "開く"}</Link> : null}</article> : announcements?.map((item) => <button className="native-news-row" key={item.id} onClick={() => open(item)}><div className="native-news-row__heading"><i className="fa-solid fa-bullhorn" /><div><strong>{item.title || "お知らせ"}</strong><small>{nativeDate(item.published_at)} {!item.is_read ? <b>NEW</b> : null}</small></div>{item.is_pinned ? <span>固定</span> : null}</div>{images(item).map((url) => <img src={url} alt="" key={url} />)}{item.body ? <p>{item.body}</p> : null}</button>)}
  </NativeState></div>;
}
export function NativeGalleryPage() {
  const resource = useNativeResource<GalleryPayload>("/api/gallery", buildBuiltinSampleGallery());
  const [category, setCategory] = useState<string>("");
  const [selected, setSelected] = useState<GalleryItem>();
  const list = resource.data.items?.filter((item) => !category || String(item.category_id) === category) ?? [];
  const featured = list[0];
  return <div className="native-gallery">{resource.data.categories?.length ? <div className="native-chips" aria-label="写真のカテゴリ"><button className={!category ? "is-active" : ""} onClick={() => setCategory("")}>すべて</button>{resource.data.categories.map((item) => <button className={category === String(item.id) ? "is-active" : ""} key={item.id} onClick={() => setCategory(String(item.id))}>{item.name}</button>)}</div> : null}
    <NativeState loading={resource.loading} error={resource.error} empty={!list.length} title="ギャラリー画像はありません" subtitle="店舗の写真が登録されるとここに表示されます" icon="fa-images" reload={() => void resource.reload()}><div className="native-screen"><div className="native-gallery__intro"><small>STORE JOURNAL</small><h1>お店の風景とおすすめ</h1><p>{list.length}件の写真</p></div>{featured ? <button className="native-gallery__featured" onClick={() => setSelected(featured)}><img src={featured.image_url} alt={featured.title ?? ""} /><strong>{featured.title}</strong></button> : null}<div className="native-gallery__grid">{list.slice(1).map((item, index) => <button key={item.id ?? index} onClick={() => setSelected(item)}><img src={item.image_url} alt="" /><strong>{item.title}</strong><p>{item.description}</p></button>)}</div></div></NativeState>
    <BottomSheet open={!!selected} onClose={() => setSelected(undefined)} title={selected?.title}><img className="native-gallery__detail" src={selected?.image_url} alt={selected?.title ?? ""} /><p>{selected?.description}</p></BottomSheet>
  </div>;
}
type Order = { id: number | string; order_number?: string; status?: string; created_at?: string; total_amount?: number; items?: { product_name?: string; quantity?: number }[] };
export function NativeOrdersPage() {
  const runtime = useRuntimeData();
  const sample = { orders: (runtime.history ?? []).map((item) => ({ id: item.id, order_number: item.summary, status: item.status, created_at: item.date, total_amount: item.total, items: [{ product_name: item.title }] })) as Order[] };
  const resource = useNativeResource<{ orders?: Order[]; data?: Order[] }>("/api/orders", sample);
  const [segment, setSegment] = useState(0);
  const [selected, setSelected] = useState<Order>();
  const all = resource.data.orders ?? resource.data.data ?? [];
  const active = (item: Order) => !["completed", "cancelled"].includes(item.status ?? "pending");
  const list = all.filter((item) => segment === 0 || (segment === 1 ? active(item) : !active(item)));
  const statusLabels: Record<string, string> = { pending: "受付済み", confirmed: "受付済み", preparing: "準備中", ready: "お渡し可能", shipped: "発送済み", completed: "完了", cancelled: "キャンセル" };
  return <div className="native-screen native-orders"><NativeState loading={resource.loading} error={resource.error} empty={!all.length} title="注文履歴はありません" subtitle="注文するとここに表示されます" icon="fa-list" reload={() => void resource.reload()}><NativeSegments labels={["すべて", "進行中", "完了・キャンセル"]} selected={segment} onSelect={setSegment} />{list.length ? list.map((item) => <div className="native-order-row" key={item.id}><button onClick={() => setSelected(item)}><div><span className="native-badge">{statusLabels[item.status ?? "pending"]}</span><h2>{item.order_number ?? item.id}</h2><p>{item.items?.map((product) => product.product_name).join("、")}</p><small>{nativeDate(item.created_at)}</small></div><b>¥{Number(item.total_amount ?? 0).toLocaleString()}</b></button><Link className="native-order-reorder" to={`/mobileOrder?reorder=${encodeURIComponent(item.id)}`}>再注文</Link></div>) : <div className="native-state"><h2>該当する注文はありません</h2><p>注文状況はここで確認できます</p></div>}</NativeState><BottomSheet open={!!selected} onClose={() => setSelected(undefined)} title="注文状況"><h2>{selected?.order_number}</h2><div className="native-order-status">{["受付済み", "準備中", selected?.status === "shipped" ? "発送済み" : "お渡し可能", "完了"].map((label) => <div key={label} className={statusLabels[selected?.status ?? ""] === label ? "is-active" : ""}><i className="fa-solid fa-circle-check" />{label}</div>)}</div>{selected?.items?.map((product, index) => <p key={index}>{product.product_name} × {product.quantity ?? 1}</p>)}<strong>合計 ¥{Number(selected?.total_amount ?? 0).toLocaleString()}</strong>{!isDemoDataMode() ? <p className="native-caption">注文の状態は店舗の更新後に反映されます</p> : null}</BottomSheet></div>;
}

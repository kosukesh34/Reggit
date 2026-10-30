import { useState } from "react";
import { useRuntimeData } from "../context/RuntimeDataContext";
import { useNativeResource, NativeState, NativeSegments, nativeRequest, nativeDate } from "./NativeResource";
import { isDemoDataMode } from "../../frontend/demo/demoMode";
import type { CouponRecord } from "../../frontend/features/coupons/couponHelpers";
import { CenterPopup } from "../components/ui/CenterPopup";
import { MembershipBarcode } from "../components/membership/MembershipBarcode";
import { MembershipQr } from "../components/membership/MembershipQr";
import { useAdminFormPreview } from "../hooks/useAdminFormPreview";
type DeviceCoupon = { id: number; name: string; description?: string; image_url?: string };
type Redemption = { code?: string; use_display?: string; coupon_name?: string };
export function NativeCouponsPage() {
  const runtime = useRuntimeData();
  const samples: CouponRecord[] = runtime.coupons.map((coupon) => ({ id: coupon.id, coupon_name: coupon.title, description: coupon.description, image_url: coupon.image, valid_from: coupon.validFrom, valid_until: coupon.validUntil, status: runtime.usedCouponIds.includes(coupon.id) ? "used" : "available" }));
  const resource = useNativeResource("/api/member-coupons", { coupons: samples });
  const claimable = useNativeResource("/api/device-coupons", { coupons: [] as DeviceCoupon[] });
  const [selected, setSelected] = useState(0);
  const [pending, setPending] = useState<CouponRecord>();
  const [redemption, setRedemption] = useState<Redemption>();
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState("");
  const draft = useAdminFormPreview<CouponRecord>("coupon");
  const coupons = draft ? [draft] : resource.data.coupons ?? [];
  const archived = coupons.filter((coupon) => ["used", "expired"].includes(coupon.status ?? ""));
  const list = selected === 0 ? coupons.filter((coupon) => !archived.includes(coupon)) : archived;
  const labels: Record<string, string> = { available: "利用可能", unused: "利用可能", upcoming: "まもなく利用可能", used: "利用済み", expired: "期限切れ" };
  async function redeem() {
    if (!pending || busy) return;
    const coupon = pending;
    setBusy(true); setMessage("");
    try {
      let result: Redemption;
      // 管理画面のプレビューでは利用処理をせず、選んだ表示方法（バーコード・QR）だけを見せる
      if (draft) {
        result = { code: draft.code, use_display: draft.use_display, coupon_name: draft.coupon_name };
      } else if (isDemoDataMode()) {
        await runtime.useCoupon(String(coupon.id));
        result = { code: "DEMO000012345", use_display: "barcode", coupon_name: coupon.coupon_name };
        resource.setData((data) => ({ ...data, coupons: data.coupons.map((item) => item.id === coupon.id ? { ...item, status: "used" } : item) }));
      } else {
        const payload = await nativeRequest<Redemption & { data?: Redemption }>(`/api/member-coupons/${encodeURIComponent(coupon.id)}/redeem`, {});
        result = payload.data ?? payload;
        await resource.reload();
      }
      if (result.code && result.use_display !== "none") setRedemption(result);
      else setMessage(`「${coupon.coupon_name}」を利用しました`);
      setPending(undefined);
    } catch (caught) { setMessage(caught instanceof Error ? caught.message : "利用できませんでした"); }
    finally { setBusy(false); }
  }
  async function claim(coupon: DeviceCoupon) {
    if (busy) return;
    setBusy(true);
    try { await nativeRequest(`/api/device-coupons/${coupon.id}/claim`, {}); await Promise.all([resource.reload(), claimable.reload()]); setMessage(`「${coupon.name}」を獲得しました`); }
    catch (caught) { setMessage(caught instanceof Error ? caught.message : "獲得できませんでした"); }
    finally { setBusy(false); }
  }
  return <div className="native-coupons"><NativeSegments labels={["利用可能", "履歴"]} selected={selected} onSelect={setSelected} />
    {message ? <p className="native-message" role="status">{message}</p> : null}
    <NativeState loading={!draft && resource.loading} error={draft ? undefined : resource.error} empty={list.length === 0 && (selected !== 0 || !claimable.data.coupons?.length)} title={selected === 0 ? "利用できるクーポンはありません" : "履歴はありません"} subtitle={selected === 0 ? "新しいクーポンが届くとここに表示されます" : "使用済み・期限切れのクーポンがここに表示されます"} icon="fa-ticket" reload={() => void resource.reload()}>
      <div className="native-screen native-coupon-list">
        {selected === 0 && claimable.data.coupons?.length ? <section><h2>この端末で獲得できるクーポン</h2>{claimable.data.coupons.map((coupon) => <article className="native-claimable" key={coupon.id}>{coupon.image_url ? <img src={coupon.image_url} alt="" /> : null}<div><strong>{coupon.name}</strong><p>{coupon.description}</p><small>1端末1回限り</small></div><button className="native-button" disabled={busy} onClick={() => void claim(coupon)}>獲得</button></article>)}</section> : null}
        {selected === 1 ? <div className="native-history-summary"><i className="fa-solid fa-clock-rotate-left" /><div><strong>これまでのクーポン</strong><p>利用済み {archived.filter((coupon) => coupon.status === "used").length}件 · 期限切れ {archived.filter((coupon) => coupon.status === "expired").length}件</p></div></div> : null}
        {list.map((coupon) => {
          const status = coupon.status ?? "available";
          const usable = ["available", "unused", "issued"].includes(status);
          return <article className={`native-coupon-card${usable ? "" : " is-inactive"}`} key={coupon.id}><div className="native-coupon-card__image">{coupon.image_url ? <img src={coupon.image_url} alt="" /> : <i className="fa-solid fa-ticket" aria-hidden="true" />}<span className={`native-badge${usable ? " is-emphasized" : ""}`}>{labels[status] ?? status}</span></div><div className="native-coupon-card__body"><h2>{coupon.coupon_name}</h2>{coupon.description ? <p>{coupon.description}</p> : null}<div className="native-coupon-dates">{coupon.valid_from ? <span><i className="fa-solid fa-calendar" /> 利用開始 {nativeDate(coupon.valid_from)}</span> : null}{coupon.valid_until ? <span><i className="fa-solid fa-calendar" /> 利用終了 {nativeDate(coupon.valid_until)}</span> : null}{!coupon.valid_from && !coupon.valid_until ? <span>∞ 期間中いつでも利用できます</span> : null}</div>{selected === 0 && usable ? <div className="native-coupon-actions"><button className="native-button native-button--ink" disabled={busy} onClick={() => setPending(coupon)}>使う</button></div> : null}</div></article>;
        })}
      </div>
    </NativeState>
    <CenterPopup open={!!pending} onClose={() => { if (!busy) setPending(undefined); }} title="クーポンを使いますか？"><p>スタッフにご確認のうえ利用してください。利用後は元に戻せません。</p><p>{pending?.coupon_name}</p><div className="native-dialog-actions"><button className="native-button native-button--secondary" disabled={busy} onClick={() => setPending(undefined)}>キャンセル</button><button className="native-button" disabled={busy} onClick={() => void redeem()}>使用する</button></div></CenterPopup>
    <CenterPopup open={!!redemption} onClose={() => setRedemption(undefined)} title={redemption?.coupon_name ?? "クーポン"}>{redemption?.use_display === "qr" ? <MembershipQr value={redemption.code ?? ""} /> : <MembershipBarcode value={redemption?.code} enlarged />}</CenterPopup>
  </div>;
}

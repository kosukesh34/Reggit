import { useEffect, useState } from "react";
import { useNativeResource, NativeState, NativeSegments, nativeRequest, nativeDate } from "./NativeResource";
import { PaymentBrandPicker, devicePaymentBrands, type PaymentBrand } from "./PaymentBrandPicker";
import { CenterPopup } from "../components/ui/CenterPopup";
type TicketPlan = { id: number; name: string; description?: string | null; price: number; image_url?: string | null; ticket_uses: number; ticket_valid_days?: number | null };
type MemberTicket = { id: number; name: string; total_uses: number; remaining_uses: number; expires_at?: string | null; status: "active" | "used_up" | "expired" | "revoked" };
type TicketsPayload = { plans: TicketPlan[]; tickets: MemberTicket[]; requires_membership?: boolean };
const statusLabels: Record<MemberTicket["status"], string> = { active: "利用可能", used_up: "使い切り", expired: "期限切れ", revoked: "無効" };

export function NativeTicketsPage() {
  const resource = useNativeResource<TicketsPayload>("/api/member-tickets", { plans: [], tickets: [] });
  const [selected, setSelected] = useState(0);
  const [pending, setPending] = useState<MemberTicket>();
  const [buying, setBuying] = useState<TicketPlan>();
  const [methods, setMethods] = useState<PaymentBrand[]>([]);
  const [brand, setBrand] = useState<PaymentBrand>("card");
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState("");
  const { plans = [], tickets = [], requires_membership: requiresMembership } = resource.data;
  const active = tickets.filter((ticket) => ticket.status === "active");
  const archived = tickets.filter((ticket) => ticket.status !== "active");

  useEffect(() => {
    // Stripe から戻ったら決済を確認し、発行された回数券を表示する
    const url = new URL(window.location.href);
    const orderId = Number(url.searchParams.get("payment_order_id"));
    if (!(orderId > 0)) return;
    url.searchParams.delete("payment_order_id");
    url.searchParams.delete("payment_status");
    window.history.replaceState(null, "", url.toString());
    void nativeRequest<{ success: boolean }>("/api/payment/checkout/confirm", { order_id: orderId })
      .then((result) => setMessage(result.success ? "回数券を購入しました" : "お支払いはまだ完了していません"))
      .catch(() => setMessage("お支払いを確認できませんでした。しばらくしてから再度開いてください"))
      .finally(() => void resource.reload());
  // Runs once for the return URL.
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  useEffect(() => {
    if (!buying) return;
    void nativeRequest<{ methods: PaymentBrand[] }>("/api/payment/methods").then((data) => {
      const usable = data.methods.filter((method) => method !== "cash" && devicePaymentBrands().includes(method));
      setMethods(usable);
      setBrand((current) => usable.includes(current) ? current : usable[0] ?? "card");
    }).catch(() => setMessage("お支払い方法を読み込めませんでした"));
  }, [buying]);

  async function redeemTicket() {
    if (!pending || busy) return;
    setBusy(true); setMessage("");
    try {
      const ticket = await nativeRequest<MemberTicket>(`/api/member-tickets/${pending.id}/use`, {});
      setMessage(`「${ticket.name}」を1回使いました。残り${ticket.remaining_uses}回です`);
      setPending(undefined);
      await resource.reload();
    } catch (caught) { setMessage(caught instanceof Error ? caught.message : "利用できませんでした"); }
    finally { setBusy(false); }
  }

  async function buy() {
    if (!buying || busy) return;
    setBusy(true); setMessage("");
    try {
      if (!methods.includes(brand)) throw new Error("このお支払い方法は現在利用できません");
      const created = await nativeRequest<{ order: { id: number } }>("/api/orders", {
        order_type: "ticket", payment_method: "stripe", payment_brand: brand, points_used: 0,
        items: [{ product_id: buying.id, quantity: 1 }],
      });
      const session = await nativeRequest<{ checkout_url: string }>("/api/payment/checkout", { order_id: created.order.id, surface: "web" });
      const url = new URL(session.checkout_url);
      if (url.protocol !== "https:" && url.origin !== window.location.origin) throw new Error("決済画面のURLが不正です");
      const liff = window.liff as { isInClient?: () => boolean; openWindow?: (input: { url: string; external: boolean }) => void } | undefined;
      if (liff?.isInClient?.() && liff.openWindow) liff.openWindow({ url: url.toString(), external: true });
      else window.location.assign(url.toString());
    } catch (caught) { setMessage(caught instanceof Error ? caught.message : "購入できませんでした"); }
    finally { setBusy(false); }
  }

  const list = selected === 0 ? active : selected === 1 ? archived : [];
  return <div className="native-coupons"><NativeSegments labels={["利用中", "履歴", "購入"]} selected={selected} onSelect={setSelected} />
    {message ? <p className="native-message" role="status">{message}</p> : null}
    <NativeState loading={resource.loading} error={resource.error}
      empty={selected === 2 ? plans.length === 0 : list.length === 0}
      title={selected === 0 ? "利用できる回数券はありません" : selected === 1 ? "履歴はありません" : "販売中の回数券はありません"}
      subtitle={requiresMembership ? "回数券の購入・利用には会員登録が必要です" : selected === 0 && plans.length > 0 ? "「購入」から回数券を購入できます" : undefined}
      icon="fa-ticket" reload={() => void resource.reload()}>
      <div className="native-screen native-coupon-list">
        {selected === 2 ? plans.map((plan) => <article className="native-coupon-card" key={plan.id}>
          {plan.image_url ? <div className="native-coupon-card__image"><img src={plan.image_url} alt="" /></div> : null}
          <div className="native-coupon-card__body"><h2>{plan.name}</h2>{plan.description ? <p>{plan.description}</p> : null}
            <div className="native-coupon-dates"><span><i className="fa-solid fa-ticket" /> {plan.ticket_uses}回分</span><span><i className="fa-solid fa-calendar" /> {plan.ticket_valid_days ? `購入から${plan.ticket_valid_days}日間有効` : "有効期限なし"}</span></div>
            <div className="native-coupon-actions"><strong>¥{plan.price.toLocaleString()}</strong><button className="native-button native-button--ink" disabled={busy || requiresMembership} onClick={() => setBuying(plan)}>購入</button></div>
          </div></article>) : list.map((ticket) => <article className={`native-coupon-card${ticket.status === "active" ? "" : " is-inactive"}`} key={ticket.id}>
          <div className="native-coupon-card__body"><span className={`native-badge${ticket.status === "active" ? " is-emphasized" : ""}`}>{statusLabels[ticket.status]}</span><h2>{ticket.name}</h2>
            <p><strong>残り {ticket.remaining_uses}</strong> / {ticket.total_uses}回</p>
            <div className="native-coupon-dates"><span><i className="fa-solid fa-calendar" /> {ticket.expires_at ? `有効期限 ${nativeDate(ticket.expires_at)}` : "有効期限なし"}</span></div>
            {ticket.status === "active" ? <div className="native-coupon-actions"><button className="native-button native-button--ink" disabled={busy} onClick={() => setPending(ticket)}>1回使う</button></div> : null}
          </div></article>)}
      </div>
    </NativeState>
    <CenterPopup open={!!pending} onClose={() => { if (!busy) setPending(undefined); }} title="回数券を1回使いますか？"><p>スタッフにご確認のうえ利用してください。</p><p>{pending?.name}（残り{pending?.remaining_uses}回）</p><div className="native-dialog-actions"><button className="native-button native-button--secondary" disabled={busy} onClick={() => setPending(undefined)}>キャンセル</button><button className="native-button" disabled={busy} onClick={() => void redeemTicket()}>使用する</button></div></CenterPopup>
    <CenterPopup open={!!buying} onClose={() => { if (!busy) setBuying(undefined); }} title={buying?.name ?? "回数券"}><p>¥{buying?.price.toLocaleString()}・{buying?.ticket_uses}回分</p><PaymentBrandPicker value={brand} methods={methods} onChange={setBrand} disabled={busy} /><div className="native-dialog-actions"><button className="native-button native-button--secondary" disabled={busy} onClick={() => setBuying(undefined)}>キャンセル</button><button className="native-button" disabled={busy || methods.length === 0} onClick={() => void buy()}>お支払いへ</button></div></CenterPopup>
  </div>;
}

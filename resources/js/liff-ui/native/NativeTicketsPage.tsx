import { useEffect, useState } from "react";
import { useNativeResource, NativeState, NativeSegments, nativeRequest, nativeDate } from "./NativeResource";
import { PaymentBrandPicker, devicePaymentBrands, type PaymentBrand } from "./PaymentBrandPicker";
import { CenterPopup } from "../components/ui/CenterPopup";
type TicketPlan = { id: number; kind: "ticket" | "subscription"; name: string; description?: string | null; price: number; image_url?: string | null; ticket_uses: number | null; ticket_valid_days?: number | null; daily_limit?: number | null };
type MemberTicket = { id: number; name: string; total_uses: number | null; remaining_uses: number | null; daily_limit?: number | null; is_subscription?: boolean; expires_at?: string | null; status: "active" | "used_up" | "expired" | "revoked" };
type MemberSubscription = { id: number; product_id: number | null; name: string; status: string; cancel_at_period_end: boolean; current_period_end?: string | null };
type TicketsPayload = { plans: TicketPlan[]; tickets: MemberTicket[]; subscriptions?: MemberSubscription[]; requires_membership?: boolean };
const statusLabels: Record<MemberTicket["status"], string> = { active: "利用可能", used_up: "使い切り", expired: "期限切れ", revoked: "無効" };

function openExternal(rawUrl: string) {
  const url = new URL(rawUrl);
  if (url.protocol !== "https:" && url.origin !== window.location.origin) throw new Error("決済画面のURLが不正です");
  const liff = window.liff as { isInClient?: () => boolean; openWindow?: (input: { url: string; external: boolean }) => void } | undefined;
  if (liff?.isInClient?.() && liff.openWindow) liff.openWindow({ url: url.toString(), external: true });
  else window.location.assign(url.toString());
}

function usesLabel(uses: number | null, dailyLimit?: number | null, monthly = false) {
  return `${uses === null ? "回数無制限" : `${monthly ? "毎月" : ""}${uses}回分`}${dailyLimit ? `・1日${dailyLimit}回まで` : ""}`;
}

export function NativeTicketsPage() {
  const resource = useNativeResource<TicketsPayload>("/api/member-tickets", { plans: [], tickets: [], subscriptions: [] });
  const [selected, setSelected] = useState(0);
  const [pending, setPending] = useState<MemberTicket>();
  const [buying, setBuying] = useState<TicketPlan>();
  const [cancelling, setCancelling] = useState<MemberSubscription>();
  const [methods, setMethods] = useState<PaymentBrand[]>([]);
  const [brand, setBrand] = useState<PaymentBrand>("card");
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState("");
  const { plans = [], tickets = [], subscriptions = [], requires_membership: requiresMembership } = resource.data;
  const active = tickets.filter((ticket) => ticket.status === "active");
  const archived = tickets.filter((ticket) => ticket.status !== "active");
  const subscribedProductIds = new Set(subscriptions.map((subscription) => subscription.product_id));

  useEffect(() => {
    // Stripe から戻ったら決済を確認し、発行された回数券を表示する
    const url = new URL(window.location.href);
    const orderId = Number(url.searchParams.get("payment_order_id"));
    const subscriptionStatus = url.searchParams.get("subscription_status");
    if (!(orderId > 0) && subscriptionStatus === null) return;
    ["payment_order_id", "payment_status", "subscription_status"].forEach((key) => url.searchParams.delete(key));
    window.history.replaceState(null, "", url.toString());
    if (subscriptionStatus !== null) {
      // サブスクは Stripe の Webhook で反映されるため、少し待ってから読み直す
      setMessage(subscriptionStatus === "success" ? "お申し込みを受け付けました。反映まで少しお待ちください" : "お申し込みは完了していません");
      const timer = window.setTimeout(() => void resource.reload(), 3000);
      return () => window.clearTimeout(timer);
    }
    void nativeRequest<{ success: boolean }>("/api/payment/checkout/confirm", { order_id: orderId })
      .then((result) => setMessage(result.success ? "回数券を購入しました" : "お支払いはまだ完了していません"))
      .catch(() => setMessage("お支払いを確認できませんでした。しばらくしてから再度開いてください"))
      .finally(() => void resource.reload());
  // Runs once for the return URL.
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  useEffect(() => {
    if (buying?.kind !== "ticket") return;
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
      setMessage(`「${ticket.name}」を1回使いました。${ticket.remaining_uses === null ? "" : `残り${ticket.remaining_uses}回です`}`);
      setPending(undefined);
      await resource.reload();
    } catch (caught) { setMessage(caught instanceof Error ? caught.message : "利用できませんでした"); }
    finally { setBusy(false); }
  }

  async function buy() {
    if (!buying || busy) return;
    setBusy(true); setMessage("");
    try {
      if (buying.kind === "subscription") {
        const session = await nativeRequest<{ checkout_url: string }>("/api/member-subscriptions/checkout", { product_id: buying.id });
        openExternal(session.checkout_url);
        return;
      }
      if (!methods.includes(brand)) throw new Error("このお支払い方法は現在利用できません");
      const created = await nativeRequest<{ order: { id: number } }>("/api/orders", {
        order_type: "ticket", payment_method: "stripe", payment_brand: brand, points_used: 0,
        items: [{ product_id: buying.id, quantity: 1 }],
      });
      const session = await nativeRequest<{ checkout_url: string }>("/api/payment/checkout", { order_id: created.order.id, surface: "web" });
      openExternal(session.checkout_url);
    } catch (caught) { setMessage(caught instanceof Error ? caught.message : "購入できませんでした"); }
    finally { setBusy(false); }
  }

  async function cancelSubscription() {
    if (!cancelling || busy) return;
    setBusy(true); setMessage("");
    try {
      await nativeRequest(`/api/member-subscriptions/${cancelling.id}/cancel`, {});
      setMessage(`「${cancelling.name}」は現在の期間の終わりで解約されます`);
      setCancelling(undefined);
      await resource.reload();
    } catch (caught) { setMessage(caught instanceof Error ? caught.message : "解約できませんでした"); }
    finally { setBusy(false); }
  }

  const list = selected === 0 ? active : selected === 1 ? archived : [];
  return <div className="native-coupons"><NativeSegments labels={["利用中", "履歴", "購入"]} selected={selected} onSelect={setSelected} />
    {message ? <p className="native-message" role="status">{message}</p> : null}
    <NativeState loading={resource.loading} error={resource.error}
      empty={selected === 2 ? plans.length === 0 : list.length === 0 && (selected !== 0 || subscriptions.length === 0)}
      title={selected === 0 ? "利用できる回数券はありません" : selected === 1 ? "履歴はありません" : "販売中の回数券・サブスクはありません"}
      subtitle={requiresMembership ? "回数券・サブスクの購入と利用には会員登録が必要です" : selected === 0 && plans.length > 0 ? "「購入」から購入・申し込みできます" : undefined}
      icon="fa-ticket" reload={() => void resource.reload()}>
      <div className="native-screen native-coupon-list">
        {selected === 0 ? subscriptions.map((subscription) => <article className="native-coupon-card" key={`subscription-${subscription.id}`}>
          <div className="native-coupon-card__body"><span className="native-badge is-emphasized">{subscription.cancel_at_period_end ? "期間末で解約" : "サブスク契約中"}</span><h2>{subscription.name}</h2>
            <div className="native-coupon-dates"><span><i className="fa-solid fa-calendar" /> {subscription.current_period_end ? `${subscription.cancel_at_period_end ? "終了" : "次回更新"} ${nativeDate(subscription.current_period_end)}` : "反映中"}</span></div>
            {subscription.cancel_at_period_end ? null : <div className="native-coupon-actions"><button className="native-button native-button--secondary" disabled={busy} onClick={() => setCancelling(subscription)}>解約する</button></div>}
          </div></article>) : null}
        {selected === 2 ? plans.map((plan) => <article className="native-coupon-card" key={plan.id}>
          {plan.image_url ? <div className="native-coupon-card__image"><img src={plan.image_url} alt="" /></div> : null}
          <div className="native-coupon-card__body">{plan.kind === "subscription" ? <span className="native-badge">月額サブスク</span> : null}<h2>{plan.name}</h2>{plan.description ? <p>{plan.description}</p> : null}
            <div className="native-coupon-dates"><span><i className="fa-solid fa-ticket" /> {usesLabel(plan.ticket_uses, plan.daily_limit, plan.kind === "subscription")}</span>{plan.kind === "ticket" ? <span><i className="fa-solid fa-calendar" /> {plan.ticket_valid_days ? `購入から${plan.ticket_valid_days}日間有効` : "有効期限なし"}</span> : null}</div>
            <div className="native-coupon-actions"><strong>{plan.kind === "subscription" ? "月額 " : ""}¥{plan.price.toLocaleString()}</strong>
              {plan.kind === "subscription" && subscribedProductIds.has(plan.id)
                ? <span>契約中</span>
                : <button className="native-button native-button--ink" disabled={busy || requiresMembership} onClick={() => setBuying(plan)}>{plan.kind === "subscription" ? "申し込む" : "購入"}</button>}
            </div>
          </div></article>) : list.map((ticket) => <article className={`native-coupon-card${ticket.status === "active" ? "" : " is-inactive"}`} key={ticket.id}>
          <div className="native-coupon-card__body"><span className={`native-badge${ticket.status === "active" ? " is-emphasized" : ""}`}>{statusLabels[ticket.status]}</span><h2>{ticket.name}</h2>
            <p>{ticket.remaining_uses === null ? <strong>回数無制限</strong> : <><strong>残り {ticket.remaining_uses}</strong> / {ticket.total_uses}回</>}{ticket.daily_limit ? `（1日${ticket.daily_limit}回まで）` : ""}</p>
            <div className="native-coupon-dates"><span><i className="fa-solid fa-calendar" /> {ticket.expires_at ? `有効期限 ${nativeDate(ticket.expires_at)}` : "有効期限なし"}</span></div>
            {ticket.status === "active" ? <div className="native-coupon-actions"><button className="native-button native-button--ink" disabled={busy} onClick={() => setPending(ticket)}>1回使う</button></div> : null}
          </div></article>)}
      </div>
    </NativeState>
    <CenterPopup open={!!pending} onClose={() => { if (!busy) setPending(undefined); }} title="1回使いますか？"><p>スタッフにご確認のうえ利用してください。</p><p>{pending?.name}{pending?.remaining_uses === null || pending === undefined ? "" : `（残り${pending.remaining_uses}回）`}</p><div className="native-dialog-actions"><button className="native-button native-button--secondary" disabled={busy} onClick={() => setPending(undefined)}>キャンセル</button><button className="native-button" disabled={busy} onClick={() => void redeemTicket()}>使用する</button></div></CenterPopup>
    <CenterPopup open={!!buying} onClose={() => { if (!busy) setBuying(undefined); }} title={buying?.name ?? "回数券"}>
      <p>{buying?.kind === "subscription" ? "月額 " : ""}¥{buying?.price.toLocaleString()}・{buying ? usesLabel(buying.ticket_uses, buying.daily_limit, buying.kind === "subscription") : ""}</p>
      {buying?.kind === "subscription" ? <p>クレジットカードで毎月自動でお支払いします。解約はこの画面からいつでもでき、現在の期間の終わりまで利用できます。</p> : <PaymentBrandPicker value={brand} methods={methods} onChange={setBrand} disabled={busy} />}
      <div className="native-dialog-actions"><button className="native-button native-button--secondary" disabled={busy} onClick={() => setBuying(undefined)}>キャンセル</button><button className="native-button" disabled={busy || (buying?.kind === "ticket" && methods.length === 0)} onClick={() => void buy()}>お支払いへ</button></div>
    </CenterPopup>
    <CenterPopup open={!!cancelling} onClose={() => { if (!busy) setCancelling(undefined); }} title="サブスクを解約しますか？"><p>{cancelling?.name}は現在の期間の終わりで終了し、以降の請求は止まります。</p><div className="native-dialog-actions"><button className="native-button native-button--secondary" disabled={busy} onClick={() => setCancelling(undefined)}>戻る</button><button className="native-button" disabled={busy} onClick={() => void cancelSubscription()}>解約する</button></div></CenterPopup>
  </div>;
}

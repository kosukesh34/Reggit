import {
  Check,
  CheckCircle2,
  Clock3,
  Beer,
  Camera,
  Coins,
  Coffee,
  Croissant,
  Dumbbell,
  Flower2,
  Gift,
  HeartPulse,
  Hotel,
  PawPrint,
  RefreshCcw,
  Sandwich,
  Scissors,
  Shirt,
  ShoppingBasket,
  Stamp as StampIcon,
  Ticket,
  UtensilsCrossed,
  Users,
} from "lucide-react";
import { useCallback, useEffect, useRef, useState } from "react";
import { useSearchParams } from "react-router-dom";
import { BottomSheet } from "../../components/ui/BottomSheet";
import { openStampScanner, StampScannerPopup } from "../../components/stamp/StampScannerPopup";
import { Button } from "../../components/ui/Button";
import { Card } from "../../components/ui/Card";
import type { CouponItem } from "../../types/app";
import type { AppCustomization, CouponStatus } from "../../types/customization";
import { useRuntimeData } from "../../context/RuntimeDataContext";
import { parseStampGrant } from "../../utils/stampGrant";

export function QueueDemo({ storeId }: { storeId: string }) {
  const { joinQueue, queueStatus } = useRuntimeData();
  const [joined, setJoined] = useState(false);
  const [reference, setReference] = useState<string>();
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string>();

  const submitQueue = async () => {
    setSubmitting(true);
    setError(undefined);
    try {
      const receipt = await joinQueue({ storeId, partySize: 2 });
      setReference(receipt.reference);
      setJoined(true);
    } catch (caught) {
      setError(caught instanceof Error ? caught.message : "順番待ちを受付できませんでした");
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="stack stack--lg">
      <Card className="queue-status" padding="lg">
        <div className="queue-status__icon"><Users size={28} /></div>
        <div className="feature-success__eyebrow">現在の状況</div>
        <strong className="queue-status__count">{queueStatus?.waitingGroups ?? 3}<span>組</span></strong>
        <p>現在の待ち時間目安：約{queueStatus?.estimatedMinutes ?? 15}分</p>
      </Card>
      {joined ? (
        <Card className="queue-ticket" padding="lg">
          <span>あなたの受付番号</span>
          <strong>A-042</strong>
          <p>あと2組です。順番が近づいたら通知します。</p>
          {reference ? <span className="operation-reference">受付ID {reference}</span> : null}
          <Button variant="outline" onClick={() => setJoined(false)}>受付をキャンセル</Button>
        </Card>
      ) : (
        <Card padding="lg">
          <div className="stack stack--lg">
            <div className="confirmation-card"><span>利用人数</span><strong>2名</strong><span>通知方法</span><strong>アプリ通知</strong></div>
            {error ? <div className="form-error" role="alert">{error}</div> : null}
            <Button size="lg" disabled={submitting} onClick={() => void submitQueue()}>{submitting ? "送信中…" : "順番待ちを受付する"}</Button>
          </div>
        </Card>
      )}
    </div>
  );
}

export function CouponDemo({ coupons, config }: { coupons: CouponItem[]; config: AppCustomization["coupon"] }) {
  const { usedCouponIds: usedIds, useCoupon: persistCoupon } = useRuntimeData();
  const [selected, setSelected] = useState<CouponItem>();
  const [status, setStatus] = useState<CouponStatus>(config.defaultStatus);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string>();
  const isUpcoming = (coupon: CouponItem) => Boolean(coupon.validFrom && new Date(`${coupon.validFrom}T00:00:00`).getTime() > Date.now());
  const expiredCoupons: CouponItem[] = coupons[0] ? [{ ...coupons[0], id: `ended-${coupons[0].id}`, title: "終了した会員特典", validFrom: undefined, validUntil: "2026-07-31" }] : [];
  const upcomingCoupons = coupons.filter(isUpcoming);
  const availableCoupons = coupons.filter((coupon) => !usedIds.includes(coupon.id) && !isUpcoming(coupon));
  const usedCoupons = coupons.filter((coupon) => usedIds.includes(coupon.id));
  const currentCoupons = config.showBeforeAvailable ? [...availableCoupons, ...upcomingCoupons] : availableCoupons;
  const endedCoupons = [...usedCoupons, ...expiredCoupons];
  const visibleCoupons = config.showStatusTabs && status === "ended" ? endedCoupons : currentCoupons;

  useEffect(() => {
    setStatus(config.defaultStatus);
  }, [config.defaultStatus]);

  const confirmCoupon = async (coupon: CouponItem) => {
    setSubmitting(true);
    setError(undefined);
    try {
      await persistCoupon(coupon.id);
      setSelected(undefined);
      if (config.showStatusTabs) setStatus("ended");
    } catch (caught) {
      setError(caught instanceof Error ? caught.message : "クーポンを利用済みにできませんでした");
    } finally {
      setSubmitting(false);
    }
  };

  const useCoupon = (coupon: CouponItem) => {
    if (config.requireConfirmation) {
      setSelected(coupon);
      return;
    }
    void confirmCoupon(coupon);
  };

  return (
    <>
      {config.showStatusTabs ? (
        <div className="coupon-tabs" role="tablist" aria-label="クーポン状態">
          {(["available", "ended"] as CouponStatus[]).map((item) => (
            <button type="button" role="tab" aria-selected={status === item} className={status === item ? "is-active" : ""} key={item} onClick={() => setStatus(item)}>
              {item === "available" ? "利用可能" : "利用終了"}
              <span>{item === "available" ? currentCoupons.length : endedCoupons.length}</span>
            </button>
          ))}
        </div>
      ) : null}
      <div className={`feature-card-list${config.layout === "compact" ? " feature-card-list--compact" : ""}`}>
        {visibleCoupons.map((coupon) => {
          const ended = usedIds.includes(coupon.id) || coupon.id.startsWith("ended-") || status === "ended";
          const upcoming = isUpcoming(coupon);
          return (
            <Card className={`benefit-card${ended ? " is-ended" : ""}${upcoming ? " is-upcoming" : ""}${config.showImage && coupon.image ? " benefit-card--with-image" : ""}`} padding="lg" key={coupon.id}>
              {config.showImage && coupon.image ? <img className="benefit-card__image" src={coupon.image} alt="" /> : <div className="benefit-card__icon"><Ticket size={22} /></div>}
              <div className="benefit-card__body">
                <span className="feature-success__eyebrow">会員限定</span>
                {config.showStatusBadge ? <span className={`coupon-status coupon-status--${ended ? "ended" : upcoming ? "upcoming" : "available"}`}>{ended ? "利用終了" : upcoming ? "利用開始前" : "利用可能"}</span> : null}
                <h2>{coupon.title}</h2>
                {config.showDescription ? <p>{coupon.description}</p> : null}
                {config.showExpiryDate ? <span className="benefit-card__date"><Clock3 size={13} /> {upcoming ? `${coupon.validFrom}から利用可能` : `${coupon.validUntil}まで`}</span> : null}
              </div>
              <Button variant={ended || upcoming ? "outline" : "solid"} disabled={ended || upcoming} onClick={() => useCoupon(coupon)}>{ended ? "利用終了" : upcoming ? "利用開始前" : "利用する"}</Button>
            </Card>
          );
        })}
        {visibleCoupons.length === 0 ? <Card className="feature-empty" padding="lg"><Ticket size={28} /><h2>該当するクーポンはありません</h2></Card> : null}
      </div>
      <BottomSheet open={Boolean(selected) && config.requireConfirmation} onClose={() => setSelected(undefined)} title="クーポンを利用しますか？">
        <div className="stack stack--lg">
          <Card className="coupon-confirm" padding="lg"><Gift size={28} /><strong>{selected?.title}</strong><p>「利用を確定」を押した後は取り消せません。店舗スタッフに画面をご提示ください。</p></Card>
          {error ? <div className="form-error" role="alert">{error}</div> : null}
          <Button size="lg" disabled={submitting || !selected} onClick={() => { if (selected) void confirmCoupon(selected); }}>{submitting ? "処理中…" : "利用を確定"}</Button>
        </div>
      </BottomSheet>
    </>
  );
}

const rewardProfileMap: Record<string, { eyebrow: string; title: string; reward: string; action: string; Icon: typeof StampIcon }> = {
  store_cafe: { eyebrow: "COFFEE JOURNEY", title: "コーヒースタンプ", reward: "お好きなドリンクを1杯プレゼント", action: "来店スタンプを追加", Icon: Coffee },
  store_apparel: { eyebrow: "STYLE REWARDS", title: "ショッピングパス", reward: "次回のお買い物で使える500円特典", action: "お買い物記録を追加", Icon: Shirt },
  store_salon: { eyebrow: "BEAUTY JOURNEY", title: "ケアスタンプ", reward: "トリートメントを無料アップグレード", action: "来店記録を追加", Icon: Scissors },
  store_bakery: { eyebrow: "BAKER'S PASSPORT", title: "パンの旅カード", reward: "焼きたてパンをひとつプレゼント", action: "パンを購入した", Icon: Croissant },
  store_fitness: { eyebrow: "WEEKLY CHALLENGE", title: "ムーブチャレンジ", reward: "パーソナルセッションを1回プレゼント", action: "ワークアウトを記録", Icon: Dumbbell },
  store_flower: { eyebrow: "BLOOM COLLECTION", title: "ブルームカード", reward: "季節のミニブーケをプレゼント", action: "花のある日を記録", Icon: Flower2 },
  store_hotel: { eyebrow: "STAY COLLECTION", title: "ステイコレクション", reward: "朝食を無料アップグレード", action: "滞在記録を追加", Icon: Hotel },
  store_pets: { eyebrow: "HAPPY PAWS", title: "ハッピーパウカード", reward: "お好きなケアメニューをひとつプレゼント", action: "ケア記録を追加", Icon: PawPrint },
  store_clinic: { eyebrow: "WELLNESS STEPS", title: "ウェルネスステップ", reward: "コンディション相談をご案内", action: "ケア記録を追加", Icon: HeartPulse },
  store_market: { eyebrow: "LOCAL HARVEST", title: "マーケットカード", reward: "旬のファーマーズセットをプレゼント", action: "お買い物記録を追加", Icon: ShoppingBasket },
  store_dining: { eyebrow: "DINING REWARDS", title: "ご来店スタンプ", reward: "お好きなデザートをひとつプレゼント", action: "来店記録を追加", Icon: UtensilsCrossed },
  store_izakaya: { eyebrow: "NIGHT REWARDS", title: "常連の酒札", reward: "乾杯ドリンクを1杯プレゼント", action: "来店記録を追加", Icon: Beer },
  store_fastfood: { eyebrow: "QUICK BITES", title: "バイトスタンプ", reward: "お好きなバーガーをひとつプレゼント", action: "注文記録を追加", Icon: Sandwich },
};

export function StampDemo({ config, storeId, brandName }: { config: AppCustomization["stamp"]; storeId: string; brandName: string }) {
  const { stampCount, addStamp } = useRuntimeData();
  const count = Math.min(stampCount, config.goal);
  const [scannerOpen, setScannerOpen] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string>();
  const [successMessage, setSuccessMessage] = useState<string>();
  const [newStampIndex, setNewStampIndex] = useState<number>();
  const [animationKey, setAnimationKey] = useState(0);
  const [searchParams, setSearchParams] = useSearchParams();
  const queryHandled = useRef(false);
  const complete = count >= config.goal;
  const profile = rewardProfileMap[storeId] ?? rewardProfileMap.store_cafe;
  const RewardIcon = profile.Icon;
  const brandClass = storeId.replace("store_", "");

  const grantStamp = useCallback(async (rawValue: string) => {
    const payload = parseStampGrant(rawValue);
    if (!payload) {
      setError("QRコードを確認してください。");
      return;
    }
    setSubmitting(true);
    setError(undefined);
    setSuccessMessage(undefined);
    try {
      await addStamp(payload.value);
      setNewStampIndex(Math.min(count, config.goal - 1));
      setAnimationKey((current) => current + 1);
      setSuccessMessage(`スタンプを1個追加しました。現在 ${Math.min(count + 1, config.goal)} / ${config.goal} 個です。`);
      setScannerOpen(false);
    } catch (caught) {
      setError(caught instanceof Error ? caught.message : "スタンプを追加できませんでした");
    } finally {
      setSubmitting(false);
    }
  }, [addStamp, config.goal, count]);

  useEffect(() => {
    const grant = searchParams.get("g") ?? searchParams.get("grant") ?? searchParams.get("stampCode");
    if (!grant || queryHandled.current) return;
    queryHandled.current = true;
    void grantStamp(grant).finally(() => setSearchParams({}, { replace: true }));
  }, [grantStamp, searchParams, setSearchParams]);

  const handleDetected = useCallback((value: string) => {
    void grantStamp(value);
  }, [grantStamp]);

  return (
    <div className="stack stack--lg">
      <Card className={`stamp-card stamp-card--${config.style} stamp-card--brand-${brandClass}`} padding="lg">
        <div className="stamp-card__brand"><RewardIcon size={18} /><span>{brandName}</span></div>
        <div className="stamp-card__head"><div><span>{profile.eyebrow}</span><h2>{profile.title}</h2></div><strong>{count}<span>/{config.goal}</span></strong></div>
        {config.style === "progress" ? (
          <div className="stamp-progress"><span style={{ width: `${(count / config.goal) * 100}%` }} /></div>
        ) : <div className="stamp-grid">
          {Array.from({ length: config.goal }).map((_, index) => (
            <div className={`${index < count ? "is-stamped" : ""}${index === newStampIndex ? " is-new" : ""}`} key={`${index}-${index === newStampIndex ? animationKey : 0}`}>{index < count ? <RewardIcon size={20} /> : index + 1}</div>
          ))}
        </div>}
        {config.showRewardText ? <p>{complete ? `${profile.reward}。次回ご利用時にお使いください。` : `あと${config.goal - count}個で、${profile.reward}。`}</p> : null}
      </Card>
      {successMessage ? <div className="stamp-grant-success" role="status" key={animationKey}><span><Check size={19} /></span><div><strong>スタンプ付与完了</strong><p>{successMessage}</p></div></div> : null}
      {error ? <div className="form-error" role="alert">{error}</div> : null}
      <Button size="lg" disabled={complete || submitting} onClick={() => { setError(undefined); openStampScanner(setScannerOpen); }}><Camera size={19} />{complete ? "特典獲得済み" : "カメラでQRを読み取る"}</Button>
      <StampScannerPopup open={scannerOpen} onClose={() => setScannerOpen(false)} onDetected={handleDetected} />
    </div>
  );
}

export function PointDemo({ initialPoints }: { initialPoints: number }) {
  const { redeemPoints } = useRuntimeData();
  const [points, setPoints] = useState(initialPoints);
  const [message, setMessage] = useState<string>();
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string>();

  const redeem = async () => {
    setSubmitting(true);
    setError(undefined);
    try {
      await redeemPoints(500);
      setPoints((current) => current - 500);
      setMessage("500ポイントをクーポンへ交換しました");
    } catch (caught) {
      setError(caught instanceof Error ? caught.message : "ポイントを交換できませんでした");
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="stack stack--lg">
      <Card className="point-balance" padding="lg">
        <Coins size={24} />
        <span>利用可能ポイント</span>
        <strong>{points.toLocaleString()}<small> pt</small></strong>
        <p>通常ポイント 1pt = 1円として利用できます</p>
        <Button variant="soft" disabled={points < 500 || submitting} onClick={() => void redeem()}>{submitting ? "交換中…" : "500ptをクーポンに交換"}</Button>
        {error ? <div className="form-error" role="alert">{error}</div> : null}
        {message ? <div className="inline-success"><Check size={15} />{message}</div> : null}
      </Card>
      <section className="feature-block">
        <div className="section-head"><h2 className="section-head__title">ポイント履歴</h2></div>
        <Card className="transaction-list" padding="sm">
          <div><span><b>お買い物ポイント</b><small>2026.08.09</small></span><strong className="is-plus">+68 pt</strong></div>
          <div><span><b>キャンペーン特典</b><small>2026.08.01</small></span><strong className="is-plus">+200 pt</strong></div>
          <div><span><b>ポイント利用</b><small>2026.07.28</small></span><strong>-500 pt</strong></div>
        </Card>
      </section>
    </div>
  );
}

const tickets = [
  { id: "t1", name: "ドリンクチケット", remaining: "2枚", expires: "2026.10.31" },
  { id: "t2", name: "ヘッドスパ回数券", remaining: "1回", expires: "2026.12.20" },
];

export function TicketDemo() {
  const [selected, setSelected] = useState<(typeof tickets)[number]>();
  const [usedIds, setUsedIds] = useState<string[]>([]);

  return (
    <>
      <div className="feature-card-list">
        {tickets.map((ticket) => {
          const used = usedIds.includes(ticket.id);
          return (
            <Card className="ticket-card" padding="lg" key={ticket.id}>
              <div className="ticket-card__stub"><Ticket size={24} /><span>{ticket.remaining}</span></div>
              <div><span className="feature-success__eyebrow">デジタルチケット</span><h2>{ticket.name}</h2><p>有効期限 {ticket.expires}</p></div>
              <Button disabled={used} onClick={() => setSelected(ticket)}>{used ? "利用済み" : "チケットを使う"}</Button>
            </Card>
          );
        })}
      </div>
      <BottomSheet open={Boolean(selected)} onClose={() => setSelected(undefined)} title="チケットを利用">
        <div className="stack stack--lg"><div className="confirmation-card"><span>チケット</span><strong>{selected?.name}</strong><span>利用店舗</span><strong>登録店舗</strong></div><Button size="lg" onClick={() => { if (selected) setUsedIds((ids) => [...ids, selected.id]); setSelected(undefined); }}>スタッフ確認済みにする</Button></div>
      </BottomSheet>
    </>
  );
}

const plans = [
  { id: "light", name: "LIGHT", price: "¥980 / 月", description: "月2回の会員特典" },
  { id: "standard", name: "STANDARD", price: "¥1,980 / 月", description: "毎週使える会員特典" },
];

export function SubscriptionDemo() {
  const [selected, setSelected] = useState<(typeof plans)[number]>();
  const [activePlan, setActivePlan] = useState<string>();

  return (
    <>
      {activePlan ? <div className="inline-success inline-success--large"><CheckCircle2 size={20} />{activePlan}プランを利用中です</div> : null}
      <div className="feature-card-list">
        {plans.map((plan) => (
          <Card className={`plan-card${activePlan === plan.name ? " is-active" : ""}`} padding="lg" key={plan.id}>
            <RefreshCcw size={22} /><span className="feature-success__eyebrow">会員プラン</span><h2>{plan.name}</h2><strong>{plan.price}</strong><p>{plan.description}・いつでも解約可能</p><Button variant={activePlan === plan.name ? "outline" : "solid"} onClick={() => setSelected(plan)}>{activePlan === plan.name ? "プラン変更" : "このプランを選ぶ"}</Button>
          </Card>
        ))}
      </div>
      <BottomSheet open={Boolean(selected)} onClose={() => setSelected(undefined)} title="プランを確認">
        <div className="stack stack--lg"><div className="confirmation-card"><span>プラン</span><strong>{selected?.name}</strong><span>料金</span><strong>{selected?.price}</strong><span>更新日</span><strong>毎月12日</strong></div><Button size="lg" onClick={() => { if (selected) setActivePlan(selected.name); setSelected(undefined); }}>申し込みを確定</Button></div>
      </BottomSheet>
    </>
  );
}

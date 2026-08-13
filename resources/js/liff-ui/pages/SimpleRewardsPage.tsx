import { Camera, Check, ChevronRight, CreditCard, Stamp, Ticket } from "lucide-react";
import { useCallback, useEffect, useRef, useState } from "react";
import { Link, useSearchParams } from "react-router-dom";
import { openStampScanner, StampScannerPopup } from "../components/stamp/StampScannerPopup";
import { BottomSheet } from "../components/ui/BottomSheet";
import { CenterPopup } from "../components/ui/CenterPopup";
import { useRuntimeData } from "../context/RuntimeDataContext";
import { parseStampGrant, STAMP_GRANT_INVALID_QR_MESSAGE } from "../utils/stampGrant";

export const SIMPLE_REWARDS_STORE_ID = "store_simple_rewards";

export function SimpleRewardsHome() {
  return (
    <div className="simple-rewards simple-rewards--home">
      <section className="simple-rewards__intro">
        <span>シンプルリワード</span>
        <h1>必要なものだけ、<br />すぐ使える。</h1>
        <p>会員証・クーポン・スタンプに絞った、迷わない会員アプリです。</p>
      </section>
      <nav className="simple-rewards__menu" aria-label="シンプルメニュー">
        <Link to="/membership"><CreditCard /><span><strong>会員証</strong><small>レジで提示する</small></span><ChevronRight /></Link>
        <Link to="/coupon"><Ticket /><span><strong>クーポン</strong><small>使える特典を見る</small></span><ChevronRight /></Link>
        <Link to="/stamp"><Stamp /><span><strong>スタンプ</strong><small>あと何個か確認する</small></span><ChevronRight /></Link>
      </nav>
      <p className="simple-rewards__note">このデザインは、機能を最小限にしたい店舗向けです。</p>
    </div>
  );
}

export function SimpleRewardsMembership() {
  const { member } = useRuntimeData();
  const [large, setLarge] = useState(false);

  return (
    <div className="simple-rewards simple-rewards--membership">
      <section className="simple-pass">
        <div className="simple-pass__head"><span>SIMPLE PASS</span><b>会員証</b></div>
        <strong className="simple-pass__name">{member.name} 様</strong>
        <span className="simple-pass__number">会員番号 {member.number}</span>
        <div className="simple-pass__barcode" aria-label={`会員番号 ${member.number} のバーコード`}><i /><b>{member.number}</b></div>
      </section>
      <button className="simple-rewards__primary" type="button" onClick={() => setLarge(true)}>バーコードを大きく表示</button>
      <p className="simple-rewards__note">お会計の前に、この会員証をご提示ください。</p>
      <CenterPopup open={large} onClose={() => setLarge(false)} title="会員証バーコード" className="barcode-popup">
        <div className="simple-barcode-large"><i /><strong>{member.number}</strong><button type="button" onClick={() => setLarge(false)}>閉じる</button></div>
      </CenterPopup>
    </div>
  );
}

export function SimpleRewardsCoupons() {
  const { coupons, usedCouponIds, useCoupon } = useRuntimeData();
  const [selectedId, setSelectedId] = useState<string | null>(null);
  const [message, setMessage] = useState("");
  const selected = coupons.find((coupon) => coupon.id === selectedId);

  const confirm = async () => {
    if (selected === undefined) return;
    await useCoupon(selected.id);
    setMessage(`「${selected.title}」を使用済みにしました。`);
    setSelectedId(null);
  };

  return (
    <div className="simple-rewards simple-rewards--coupon">
      <div className="simple-screen-title"><span>会員限定</span><h1>クーポン</h1><p>使いたいクーポンを選んでください。</p></div>
      {message !== "" ? <p className="simple-rewards__message" role="status"><Check size={16} />{message}</p> : null}
      <section className="simple-coupon-list">
        {coupons.map((coupon) => {
          const used = usedCouponIds.includes(coupon.id);
          return <article className={used ? "is-used" : ""} key={coupon.id}><span><Ticket /><small>会員限定クーポン</small></span><h2>{coupon.title}</h2><p>{coupon.description}</p><div><small>{coupon.validUntil}まで</small><button type="button" disabled={used} onClick={() => { setMessage(""); setSelectedId(coupon.id); }}>{used ? "使用済み" : "使う"}</button></div></article>;
        })}
      </section>
      <BottomSheet open={selected !== undefined} onClose={() => setSelectedId(null)} title="クーポンを使用しますか？">
        <div className="simple-confirm"><Ticket size={34} /><strong>{selected?.title}</strong><p>店舗スタッフに画面を見せてから「使用する」を押してください。</p><button type="button" onClick={() => void confirm()}>使用する</button></div>
      </BottomSheet>
    </div>
  );
}

export function SimpleRewardsStamp() {
  const { stampCount, addStamp } = useRuntimeData();
  const [message, setMessage] = useState("");
  const [error, setError] = useState("");
  const [scannerOpen, setScannerOpen] = useState(false);
  const [newStampIndex, setNewStampIndex] = useState<number>();
  const [animationKey, setAnimationKey] = useState(0);
  const [searchParams, setSearchParams] = useSearchParams();
  const queryHandled = useRef(false);
  const goal = 10;
  const visibleCount = Math.min(stampCount, goal);

  const grantStamp = useCallback(async (rawValue: string) => {
    if (visibleCount >= goal) {
      setMessage("スタンプが貯まりました。特典をご利用ください。いつでも確認できます。");
      return;
    }
    const payload = parseStampGrant(rawValue);
    if (payload === null) {
      setError(STAMP_GRANT_INVALID_QR_MESSAGE);
      return;
    }
    setError("");
    try {
      await addStamp(payload.value);
      setNewStampIndex(visibleCount);
      setAnimationKey((current) => current + 1);
      setMessage(`スタンプを1個追加しました。現在 ${visibleCount + 1} / ${goal} 個です。`);
      setScannerOpen(false);
    } catch (caught) {
      setError(caught instanceof Error ? caught.message : "スタンプを追加できませんでした。");
    }
  }, [addStamp, visibleCount]);

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
    <div className="simple-rewards simple-rewards--stamp">
      <div className="simple-screen-title"><span>スタンプカード</span><h1>スタンプ</h1><p>10個貯まると、次回使える特典をプレゼント。</p></div>
      <section className="simple-stamp-card">
        <div><strong>{visibleCount}</strong><span>/ {goal}</span></div>
        <div className="simple-stamp-grid">{Array.from({ length: goal }, (_, index) => <span className={`${index < visibleCount ? "is-filled" : ""}${index === newStampIndex ? " is-new" : ""}`} key={`${index}-${index === newStampIndex ? animationKey : 0}`}>{index < visibleCount ? <Check size={18} /> : index + 1}</span>)}</div>
        <p>{goal - visibleCount > 0 ? `特典まであと ${goal - visibleCount} 個です。` : "特典を獲得しました！"}</p>
      </section>
      <button className="simple-rewards__primary" type="button" disabled={visibleCount >= goal} onClick={() => openStampScanner(setScannerOpen)}><Camera size={18} />カメラでQRを読み取る</button>
      {message !== "" ? <p className="simple-rewards__message is-animated" role="status" key={animationKey}><Check size={16} />{message}</p> : null}
      {error !== "" ? <p className="form-error" role="alert">{error}</p> : null}
      <StampScannerPopup open={scannerOpen} onClose={() => setScannerOpen(false)} onDetected={handleDetected} />
    </div>
  );
}

export function SimpleRewardsFeature({ feature }: { feature: string }) {
  if (feature === "coupon") return <SimpleRewardsCoupons />;
  if (feature === "stamp") return <SimpleRewardsStamp />;
  return null;
}

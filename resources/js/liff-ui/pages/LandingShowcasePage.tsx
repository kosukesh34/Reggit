import {
  ArrowRight,
  CalendarDays,
  Bell,
  CheckCircle2,
  ChevronRight,
  Clock3,
  Coffee,
  Coins,
  CreditCard,
  Gift,
  Heart,
  History,
  Info,
  Leaf,
  MessageSquareText,
  MapPinned,
  Megaphone,
  ReceiptText,
  RotateCcw,
  Search,
  ShoppingBag,
  Sparkles,
  Ticket,
  TicketPercent,
  UserRound,
  WalletCards,
  UsersRound,
} from "lucide-react";
import { useMemo, useState } from "react";
import { Link } from "react-router-dom";
import { MembershipBarcode } from "../components/membership/MembershipBarcode";
import { BottomSheet } from "../components/ui/BottomSheet";
import { useAppConfig } from "../context/AppConfigContext";
import { useRuntimeData } from "../context/RuntimeDataContext";
import type { FeatureType } from "../types/app";

export const LANDING_SHOWCASE_STORE_ID = "store_landing_showcase";

const orderItems = [
  {
    name: "カフェラテ",
    description: "香り豊かなエスプレッソに、きめ細かなスチームミルクを合わせました。",
    meta: "Mサイズ",
    category: "コーヒー",
    price: 560,
    image: "/images/liff/menu-iced-latte.jpg",
  },
  {
    name: "カプチーノ",
    description: "エスプレッソとミルク、ふんわりフォームをバランスよく仕上げました。",
    meta: "Mサイズ",
    category: "コーヒー",
    price: 560,
    image: "/images/liff/menu-cappuccino.jpg",
  },
  {
    name: "バタークロワッサン",
    description: "発酵バターが香る、外はさっくり中はしっとりのクロワッサンです。",
    meta: "店内焼き上げ",
    category: "ペストリー",
    price: 420,
    image: "/images/liff/menu-croissant.jpg",
  },
  {
    name: "アボカドトースト",
    description: "サワードウにアボカドと香ばしいシードをたっぷりのせました。",
    meta: "ベジタリアン",
    category: "朝食",
    price: 780,
    image: "/images/liff/menu-avocado-toast.jpg",
  },
  {
    name: "ブルーベリーマフィン",
    description: "甘酸っぱいブルーベリーを混ぜ込んだ、しっとり食感のマフィンです。",
    meta: "店内焼き上げ",
    category: "ペストリー",
    price: 390,
    image: "/images/liff/menu-cookie.jpg",
  },
];

function LeafMark({ size = 22 }: { size?: number }) {
  return <Leaf size={size} strokeWidth={1.8} aria-hidden="true" />;
}

export function LandingShowcaseHome() {
  const today = new Intl.DateTimeFormat("ja-JP", { month: "long", day: "numeric", weekday: "short" }).format(new Date());
  const { member } = useRuntimeData();
  const firstName = member.name.trim().split(/[\s　]+/).slice(-1)[0] || "ゲスト";

  return (
    <div className="landing-showcase landing-showcase--home">
      <section className="lp-app-intro">
        <div><small>{today}</small><h1>こんにちは、{firstName}さん</h1></div>
        <p>今日も、お店で心地よい時間を。</p>
      </section>

      <Link className="lp-app-order-feature" to="/mobileOrder">
        <img src="/images/liff/menu-iced-latte.jpg" alt="カフェラテと店内のテーブル" />
        <span className="lp-app-order-feature__shade" />
        <span className="lp-app-order-feature__copy">
          <small>モバイルオーダー</small>
          <strong>いつもの一杯を、<br />待たずに受け取る。</strong>
          <b>メニューを見る <ArrowRight size={16} /></b>
        </span>
      </Link>

      <Link className="lp-app-member-pass" to="/membership">
        <span className="lp-app-member-pass__head"><small>REGGIT MEMBER</small><b>GREEN</b></span>
        <span className="lp-app-member-pass__body"><span><small>利用可能ポイント</small><strong>{member.points.toLocaleString()}<em> pt</em></strong></span><CreditCard size={32} /></span>
        <span className="lp-app-member-pass__progress"><i><b /></i><small>ゴールドまであと550ポイント</small></span>
        <span className="lp-app-member-pass__barcode"><small>お会計時にご提示ください</small><MembershipBarcode value={member.number} /></span>
        <span className="lp-app-member-pass__link">会員証を表示 <ChevronRight size={16} /></span>
      </Link>

      <section className="lp-app-services" aria-labelledby="app-services-title">
        <header><div><small>サービス</small><h2 id="app-services-title">今日、お店でできること</h2></div><span>すべて</span></header>
        <div className="lp-app-action-grid">
        <Link to="/mobileOrder">
          <span><ReceiptText size={24} /></span>
          <span><strong>注文</strong><small>受取時間を短く</small></span>
          <ChevronRight size={18} />
        </Link>
        <Link to="/coupon">
          <span><Ticket size={24} /></span>
          <span><strong>クーポン</strong><small>使える特典を確認</small></span>
          <ChevronRight size={18} />
        </Link>
        <Link to="/queue">
          <span><UsersRound size={24} /></span>
          <span><strong>順番待ち</strong><small>店外から状況確認</small></span>
          <ChevronRight size={18} />
        </Link>
        <Link to="/stamp">
          <span><Gift size={24} /></span>
          <span><strong>スタンプ</strong><small>あと6個で特典</small></span>
          <ChevronRight size={18} />
        </Link>
        </div>
      </section>

      <Link className="lp-app-queue-card" to="/queue">
        <span className="lp-app-queue-card__icon"><UsersRound size={22} /></span>
        <span><small>順番待ち</small><strong>現在 12番・あと5組</strong><b>目安 25分</b></span>
        <ChevronRight size={20} />
      </Link>

      <section className="lp-app-picks">
        <header><small>おすすめ</small><h2>季節のメニュー</h2></header>
        <div>
          <Link to="/mobileOrder"><img src="/images/liff/menu-cappuccino.jpg" alt="カプチーノ" /><span><strong>カプチーノ</strong><small>¥560</small></span></Link>
          <Link to="/mobileOrder"><img src="/images/liff/menu-croissant.jpg" alt="バタークロワッサン" /><span><strong>バタークロワッサン</strong><small>¥420</small></span></Link>
        </div>
      </section>
    </div>
  );
}

export function LandingShowcaseOrder() {
  const [category, setCategory] = useState("すべて");
  const [query, setQuery] = useState("");
  const [cart, setCart] = useState<Record<string, number>>({});
  const [cartOpen, setCartOpen] = useState(false);
  const [ordered, setOrdered] = useState(false);
  const categories = ["すべて", "コーヒー", "朝食", "ペストリー", "サンドイッチ", "軽食"];
  const visibleItems = useMemo(() => orderItems.filter((item) => {
    const categoryMatches = category === "すべて" || item.category === category;
    const queryMatches = `${item.name} ${item.description}`.toLowerCase().includes(query.toLowerCase());
    return categoryMatches && queryMatches;
  }), [category, query]);
  const total = Object.values(cart).reduce((sum, count) => sum + count, 0);
  const totalPrice = orderItems.reduce((sum, item) => sum + item.price * (cart[item.name] ?? 0), 0);

  return (
    <div className="landing-showcase landing-showcase--order">
      <button className="lp-order-cart" type="button" aria-label={`カートを確認・${total}点`} onClick={() => setCartOpen(true)}>
        <ShoppingBag size={25} />{total > 0 ? <b>{total}</b> : null}
      </button>
      <label className="lp-order-search">
        <Search size={21} />
        <input value={query} onChange={(event) => setQuery(event.target.value)} placeholder="メニューを検索" />
      </label>
      <div className="lp-order-categories" aria-label="メニューカテゴリー">
        {categories.map((item) => <button className={item === category ? "is-active" : ""} type="button" onClick={() => setCategory(item)} key={item}>{item}</button>)}
      </div>
      <section className="lp-order-list" aria-live="polite">
        {visibleItems.map((item) => {
          const count = cart[item.name] ?? 0;
          return (
            <article className="lp-order-item" key={item.name}>
              <img src={item.image} alt="" />
              <span className="lp-order-item__copy"><strong>{item.name}</strong><p>{item.description}</p><small><Coffee size={13} />{item.meta}</small></span>
              <span className="lp-order-item__action"><b>¥{item.price.toLocaleString()}</b><button type="button" onClick={() => { setOrdered(false); setCart((current) => ({ ...current, [item.name]: count + 1 })); }}>{count > 0 ? `追加済み ${count}` : "追加"}</button></span>
            </article>
          );
        })}
        {visibleItems.length === 0 ? <p className="lp-order-empty">該当するメニューがありません。</p> : null}
      </section>
      {total > 0 ? <button className="lp-order-dock" type="button" onClick={() => setCartOpen(true)}><span>{total}点</span><strong>カートを確認</strong><b>¥{totalPrice.toLocaleString()}</b></button> : null}

      <BottomSheet open={cartOpen} onClose={() => setCartOpen(false)} title="ご注文内容">
        {ordered ? (
          <div className="lp-order-complete" role="status"><span>✓</span><strong>注文を受け付けました</strong><p>受取番号は <b>R-102</b> です。準備ができましたらLINEでお知らせします。</p><button type="button" onClick={() => { setOrdered(false); setCart({}); setCartOpen(false); }}>閉じる</button></div>
        ) : total === 0 ? (
          <div className="lp-order-complete"><ShoppingBag size={34} /><strong>カートは空です</strong><p>メニューから商品を追加してください。</p></div>
        ) : (
          <div className="lp-order-sheet">
            {orderItems.filter((item) => (cart[item.name] ?? 0) > 0).map((item) => <div key={item.name}><span><strong>{item.name}</strong><small>{cart[item.name]}点</small></span><b>¥{(item.price * cart[item.name]).toLocaleString()}</b><button type="button" aria-label={`${item.name}を1点減らす`} onClick={() => setCart((current) => ({ ...current, [item.name]: Math.max(0, (current[item.name] ?? 0) - 1) }))}>−</button></div>)}
            <div className="lp-order-sheet__total"><strong>合計</strong><b>¥{totalPrice.toLocaleString()}</b></div>
            <button className="lp-order-sheet__submit" type="button" onClick={() => setOrdered(true)}>この内容で注文する</button>
          </div>
        )}
      </BottomSheet>
    </div>
  );
}

export function LandingShowcaseMembership() {
  const [termsOpen, setTermsOpen] = useState(false);
  const { member } = useRuntimeData();

  return (
    <div className="landing-showcase landing-showcase--membership">
      <section className="lp-member-greeting">
        <span><UserRound size={31} /></span>
        <div><h1>{member.name} 様</h1><p>いつもご利用いただきありがとうございます。</p></div>
      </section>
      <section className="lp-member-card">
        <div className="lp-member-card__top">
          <span><small>Reggit Café</small><strong>{member.name || "会員さま"}</strong></span>
          <b>グリーン</b>
        </div>
        <div className="lp-member-card__points"><small>現在のポイント</small><span><strong>{member.points.toLocaleString()}</strong><em>pt</em></span></div>
        <div className="lp-member-card__barcode"><small>ためる・使うときにご提示ください</small><MembershipBarcode value={member.number} /></div>
      </section>
      <section className="lp-member-rank-progress">
        <span><small>次のランクまで</small><strong>あと550ポイントでゴールド</strong></span>
        <i><b /></i>
      </section>
      <div className="lp-member-terms"><span><Info size={17} /> ポイントの有効期限はありません</span><button type="button" onClick={() => setTermsOpen(true)}>利用規約 <ChevronRight size={17} /></button></div>
      <section className="lp-member-shortcuts" aria-label="会員メニュー">
        <Link to="/coupon"><Ticket /><span>クーポン</span></Link>
        <Link to="/mobileOrder"><ShoppingBag /><span>注文</span></Link>
        <Link to="/orderHistory"><History /><span>利用履歴</span></Link>
        <Link to="/more"><UserRound /><span>マイページ</span></Link>
      </section>
      <BottomSheet open={termsOpen} onClose={() => setTermsOpen(false)} title="ポイント利用規約">
        <div className="lp-member-terms-sheet"><strong>メンバー規約</strong><p>ポイントは対象店舗でのお会計時にためる・使うことができます。第三者への譲渡や換金はできません。</p><p>規約の内容は店舗の設定に従います。</p><button type="button" onClick={() => setTermsOpen(false)}>確認しました</button></div>
      </BottomSheet>
    </div>
  );
}

export function LandingShowcaseStamp() {
  return (
    <div className="landing-showcase landing-showcase--stamp">
      <section className="lp-stamp-brand">
        <span><Coffee size={50} /><Leaf size={28} /></span>
        <h1>GREENLEAF</h1>
        <p><i /> C A F É <i /></p>
      </section>
      <section className="lp-stamp-copy"><h2>スタンプカード</h2><p>ドリンクを9杯購入すると、10杯目をプレゼント！</p></section>
      <section className="lp-stamp-grid" aria-label="10個中4個のスタンプ">
        {Array.from({ length: 10 }, (_, index) => <span className={index < 4 ? "is-stamped" : ""} key={index}>{index < 4 ? <LeafMark size={27} /> : index + 1}</span>)}
      </section>
      <section className="lp-stamp-progress-card">
        <span><Gift size={41} /></span>
        <div><strong>4 / 10 スタンプ</strong><i><b /></i><p>無料ドリンクまで、あと6個です。</p></div>
      </section>
      <Link className="lp-stamp-thanks" to="/coupon"><Heart size={27} /><span><strong>いつもありがとうございます。</strong><small>会員限定クーポンもご確認ください。</small></span><ChevronRight /></Link>
    </div>
  );
}

export function LandingShowcaseQueue() {
  const [notifications, setNotifications] = useState(true);
  const [requested, setRequested] = useState(false);

  return (
    <div className="landing-showcase landing-showcase--queue">
      <section className="lp-queue-welcome"><span><UsersRound size={27} /></span><div><strong>順番待ちを受け付けました</strong><p>ご案内まで、もうしばらくお待ちください。</p></div></section>
      <section className="lp-queue-number">
        <p>あなたの受付番号</p>
        <strong>34</strong>
        <span><i /><Coffee size={25} /><i /></span>
        <h2>待ち時間の目安</h2>
        <h3>25〜30 <small>分</small></h3>
        <p>お席の準備が近づいたらLINEでお知らせします。</p>
      </section>
      <button className="lp-queue-notification" type="button" onClick={() => setNotifications((current) => !current)} aria-pressed={notifications}>
        <span><Bell size={25} /></span><span><strong>LINE通知</strong><small>{notifications ? "呼び出し通知を受け取ります。" : "通知はオフになっています。"}</small></span><i className={notifications ? "is-on" : ""}><b /></i>
      </button>
      <button className={`lp-queue-primary${requested ? " is-complete" : ""}`} type="button" onClick={() => setRequested((current) => !current)}><MessageSquareText size={21} />{requested ? "呼び出し通知を登録しました" : "席が用意できたら通知する"}</button>
      {requested ? <p className="lp-queue-feedback" role="status">通知登録済みです。もう一度押すと解除できます。</p> : null}
    </div>
  );
}

function ShowcaseUtilityHeader({ eyebrow, title, description }: { eyebrow: string; title: string; description: string }) {
  return <header className="lp-utility-header"><small>{eyebrow}</small><h1>{title}</h1><p>{description}</p></header>;
}

function formatShowcaseDate(value: string): string {
  const date = new Date(`${value}T00:00:00`);
  if (Number.isNaN(date.getTime())) return value;
  return new Intl.DateTimeFormat("ja-JP", { year: "numeric", month: "short", day: "numeric" }).format(date);
}

export function LandingShowcaseUtility({ feature }: { feature: FeatureType | string }) {
  const { activeStore } = useAppConfig();
  const { member, coupons, news, stores, history, usedCouponIds, useCoupon, createReservation } = useRuntimeData();
  const [selectedDate, setSelectedDate] = useState("9月21日(月)");
  const [selectedTime, setSelectedTime] = useState("11:30");
  const [reserved, setReserved] = useState(false);
  const [couponTab, setCouponTab] = useState<"available" | "history">("available");
  const [selectedCoupon, setSelectedCoupon] = useState<(typeof coupons)[number]>();
  const [couponSubmitting, setCouponSubmitting] = useState(false);
  const [couponMessage, setCouponMessage] = useState("");

  if (feature === "coupon") {
    const today = new Date().toISOString().slice(0, 10);
    const couponStatus = (coupon: typeof coupons[number]) => {
      if (usedCouponIds.includes(coupon.id)) return "used";
      if (coupon.validUntil < today) return "expired";
      if (coupon.validFrom && coupon.validFrom > today) return "upcoming";
      return "available";
    };
    const availableCoupons = coupons.filter((coupon) => ["available", "upcoming"].includes(couponStatus(coupon)));
    const historyCoupons = coupons.filter((coupon) => ["used", "expired"].includes(couponStatus(coupon)));
    const visibleCoupons = couponTab === "available" ? availableCoupons : historyCoupons;
    return (
      <div className="landing-showcase landing-showcase--utility">
        <ShowcaseUtilityHeader eyebrow="会員特典" title="クーポン" description="使える特典と期限を、ここでまとめて確認できます。" />
        {couponMessage !== "" ? <div className="lp-utility-feedback" role="status"><CheckCircle2 size={18} />{couponMessage}</div> : null}
        <div className="lp-utility-coupon-tabs" role="tablist" aria-label="クーポンの状態">
          <button type="button" role="tab" aria-selected={couponTab === "available"} className={couponTab === "available" ? "is-active" : ""} onClick={() => setCouponTab("available")}>利用可能 <b>{availableCoupons.length}</b></button>
          <button type="button" role="tab" aria-selected={couponTab === "history"} className={couponTab === "history" ? "is-active" : ""} onClick={() => setCouponTab("history")}>利用履歴 <b>{historyCoupons.length}</b></button>
        </div>
        {couponTab === "history" && historyCoupons.length > 0 ? (
          <section className="lp-coupon-history-summary" aria-label="クーポン利用履歴の概要">
            <span><History size={20} /></span>
            <div><strong>これまでのクーポン</strong><small>使用済み {historyCoupons.filter((coupon) => couponStatus(coupon) === "used").length}件 · 期限切れ {historyCoupons.filter((coupon) => couponStatus(coupon) === "expired").length}件</small></div>
          </section>
        ) : null}
        <div className="lp-utility-coupon-list">
          {visibleCoupons.length > 0 ? visibleCoupons.map((coupon, index) => {
            const status = couponStatus(coupon);
            const used = status === "used";
            const expired = status === "expired";
            const upcoming = status === "upcoming";
            const statusLabel = used ? "使用済み" : expired ? "期限切れ" : upcoming ? "利用開始前" : coupon.featured ? "おすすめ" : "会員限定";
            const dateLabel = upcoming && coupon.validFrom
              ? `${formatShowcaseDate(coupon.validFrom)}から`
              : `${formatShowcaseDate(coupon.validUntil)}まで`;
            return (
              <article className={`lp-utility-coupon${used || expired ? " is-used is-history" : ""}${upcoming ? " is-upcoming" : ""}`} key={coupon.id}>
                <div className="lp-utility-coupon__visual">
                  {coupon.image
                    ? <img className="lp-utility-coupon__image" src={coupon.image} alt={`${coupon.title}のイメージ`} />
                    : <span className={`lp-utility-coupon__mark lp-utility-coupon__mark--${index + 1}`}><TicketPercent size={28} /></span>}
                  <span className="lp-utility-coupon__status">{statusLabel}</span>
                </div>
                <div className="lp-utility-coupon__body">
                  <small>{dateLabel}</small>
                  <h2>{coupon.title}</h2>
                  <p>{coupon.description}</p>
                </div>
                {used || expired ? (
                  <div className="lp-utility-coupon__history-meta">
                    <span>{used ? <CheckCircle2 size={16} /> : <Clock3 size={16} />}{statusLabel}</span>
                    <small>このクーポンは再利用できません</small>
                  </div>
                ) : (
                  <button type="button" disabled={upcoming} onClick={() => { setCouponMessage(""); setSelectedCoupon(coupon); }}>
                    {upcoming ? `${formatShowcaseDate(coupon.validFrom ?? "")}から利用可能` : "クーポンを確認する"}
                  </button>
                )}
              </article>
            );
          }) : <div className="lp-utility-empty">この状態のクーポンはありません。</div>}
        </div>
        <BottomSheet open={selectedCoupon !== undefined} onClose={() => setSelectedCoupon(undefined)} title="クーポンを使う">
          {selectedCoupon ? (
            <div className="lp-coupon-confirm">
              {selectedCoupon.image ? <img src={selectedCoupon.image} alt="" /> : <TicketPercent size={30} />}
              <div><small>{formatShowcaseDate(selectedCoupon.validUntil)}まで</small><h2>{selectedCoupon.title}</h2><p>{selectedCoupon.description}</p></div>
              <p className="lp-coupon-confirm__notice"><Info size={17} />確定後は取り消せません。店舗スタッフにこの画面を見せてから確定してください。</p>
              <button type="button" disabled={couponSubmitting} onClick={async () => {
                setCouponSubmitting(true);
                try {
                  await useCoupon(selectedCoupon.id);
                  setCouponMessage(`「${selectedCoupon.title}」を使用済みにしました。`);
                  setSelectedCoupon(undefined);
                  setCouponTab("history");
                } finally {
                  setCouponSubmitting(false);
                }
              }}>{couponSubmitting ? "処理中…" : "利用を確定する"}</button>
            </div>
          ) : null}
        </BottomSheet>
      </div>
    );
  }

  if (feature === "point") {
    return <div className="landing-showcase landing-showcase--utility"><ShowcaseUtilityHeader eyebrow="会員ポイント" title="ポイント" description="ためる・使うの履歴をいつでも確認できます。" /><section className="lp-utility-balance"><small>現在のポイント</small><strong>{member.points.toLocaleString()}<em> pt</em></strong><span><Coins size={16} />ゴールドまであと550ポイント</span><i><b /></i></section><h2 className="lp-utility-subtitle">最近の履歴</h2><div className="lp-utility-history"><div><span><Coffee size={17} /></span><p><strong>カフェラテのご利用</strong><small>2026年9月18日</small></p><b>+56 pt</b></div><div><span><Ticket size={17} /></span><p><strong>ドリンクチケットを利用</strong><small>2026年9月12日</small></p><b className="is-minus">−300 pt</b></div><div><span><CreditCard size={17} /></span><p><strong>会員登録ボーナス</strong><small>2026年8月30日</small></p><b>+500 pt</b></div></div></div>;
  }

  if (feature === "reservation") {
    return <div className="landing-showcase landing-showcase--utility"><ShowcaseUtilityHeader eyebrow="来店予約" title="席を予約する" description="空いている時間を選んで、ゆっくり過ごせます。" /><section className="lp-utility-booking"><div className="lp-utility-booking__status"><CalendarDays size={20} /><span><small>次回の予約</small><strong>{reserved ? `${selectedDate} · ${selectedTime}` : "予約はありません"}</strong></span></div><h2>日付を選ぶ</h2><div className="lp-utility-date-list">{["9月21日(月)", "9月22日(火)", "9月23日(水)"].map((date) => <button className={selectedDate === date ? "is-active" : ""} type="button" onClick={() => setSelectedDate(date)} key={date}>{date}</button>)}</div><h2>時間を選ぶ</h2><div className="lp-utility-time-list">{["11:30", "12:00", "13:00", "14:30"].map((time) => <button className={selectedTime === time ? "is-active" : ""} type="button" onClick={() => setSelectedTime(time)} key={time}><Clock3 size={14} />{time}</button>)}</div><button className="lp-utility-primary" type="button" onClick={() => { if (reserved) { setReserved(false); return; } void createReservation({ storeId: activeStore.storeId, venueName: activeStore.storeName, dateLabel: selectedDate, time: selectedTime, partySize: 2, plan: "カフェ利用" }); setReserved(true); }}>{reserved ? <><CheckCircle2 size={18} />予約をキャンセル</> : <><CalendarDays size={18} />この内容で予約する</>}</button></section></div>;
  }

  if (feature === "news") {
    return <div className="landing-showcase landing-showcase--utility"><ShowcaseUtilityHeader eyebrow="JOURNAL" title="お知らせ" description="お店からの最新情報をお届けします。" /><div className="lp-utility-news-list">{news.slice(0, 4).map((item) => <article key={item.id}><span><Megaphone size={17} /></span><div><small>{item.category} · {item.date}</small><h2>{item.title}</h2><p>{item.body ?? "季節のおすすめや店舗からのお知らせをご案内します。"}</p></div><ChevronRight size={18} /></article>)}</div></div>;
  }

  if (feature === "storeSearch") {
    return <div className="landing-showcase landing-showcase--utility"><ShowcaseUtilityHeader eyebrow="LOCATIONS" title="店舗を探す" description="お近くの店舗で、Reggitをご利用いただけます。" /><div className="lp-utility-store-list">{stores.slice(0, 3).map((store) => <article key={store.name}><span><MapPinned size={20} /></span><div><h2>{store.name}</h2><p>{store.address}</p><small>{store.openHours}</small></div><ChevronRight size={18} /></article>)}</div></div>;
  }

  if (feature === "orderHistory" || feature === "purchaseHistory") {
    const historyRows = history ?? [];
    const rows = historyRows.length > 0 ? historyRows : [{ id: "demo-1", title: "カフェラテ・クロワッサン", status: "受取済み", date: "2026年9月18日", total: 980 }, { id: "demo-2", title: "アイスティー", status: "受取済み", date: "2026年9月10日", total: 420 }];
    return <div className="landing-showcase landing-showcase--utility"><ShowcaseUtilityHeader eyebrow="YOUR ORDERS" title="注文履歴" description="過去の注文を確認して、もう一度注文できます。" /><div className="lp-utility-order-list">{rows.slice(0, 4).map((row) => <article key={row.id}><div><small>{row.date}</small><strong>{row.title}</strong><span><CheckCircle2 size={14} />{row.status}</span></div><b>¥{Number(row.total).toLocaleString()}</b><button type="button"><RotateCcw size={15} />もう一度</button></article>)}</div></div>;
  }

  if (feature === "menu" || feature === "dineInOrder") return <LandingShowcaseOrder />;

  if (feature === "gallery" || feature === "favorite" || feature === "ticket" || feature === "subscription") {
    const titleMap: Record<string, [string, string, string]> = { gallery: ["JOURNAL", "お店の風景", "季節のおすすめと、店内の空気をお楽しみください。"], favorite: ["SAVED", "お気に入り", "気になったメニューを、いつでも呼び出せます。"], ticket: ["TICKETS", "チケット", "保有中のチケット・回数券を確認できます。"], subscription: ["MEMBERSHIP", "メンバーシップ", "いつもの利用を、もっと便利に。"] };
    const [eyebrow, title, description] = titleMap[feature] ?? titleMap.gallery;
    const gallery = feature === "gallery";
    return <div className="landing-showcase landing-showcase--utility"><ShowcaseUtilityHeader eyebrow={eyebrow} title={title} description={description} /><div className={`lp-utility-feature-grid${gallery ? " lp-utility-feature-grid--gallery" : ""}`}>{gallery ? <><Link to="/gallery"><img src="/images/landing/editorial/store-hero.webp" alt="店内の風景" /><strong>店内の風景</strong><small>静かな午後のカフェ時間</small></Link><Link to="/gallery"><img src="/images/liff/menu-iced-latte.jpg" alt="季節のドリンク" /><strong>季節のドリンク</strong><small>今月のおすすめ</small></Link><Link to="/gallery"><img src="/images/liff/menu-croissant.jpg" alt="焼きたてペストリー" /><strong>焼きたてペストリー</strong><small>毎朝店内で焼き上げ</small></Link></> : <><Link to="/mobileOrder"><img src="/images/liff/menu-cappuccino.jpg" alt="カプチーノ" /><strong>カプチーノ</strong><small>¥560 · おすすめ</small></Link><Link to="/mobileOrder"><img src="/images/liff/menu-croissant.jpg" alt="クロワッサン" /><strong>バタークロワッサン</strong><small>¥420 · 店内焼き上げ</small></Link><Link to="/membership"><span><WalletCards size={30} /></span><strong>会員証を表示</strong><small>バーコードを提示する</small></Link></>}</div></div>;
  }

  return <div className="landing-showcase landing-showcase--utility"><ShowcaseUtilityHeader eyebrow="REGGIT" title="サービス" description="ホームから使いたい機能を選んでください。" /><Link className="lp-utility-primary" to="/home"><ChevronRight size={18} />ホームへ戻る</Link></div>;
}

export function LandingShowcaseRegister() {
  return (
    <div className="landing-showcase landing-showcase--register">
      <section className="lp-register-hero">
        <span className="lp-register-hero__mark"><LeafMark size={28} /></span>
        <h1>メンバー登録</h1>
        <p>会員パスを発行すると、ポイントやクーポンを店舗で使えます。</p>
        <b>登録すると、ドリンク1杯サービス！</b>
      </section>
      <form className="lp-register-form" onSubmit={(event) => event.preventDefault()}>
        <label>
          <span>お名前</span>
          <span className="lp-register-form__name">
            <input type="text" name="last_name" placeholder="山田" autoComplete="family-name" />
            <input type="text" name="first_name" placeholder="太郎" autoComplete="given-name" />
          </span>
        </label>
        <label>
          <span>電話番号</span>
          <input type="tel" name="phone" placeholder="09012345678" autoComplete="tel" />
        </label>
        <label>
          <span>メールアドレス</span>
          <input type="email" name="email" placeholder="you@example.com" autoComplete="email" />
        </label>
        <label>
          <span>生年月日</span>
          <input type="date" name="birthday" />
        </label>
        <button className="lp-register-form__submit" type="submit">登録する</button>
      </form>
      <section className="lp-register-play" aria-labelledby="lp-register-play-title">
        <div><small>MEMBER BONUS</small><h2 id="lp-register-play-title">登録後はガチャで特典を体験</h2><p>会員登録後に、カプセルを回してクーポンを獲得できます。</p></div>
        <Link className="lp-register-play__link" to="/designlab"><Sparkles size={20} /><span><strong>ガチャを試す</strong><small>スクラッチ・ルーレットも available</small></span><ChevronRight size={18} /></Link>
      </section>
    </div>
  );
}

const showcaseMoreItems = [
  { to: "/point", label: "ポイント", description: "残高と履歴", icon: Coins },
  { to: "/stamp", label: "スタンプ", description: "特典まであと6個", icon: Gift },
  { to: "/reservation", label: "来店予約", description: "席を予約", icon: CalendarDays },
  { to: "/queue", label: "順番待ち", description: "現在の待ち時間", icon: UsersRound },
  { to: "/news", label: "お知らせ", description: "店舗からの最新情報", icon: Megaphone },
  { to: "/storeSearch", label: "店舗を探す", description: "営業時間・アクセス", icon: MapPinned },
  { to: "/orderHistory", label: "注文履歴", description: "再注文もこちら", icon: History },
  { to: "/favorite", label: "お気に入り", description: "保存したメニュー", icon: Heart },
  { to: "/gallery", label: "お店の風景", description: "季節のおすすめ", icon: Sparkles },
  { to: "/ticket", label: "チケット", description: "保有中の回数券", icon: Ticket },
];

export function LandingShowcaseMore() {
  const { member } = useRuntimeData();

  return (
    <div className="landing-showcase landing-showcase--utility landing-showcase--more">
      <ShowcaseUtilityHeader eyebrow="マイページ" title="サービス一覧" description="会員情報と、利用できるすべてのサービスを確認できます。" />
      <Link className="lp-more-member" to="/membership">
        <span><UserRound size={22} /></span>
        <div><small>{member.rank} MEMBER</small><strong>{member.name} 様</strong><p>{member.points.toLocaleString()}ポイント利用可能</p></div>
        <ChevronRight size={20} />
      </Link>
      <section className="lp-more-section" aria-labelledby="lp-more-services-title">
        <h2 id="lp-more-services-title">すべてのサービス</h2>
        <div className="lp-more-grid">
          {showcaseMoreItems.map((item) => (
            <Link to={item.to} key={item.to}>
              <span><item.icon size={20} /></span>
              <div><strong>{item.label}</strong><small>{item.description}</small></div>
              <ChevronRight size={17} />
            </Link>
          ))}
        </div>
      </section>
      <Link className="lp-more-register" to="/profile"><CreditCard size={20} /><span><strong>会員情報を確認する</strong><small>プロフィール・会員証の設定</small></span><ChevronRight size={18} /></Link>
    </div>
  );
}

export function LandingShowcaseFeature({ feature }: { feature: string }) {
  if (feature === "mobileOrder") return <LandingShowcaseOrder />;
  if (feature === "stamp") return <LandingShowcaseStamp />;
  if (feature === "queue") return <LandingShowcaseQueue />;
  return null;
}

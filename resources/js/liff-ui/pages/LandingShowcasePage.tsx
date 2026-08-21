import {
  Bell,
  ChevronRight,
  Coffee,
  CreditCard,
  Gift,
  Heart,
  History,
  Info,
  Leaf,
  MessageSquareText,
  ReceiptText,
  Search,
  ShoppingBag,
  Ticket,
  UserRound,
  UsersRound,
} from "lucide-react";
import { useMemo, useState } from "react";
import { Link } from "react-router-dom";
import { BottomSheet } from "../components/ui/BottomSheet";

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
  return (
    <div className="landing-showcase landing-showcase--home">
      <section className="lp-app-hero">
        <div className="lp-app-hero__halo" />
        <div className="lp-app-hero__store" aria-hidden="true">
          <span>Reggit</span>
          <i /><b /><em />
        </div>
        <p className="lp-app-hero__wordmark">Reggit</p>
        <h1>注文・会員証・順番待ち</h1>
      </section>

      <Link className="lp-app-queue-card" to="/queue">
        <span className="lp-app-queue-card__ticket"><strong>12</strong><i /><i /></span>
        <span className="lp-app-queue-card__number"><small>あなたの現在の番号</small><strong>12 <b>番</b></strong></span>
        <span className="lp-app-queue-card__status"><b>ご案内中</b><strong>あと 5 <small>組</small></strong><small>目安時間 25分</small></span>
        <UsersRound size={34} aria-hidden="true" />
        <ChevronRight size={20} aria-hidden="true" />
      </Link>

      <section className="lp-app-action-grid" aria-label="サービス">
        <Link to="/mobileOrder">
          <span><ReceiptText size={36} /></span>
          <strong>事前注文</strong>
          <small>メニューから注文する</small>
          <ChevronRight size={18} />
        </Link>
        <Link to="/membership">
          <span><CreditCard size={36} /></span>
          <strong>会員証</strong>
          <small>会員証を表示する</small>
          <ChevronRight size={18} />
        </Link>
        <Link to="/queue">
          <span><UsersRound size={36} /></span>
          <strong>順番待ち</strong>
          <small>待ち状況を確認する</small>
          <ChevronRight size={18} />
        </Link>
      </section>

      <Link className="lp-app-member-banner" to="/more">
        <span><small>Reggit メンバー</small><strong>会員限定の特典を受け取ろう</strong><b>登録すると、ドリンク1杯サービス！</b></span>
        <span className="lp-app-member-banner__gift"><Gift size={48} /><i>★</i></span>
        <ChevronRight size={20} />
      </Link>
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

  return (
    <div className="landing-showcase landing-showcase--membership">
      <section className="lp-member-greeting">
        <span><UserRound size={31} /></span>
        <div><h1>こんにちは、アレックスさん</h1><p>いつもご利用いただきありがとうございます。</p></div>
      </section>
      <section className="lp-member-card">
        <div className="lp-member-card__top">
          <span className="lp-member-card__logo"><LeafMark size={31} /></span>
          <span><strong>Evergreen Rewards</strong><small>メンバー</small></span>
          <b>グリーン</b>
        </div>
        <div className="lp-member-card__points"><small>利用可能ポイント</small><strong>2,450</strong><span>ポイント</span></div>
        <div className="lp-member-card__progress"><i>★</i><span>ゴールドまであと550ポイント</span><ChevronRight size={20} /></div>
        <div className="lp-member-card__barcode"><small>ためる・使うときにご提示ください</small><i /><strong>1234 5678 9012 3456</strong></div>
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
    </div>
  );
}

export function LandingShowcaseFeature({ feature }: { feature: string }) {
  if (feature === "mobileOrder") return <LandingShowcaseOrder />;
  if (feature === "stamp") return <LandingShowcaseStamp />;
  if (feature === "queue") return <LandingShowcaseQueue />;
  return null;
}

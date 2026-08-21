import { CalendarDays, ChevronRight, Gift, ScanLine, Star, TrendingUp, Zap } from "lucide-react";
import type { CSSProperties } from "react";
import { Link } from "react-router-dom";
import { DesignShowcaseBanner } from "../components/layout/DesignPicker";
import { MembershipBarcode } from "../components/membership/MembershipBarcode";
import { resolveAppBrand } from "../config/customization";
import type { HomePattern } from "../config/homePatterns";
import { useAppConfig } from "../context/AppConfigContext";
import { useRuntimeData } from "../context/RuntimeDataContext";
import type { CouponItem, MemberProfile } from "../types/app";

function guestName(member: MemberProfile): string {
  const name = member.name.trim() !== "" ? member.name : "ゲスト";
  return name.split(/[\s　]/)[0];
}

function stampGoal(goal: number, fallback: number): number {
  return goal > 0 ? goal : fallback;
}

function RewardsHome({ member, coupons, stampCount, goal }: { member: MemberProfile; coupons: CouponItem[]; stampCount: number; goal: number }) {
  const filled = Math.min(stampCount, goal);
  const remain = Math.max(goal - filled, 0);
  const progress = Math.round((filled / goal) * 100);
  return (
    <div className="ph ph--rewards">
      <p className="ph-kicker">おはようございます</p>
      <h1>{guestName(member)} さん</h1>
      <section className="ph-reward">
        <div className="ph-reward__top"><span>{member.rank || "メンバー"}</span><Link to="/stamp">詳細を見る</Link></div>
        <div className="ph-reward__body">
          <div className="ph-ring" style={{ "--ph-progress": progress } as CSSProperties}><span><b>{filled}</b><small>/ {goal}</small></span></div>
          <div><span>Rewards Stars</span><strong>あと {remain} 個でドリンク1杯無料</strong></div>
        </div>
        <div className="ph-stars" aria-hidden="true">{Array.from({ length: goal }, (_, i) => <Star key={i} size={14} fill={i < filled ? "currentColor" : "none"} />)}</div>
        <Link className="ph-reward__cta" to="/membership">会計用コードを提示</Link>
      </section>
      <div className="ph-section-head"><h2>保有チケット</h2><Link to="/coupon">すべて</Link></div>
      <div className="ph-tickets">
        {coupons.slice(0, 3).map((coupon) => (
          <Link className="ph-ticket" to="/coupon" key={coupon.id}>
            <strong>{coupon.featured === true ? "FREE" : coupon.title}</strong>
            <span>{coupon.description}</span>
            <small>〜{coupon.validUntil}</small>
          </Link>
        ))}
      </div>
    </div>
  );
}

function CheckinHome({ member }: { member: MemberProfile }) {
  return (
    <div className="ph ph--checkin">
      <p className="ph-kicker">会員ID {member.number}</p>
      <h1>{guestName(member)}</h1>
      <Link className="ph-checkin" to="/membership">
        <Zap size={28} />
        <strong>TAP TO CHECK-IN</strong>
        <small>かざしてチェックイン</small>
      </Link>
      <div className="ph-stats">
        <article><span>今月の来館数</span><b>12 <small>/ 15 回</small></b></article>
        <article><span>最新の体脂肪率</span><b>14.2 <small>%</small></b></article>
      </div>
      <Link className="ph-next" to="/reservation">
        <div><span>次回パーソナル予約</span><strong>本日 19:30 — 20:30</strong><small>担当：KENJI</small></div>
        <ChevronRight size={18} />
      </Link>
      <p className="ph-note"><TrendingUp size={14} />筋肉量 56.4 kg（+1.2kg）</p>
    </div>
  );
}

function BookingHome({ member }: { member: MemberProfile }) {
  return (
    <div className="ph ph--booking">
      <section className="ph-points">
        <div><span>現在のポイント残高</span><strong>{member.points.toLocaleString()} <small>P</small></strong></div>
        <Link to="/membership">カード提示</Link>
      </section>
      <h2>次回ご予約情報</h2>
      <article className="ph-reserve">
        <div><b>予約確定</b><strong>6月18日（金） 13:00〜</strong></div>
        <p><span>スタイリスト</span><span>メニュー</span></p>
        <p><strong>指名あり</strong><strong>カット＋カラー</strong></p>
      </article>
      <h2>ご来店履歴</h2>
      <ol className="ph-timeline">
        <li><time>4月12日</time><span>トリートメント & スパ</span></li>
        <li><time>2月20日</time><span>カット & リタッチカラー</span></li>
      </ol>
      <Link className="ph-primary" to="/reservation"><CalendarDays size={16} />新しい予約を取る</Link>
    </div>
  );
}

function StampHome({ coupons, stampCount, goal }: { coupons: CouponItem[]; stampCount: number; goal: number }) {
  const filled = Math.min(stampCount, goal);
  const unlock = Math.round((filled / goal) * 100);
  return (
    <div className="ph ph--stamp">
      <section className="ph-stampcard">
        <div><h2>スタンプカード</h2><p>獲得 <b>{filled}</b> / {goal}</p></div>
        <div className="ph-grid">
          {Array.from({ length: goal }, (_, i) => (
            <span className={i < filled ? "is-on" : ""} key={i}>{i < filled ? "★" : i + 1}</span>
          ))}
        </div>
        <small>{goal}個で特典と交換できます</small>
      </section>
      <h2>限定メニュー解放</h2>
      <div className="ph-unlock" style={{ "--ph-unlock": `${unlock}%` } as CSSProperties}>
        <span>あと {Math.max(goal - filled, 0)} 個</span>
        <i style={{ width: `${unlock}%` }} />
      </div>
      {coupons[0] !== undefined ? (
        <Link className="ph-perk" to="/coupon"><Gift size={18} /><div><strong>{coupons[0].title}</strong><small>{coupons[0].validUntil}まで</small></div></Link>
      ) : null}
    </div>
  );
}

function PassHome({ member }: { member: MemberProfile }) {
  return (
    <div className="ph ph--pass">
      <p className="ph-kicker">MEMBERSHIP PORTAL</p>
      <section className="ph-passcard">
        <div><span>MEMBER</span><strong>{member.rank || "会員"}</strong></div>
        <div className="ph-passcard__code"><MembershipBarcode value={member.number} /></div>
        <div className="ph-passcard__meta"><span>保有ポイント<b>{member.points.toLocaleString()} pt</b></span><span>会員番号<b>{member.number}</b></span></div>
      </section>
      <h2>会員限定特典</h2>
      <ul className="ph-benefits">
        <li>全品 10% OFF 優待</li>
        <li>焼き上がりアラート優先配信</li>
        <li>お誕生日月の特別ギフト</li>
      </ul>
    </div>
  );
}

function CouponsHome({ coupons }: { coupons: CouponItem[] }) {
  return (
    <div className="ph ph--coupons">
      <h1>お届けクーポン</h1>
      <p>店頭でのお支払いの際、スタッフに画面をご提示ください。</p>
      <div className="ph-coupon-list">
        {coupons.map((coupon) => (
          <article key={coupon.id}>
            <div><Gift size={18} /><div><small>{coupon.featured === true ? "注目" : "会員特典"}</small><strong>{coupon.title}</strong><span>{coupon.description}</span></div></div>
            <footer><small>期限 {coupon.validUntil}</small><Link to="/coupon">クーポンを使用</Link></footer>
          </article>
        ))}
      </div>
    </div>
  );
}

function LookbookHome({ title, image, featuredImage, featuredTitle }: { title: string; image: string; featuredImage: string; featuredTitle: string }) {
  return (
    <div className="ph ph--lookbook">
      <section className="ph-look" style={{ backgroundImage: `url("${image}")` }}>
        <span>LOOKBOOK</span>
        <h1>{title}</h1>
      </section>
      <Link className="ph-look-item" to="/favorite" style={{ backgroundImage: `url("${featuredImage || image}")` }}>
        <strong>{featuredTitle}</strong>
      </Link>
    </div>
  );
}

function ClinicHome({ member }: { member: MemberProfile }) {
  return (
    <div className="ph ph--clinic">
      <p className="ph-kicker">診察券 {member.number}</p>
      <h1>{guestName(member)} さん</h1>
      <section className="ph-wait">
        <span>ただいまの待ち</span>
        <strong>3<b>組</b></strong>
        <small>目安 12分</small>
        <Link to="/queue">順番待ちを確認</Link>
      </section>
      <Link className="ph-next" to="/reservation">
        <div><span>次回のご予約</span><strong>本日 17:30</strong><small>定期検診</small></div>
        <ChevronRight size={18} />
      </Link>
    </div>
  );
}

function MonoHome({ member }: { member: MemberProfile }) {
  return (
    <div className="ph ph--mono">
      <p>ウェルカム,</p>
      <h1>{member.name !== "" ? member.name : "ゲスト"} 様</h1>
      <section className="ph-mono-card">
        <MembershipBarcode value={member.number} />
      </section>
      <div className="ph-mono-points"><span>現在のポイント残高</span><b>{member.points.toLocaleString()} <small>P</small></b></div>
      <Link className="ph-ghost" to="/membership"><ScanLine size={16} />会員証を大きく表示</Link>
    </div>
  );
}

function StayHome({ member, image }: { member: MemberProfile; image: string }) {
  return (
    <div className="ph ph--stay">
      <section className="ph-stay-hero" style={{ backgroundImage: `url("${image}")` }}>
        <span>YOUR STAY</span>
        <h1>{guestName(member)} 様</h1>
      </section>
      <article className="ph-room">
        <span>チェックイン 15:00〜</span>
        <strong>Deluxe Twin · 5F</strong>
        <small>滞在 8/21 — 8/22</small>
        <Link to="/reservation">予約を確認</Link>
      </article>
    </div>
  );
}

function OrderHome({ label, image, featuredTitle }: { label: string; image: string; featuredTitle: string }) {
  return (
    <div className="ph ph--order">
      <Link className="ph-order-cta" to="/mobileOrder">
        <img src={image} alt="" />
        <div><small>最短 8–12分</small><strong>{label}</strong></div>
      </Link>
      <Link className="ph-order-item" to="/mobileOrder">
        <span>おすすめ</span>
        <strong>{featuredTitle}</strong>
      </Link>
    </div>
  );
}

export function PatternHome({ pattern }: { pattern: HomePattern }) {
  const { activeStore, customization } = useAppConfig();
  const { member, coupons, stampCount } = useRuntimeData();
  const brand = resolveAppBrand(activeStore, customization);
  const goal = stampGoal(customization.stamp.goal, pattern === "stamp" ? 15 : 10);
  const image = brand.heroImage || activeStore.home.heroImage;

  return (
    <div className={`page pattern-home pattern-home--${pattern}`}>
      <DesignShowcaseBanner />
      {pattern === "rewards" ? <RewardsHome member={member} coupons={coupons} stampCount={stampCount} goal={goal} /> : null}
      {pattern === "checkin" ? <CheckinHome member={member} /> : null}
      {pattern === "booking" ? <BookingHome member={member} /> : null}
      {pattern === "stamp" ? <StampHome coupons={coupons} stampCount={stampCount} goal={goal} /> : null}
      {pattern === "pass" ? <PassHome member={member} /> : null}
      {pattern === "coupons" ? <CouponsHome coupons={coupons} /> : null}
      {pattern === "lookbook" ? <LookbookHome title={brand.appName} image={image} featuredImage={activeStore.home.featuredImage} featuredTitle={activeStore.home.featuredTitle} /> : null}
      {pattern === "clinic" ? <ClinicHome member={member} /> : null}
      {pattern === "mono" ? <MonoHome member={member} /> : null}
      {pattern === "stay" ? <StayHome member={member} image={image} /> : null}
      {pattern === "order" ? <OrderHome label={activeStore.home.primaryActionLabel} image={image} featuredTitle={activeStore.home.featuredTitle} /> : null}
    </div>
  );
}

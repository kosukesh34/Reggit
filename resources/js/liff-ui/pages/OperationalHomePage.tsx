import {
  ArrowRight,
  CalendarDays,
  ChevronRight,
  Clock3,
  CreditCard,
  MapPin,
  ScanSearch,
  ShoppingBag,
  Sparkles,
  Ticket,
  UsersRound,
} from "lucide-react";
import { Link } from "react-router-dom";
import { useRuntimeData } from "../context/RuntimeDataContext";

export const COUNTER_OPERATIONS_STORE_ID = "store_counter_operations";
export const APPOINTMENT_OPERATIONS_STORE_ID = "store_appointment_operations";

function CounterOperationsHome() {
  const { member, coupons, stores, queueStatus } = useRuntimeData();
  const store = stores[0];
  const waitingGroups = queueStatus?.waitingGroups ?? 3;
  const estimatedMinutes = queueStatus?.estimatedMinutes ?? 12;

    return (
    <div className="operational-home counter-home">
      <section className="counter-home__welcome">
        <span className="operational-kicker">REGGIT COUNTER</span>
        <h1>{member.name.split(/[\s　]/)[0]}さん、<br />今日はどうしますか？</h1>
        <p>注文・順番待ち・受け取りをホームから操作できます。</p>
      </section>

      <section className="counter-status" aria-label="現在の店舗状況">
        <div><Clock3 /><span>最短受取<strong>{estimatedMinutes}分</strong></span></div>
        <div><UsersRound /><span>店内待ち<strong>{waitingGroups}組</strong></span></div>
        <div><MapPin /><span>営業時間<strong>21:00まで</strong></span></div>
      </section>

      <nav className="counter-launcher" aria-label="今すぐ使う">
        <Link className="counter-launcher__primary" to="/mobileOrder">
          <span><ShoppingBag /><small>TAKE OUT</small></span>
          <strong>持ち帰りを<br />注文する</strong>
          <ArrowRight />
        </Link>
        <Link className="counter-launcher__secondary" to="/queue">
          <span><ScanSearch /><small>WAIT LIST</small></span>
          <strong>順番待ちを<br />受け付ける</strong>
          <ArrowRight />
        </Link>
      </nav>

      <section className="counter-service-panel">
        <div className="operational-section-title">
          <span>店舗で使う</span><h2>クイックサービス</h2>
        </div>
        <div className="counter-service-list">
          <Link to="/membership"><CreditCard /><span><strong>会員証を表示</strong><small>{member.points.toLocaleString("ja-JP")}ポイント利用可能</small></span><ChevronRight /></Link>
          <Link to="/coupon"><Ticket /><span><strong>クーポンを選ぶ</strong><small>利用できる特典 {coupons.length}件</small></span><ChevronRight /></Link>
          <Link to="/orderHistory"><ShoppingBag /><span><strong>注文・受取状況</strong><small>過去の注文から再注文できます</small></span><ChevronRight /></Link>
        </div>
      </section>

      <Link className="counter-store-strip" to="/storeSearch">
        <span><small>利用店舗</small><strong>{store?.name ?? "渋谷駅前店"}</strong></span>
        <span>{store?.openHours ?? "08:00 - 21:00"}<ChevronRight /></span>
      </Link>
    </div>
  );
}

function AppointmentOperationsHome() {
  const { member, stores } = useRuntimeData();
  const store = stores[0];

    return (
    <div className="operational-home appointment-home">
      <section className="appointment-hero">
        <img src="/images/beauty.jpg" alt="落ち着いた店内" />
        <div className="appointment-hero__shade" />
        <div className="appointment-hero__copy">
          <span className="operational-kicker">REGGIT STUDIO</span>
          <h1>次のご予約</h1>
          <p>{member.name.split(/[\s　]/)[0]}さんの来店予定</p>
        </div>
        <article className="next-appointment-card">
          <time><b>21</b><span>AUG<br />FRI</span></time>
          <div><small>14:00 — 15:30</small><strong>カット＋カラー</strong><span>担当：田中 スタイリスト</span></div>
          <Link to="/reservation" aria-label="予約を確認・変更"><ChevronRight /></Link>
        </article>
      </section>

      <section className="appointment-availability">
        <div className="operational-section-title"><span>直近の予約枠</span><h2>空き時間から選ぶ</h2></div>
        <div className="appointment-slots">
          <Link to="/reservation"><small>本日</small><strong>17:30</strong><span>残り1枠</span></Link>
          <Link to="/reservation"><small>明日</small><strong>11:00</strong><span>空きあり</span></Link>
          <Link to="/reservation"><small>8/14</small><strong>15:30</strong><span>空きあり</span></Link>
        </div>
        <Link className="appointment-book-button" to="/reservation"><CalendarDays />新しい予約を取る<ArrowRight /></Link>
      </section>

      <section className="appointment-tools">
        <Link to="/membership"><span><CreditCard /></span><strong>会員証</strong><small>来店時に提示</small></Link>
        <Link to="/coupon"><span><Ticket /></span><strong>ご優待</strong><small>使える特典</small></Link>
        <Link to="/gallery"><span><Sparkles /></span><strong>スタイル</strong><small>仕上がりを探す</small></Link>
      </section>

      <section className="appointment-store-card">
        <div><span>MY SALON</span><h2>{store?.name ?? "銀座スタジオ"}</h2><p>{store?.access ?? "銀座駅 徒歩2分"} · {store?.openHours ?? "10:00 - 20:00"}</p></div>
        <Link to="/storeSearch"><MapPin />店舗情報</Link>
      </section>
    </div>
  );
}

export function OperationalHomePage({ variant }: { variant: "counter" | "appointment" }) {
  return variant === "counter" ? <CounterOperationsHome /> : <AppointmentOperationsHome />;
}

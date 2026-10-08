import { useEffect, useState, type CSSProperties } from "react";
import { Link, useLocation } from "react-router-dom";
import { useRuntimeData } from "../context/RuntimeDataContext";
import { useNativeConfig } from "../native/nativeConfig";
import { NativeState, nativeRequest, nativeDate, useNativeResource } from "../native/NativeResource";
import { MembershipBarcode } from "../components/membership/MembershipBarcode";
import { BottomSheet } from "../components/ui/BottomSheet";
import { MemberRegisterHost } from "../../frontend/features/member/MemberRegisterHost";
import { startLineLogin, shouldShowAppleWallet, shouldShowGoogleWallet, walletPassUrl } from "../../frontend/features/member/api";
import { isDemoDataMode } from "../../frontend/demo/demoMode";

type MemberData = {
  is_member?: boolean;
  member?: { member_number?: string; points?: number; user?: { name?: string } };
  user?: { name?: string };
  profile?: { name?: string };
  show_rank_progress?: boolean;
  member_card_logo_url?: string;
  points?: number;
  current_rank_label?: string;
  next_rank_label?: string;
  points_to_next_rank?: number;
  current_rank_color?: string;
  member_card_code_type?: string;
  points_expiry?: { expires_at_label?: string };
  profile_fields?: unknown[];
};
type PointHistory = { description?: string; created_at?: string; points?: number };
export function MembershipPage() {
  const location = useLocation();
  const [profileSuccess, setProfileSuccess] = useState<string>();
  useEffect(() => {
    const success = (location.state as { profileSuccess?: string } | null)?.profileSuccess;
    setProfileSuccess(success);
    if (!success) return;
    const timer = window.setTimeout(() => setProfileSuccess(undefined), 2400);
    return () => window.clearTimeout(timer);
  }, [location.state]);
  const runtime = useRuntimeData();
  const { config } = useNativeConfig();
  const sample: MemberData = { is_member: runtime.member.isMember, member: { member_number: runtime.member.number }, user: { name: runtime.member.name }, points: runtime.member.points, current_rank_label: runtime.member.rank, points_expiry: { expires_at_label: runtime.member.expiration } };
  const adminPreview = window.__IS_DESIGNER_EMBED_PREVIEW__ === true;
  const previewData = window.__DESIGNER_PREVIEW_MEMBER_PAYLOAD__ as MemberData | undefined;
  const previewSample: MemberData = { ...sample, ...previewData, is_member: true };
  const resource = useNativeResource("/api/member-profile", adminPreview ? previewSample : sample, isDemoDataMode() || adminPreview);
  const history = useNativeResource("/api/members/points/history", { transactions: [] as PointHistory[] }, isDemoDataMode() || adminPreview);
  const [walletBusy, setWalletBusy] = useState(false);
  const [walletError, setWalletError] = useState("");
  async function openGoogleWallet() {
    setWalletBusy(true); setWalletError("");
    try { const result = await nativeRequest<{ url: string }>("/api/member-wallet/google-url"); window.location.assign(result.url); }
    catch (caught) { setWalletError(caught instanceof Error ? caught.message : "Walletを開けませんでした"); }
    finally { setWalletBusy(false); }
  }
  const [enlarged, setEnlarged] = useState(false);
  const data = resource.data;
  const name = data.profile?.name ?? data.user?.name ?? data.member?.user?.name ?? runtime.member.name;
  const number = data.member?.member_number ?? runtime.member.number;
  const points = Number(data.points ?? data.member?.points ?? runtime.member.points);
  const rank = data.current_rank_label ?? runtime.member.rank;
  const qr = data.member_card_code_type === "qrcode";
  const rawColor = data.current_rank_color ?? config.theme?.primary_color;
  const color = rawColor && /^#[0-9a-f]{6}$/i.test(rawColor) ? rawColor : "#173f35";
  const channels = [1, 3, 5].map((start) => parseInt(color.slice(start, start + 2), 16));
  const foreground = channels[0] * .299 + channels[1] * .587 + channels[2] * .114 > 160 ? "#000" : "#fff";
  const memberScreen = config.member_screen;
  const isMember = data.is_member === true;
  const code = qr ? <div className="native-code-fallback"><strong>{number}</strong><MembershipBarcode value={number} height={45} /></div> : <MembershipBarcode value={number} height={config.member_screen?.style === "minimal" ? 96 : 64} />;
  return <div id="memberSection" data-tab-mount="member" data-react-owned="1" className={`native-screen native-membership native-membership--${config.member_screen?.style ?? "classic"}`}><NativeState loading={resource.loading} error={resource.error} reload={() => void resource.reload()}>
    {isMember ? <>
      {(memberScreen?.style ?? "classic") === "classic" ? <>
      <article className="native-member-pass" style={{ "--member-color": color, "--member-foreground": foreground } as CSSProperties}>
        {data.member_card_logo_url ? <header className="native-member-pass__image-header"><img src={data.member_card_logo_url} alt="会員証ヘッダー" /></header> : null}
        <div className="native-member-pass__body"><div className="native-member-pass__heading"><div><small>{config.project_name ?? "Reggit"}</small><h1>会員証</h1><h2>{name}</h2></div>{rank ? <span>{rank}</span> : null}</div><div className="native-member-pass__points"><strong>{points}</strong><span>pt</span>{data.points_expiry?.expires_at_label ? <div><small>ポイント期限</small><b>{data.points_expiry.expires_at_label}</b></div> : null}</div></div>
        <button className="native-member-pass__code" type="button" onClick={() => setEnlarged(true)} aria-label="会員コードを拡大">{code}<small>タップして拡大</small></button>
      </article>
      </> : <>
      <article className={`member-layout member-layout--${memberScreen?.style ?? "classic"}`} style={{ "--member-color": color, "--member-foreground": foreground } as CSSProperties}>
        {memberScreen?.style !== "wallet" && data.member_card_logo_url ? <header className="member-layout__image"><img src={data.member_card_logo_url} alt={`${config.project_name ?? "店舗"}の会員証ヘッダー`} /></header> : null}
        {memberScreen?.style === "minimal" ? <>
          <div className="member-layout__identity"><small>{config.project_name}</small><h1>会員証</h1><p>{name}</p></div>
          <button className="member-layout__code member-layout__code--large" onClick={() => setEnlarged(true)} aria-label="会員コードを拡大">{code}<small>レジで提示してください · タップして拡大</small></button>
          <div className="member-layout__stats"><div><small>ポイント</small><strong>{points.toLocaleString()} <small>pt</small></strong></div><div><small>会員ランク</small><strong>{rank || "会員"}</strong></div></div>
        </> : memberScreen?.style === "ticket" ? <>
          <div className="member-layout__ticket"><div className="member-layout__ticket-label"><small>MEMBER PASS</small><h1>{config.project_name}</h1><p>{rank || "会員"}</p></div><div className="member-layout__ticket-info"><h2>{name}</h2><strong>{points.toLocaleString()} <small>pt</small></strong><small>{data.points_expiry?.expires_at_label ? `期限 ${data.points_expiry.expires_at_label}` : "会員特典をご利用いただけます"}</small></div></div>
          <button className="member-layout__code" onClick={() => setEnlarged(true)} aria-label="会員コードを拡大">{code}<small>タップして拡大</small></button>
        </> : <>
          <div className="member-layout__dashboard">
            {data.member_card_logo_url ? <header className="member-layout__image"><img src={data.member_card_logo_url} alt="会員証ヘッダー" /></header> : null}
            <div className="member-layout__identity"><div><small>{config.project_name}</small><h1>{name}</h1></div><span className="member-layout__rank">{rank || "会員"}</span></div>
            <div className="member-layout__balance"><small>保有ポイント</small><strong>{points.toLocaleString()}<span>pt</span></strong>{data.points_expiry?.expires_at_label ? <small>ポイント期限 {data.points_expiry.expires_at_label}</small> : null}</div>
          </div>
          <button className="member-layout__code" onClick={() => setEnlarged(true)} aria-label="会員コードを拡大"><strong>会員コード</strong>{code}<small>レジで提示してください · タップして拡大</small></button>
        </>}
      </article>
      </>}
      <Link className="native-profile-link" to="/profile"><i className="fa-solid fa-user" /><div><strong>プロフィール編集</strong><small>登録情報を確認・編集</small></div><i className="fa-solid fa-chevron-right" /></Link>
      {(shouldShowAppleWallet() || shouldShowGoogleWallet()) && !isDemoDataMode() ? <div className="native-wallet">{shouldShowAppleWallet() ? <a className="native-button native-button--ink" href={walletPassUrl()}>Apple Walletに追加</a> : <button className="native-button native-button--ink" disabled={walletBusy} onClick={() => void openGoogleWallet()}>Google Walletに追加</button>}{walletError ? <p role="alert">{walletError}</p> : null}<p>会員証をWalletに保存して、すぐに提示。</p></div> : null}
      {memberScreen?.show_rank_progress !== false && data.show_rank_progress !== false && data.points_to_next_rank !== undefined && data.next_rank_label ? <section className="native-rank-progress"><small>次のランクまで</small><strong>あと {data.points_to_next_rank.toLocaleString()}ポイントで{data.next_rank_label}</strong><progress max={points + data.points_to_next_rank} value={points} /></section> : null}
      {memberScreen?.show_point_history !== false ? <section><h2>ポイント履歴</h2>{history.data.transactions?.length ? history.data.transactions.slice(0, 10).map((item, index) => <div className="native-history-row" key={index}><div><strong>{item.description}</strong><small>{nativeDate(item.created_at)}</small></div><b>{Number(item.points) >= 0 ? "+" : ""}{item.points} pt</b></div>) : <p className="native-secondary">ポイントの付与・利用履歴はまだありません</p>}</section> : null}
      {memberScreen?.show_coupon_activity !== false ? <section><h2>最近獲得したクーポン</h2>{runtime.coupons.length ? runtime.coupons.slice(0, 3).map((coupon) => <Link className="native-history-row" to="/coupon" key={coupon.id}><strong>{coupon.title}</strong><i className="fa-solid fa-chevron-right" /></Link>) : <p className="native-secondary">獲得したクーポンはまだありません</p>}</section> : null}
    </> : window.__LINE_USER_ID__ || isDemoDataMode() ? <MemberRegisterHost mode="register" /> : <div className="native-state"><h2>会員登録</h2><p>LINEでログインすると、会員証をご利用いただけます。</p><button className="native-button" onClick={startLineLogin}>LINEでログイン</button></div>}
    </NativeState>{profileSuccess ? <div className="native-toast" role="status">{profileSuccess}</div> : null}<BottomSheet open={enlarged} onClose={() => setEnlarged(false)} title="会員コード"><div className="native-member-code-enlarged">{code}</div></BottomSheet>
  </div>;
}

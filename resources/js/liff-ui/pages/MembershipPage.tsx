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
  const resource = useNativeResource("/api/member-profile", sample);
  const history = useNativeResource("/api/members/points/history", { transactions: [] as PointHistory[] });
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
  const code = qr ? <div className="native-code-fallback"><strong>{number}</strong><MembershipBarcode value={number} height={45} /></div> : <MembershipBarcode value={number} />;
  return <div id="memberSection" data-tab-mount="member" data-react-owned="1" className="native-screen native-membership"><NativeState loading={resource.loading} error={resource.error} reload={() => void resource.reload()}>
    {isMember ? <>
      <article className="native-member-pass" style={{ "--member-color": color, "--member-foreground": foreground } as CSSProperties}>
        <div className="native-member-pass__body"><div className="native-member-pass__heading"><div><small>{config.project_name ?? "Reggit"}</small><h1>会員証</h1><h2>{name}</h2></div>{rank ? <span>{rank}</span> : null}</div><div className="native-member-pass__points"><strong>{points}</strong><span>pt</span>{data.points_expiry?.expires_at_label ? <div><small>ポイント期限</small><b>{data.points_expiry.expires_at_label}</b></div> : null}{data.member_card_logo_url ? <img className="native-member-logo" src={data.member_card_logo_url} alt="" /> : null}</div></div>
        <button className="native-member-pass__code" type="button" onClick={() => setEnlarged(true)} aria-label="会員コードを拡大">{code}<small>タップして拡大</small></button>
      </article>
      <Link className="native-profile-link" to="/profile"><i className="fa-solid fa-user" /><div><strong>プロフィール編集</strong><small>登録情報を確認・編集</small></div><i className="fa-solid fa-chevron-right" /></Link>
      {(shouldShowAppleWallet() || shouldShowGoogleWallet()) && !isDemoDataMode() ? <div className="native-wallet">{shouldShowAppleWallet() ? <a className="native-button native-button--ink" href={walletPassUrl()}>Apple Walletに追加</a> : <button className="native-button native-button--ink" disabled={walletBusy} onClick={() => void openGoogleWallet()}>Google Walletに追加</button>}{walletError ? <p role="alert">{walletError}</p> : null}<p>会員証をWalletに保存して、すぐに提示。</p></div> : null}
      {memberScreen?.show_rank_progress !== false && data.show_rank_progress !== false && data.points_to_next_rank !== undefined && data.next_rank_label ? <section className="native-rank-progress"><small>次のランクまで</small><strong>あと {data.points_to_next_rank.toLocaleString()}ポイントで{data.next_rank_label}</strong><progress max={points + data.points_to_next_rank} value={points} /></section> : null}
      {memberScreen?.show_point_history !== false ? <section><h2>ポイント履歴</h2>{history.data.transactions?.length ? history.data.transactions.slice(0, 10).map((item, index) => <div className="native-history-row" key={index}><div><strong>{item.description}</strong><small>{nativeDate(item.created_at)}</small></div><b>{Number(item.points) >= 0 ? "+" : ""}{item.points} pt</b></div>) : <p className="native-secondary">ポイントの付与・利用履歴はまだありません</p>}</section> : null}
      {memberScreen?.show_coupon_activity !== false ? <section><h2>最近獲得したクーポン</h2>{runtime.coupons.length ? runtime.coupons.slice(0, 3).map((coupon) => <Link className="native-history-row" to="/coupon" key={coupon.id}><strong>{coupon.title}</strong><i className="fa-solid fa-chevron-right" /></Link>) : <p className="native-secondary">獲得したクーポンはまだありません</p>}</section> : null}
    </> : window.__LINE_USER_ID__ || isDemoDataMode() ? <MemberRegisterHost mode="register" /> : <div className="native-state"><h2>会員登録</h2><p>LINEでログインすると、会員証をご利用いただけます。</p><button className="native-button" onClick={startLineLogin}>LINEでログイン</button></div>}
    </NativeState>{profileSuccess ? <div className="native-toast" role="status">{profileSuccess}</div> : null}<BottomSheet open={enlarged} onClose={() => setEnlarged(false)} title="会員コード"><div className="native-member-code-enlarged">{code}</div></BottomSheet>
  </div>;
}

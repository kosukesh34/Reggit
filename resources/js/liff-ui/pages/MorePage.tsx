import { ChevronRight, CircleHelp, FileText, Heart, History, LogOut, MapPin, MessageCircle, Settings, ShoppingBag, Sparkles, UserRound } from "lucide-react";
import { Link } from "react-router-dom";
import { useState } from "react";
import { LandingShowcaseRegister } from "./LandingShowcasePage";
import { useAppConfig } from "../context/AppConfigContext";
import { Card } from "../components/ui/Card";
import { Button } from "../components/ui/Button";
import { featureDefinitions } from "../features/demo/featureCatalog";
import { useRuntimeData } from "../context/RuntimeDataContext";
import { reggitApi } from "../services/reggitApi";
import { openProfileEdit, openRegister } from "../../frontend/features/member/api";
import { shouldShowLotteryEntry } from "../../frontend/features/coupons/couponHelpers";

const accountItems = [
  { to: "/orderHistory", label: "ご利用履歴", icon: History },
  { to: "/favorite", label: "お気に入り", icon: Heart },
  { to: "/storeSearch", label: "店舗を探す", icon: MapPin },
  { to: "/mobileOrder", label: "注文状況", icon: ShoppingBag },
];

const supportItems = [
  { to: "/support", label: "よくあるご質問", icon: CircleHelp },
  { to: "/contact", label: "お問い合わせ", icon: MessageCircle },
  { to: "/terms", label: "利用規約・プライバシー", icon: FileText },
  { to: "/settings", label: "アプリ設定", icon: Settings },
];

export function MorePage() {
  const { activeStore, experienceId } = useAppConfig();
  const { member, mode, refresh } = useRuntimeData();
  const isMember = member.isMember;

  if (experienceId === "landing-showcase") {
    return <LandingShowcaseRegister />;
  }
  const availableFeatures = featureDefinitions.filter((feature) => activeStore.features[feature.type]);
  const lotteryServices = shouldShowLotteryEntry()
    ? [
      { to: "/designlab", label: "ガチャ", icon: Sparkles, focus: "gacha" },
      { to: "/designlab", label: "スクラッチ", icon: Sparkles, focus: "scratch" },
      { to: "/designlab", label: "ルーレット", icon: Sparkles, focus: "roulette" },
    ]
    : [];
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string>();

  const runLogout = async () => {
    if (!window.confirm("ログアウトしますか？\n（会員データは残ります。再度開くと同じアカウントに戻れます）")) {
      return;
    }
    setBusy(true);
    setError(undefined);
    try {
      await reggitApi.logout();
      await refresh();
      window.location.reload();
    } catch (caught) {
      setError(caught instanceof Error ? caught.message : "ログアウトに失敗しました");
      setBusy(false);
    }
  };

  const runWithdraw = async () => {
    if (!window.confirm("退会すると会員情報・ポイント・スタンプ等は復元できません。本当に退会しますか？")) {
      return;
    }
    setBusy(true);
    setError(undefined);
    try {
      await reggitApi.withdraw();
      window.location.reload();
    } catch (caught) {
      setError(caught instanceof Error ? caught.message : "退会に失敗しました");
      setBusy(false);
    }
  };

  return (
    <div className="page stack stack--lg">
      <Link className="card card--lg account-card" to="/membership" aria-label="会員情報を確認・変更">
        <div className="account-card__monogram">{activeStore.logoText}</div>
        <div>
          <div className="account-card__label">{isMember ? "会員情報" : "ゲスト"}</div>
          <h2 className="account-card__title">{isMember ? `${member.name.trim() !== "" ? member.name : "会員"} 様` : "ゲストで閲覧中"}</h2>
          <div className="account-card__meta">{isMember ? "会員証・プロフィールを確認" : "会員パスを発行すると、ポイントやクーポンを店舗で使えます"}</div>
        </div>
        <ChevronRight size={18} />
      </Link>

      {mode !== "preview" ? (
        <Card className="menu-list" padding="sm">
          {isMember ? (
            <button type="button" className="menu-list__item" onClick={() => openProfileEdit()}>
              <span className="menu-list__icon"><UserRound size={19} /></span>
              <span>プロフィール編集</span>
              <ChevronRight size={16} />
            </button>
          ) : (
            <button type="button" className="menu-list__item" onClick={() => openRegister(false)}>
              <span className="menu-list__icon"><UserRound size={19} /></span>
              <span>会員パスを発行</span>
              <ChevronRight size={16} />
            </button>
          )}
        </Card>
      ) : null}

      <section>
        <div className="section-head">
          <h2 className="section-head__title">マイメニュー</h2>
        </div>
        <Card className="menu-list" padding="sm">
          {accountItems.filter((item) => activeStore.features[item.to.slice(1) as keyof typeof activeStore.features] !== false).map((item) => (
            <Link className="menu-list__item" to={item.to} key={item.to}>
              <span className="menu-list__icon"><item.icon size={19} /></span>
              <span>{item.label}</span>
              <ChevronRight size={16} />
            </Link>
          ))}
        </Card>
      </section>

      <section>
        <div className="section-head">
          <h2 className="section-head__title">すべてのサービス</h2>
          <span className="section-head__meta">利用可能な機能</span>
        </div>
        <div className="all-feature-grid">
          {availableFeatures.map((feature) => (
            <Link className="all-feature-grid__item" to={`/${feature.type}`} key={feature.type}>
              <span><feature.icon size={19} /></span>
              <strong>{feature.shortLabel}</strong>
            </Link>
          ))}
          {lotteryServices.map((item) => (
            <Link
              className="all-feature-grid__item"
              to={item.to}
              key={item.focus}
              onClick={() => {
                try {
                  window.sessionStorage.setItem(
                    mode === "preview" || document.body.classList.contains("liff-demo-data")
                      ? "liff_demo_play_focus"
                      : "liff_lottery_play_focus",
                    item.focus,
                  );
                } catch {
                  // ignore
                }
              }}
            >
              <span><item.icon size={19} /></span>
              <strong>{item.label}</strong>
            </Link>
          ))}
        </div>
      </section>

      <section>
        <div className="section-head">
          <h2 className="section-head__title">サポート・設定</h2>
        </div>
        <Card className="menu-list" padding="sm">
          {supportItems.map((item) => (
            <Link className="menu-list__item" to={item.to} key={item.to}>
              <span className="menu-list__icon"><item.icon size={19} /></span>
              <span>{item.label}</span>
              <ChevronRight size={16} />
            </Link>
          ))}
        </Card>
      </section>

      {mode !== "preview" ? (
        <section className="stack stack--md">
          {error ? <div className="form-error" role="alert">{error}</div> : null}
          <Button variant="outline" disabled={busy} onClick={() => void runLogout()}>
            <LogOut size={17} /> {busy ? "処理中…" : "ログアウト"}
          </Button>
          {isMember ? (
            <Button variant="soft" disabled={busy} onClick={() => void runWithdraw()}>
              退会する
            </Button>
          ) : null}
        </section>
      ) : null}

      <div className="app-version">Reggit LIFF · {mode === "live" ? "店舗データ接続中" : mode === "offline" ? "オフライン" : "プレビュー"}</div>
    </div>
  );
}

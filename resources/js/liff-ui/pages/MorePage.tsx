import { ChevronRight, CircleHelp, FileText, Heart, History, MapPin, MessageCircle, Settings, ShoppingBag } from "lucide-react";
import { Link } from "react-router-dom";
import { useAppConfig } from "../context/AppConfigContext";
import { Card } from "../components/ui/Card";
import { featureDefinitions } from "../features/demo/featureCatalog";
import { useRuntimeData } from "../context/RuntimeDataContext";

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
  const { activeStore } = useAppConfig();
  const { member, mode } = useRuntimeData();
  const availableFeatures = featureDefinitions.filter((feature) => activeStore.features[feature.type]);

  return (
    <div className="page stack stack--lg">
      <Link className="card card--lg account-card" to="/settings" aria-label="会員情報を確認・変更">
        <div className="account-card__monogram">{activeStore.logoText}</div>
        <div>
          <div className="account-card__label">会員情報</div>
          <h2 className="account-card__title">{member.name} 様</h2>
          <div className="account-card__meta">登録情報を確認・変更</div>
        </div>
        <ChevronRight size={18} />
      </Link>

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
      <div className="app-version">Reggit LIFF · {mode === "live" ? "店舗データ接続中" : mode === "offline" ? "オフライン" : "プレビュー"}</div>
    </div>
  );
}

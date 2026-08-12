import { Bell, CalendarDays, Check, ChevronDown, CloudOff, CreditCard, Menu, QrCode, ShoppingBag, Stamp, Ticket, UserRound, X, type LucideIcon } from "lucide-react";
import { useMemo, useState } from "react";
import { useLocation, useNavigate } from "react-router-dom";
import { designPresetMap } from "../../config/designPresets";
import { useAppConfig } from "../../context/AppConfigContext";
import { useRuntimeData } from "../../context/RuntimeDataContext";
import { featureDefinitionMap, isFeatureType } from "../../features/demo/featureCatalog";
import { isUtilityType, utilityLabels } from "../../features/demo/UtilityDemo";
import { themeMap } from "../../themes/themes";
import { BottomSheet } from "../ui/BottomSheet";
import { Button } from "../ui/Button";
import { Card } from "../ui/Card";
import { LANDING_SHOWCASE_STORE_ID } from "../../pages/LandingShowcasePage";
import { SIMPLE_REWARDS_STORE_ID } from "../../pages/SimpleRewardsPage";
import type { FeatureType } from "../../types/app";
import type { HeaderShortcut } from "../../types/customization";

const headerShortcutDefinitions: Record<Exclude<HeaderShortcut, "none">, {
  feature: FeatureType;
  label: string;
  path: string;
  icon: LucideIcon;
}> = {
  news: { feature: "news", label: "お知らせ", path: "/news", icon: Bell },
  membership: { feature: "membership", label: "会員証", path: "/membership", icon: CreditCard },
  coupon: { feature: "coupon", label: "クーポン", path: "/coupon", icon: Ticket },
  stamp: { feature: "stamp", label: "スタンプ", path: "/stamp", icon: Stamp },
  reservation: { feature: "reservation", label: "予約", path: "/reservation", icon: CalendarDays },
  queue: { feature: "queue", label: "順番待ち", path: "/queue", icon: QrCode },
  mobileOrder: { feature: "mobileOrder", label: "モバイルオーダー", path: "/mobileOrder", icon: ShoppingBag },
};

export function Header() {
  const { activeStore, stores, setActiveStoreId, customization, getStoreCustomization } = useAppConfig();
  const { mode, notificationCount } = useRuntimeData();
  const [open, setOpen] = useState(false);
  const location = useLocation();
  const navigate = useNavigate();
  const canSwitchStore = stores.length > 1;
  const isLandingShowcase = activeStore.storeId === LANDING_SHOWCASE_STORE_ID;
  const isSimpleRewards = activeStore.storeId === SIMPLE_REWARDS_STORE_ID;
  const shortcutKey = customization.header.showNotifications ? customization.header.shortcut : "none";
  const shortcut = shortcutKey === "none" ? null : headerShortcutDefinitions[shortcutKey];
  const visibleShortcut = shortcut !== null && activeStore.features[shortcut.feature] ? shortcut : null;
  const ShortcutIcon = visibleShortcut?.icon;

  const title = useMemo(() => {
    if (location.pathname === "/membership") {
      return "会員証";
    }
    if (location.pathname === "/more") {
      return "その他";
    }
    if (location.pathname === "/dev/themes") {
      return "Theme Preview";
    }
    const feature = location.pathname.slice(1);
    if (isFeatureType(feature)) {
      return featureDefinitionMap[feature].label;
    }
    if (isUtilityType(feature)) {
      return utilityLabels[feature];
    }
    return activeStore.storeName;
  }, [activeStore.storeName, location.pathname]);

  const logoUrl = typeof activeStore.logoUrl === 'string' && activeStore.logoUrl.trim() !== ''
    ? activeStore.logoUrl.trim()
    : null;

  const brandContent = (
    <>
      {customization.header.showLogo ? (
        <span className={`header__logo${logoUrl !== null ? ' header__logo--image' : ''}`} aria-hidden="true">
          {logoUrl !== null ? <img src={logoUrl} alt="" /> : <><i />{activeStore.logoText}</>}
        </span>
      ) : null}
      <span className="header__text">
        {customization.header.showSubtitle ? <span className="header__subtitle">REGGIT MEMBER</span> : null}
        <span className="header__title">{title}</span>
      </span>
      {canSwitchStore ? <ChevronDown className="header__chevron" size={15} aria-hidden="true" /> : null}
    </>
  );

  const showcaseTitle = location.pathname === "/mobileOrder"
    ? "メニュー"
    : location.pathname === "/membership"
      ? "会員証"
      : location.pathname === "/stamp"
        ? "スタンプカード"
        : location.pathname === "/queue"
          ? "LAUREL & IVY"
          : "Reggit";
  const ShowcaseActionIcon = location.pathname === "/mobileOrder"
    ? ShoppingBag
    : location.pathname === "/home"
      ? X
      : UserRound;

  return (
    <>
      <header className={`header${isLandingShowcase ? " header--landing-showcase" : isSimpleRewards ? " header--simple-rewards" : ""}`}>
        {isLandingShowcase ? (
          <>
            <button className="header__showcase-menu" onClick={() => setOpen(true)} type="button" aria-label="店舗を切り替える">
              <Menu size={27} />
            </button>
            <div className="header__showcase-title">
              <strong>{showcaseTitle}</strong>
              {location.pathname === "/home" ? <span>LINE ミニアプリ</span> : null}
              {location.pathname === "/queue" ? <span>モダンキッチン</span> : null}
            </div>
            <button
              className="header__showcase-action"
              type="button"
              aria-label={location.pathname === "/home" ? "LPデモを終了" : location.pathname === "/mobileOrder" ? "注文履歴を見る" : "登録情報を見る"}
              onClick={() => {
                if (location.pathname === "/home") {
                  setActiveStoreId("store_cafe");
                  navigate("/home");
                  return;
                }
                navigate(location.pathname === "/mobileOrder" ? "/orderHistory" : "/more");
              }}
            >
              <ShowcaseActionIcon size={location.pathname === "/home" ? 29 : 26} />
            </button>
          </>
        ) : isSimpleRewards ? (
          <button className="header__simple-brand" type="button" onClick={() => setOpen(true)} aria-label="店舗を切り替える">
            <span>SIMPLE PASS</span><strong>{location.pathname === "/home" ? "ホーム" : title}</strong><ChevronDown size={15} />
          </button>
        ) : (
          <>
            {canSwitchStore ? (
              <button className="header__brand" onClick={() => setOpen(true)} type="button" aria-label="店舗を切り替える">
                {brandContent}
              </button>
            ) : (
              <div className="header__brand">{brandContent}</div>
            )}
            <div className="header__actions">
              {mode === "offline" ? <span className="header__connection" aria-label="オフライン"><CloudOff size={14} /></span> : null}
              {visibleShortcut !== null && ShortcutIcon !== undefined ? (
                <Button className="header__notification header__shortcut" variant="ghost" size="icon" ariaLabel={`${visibleShortcut.label}を開く`} onClick={() => navigate(visibleShortcut.path)}>
                  <ShortcutIcon size={18} />
                  {shortcutKey === "news" && notificationCount > 0 ? <span className="header__notification-dot">{notificationCount}</span> : null}
                </Button>
              ) : null}
            </div>
          </>
        )}
      </header>

      {canSwitchStore ? (
        <BottomSheet open={open} onClose={() => setOpen(false)} title="店舗を切り替える">
          <div className="stack stack--sm">
            {stores.map((store) => {
              const storeCustomization = getStoreCustomization(store.storeId);
              const storeTheme = themeMap[store.themeId];
              const primary = storeCustomization.colors.primary ?? storeTheme?.colors.primary ?? "#17130f";
              const accent = storeCustomization.colors.accent ?? storeTheme?.colors.accent ?? "#777777";
              const active = store.storeId === activeStore.storeId;
              const preset = designPresetMap[storeCustomization.designPreset];
              return (
                <Card key={store.storeId} className={`store-switcher${active ? " is-active" : ""}`} padding="md">
                  {typeof store.logoUrl === 'string' && store.logoUrl.trim() !== '' ? (
                    <span className="store-switcher__mark store-switcher__mark--image">
                      <img src={store.logoUrl.trim()} alt="" />
                    </span>
                  ) : (
                    <span className="store-switcher__mark" style={{ background: `linear-gradient(135deg, ${primary} 0 58%, ${accent} 58%)` }}>{store.logoText}</span>
                  )}
                  <div className="store-switcher__body">
                    <div className="store-switcher__name">{store.storeName}</div>
                    <div className="store-switcher__meta">{store.brandName}</div>
                    {customization.header.showStoreTheme && preset !== undefined ? (
                      <div className="store-switcher__theme">{
                        store.themeId === "landing-showcase" ? "LPスマホUI"
                          : store.themeId === "simple-rewards" ? "シンプルリワード"
                            : store.themeId === "operations-counter" ? "店舗オペレーション・注文型"
                              : store.themeId === "operations-booking" ? "店舗オペレーション・予約型"
                                : preset.name
                      }</div>
                    ) : null}
                  </div>
                  <Button
                    variant={active ? "solid" : "soft"}
                    size="sm"
                    onClick={() => {
                      setActiveStoreId(store.storeId);
                      setOpen(false);
                      navigate("/home");
                    }}
                  >
                    {active ? <><Check size={14} />選択中</> : "切り替え"}
                  </Button>
                </Card>
              );
            })}
          </div>
        </BottomSheet>
      ) : null}
    </>
  );
}

import { Bell, CalendarDays, ChevronDown, CloudOff, CreditCard, Home, Menu, MoreHorizontal, QrCode, ShoppingBag, Stamp, Ticket, UserRound, X, type LucideIcon } from "lucide-react";
import { useEffect, useMemo, useState } from "react";
import { useLocation, useNavigate } from "react-router-dom";
import { resolveAppBrand } from "../../config/customization";
import { useAppConfig } from "../../context/AppConfigContext";
import { useRuntimeData } from "../../context/RuntimeDataContext";
import { featureDefinitionMap, isFeatureType } from "../../features/demo/featureCatalog";
import { isUtilityType, utilityLabels } from "../../features/demo/UtilityDemo";
import type { FeatureType } from "../../types/app";
import type { HeaderButtonIcon, HeaderShortcut } from "../../types/customization";
import { DesignPicker, OPEN_DESIGN_PICKER_EVENT } from "./DesignPicker";
import { Button } from "../ui/Button";

const headerShortcutDefinitions: Record<Exclude<HeaderShortcut, "none">, {
  feature: FeatureType;
  label: string;
  path: string;
  icon: LucideIcon;
}> = {
  news: { feature: "news", label: "お知らせ", path: "/news", icon: Bell },
  membership: { feature: "membership", label: "会員証", path: "/membership", icon: CreditCard },
  profile: { feature: "membership", label: "プロフィール", path: "/profile", icon: UserRound },
  coupon: { feature: "coupon", label: "クーポン", path: "/coupon", icon: Ticket },
  stamp: { feature: "stamp", label: "スタンプ", path: "/stamp", icon: Stamp },
  reservation: { feature: "reservation", label: "予約", path: "/reservation", icon: CalendarDays },
  queue: { feature: "queue", label: "順番待ち", path: "/queue", icon: QrCode },
  mobileOrder: { feature: "mobileOrder", label: "モバイルオーダー", path: "/mobileOrder", icon: ShoppingBag },
};

const headerIconDefinitions: Record<HeaderButtonIcon, LucideIcon> = {
  bell: Bell,
  creditCard: CreditCard,
  userRound: UserRound,
  ticket: Ticket,
  stamp: Stamp,
  calendarDays: CalendarDays,
  qrCode: QrCode,
  shoppingBag: ShoppingBag,
  menu: Menu,
  home: Home,
  moreHorizontal: MoreHorizontal,
};

export function Header() {
  const { activeStore, stores, setActiveStoreId, customization, experienceId } = useAppConfig();
  const { mode, notificationCount } = useRuntimeData();
  const [open, setOpen] = useState(false);
  const location = useLocation();
  const navigate = useNavigate();
  const canSwitchStore = stores.length > 1;
  const isLandingShowcase = canSwitchStore && experienceId === "landing-showcase";
  const isSimpleRewards = experienceId === "simple-rewards";
  const headerButton = customization.header.rightButton;
  const effectiveMode = headerButton.mode ?? (customization.header.showNotifications ? "shortcut" : "none");
  const shortcutKey = effectiveMode === "shortcut" ? (headerButton.shortcut || customization.header.shortcut) : "none";
  const shortcut = shortcutKey === "none" ? null : headerShortcutDefinitions[shortcutKey];
  const visibleShortcut = shortcut !== null && activeStore.features[shortcut.feature] ? shortcut : null;
  const visibleCustomButton = effectiveMode === "custom"
    ? {
        label: headerButton.label.trim() || "ボタン",
        path: headerButton.path.trim(),
        icon: headerIconDefinitions[headerButton.icon],
      }
    : null;
  const visibleButton = visibleShortcut !== null
    ? { label: visibleShortcut.label, path: visibleShortcut.path, icon: visibleShortcut.icon, isNotification: shortcutKey === "news" }
    : (visibleCustomButton !== null && visibleCustomButton.path !== ""
        ? { ...visibleCustomButton, isNotification: false }
        : null);

  useEffect(() => {
    const openPicker = () => setOpen(true);
    window.addEventListener(OPEN_DESIGN_PICKER_EVENT, openPicker);
    return () => window.removeEventListener(OPEN_DESIGN_PICKER_EVENT, openPicker);
  }, []);

  const openHeaderTarget = (path: string): void => {
    if (path === "") {
      return;
    }
    if (/^https?:\/\//i.test(path)) {
      window.location.assign(path);
      return;
    }
    const normalized = path.startsWith("/") ? path : `/${path.replace(/^\/+/, "")}`;
    navigate(normalized);
  };

  const title = useMemo(() => {
    if (location.pathname === "/membership") {
      return "会員証";
    }
    if (location.pathname === "/profile") {
      return "プロフィール";
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
    return resolveAppBrand(activeStore, customization).appName;
  }, [activeStore, customization, location.pathname]);

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
        {customization.header.showSubtitle ? <span className="header__subtitle">{canSwitchStore ? "デザインを選ぶ" : "REGGIT MEMBER"}</span> : null}
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
          : location.pathname === "/more"
          ? "マイページ"
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
            <button className="header__showcase-menu" onClick={() => setOpen(true)} type="button" aria-label="デザインを選ぶ">
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
              aria-label={location.pathname === "/home" ? "LPデモを終了" : location.pathname === "/mobileOrder" ? "注文履歴を見る" : "マイページを見る"}
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
          canSwitchStore ? (
            <button className="header__simple-brand" type="button" onClick={() => setOpen(true)} aria-label="デザインを選ぶ">
              <span>SIMPLE PASS</span><strong>{location.pathname === "/home" ? "ホーム" : title}</strong><ChevronDown size={15} />
            </button>
          ) : (
            <div className="header__simple-brand">
              <span>SIMPLE PASS</span><strong>{location.pathname === "/home" ? "ホーム" : title}</strong>
            </div>
          )
        ) : (
          <>
            {canSwitchStore ? (
              <button className="header__brand" onClick={() => setOpen(true)} type="button" aria-label="デザインを選ぶ">
                {brandContent}
              </button>
            ) : (
              <div className="header__brand">{brandContent}</div>
            )}
            <div className="header__actions">
              {mode === "offline" ? <span className="header__connection" aria-label="オフライン"><CloudOff size={14} /></span> : null}
              {visibleButton !== null ? (
                <Button className="header__notification header__shortcut" variant="ghost" size="icon" ariaLabel={`${visibleButton.label}を開く`} onClick={() => openHeaderTarget(visibleButton.path)}>
                  <visibleButton.icon size={18} />
                  {visibleButton.isNotification && notificationCount > 0 ? <span className="header__notification-dot">{notificationCount}</span> : null}
                </Button>
              ) : null}
            </div>
          </>
        )}
      </header>

      {canSwitchStore ? <DesignPicker open={open} onClose={() => setOpen(false)} /> : null}
    </>
  );
}

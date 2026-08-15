import { Home, CreditCard, CircleEllipsis, Ticket, Sparkles, CalendarDays, ShoppingBag, MapPinned, Stamp, ScanSearch, ExternalLink, UserRound } from "lucide-react";
import type { CSSProperties } from "react";
import { Link, useLocation } from "react-router-dom";
import { tabLabelMap } from "../../config/navigation";
import { useAppConfig } from "../../context/AppConfigContext";
import type { TabConfig, TabType } from "../../types/app";

const iconMap: Record<TabType, typeof Home> = {
  home: Home,
  membership: CreditCard,
  profile: UserRound,
  mobileOrder: ShoppingBag,
  dineInOrder: ShoppingBag,
  reservation: CalendarDays,
  queue: ScanSearch,
  coupon: Ticket,
  stamp: Stamp,
  point: Sparkles,
  menu: Sparkles,
  store: MapPinned,
  news: Sparkles,
  more: CircleEllipsis,
  link_1: ExternalLink,
  link_2: ExternalLink,
  link_3: ExternalLink,
};

const pathByType: Record<TabType, string> = {
  home: "/home",
  membership: "/membership",
  profile: "/profile",
  mobileOrder: "/mobileOrder",
  dineInOrder: "/dineInOrder",
  reservation: "/reservation",
  queue: "/queue",
  coupon: "/coupon",
  stamp: "/stamp",
  point: "/point",
  menu: "/menu",
  store: "/storeSearch",
  news: "/news",
  more: "/more",
  link_1: "/link_1",
  link_2: "/link_2",
  link_3: "/link_3",
};

function resolveActive(pathname: string, tab: TabConfig) {
  if (tab.type === "home") {
    return pathname === "/home";
  }
  return pathname.startsWith(pathByType[tab.type]);
}

export function BottomNavigation() {
  const { activeStore, customization } = useAppConfig();
  const location = useLocation();
  const featureByTab: Partial<Record<TabType, keyof typeof activeStore.features>> = {
    membership: "membership",
    profile: "membership",
    mobileOrder: "mobileOrder",
    dineInOrder: "dineInOrder",
    reservation: "reservation",
    queue: "queue",
    coupon: "coupon",
    stamp: "stamp",
    point: "point",
    menu: "menu",
    store: "storeSearch",
    news: "news",
  };
  const visibleTypes = customization.navigation.tabs.filter((type) => {
    if (type === "home" && window.__FEATURE_FLAGS__?.feature_home_enabled === false) {
      return false;
    }
    const feature = featureByTab[type];
    return feature === undefined || activeStore.features[feature];
  });
  const safeTypes: TabType[] = visibleTypes.length > 0 ? visibleTypes : ["home", "more"];
  const tabs: TabConfig[] = safeTypes.slice(0, 6).map((type, index) => {
    const storeTab = activeStore.tabs.find((tab) => tab.type === type);
    return {
      id: `${type}-${index}`,
      type,
      label: (storeTab?.label?.trim() || tabLabelMap[type]),
      url: storeTab?.url,
      enabled: true,
      order: index,
    };
  });

  return (
    <nav
      className={`bottom-nav bottom-nav--${customization.navigation.position}${customization.navigation.showLabels ? "" : " bottom-nav--icons-only"}${customization.navigation.emphasizeCenterTab ? " bottom-nav--emphasize-center" : ""}`}
      style={{ "--tab-count": Math.max(1, tabs.length) } as CSSProperties}
      aria-label="主要ナビゲーション"
    >
      {tabs.map((tab) => {
        const Icon = iconMap[tab.type];
        const active = resolveActive(location.pathname, tab);
        return (
          <Link key={tab.id} to={pathByType[tab.type]} className={`bottom-nav__item${active ? " is-active" : ""}`}>
            <span className="bottom-nav__icon"><Icon size={20} /></span>
            {customization.navigation.showLabels ? <span>{tab.label}</span> : null}
          </Link>
        );
      })}
    </nav>
  );
}

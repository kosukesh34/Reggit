import { Home, CreditCard, CircleEllipsis, Ticket, Sparkles, CalendarDays, ShoppingBag, MapPinned, Stamp, ScanSearch } from "lucide-react";
import type { CSSProperties } from "react";
import { Link, useLocation } from "react-router-dom";
import { tabLabelMap } from "../../config/navigation";
import { useAppConfig } from "../../context/AppConfigContext";
import type { TabConfig, TabType } from "../../types/app";

const iconMap: Record<TabType, typeof Home> = {
  home: Home,
  membership: CreditCard,
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
};

const pathByType: Record<TabType, string> = {
  home: "/home",
  membership: "/membership",
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
};

function resolveActive(pathname: string, tab: TabConfig) {
  if (tab.type === "home") {
    return pathname === "/home";
  }
  return pathname.startsWith(pathByType[tab.type]);
}

export function BottomNavigation() {
  const { customization } = useAppConfig();
  const location = useLocation();
  const tabs: TabConfig[] = customization.navigation.tabs.slice(0, 6).map((type, index) => ({
    id: `${type}-${index}`,
    type,
    label: tabLabelMap[type],
    enabled: true,
    order: index,
  }));

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

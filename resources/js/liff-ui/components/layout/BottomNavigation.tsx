import { useRuntimeData } from "../../context/RuntimeDataContext";
import type { CSSProperties } from "react";
import { useLocation } from "react-router-dom";
import {
  nativeTabHref,
  nativeTabIconClass,
  nativeTabPath,
  useNativeConfig,
} from "../../native/nativeConfig";

import { useNativeNavigation } from "../../native/NativeNavigation";

export function BottomNavigation() {
  const { notificationCount } = useRuntimeData();
  const { back } = useNativeNavigation();
  const { config } = useNativeConfig();
  const location = useLocation();
  const configured = config.navigation?.footer ?? [];
  const tabs = (configured.length > 0 ? configured : (config.tabs ?? [])).slice(0, 5);

  if (back || tabs.length === 0) return null;

  return (
    <nav
      className="bottom-nav bottom-nav--bottom"
      style={{
        "--tab-count": tabs.length,
        ...Object.fromEntries(Object.entries(config.native_chrome_colors ?? {}).filter(([key, value]) => key.startsWith("footer_") && /^#[0-9a-f]{6}$/i.test(value)).map(([key, value]) => [`--native-${key.replace(/_/g, "-")}`, value])),
      } as CSSProperties}
      aria-label="主要ナビゲーション"
    >
      {tabs.map((tab) => {
        const path = nativeTabPath(tab.key);
        const active = path === "/home"
          ? location.pathname === path
          : location.pathname.startsWith(path);
        return (
          <a
            key={tab.key}
            href={nativeTabHref(tab)}
            className={`bottom-nav__item${active ? " is-active" : ""}`}
            aria-current={active ? "page" : undefined}
          >
            <span className="bottom-nav__icon">
              <i className={nativeTabIconClass(tab)} aria-hidden="true" />
              {["news", "announcements"].includes(tab.key) && notificationCount > 0 ? <small className="native-tab-badge">{notificationCount > 99 ? "99+" : notificationCount}</small> : null}
            </span>
            <span>{tab.label}</span>
          </a>
        );
      })}
    </nav>
  );
}

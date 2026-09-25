import type { CSSProperties } from "react";
import { useLocation } from "react-router-dom";
import {
  nativeTabHref,
  nativeTabIconClass,
  nativeTabPath,
  useNativeConfig,
} from "../../native/nativeConfig";

export function BottomNavigation() {
  const { config } = useNativeConfig();
  const location = useLocation();
  const configured = config.navigation?.footer ?? [];
  const tabs = (configured.length > 0 ? configured : (config.tabs ?? [])).slice(0, 5);

  if (tabs.length === 0) return null;

  return (
    <nav
      className="bottom-nav bottom-nav--bottom"
      style={{ "--tab-count": tabs.length } as CSSProperties}
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
            </span>
            <span>{tab.label}</span>
          </a>
        );
      })}
    </nav>
  );
}

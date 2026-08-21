import { useEffect, useLayoutEffect, useRef, type PropsWithChildren } from "react";
import { useLocation } from "react-router-dom";
import { useAppConfig } from "../../context/AppConfigContext";
import { useRuntimeData } from "../../context/RuntimeDataContext";
import { themeMap } from "../../themes/themes";
import { themeToCssVars } from "../../utils/theme";
import { contrastCssVars, customizationToCssVars } from "../../utils/customization";
import { Header } from "./Header";
import { BottomNavigation } from "./BottomNavigation";

export function AppShell({ children, gateSettled = true }: PropsWithChildren<{ gateSettled?: boolean }>) {
  const { activeStore, activeThemeId, customization, experienceId } = useAppConfig();
  const { loading, mode } = useRuntimeData();
  const location = useLocation();
  const theme = themeMap[customization.themeId ?? ""] ?? themeMap[activeThemeId] ?? themeMap[activeStore.themeId] ?? themeMap["minimal-light"];
  const showNavigationBelowHeader = customization.navigation.position === "top";
  const mainRef = useRef<HTMLElement>(null);
  const dataSettled = mode === "preview" || mode === "live" || (mode === "offline" && !loading);

  useLayoutEffect(() => {
    if (dataSettled && gateSettled) {
      document.body.classList.add("liff-ui-ready");
      return undefined;
    }
    const timer = window.setTimeout(() => {
      document.body.classList.add("liff-ui-ready");
    }, 8000);
    return () => {
      window.clearTimeout(timer);
    };
  }, [dataSettled, gateSettled]);

  useEffect(() => {
    mainRef.current?.scrollTo({ top: 0, left: 0, behavior: "auto" });
    window.scrollTo({ top: 0, left: 0, behavior: "auto" });
  }, [location.pathname]);

  return (
    <div
      className="app-shell"
      data-store-id={activeStore.storeId}
      data-design-preset={customization.designPreset}
      data-app-experience={experienceId}
      data-visual-style={customization.brand?.visualStyle ?? ""}
      data-nav-position={customization.navigation.position}
      data-button-style={customization.appearance.buttonStyle}
      data-density={customization.appearance.density}
      style={{ ...themeToCssVars(theme), ...customizationToCssVars(customization), ...contrastCssVars(customization, theme) }}
    >
      <div className="app-shell__backdrop" />
      <div className="app-shell__frame">
        <Header />
        {showNavigationBelowHeader ? <BottomNavigation /> : null}
        <main className="app-shell__main" ref={mainRef}>{children}</main>
        {!showNavigationBelowHeader ? <BottomNavigation /> : null}
      </div>
    </div>
  );
}

import { useEffect, type PropsWithChildren } from "react";
import { useLocation } from "react-router-dom";
import { useAppConfig } from "../../context/AppConfigContext";
import { themeMap } from "../../themes/themes";
import { themeToCssVars } from "../../utils/theme";
import { customizationToCssVars } from "../../utils/customization";
import { Header } from "./Header";
import { BottomNavigation } from "./BottomNavigation";

export function AppShell({ children }: PropsWithChildren) {
  const { activeStore, activeThemeId, customization } = useAppConfig();
  const location = useLocation();
  const theme = themeMap[activeThemeId] ?? themeMap[activeStore.themeId] ?? themeMap["minimal-light"];
  const showNavigationBelowHeader = customization.navigation.position === "top";

  useEffect(() => {
    window.scrollTo({ top: 0, left: 0, behavior: "auto" });
  }, [location.pathname]);

  return (
    <div
      className="app-shell"
      data-store-id={activeStore.storeId}
      data-design-preset={customization.designPreset}
      data-nav-position={customization.navigation.position}
      data-button-style={customization.appearance.buttonStyle}
      data-density={customization.appearance.density}
      style={{ ...themeToCssVars(theme), ...customizationToCssVars(customization) }}
    >
      <div className="app-shell__backdrop" />
      <div className="app-shell__frame">
        <Header />
        {showNavigationBelowHeader ? <BottomNavigation /> : null}
        <main className="app-shell__main">{children}</main>
      </div>
      {/* fixed タブは overflow:clip の外に置き、スクロール時の位置ずれを防ぐ */}
      {!showNavigationBelowHeader ? <BottomNavigation /> : null}
    </div>
  );
}

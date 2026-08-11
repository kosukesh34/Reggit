import { Bell, CloudOff } from "lucide-react";
import { useMemo } from "react";
import { useLocation, useNavigate } from "react-router-dom";
import { useAppConfig } from "../../context/AppConfigContext";
import { Button } from "../ui/Button";
import { featureDefinitionMap, isFeatureType } from "../../features/demo/featureCatalog";
import { isUtilityType, utilityLabels } from "../../features/demo/UtilityDemo";
import { useRuntimeData } from "../../context/RuntimeDataContext";

export function Header() {
  const { activeStore, customization } = useAppConfig();
  const { mode, notificationCount } = useRuntimeData();
  const location = useLocation();
  const navigate = useNavigate();

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

  return (
    <header className="header">
      <div className="header__brand">
        {customization.header.showLogo ? <span className="header__logo" aria-hidden="true"><i />{activeStore.logoText}</span> : null}
        <span className="header__text">
          {customization.header.showSubtitle ? <span className="header__subtitle">REGGIT MEMBER</span> : null}
          <span className="header__title">{title}</span>
        </span>
      </div>
      <div className="header__actions">
        {mode === "offline" ? <span className="header__connection" aria-label="オフライン"><CloudOff size={14} /></span> : null}
        {customization.header.showNotifications && activeStore.features.news ? <Button className="header__notification" variant="ghost" size="icon" ariaLabel="通知" onClick={() => navigate("/news")}>
          <Bell size={18} />
          {notificationCount > 0 ? <span className="header__notification-dot">{notificationCount}</span> : null}
        </Button> : null}
      </div>
    </header>
  );
}

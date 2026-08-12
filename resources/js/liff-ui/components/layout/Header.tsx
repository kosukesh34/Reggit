import { Bell, Check, ChevronDown, CloudOff } from "lucide-react";
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

export function Header() {
  const { activeStore, stores, setActiveStoreId, customization, getStoreCustomization } = useAppConfig();
  const { mode, notificationCount } = useRuntimeData();
  const [open, setOpen] = useState(false);
  const location = useLocation();
  const navigate = useNavigate();
  const canSwitchStore = stores.length > 1;

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

  const brandContent = (
    <>
      {customization.header.showLogo ? <span className="header__logo" aria-hidden="true"><i />{activeStore.logoText}</span> : null}
      <span className="header__text">
        {customization.header.showSubtitle ? <span className="header__subtitle">REGGIT MEMBER</span> : null}
        <span className="header__title">{title}</span>
      </span>
      {canSwitchStore ? <ChevronDown className="header__chevron" size={15} aria-hidden="true" /> : null}
    </>
  );

  return (
    <>
      <header className="header">
        {canSwitchStore ? (
          <button className="header__brand" onClick={() => setOpen(true)} type="button" aria-label="店舗を切り替える">
            {brandContent}
          </button>
        ) : (
          <div className="header__brand">{brandContent}</div>
        )}
        <div className="header__actions">
          {mode === "offline" ? <span className="header__connection" aria-label="オフライン"><CloudOff size={14} /></span> : null}
          {customization.header.showNotifications && activeStore.features.news ? (
            <Button className="header__notification" variant="ghost" size="icon" ariaLabel="通知" onClick={() => navigate("/news")}>
              <Bell size={18} />
              {notificationCount > 0 ? <span className="header__notification-dot">{notificationCount}</span> : null}
            </Button>
          ) : null}
        </div>
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
                  <span className="store-switcher__mark" style={{ background: `linear-gradient(135deg, ${primary} 0 58%, ${accent} 58%)` }}>{store.logoText}</span>
                  <div className="store-switcher__body">
                    <div className="store-switcher__name">{store.storeName}</div>
                    <div className="store-switcher__meta">{store.brandName}</div>
                    {customization.header.showStoreTheme && preset !== undefined ? (
                      <div className="store-switcher__theme">{preset.name}</div>
                    ) : null}
                  </div>
                  <Button
                    variant={active ? "solid" : "soft"}
                    size="sm"
                    onClick={() => {
                      setActiveStoreId(store.storeId);
                      setOpen(false);
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

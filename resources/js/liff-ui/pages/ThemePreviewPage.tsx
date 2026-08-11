import { useAppConfig } from "../context/AppConfigContext";
import { allThemes } from "../themes/themes";
import { Card } from "../components/ui/Card";
import { Button } from "../components/ui/Button";
import { HeroBanner } from "../components/home/HeroBanner";
import { MembershipSummary } from "../components/home/MembershipSummary";
import { QuickActions } from "../components/home/QuickActions";
import { couponsByStore, memberProfileByStore, newsByStore, storeSummaryByStore } from "../mocks/content";
import { CouponCarousel } from "../components/home/CouponCarousel";
import { NewsSection } from "../components/home/NewsSection";
import { StoreSummaryCard } from "../components/home/StoreSummary";

export function ThemePreviewPage() {
  const { activeStore, activeThemeId, setActiveThemeId, customization } = useAppConfig();
  const member = memberProfileByStore[activeStore.storeId];
  const coupons = couponsByStore[activeStore.storeId] ?? [];
  const news = newsByStore[activeStore.storeId] ?? [];
  const summary = storeSummaryByStore[activeStore.storeId];

  return (
    <div className="page stack stack--lg">
      <Card padding="lg">
        <div className="section-head">
          <h2 className="section-head__title">Theme Preview</h2>
          <span className="section-head__meta">{activeThemeId}</span>
        </div>
        <div className="theme-grid theme-grid--preview">
          {allThemes.map((theme) => (
            <Button
              key={theme.id}
              variant={theme.id === activeThemeId ? "solid" : "soft"}
              onClick={() => setActiveThemeId(theme.id)}
            >
              {theme.name}
            </Button>
          ))}
        </div>
      </Card>

      <HeroBanner title={activeStore.heroTitle} subtitle={activeStore.heroSubtitle} content={activeStore.home} showImage showStatus showCta />
      <MembershipSummary member={member} />
      <QuickActions actions={activeStore.quickActions} columns={customization.home.quickActionColumns} />
      <CouponCarousel coupons={coupons} config={customization.coupon} />
      <NewsSection news={news} />
      <StoreSummaryCard summary={summary} />
    </div>
  );
}

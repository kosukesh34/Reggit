import { ArrowRight, CloudOff, RefreshCw } from "lucide-react";
import type { ReactNode } from "react";
import { Link } from "react-router-dom";
import { CouponCarousel } from "../components/home/CouponCarousel";
import { HomeGallery } from "../components/home/HomeGallery";
import { HeroBanner } from "../components/home/HeroBanner";
import { MembershipSummary } from "../components/home/MembershipSummary";
import { NewsSection } from "../components/home/NewsSection";
import { QuickActions } from "../components/home/QuickActions";
import { StoreSummaryCard } from "../components/home/StoreSummary";
import { Skeleton } from "../components/ui/Skeleton";
import { resolveAppBrand, resolveHomeLayout } from "../config/customization";
import { useAppConfig, isDesignShowcaseMode } from "../context/AppConfigContext";
import { useRuntimeData } from "../context/RuntimeDataContext";
import type { HomeLayout } from "../types/customization";
import { IndustryGalleryHome } from "./IndustryGalleryHome";
import { LandingShowcaseHome } from "./LandingShowcasePage";
import { OperationalHomePage } from "./OperationalHomePage";
import { SimpleRewardsHome } from "./SimpleRewardsPage";

const HOME_SECTION_ORDER: Record<HomeLayout, string[]> = {
  standard: ["lead", "notice", "hero", "primaryBelow", "membership", "quick", "gallery", "coupons", "news", "store", "floating"],
  wallet: ["lead", "notice", "membership", "quick", "hero", "primaryBelow", "gallery", "coupons", "news", "store", "floating"],
  magazine: ["notice", "hero", "gallery", "membership", "quick", "coupons", "news", "store", "primaryBelow", "floating"],
  minimal: ["lead", "notice", "membership", "quick", "hero", "primaryBelow", "gallery", "coupons", "news", "store", "floating"],
  crm: ["notice", "membership", "news", "gallery"],
};

function HomePageSkeleton(): JSX.Element {
  return (
    <div className="page home-page stack stack--lg" aria-busy="true" aria-label="読み込み中">
      <div className="home-skeleton">
        <Skeleton className="home-skeleton__lead" />
        <Skeleton className="home-skeleton__hero" />
        <Skeleton className="home-skeleton__card" />
        <Skeleton className="home-skeleton__card home-skeleton__card--short" />
      </div>
    </div>
  );
}

export function HomePage() {
  const { activeStore, customization, experienceId } = useAppConfig();
  const { member, coupons, news, stores, mode, error, refresh, loading } = useRuntimeData();

  if (isDesignShowcaseMode() && experienceId === "landing-showcase") {
    return <LandingShowcaseHome />;
  }
  if (experienceId === "simple-rewards") {
    return <SimpleRewardsHome />;
  }
  if (experienceId === "counter-operations") {
    return <OperationalHomePage variant="counter" />;
  }
  if (experienceId === "appointment-operations") {
    return <OperationalHomePage variant="appointment" />;
  }
  if (experienceId === "industry-gallery") {
    return <IndustryGalleryHome />;
  }

  if (mode !== "live" && mode !== "preview" && !(mode === "offline" && !loading)) {
    return <HomePageSkeleton />;
  }

  const brand = resolveAppBrand(activeStore, customization);
  const heroContent = { ...activeStore.home, heroImage: brand.heroImage || activeStore.home.heroImage };
  const summary = stores[0];
  const { home } = customization;
  const layout = resolveHomeLayout(home.layout);
  const primaryActionEnabled = activeStore.features[activeStore.home.primaryActionFeature];
  const guestName = (member.name.trim() !== "" ? member.name : "ゲスト").split(/[\s　]/)[0];

  function PrimaryAction({ className }: { className: string }) {
    return (
      <Link className={className} to={`/${activeStore.home.primaryActionFeature}`}>
        {activeStore.home.primaryActionLabel}<ArrowRight size={18} />
      </Link>
    );
  }

  const byKey: Record<string, ReactNode> = {
    lead: (
      <section className="home-lead" key="lead">
        <div><span>ホーム</span><strong>{guestName}さん</strong></div>
        <time>{new Intl.DateTimeFormat("ja-JP", { month: "short", day: "numeric", weekday: "short" }).format(new Date())}</time>
      </section>
    ),
    notice: mode === "offline" ? (
      <button className="data-notice" type="button" onClick={() => void refresh()} key="data-notice">
        <CloudOff size={17} /><span><strong>店舗データに接続できません</strong><small>{error ?? "通信環境をご確認ください"}</small></span><RefreshCw size={16} />
      </button>
    ) : null,
    hero: (
      <HeroBanner
        key="hero"
        title={brand.appName}
        subtitle={activeStore.heroSubtitle}
        content={heroContent}
        showImage={home.showHeroImage}
        showStatus={home.showHeroStatus}
        showCta={home.primaryActionPlacement === "hero" && primaryActionEnabled}
      />
    ),
    primaryBelow: home.primaryActionPlacement === "belowHero" && primaryActionEnabled
      ? <PrimaryAction key="primary-action" className="home-primary-action" />
      : null,
    membership: home.showMembership && activeStore.features.membership
      ? (
        <MembershipSummary
          key="membership"
          member={member}
          variant={layout === "crm" ? "crm" : "default"}
          showRank={customization.membership.showRank}
          showPoints={customization.membership.showPoints}
          showNextRank={customization.membership.showNextRank}
          showBarcode={customization.membership.showBarcode}
          showMemberName={customization.membership.showMemberName}
        />
      )
      : null,
    quick: home.showQuickActions && activeStore.quickActions.length > 0
      ? <QuickActions key="quick" actions={activeStore.quickActions} columns={home.quickActionColumns} />
      : null,
    gallery: (home.showGallery || home.showFeatured) && activeStore.features.gallery
      ? <HomeGallery key="gallery" />
      : null,
    coupons: home.showCoupons && activeStore.features.coupon
      ? <CouponCarousel key="coupon" coupons={coupons} config={customization.coupon} />
      : null,
    news: home.showNews && activeStore.features.news
      ? <NewsSection key="news" news={news} compact={layout === "crm"} />
      : null,
    store: home.showStoreInfo && summary
      ? <StoreSummaryCard key="store" summary={summary} />
      : null,
    floating: home.primaryActionPlacement === "floating" && primaryActionEnabled
      ? <PrimaryAction key="floating-primary-action" className="home-primary-action home-primary-action--floating" />
      : null,
  };

  const sections = HOME_SECTION_ORDER[layout]
    .map((key) => byKey[key])
    .filter((node): node is ReactNode => node !== null && node !== undefined);

  return (
    <div className={`page home-page stack stack--lg home-page--layout-${layout}`}>
      {sections}
    </div>
  );
}

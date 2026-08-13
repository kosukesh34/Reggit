import { ArrowRight, CloudOff, RefreshCw } from "lucide-react";
import type { ReactNode } from "react";
import { Link } from "react-router-dom";
import { CouponCarousel } from "../components/home/CouponCarousel";
import { FeaturedContent } from "../components/home/FeaturedContent";
import { HeroBanner } from "../components/home/HeroBanner";
import { MembershipSummary } from "../components/home/MembershipSummary";
import { NewsSection } from "../components/home/NewsSection";
import { QuickActions } from "../components/home/QuickActions";
import { StoreSummaryCard } from "../components/home/StoreSummary";
import { Skeleton } from "../components/ui/Skeleton";
import { resolveHomeLayout } from "../config/customization";
import { useAppConfig } from "../context/AppConfigContext";
import { useRuntimeData } from "../context/RuntimeDataContext";
import type { HomeLayout } from "../types/customization";
import { LANDING_SHOWCASE_STORE_ID, LandingShowcaseHome } from "./LandingShowcasePage";
import { SIMPLE_REWARDS_STORE_ID, SimpleRewardsHome } from "./SimpleRewardsPage";
import {
  APPOINTMENT_OPERATIONS_STORE_ID,
  COUNTER_OPERATIONS_STORE_ID,
  OperationalHomePage,
} from "./OperationalHomePage";

const HOME_SECTION_ORDER: Record<HomeLayout, string[]> = {
  standard: ["lead", "notice", "hero", "primaryBelow", "membership", "quick", "featured", "coupons", "news", "store", "floating"],
  wallet: ["lead", "notice", "membership", "quick", "hero", "primaryBelow", "featured", "coupons", "news", "store", "floating"],
  magazine: ["notice", "hero", "featured", "membership", "quick", "coupons", "news", "store", "primaryBelow", "floating"],
  minimal: ["lead", "notice", "membership", "quick", "hero", "primaryBelow", "coupons", "news", "store", "floating"],
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
  const { activeStore, customization } = useAppConfig();
  const { member, coupons, news, stores, mode, error, refresh, loading } = useRuntimeData();

  if (activeStore.storeId === LANDING_SHOWCASE_STORE_ID) {
    return <LandingShowcaseHome />;
  }
  if (activeStore.storeId === SIMPLE_REWARDS_STORE_ID) {
    return <SimpleRewardsHome />;
  }
  if (activeStore.storeId === COUNTER_OPERATIONS_STORE_ID) {
    return <OperationalHomePage variant="counter" />;
  }
  if (activeStore.storeId === APPOINTMENT_OPERATIONS_STORE_ID) {
    return <OperationalHomePage variant="appointment" />;
  }

  if (mode !== "live" && mode !== "preview" && !(mode === "offline" && !loading)) {
    return <HomePageSkeleton />;
  }

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
        <div><span>YOUR DAY, YOUR PLACE</span><strong>{guestName}さん、こんにちは。</strong></div>
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
        title={activeStore.heroTitle}
        subtitle={activeStore.heroSubtitle}
        content={activeStore.home}
        showImage={home.showHeroImage}
        showStatus={home.showHeroStatus}
        showCta={home.primaryActionPlacement === "hero" && primaryActionEnabled}
      />
    ),
    primaryBelow: home.primaryActionPlacement === "belowHero" && primaryActionEnabled
      ? <PrimaryAction key="primary-action" className="home-primary-action" />
      : null,
    membership: home.showMembership && activeStore.features.membership
      ? <MembershipSummary key="membership" member={member} showNextRank={customization.membership.showNextRank} />
      : null,
    quick: home.showQuickActions && activeStore.quickActions.length > 0
      ? <QuickActions key="quick" actions={activeStore.quickActions} columns={home.quickActionColumns} />
      : null,
    featured: home.showFeatured && activeStore.features[activeStore.home.featuredFeature]
      ? <FeaturedContent key="featured" content={activeStore.home} />
      : null,
    coupons: home.showCoupons && activeStore.features.coupon
      ? <CouponCarousel key="coupon" coupons={coupons} config={customization.coupon} />
      : null,
    news: home.showNews && activeStore.features.news
      ? <NewsSection key="news" news={news} />
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

  return <div className={`page home-page stack stack--lg home-page--layout-${layout}`}>{sections}</div>;
}

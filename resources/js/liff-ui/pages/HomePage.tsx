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
import { useAppConfig } from "../context/AppConfigContext";
import { useRuntimeData } from "../context/RuntimeDataContext";

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

  if (loading && mode !== "live" && mode !== "preview") {
    return <HomePageSkeleton />;
  }

  const summary = stores[0];
  const { home } = customization;
  const primaryActionEnabled = activeStore.features[activeStore.home.primaryActionFeature];
  const sections: ReactNode[] = [
    <section className="home-lead" key="lead">
      <div><span>YOUR DAY, YOUR PLACE</span><strong>{(member.name || "ゲスト").split(/[\s　]/)[0]}さん、こんにちは。</strong></div>
      <time>{new Intl.DateTimeFormat("ja-JP", { month: "short", day: "numeric", weekday: "short" }).format(new Date())}</time>
    </section>,
    mode === "offline" ? <button className="data-notice" type="button" onClick={() => void refresh()} key="data-notice">
      <CloudOff size={17} /><span><strong>店舗データに接続できません</strong><small>{error ?? "通信環境をご確認ください"}</small></span><RefreshCw size={16} />
    </button> : null,
    <HeroBanner
      key="hero"
      title={activeStore.heroTitle}
      subtitle={activeStore.heroSubtitle}
      content={activeStore.home}
      showImage={home.showHeroImage}
      showStatus={home.showHeroStatus}
      showCta={home.primaryActionPlacement === "hero" && primaryActionEnabled}
    />,
  ];

  if (home.primaryActionPlacement === "belowHero" && primaryActionEnabled) {
    sections.push(<PrimaryAction key="primary-action" className="home-primary-action" />);
  }
  if (home.showMembership && activeStore.features.membership) sections.push(<MembershipSummary key="membership" member={member} showNextRank={customization.membership.showNextRank} />);
  if (home.showQuickActions && activeStore.quickActions.length > 0) sections.push(<QuickActions key="quick" actions={activeStore.quickActions} columns={home.quickActionColumns} />);
  if (home.showFeatured && activeStore.features[activeStore.home.featuredFeature]) sections.push(<FeaturedContent key="featured" content={activeStore.home} />);
  if (home.showCoupons && activeStore.features.coupon) sections.push(<CouponCarousel key="coupon" coupons={coupons} config={customization.coupon} />);
  if (home.showNews && activeStore.features.news) sections.push(<NewsSection key="news" news={news} />);
  if (home.showStoreInfo && summary) sections.push(<StoreSummaryCard key="store" summary={summary} />);
  if (home.primaryActionPlacement === "floating" && primaryActionEnabled) {
    sections.push(<PrimaryAction key="floating-primary-action" className="home-primary-action home-primary-action--floating" />);
  }

  function PrimaryAction({ className }: { className: string }) {
    return (
      <Link className={className} to={`/${activeStore.home.primaryActionFeature}`}>
        {activeStore.home.primaryActionLabel}<ArrowRight size={18} />
      </Link>
    );
  }

  return <div className="page home-page stack stack--lg">{sections}</div>;
}

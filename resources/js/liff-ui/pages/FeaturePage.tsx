import { SearchX } from "lucide-react";
import type { ReactNode } from "react";
import { Navigate, useParams, useSearchParams } from "react-router-dom";
import { Card } from "../components/ui/Card";
import { Skeleton } from "../components/ui/Skeleton";
import { useAppConfig } from "../context/AppConfigContext";
import { CollectionDemo } from "../features/demo/CollectionDemo";
import { featureDefinitionMap, isFeatureType } from "../features/demo/featureCatalog";
import { OrderDemo } from "../features/demo/OrderDemo";
import { ReservationDemo } from "../features/demo/ReservationDemo";
import { CouponDemo, PointDemo, QueueDemo, StampDemo, SubscriptionDemo, TicketDemo } from "../features/demo/RewardsDemo";
import { isUtilityType, UtilityDemo } from "../features/demo/UtilityDemo";
import { useRuntimeData } from "../context/RuntimeDataContext";
import { CouponsView } from "../../frontend/features/coupons/CouponsView";
import { StampsView } from "../../frontend/features/stamps/StampsView";
import { QueueView } from "../../frontend/features/queue/QueueView";
import { ReservationsView } from "../../frontend/features/reservations/ReservationsView";
import { pathForLegacyTab } from "../navigation/tabPath";
import { isLinkTabType } from "../types/app";
import { LinkSitePage } from "./LinkSitePage";

const FEATURE_ALIASES: Record<string, string> = {
  member: "membership",
  coupons: "coupon",
  stamps: "stamp",
  reservations: "reservation",
  products: "menu",
  announcements: "news",
  card: "membership",
};

function FeaturePageSkeleton(): JSX.Element {
  return (
    <div className="page feature-page stack stack--lg" aria-busy="true" aria-label="読み込み中">
      <div className="feature-skeleton">
        <Skeleton className="feature-skeleton__title" />
        <Skeleton className="feature-skeleton__card" />
        <Skeleton className="feature-skeleton__card" />
      </div>
    </div>
  );
}

export function FeaturePage() {
  const { feature: rawFeature } = useParams();
  const [searchParams] = useSearchParams();
  const { activeStore, customization } = useAppConfig();
  const { member, coupons, news, stores, commerce, history, mode, loading, markAnnouncementRead } = useRuntimeData();
  const activeVenue = stores[0];
  const orderPath = activeStore.features.mobileOrder ? "/mobileOrder" : activeStore.features.dineInOrder ? "/dineInOrder" : "/reservation";
  const isLiveBound = mode === "live";

  if (rawFeature === undefined || rawFeature === "") {
    return <Navigate to="/home" replace />;
  }

  if (isLinkTabType(rawFeature)) {
    return <LinkSitePage tab={rawFeature} />;
  }

  const aliased = FEATURE_ALIASES[rawFeature];
  if (aliased === "membership" || rawFeature === "membership") {
    return <Navigate to="/membership" replace />;
  }

  const legacyPath = pathForLegacyTab(rawFeature);
  if (
    legacyPath !== null
    && legacyPath !== `/${rawFeature}`
    && !isFeatureType(rawFeature)
    && !isUtilityType(rawFeature)
  ) {
    return <Navigate to={legacyPath} replace />;
  }

  const feature = aliased !== undefined ? aliased : rawFeature;
  const isNewsArticle = feature === "news" && searchParams.get("announcement") !== null;

  if (loading && mode !== "live" && mode !== "preview") {
    return <FeaturePageSkeleton />;
  }

  if (!isFeatureType(feature)) {
    if (isUtilityType(feature)) {
      return <UtilityDemo type={feature} />;
    }
    return (
      <div className="page">
        <Card className="feature-success" padding="lg">
          <SearchX size={40} />
          <h1>ページが見つかりません</h1>
          <p>URLをご確認のうえ、ホームからもう一度お試しください。</p>
        </Card>
      </div>
    );
  }

  if (!activeStore.features[feature]) {
    return (
      <div className="page">
        <Card className="feature-success" padding="lg">
          <SearchX size={40} />
          <h1>現在このサービスは利用できません</h1>
          <p>店舗の設定により、この機能は現在非公開になっています。</p>
        </Card>
      </div>
    );
  }

  if (isLiveBound) {
    if (feature === "coupon") {
      return (
        <div className="page feature-page">
          <div id="couponsSection" className="coupons-section">
            <CouponsView />
          </div>
        </div>
      );
    }
    if (feature === "stamp") {
      return <div className="page feature-page"><StampsView /></div>;
    }
    if (feature === "queue") {
      return <div className="page feature-page"><QueueView /></div>;
    }
    if (feature === "reservation") {
      return <div className="page feature-page"><ReservationsView /></div>;
    }
  }

  const definition = featureDefinitionMap[feature];
  const Icon = definition.icon;
  let content: ReactNode;

  switch (feature) {
    case "mobileOrder":
      content = <OrderDemo dineIn={false} image={activeStore.home.featuredImage} config={customization.order} storeId={activeStore.storeId} venueName={activeVenue?.name ?? activeStore.storeName} />;
      break;
    case "dineInOrder":
      content = <OrderDemo dineIn image={activeStore.home.featuredImage} config={customization.order} storeId={activeStore.storeId} venueName={activeVenue?.name ?? activeStore.storeName} />;
      break;
    case "reservation":
      content = <ReservationDemo storeId={activeStore.storeId} venueName={activeVenue?.name ?? activeStore.storeName} />;
      break;
    case "queue":
      content = <QueueDemo storeId={activeStore.storeId} />;
      break;
    case "coupon":
      content = <CouponDemo coupons={coupons} config={customization.coupon} />;
      break;
    case "stamp":
      content = <StampDemo config={customization.stamp} storeId={activeStore.storeId} brandName={activeStore.brandName} />;
      break;
    case "point":
      content = <PointDemo initialPoints={member.points} />;
      break;
    case "ticket":
      content = <TicketDemo />;
      break;
    case "subscription":
      content = <SubscriptionDemo />;
      break;
    default:
      content = (
        <CollectionDemo
          feature={feature}
          featureImage={activeStore.home.featuredImage}
          heroImage={activeStore.home.heroImage}
          news={news}
          stores={stores}
          storeId={activeStore.storeId}
          orderPath={orderPath}
          liveCatalog={commerce?.items}
          history={history}
          onReadNews={markAnnouncementRead}
        />
      );
  }

  return (
    <div className={`page feature-page stack stack--lg${isNewsArticle ? " feature-page--news-article" : ""}`}>
      {isNewsArticle ? null : (
        <section className="feature-intro">
          <span className="feature-intro__icon"><Icon size={22} /></span>
          <div><h1>{definition.label}</h1><p>{definition.description}</p></div>
        </section>
      )}
      {content}
    </div>
  );
}

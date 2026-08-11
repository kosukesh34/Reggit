import { SearchX } from "lucide-react";
import type { ReactNode } from "react";
import { useParams } from "react-router-dom";
import { Card } from "../components/ui/Card";
import { useAppConfig } from "../context/AppConfigContext";
import { CollectionDemo } from "../features/demo/CollectionDemo";
import { featureDefinitionMap, isFeatureType } from "../features/demo/featureCatalog";
import { OrderDemo } from "../features/demo/OrderDemo";
import { ReservationDemo } from "../features/demo/ReservationDemo";
import { CouponDemo, PointDemo, QueueDemo, StampDemo, SubscriptionDemo, TicketDemo } from "../features/demo/RewardsDemo";
import { isUtilityType, UtilityDemo } from "../features/demo/UtilityDemo";
import { useRuntimeData } from "../context/RuntimeDataContext";

export function FeaturePage() {
  const { feature } = useParams();
  const { activeStore, customization } = useAppConfig();
  const { member, coupons, news, stores, commerce, history } = useRuntimeData();
  const activeVenue = stores[0];
  const orderPath = activeStore.features.mobileOrder ? "/mobileOrder" : activeStore.features.dineInOrder ? "/dineInOrder" : "/reservation";

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
        />
      );
  }

  return (
    <div className="page feature-page stack stack--lg">
      <section className="feature-intro">
        <span className="feature-intro__icon"><Icon size={22} /></span>
        <div><h1>{definition.label}</h1><p>{definition.description}</p></div>
      </section>
      {content}
    </div>
  );
}

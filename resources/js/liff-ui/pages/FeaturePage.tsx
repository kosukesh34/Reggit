import { Navigate, useParams } from "react-router-dom";
import { NativeCouponsPage } from "../native/NativeCouponsPage";
import { NativeTicketsPage } from "../native/NativeTicketsPage";
import { NativeStampsPage } from "../native/NativeStampsPage";
import { NativeQueuePage } from "../native/NativeQueuePage";
import { NativeReservationsPage } from "../native/NativeReservationsPage";
import { NativeGalleryPage, NativeNewsPage, NativeOrdersPage } from "../native/NativeContentPages";
import { NativeStorePage, NativeLegalPage } from "../native/NativeUtilityPages";
import { NativeProductsPage } from "../native/NativeProductsPage";
import { useNativeConfig } from "../native/nativeConfig";
import { useRuntimeData } from "../context/RuntimeDataContext";
import { LinkSitePage } from "./LinkSitePage";
import { isLinkTabType } from "../types/app";

export function FeaturePage() {
  const { feature = "home" } = useParams();
  const { config } = useNativeConfig();
  const { stores } = useRuntimeData();
  if (isLinkTabType(feature)) return <LinkSitePage tab={feature} />;
  switch (feature) {
    case "member": case "membership": return <Navigate to="/membership" replace />;
    case "profileEdit": return <Navigate to="/profile" replace />;
    case "coupon": case "coupons": return <NativeCouponsPage />;
    case "ticket": case "tickets": return <NativeTicketsPage />;
    case "stamp": case "stamps": return <NativeStampsPage />;
    case "queue": return <NativeQueuePage />;
    case "reservation": case "reservations": return <NativeReservationsPage />;
    case "gallery": return <NativeGalleryPage />;
    case "news": case "announcements": return <NativeNewsPage />;
    case "orderHistory": case "orders": case "purchaseHistory": return <NativeOrdersPage />;
    case "storeSearch": return <NativeStorePage />;
    case "terms": case "privacy": return <NativeLegalPage kind={feature} />;
    case "lottery": return <Navigate to="/designlab" replace />;
    case "menu": case "products": case "mobileOrder": case "dineInOrder": {
      return <div className="native-screen native-products"><NativeProductsPage dineIn={feature === "dineInOrder"} image="/images/liff/product-placeholder.jpg" storeId={config.project_key ?? "demo"} venueName={stores[0]?.name ?? config.project_name ?? "店舗"} /></div>;
    }
    case "shop": {
      return <div className="native-screen native-products"><NativeProductsPage dineIn={false} shipping image="/images/liff/product-placeholder.jpg" storeId={config.project_key ?? "demo"} venueName={stores[0]?.name ?? config.project_name ?? "店舗"} /></div>;
    }
    default: return <Navigate to="/home" replace />;
  }
}

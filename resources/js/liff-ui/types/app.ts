export type FeatureType =
  | "membership"
  | "mobileOrder"
  | "dineInOrder"
  | "reservation"
  | "queue"
  | "coupon"
  | "stamp"
  | "point"
  | "menu"
  | "gallery"
  | "news"
  | "storeSearch"
  | "purchaseHistory"
  | "orderHistory"
  | "favorite"
  | "ticket"
  | "subscription";

export interface FeatureConfig {
  membership: boolean;
  mobileOrder: boolean;
  dineInOrder: boolean;
  reservation: boolean;
  queue: boolean;
  coupon: boolean;
  stamp: boolean;
  point: boolean;
  menu: boolean;
  gallery: boolean;
  news: boolean;
  storeSearch: boolean;
  purchaseHistory: boolean;
  orderHistory: boolean;
  favorite: boolean;
  ticket: boolean;
  subscription: boolean;
}

export type LinkTabType = "link_1" | "link_2" | "link_3";

export type TabType =
  | "home"
  | "membership"
  | "profile"
  | "mobileOrder"
  | "dineInOrder"
  | "reservation"
  | "queue"
  | "coupon"
  | "stamp"
  | "point"
  | "menu"
  | "store"
  | "news"
  | "more"
  | LinkTabType;

export function isLinkTabType(type: string): type is LinkTabType {
  return type === "link_1" || type === "link_2" || type === "link_3";
}

export interface TabConfig {
  id: string;
  type: TabType;
  label: string;
  icon?: string;
  url?: string;
  enabled: boolean;
  order: number;
}

export interface QuickAction {
  id: string;
  feature: FeatureType;
  label: string;
  icon: string;
  order: number;
}

export interface MembershipCardConfig {
  templateId: string;
  backgroundType: "solid" | "gradient" | "image";
  backgroundColor?: string;
  gradientFrom?: string;
  gradientTo?: string;
  backgroundImage?: string;
  textColor: string;
  logoVisible: boolean;
  memberNameVisible: boolean;
  memberNumberVisible: boolean;
  rankVisible: boolean;
  pointVisible: boolean;
  barcodeVisible: boolean;
  borderRadius: number;
  shadow: "none" | "small" | "medium" | "large";
}

export interface StoreAppConfig {
  storeId: string;
  storeName: string;
  brandName: string;
  logoText: string;
  /** 店舗設定のヘッダー画像。未設定時は logoText モノグラムを表示 */
  logoUrl?: string | null;
  themeId: string;
  features: FeatureConfig;
  tabs: TabConfig[];
  quickActions: QuickAction[];
  membershipCard: MembershipCardConfig;
  heroTitle: string;
  heroSubtitle: string;
  home: HomeContent;
}

export interface HomeContent {
  heroImage: string;
  heroEyebrow: string;
  statusLabel: string;
  primaryActionLabel: string;
  primaryActionFeature: FeatureType;
  featuredImage: string;
  featuredTitle: string;
  featuredDescription: string;
  featuredActionLabel: string;
  featuredFeature: FeatureType;
}

export interface MemberProfile {
  name: string;
  number: string;
  rank: string;
  points: number;
  expiration: string;
  nextRankLabel: string;
  isMember: boolean;
  codeType?: "barcode" | "qrcode";
}

export interface CouponItem {
  id: string;
  title: string;
  description: string;
  validFrom?: string;
  validUntil: string;
  image?: string;
  featured?: boolean;
}

export interface NewsItem {
  images?: string[];
  id: string;
  title: string;
  date: string;
  category: string;
  body?: string;
  image?: string;
  isRead?: boolean;
  isPinned?: boolean;
}

export interface StoreSummary {
  name: string;
  address: string;
  openHours: string;
  access: string;
  phone: string;
}

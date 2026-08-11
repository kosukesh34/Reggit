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

export type TabType =
  | "home"
  | "membership"
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
  | "more";

export interface TabConfig {
  id: string;
  type: TabType;
  label: string;
  icon?: string;
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
  id: string;
  title: string;
  date: string;
  category: string;
}

export interface StoreSummary {
  name: string;
  address: string;
  openHours: string;
  access: string;
  phone: string;
}

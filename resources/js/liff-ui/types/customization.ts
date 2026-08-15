import type { LinkTabType, TabType } from "./app";

export type DesignPreset =
  | "editorial"
  | "bold"
  | "airy"
  | "minimal"
  | "luxury"
  | "pop"
  | "natural"
  | "monochrome"
  | "glass"
  | "neo"
  | "restaurant"
  | "izakaya"
  | "fastfood";
export type CouponStatus = "available" | "ended";
export type CouponLayout = "cards" | "compact";
export type OrderCtaPosition = "inline" | "sticky";
export type OrderCatalogLayout = "cards" | "compact";
export type MembershipTemplate = "classic" | "minimal" | "barcodeFocus" | "split" | "pointFocus" | "pass";
export type MembershipAlignment = "left" | "center";
export type StampStyle = "circles" | "squares" | "progress";
export type NavigationPosition = "bottom" | "floating" | "top";
export type PrimaryActionPlacement = "hero" | "belowHero" | "floating";
export type HomeLayout = "standard" | "wallet" | "magazine" | "minimal";
export type ButtonStyle = "filled" | "soft" | "outline";
export type ContentDensity = "compact" | "standard" | "relaxed";
export type HeaderShortcut = "news" | "membership" | "profile" | "coupon" | "stamp" | "reservation" | "queue" | "mobileOrder" | "none";
export type HeaderButtonMode = "shortcut" | "custom" | "none";
export type HeaderButtonIcon = "bell" | "creditCard" | "userRound" | "ticket" | "stamp" | "calendarDays" | "qrCode" | "shoppingBag" | "menu" | "home" | "moreHorizontal";

export interface HeaderRightButton {
  mode: HeaderButtonMode;
  shortcut: Exclude<HeaderShortcut, "none">;
  label: string;
  path: string;
  icon: HeaderButtonIcon;
}

export interface AppCustomization {
  designPreset: DesignPreset;
  colors: {
    primary?: string;
    secondary?: string;
    accent?: string;
    background?: string;
    surface?: string;
    textPrimary?: string;
    textSecondary?: string;
  };
  appearance: {
    buttonStyle: ButtonStyle;
    density: ContentDensity;
  };
  home: {
    layout: HomeLayout;
    primaryActionPlacement: PrimaryActionPlacement;
    quickActionColumns: 2 | 3 | 4;
    showHeroImage: boolean;
    showHeroStatus: boolean;
    showMembership: boolean;
    showQuickActions: boolean;
    showFeatured: boolean;
    showCoupons: boolean;
    showNews: boolean;
    showStoreInfo: boolean;
  };
  header: {
    showLogo: boolean;
    showSubtitle: boolean;
    showNotifications: boolean;
    showStoreTheme: boolean;
    shortcut: HeaderShortcut;
    rightButton: HeaderRightButton;
  };
  coupon: {
    showStatusTabs: boolean;
    defaultStatus: CouponStatus;
    layout: CouponLayout;
    showExpiryDate: boolean;
    showBeforeAvailable: boolean;
    showImage: boolean;
    showDescription: boolean;
    showStatusBadge: boolean;
    requireConfirmation: boolean;
  };
  order: {
    showEarliestPickup: boolean;
    showItemImages: boolean;
    ctaPosition: OrderCtaPosition;
    catalogLayout: OrderCatalogLayout;
    groupByCategory: boolean;
  };
  membership: {
    template: MembershipTemplate;
    alignment: MembershipAlignment;
    showLogo: boolean;
    showMemberName: boolean;
    showMemberNumber: boolean;
    showBarcode: boolean;
    showPoints: boolean;
    showRank: boolean;
    showNextRank: boolean;
    showExpiry: boolean;
  };
  stamp: {
    style: StampStyle;
    goal: number;
    showRewardText: boolean;
  };
  navigation: {
    tabs: TabType[];
    position: NavigationPosition;
    showLabels: boolean;
    emphasizeCenterTab: boolean;
    linkUrls?: Partial<Record<LinkTabType, string>>;
  };
}

type NestedCustomizationKey = "colors" | "appearance" | "home" | "header" | "coupon" | "order" | "membership" | "stamp" | "navigation";

export type AppCustomizationPatch = Partial<Omit<AppCustomization, NestedCustomizationKey>> & {
  [Key in NestedCustomizationKey]?: Partial<AppCustomization[Key]>;
};

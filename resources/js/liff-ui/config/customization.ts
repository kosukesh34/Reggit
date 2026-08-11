import type { StoreAppConfig } from "../types/app";
import type { AppCustomization, DesignPreset } from "../types/customization";

export const defaultCustomization: AppCustomization = {
  designPreset: "editorial",
  colors: {},
  appearance: {
    buttonStyle: "filled",
    density: "standard",
  },
  home: {
    primaryActionPlacement: "hero",
    quickActionColumns: 4,
    showHeroImage: true,
    showHeroStatus: true,
    showMembership: true,
    showQuickActions: true,
    showFeatured: true,
    showCoupons: true,
    showNews: true,
    showStoreInfo: true,
  },
  header: {
    showLogo: true,
    showSubtitle: true,
    showNotifications: true,
    showStoreTheme: true,
  },
  coupon: {
    showStatusTabs: true,
    defaultStatus: "available",
    layout: "cards",
    showExpiryDate: true,
    showBeforeAvailable: true,
    showImage: true,
    showDescription: true,
    showStatusBadge: true,
    requireConfirmation: true,
  },
  order: {
    showEarliestPickup: true,
    showItemImages: true,
    ctaPosition: "inline",
    catalogLayout: "cards",
    groupByCategory: true,
  },
  membership: {
    template: "classic",
    alignment: "left",
    showLogo: true,
    showMemberName: true,
    showMemberNumber: true,
    showBarcode: true,
    showPoints: true,
    showRank: true,
    showExpiry: true,
  },
  stamp: {
    style: "circles",
    goal: 10,
    showRewardText: true,
  },
  navigation: {
    tabs: ["home", "membership", "mobileOrder", "coupon", "more"],
    position: "bottom",
    showLabels: true,
    emphasizeCenterTab: false,
  },
};

const storePresetMap: Record<string, DesignPreset> = {
  store_cafe: "editorial",
  store_apparel: "minimal",
  store_salon: "luxury",
  store_bakery: "natural",
  store_fitness: "neo",
  store_flower: "airy",
  store_hotel: "glass",
  store_pets: "pop",
  store_clinic: "monochrome",
  store_market: "bold",
  store_dining: "restaurant",
  store_izakaya: "izakaya",
  store_fastfood: "fastfood",
};

const storeExperienceMap: Record<string, {
  buttonStyle: AppCustomization["appearance"]["buttonStyle"];
  density: AppCustomization["appearance"]["density"];
  primaryActionPlacement: AppCustomization["home"]["primaryActionPlacement"];
  quickActionColumns: AppCustomization["home"]["quickActionColumns"];
  navigationPosition: AppCustomization["navigation"]["position"];
  emphasizeCenterTab: boolean;
  membershipTemplate: AppCustomization["membership"]["template"];
  stampStyle: AppCustomization["stamp"]["style"];
  stampGoal: number;
}> = {
  store_cafe: { buttonStyle: "filled", density: "standard", primaryActionPlacement: "hero", quickActionColumns: 4, navigationPosition: "bottom", emphasizeCenterTab: false, membershipTemplate: "classic", stampStyle: "circles", stampGoal: 10 },
  store_apparel: { buttonStyle: "outline", density: "relaxed", primaryActionPlacement: "belowHero", quickActionColumns: 4, navigationPosition: "bottom", emphasizeCenterTab: false, membershipTemplate: "minimal", stampStyle: "progress", stampGoal: 5 },
  store_salon: { buttonStyle: "soft", density: "relaxed", primaryActionPlacement: "floating", quickActionColumns: 3, navigationPosition: "floating", emphasizeCenterTab: true, membershipTemplate: "barcodeFocus", stampStyle: "circles", stampGoal: 5 },
  store_bakery: { buttonStyle: "soft", density: "standard", primaryActionPlacement: "belowHero", quickActionColumns: 2, navigationPosition: "bottom", emphasizeCenterTab: false, membershipTemplate: "split", stampStyle: "squares", stampGoal: 10 },
  store_fitness: { buttonStyle: "filled", density: "compact", primaryActionPlacement: "floating", quickActionColumns: 4, navigationPosition: "top", emphasizeCenterTab: true, membershipTemplate: "pointFocus", stampStyle: "progress", stampGoal: 5 },
  store_flower: { buttonStyle: "soft", density: "relaxed", primaryActionPlacement: "hero", quickActionColumns: 2, navigationPosition: "floating", emphasizeCenterTab: false, membershipTemplate: "pass", stampStyle: "circles", stampGoal: 5 },
  store_hotel: { buttonStyle: "outline", density: "relaxed", primaryActionPlacement: "belowHero", quickActionColumns: 4, navigationPosition: "bottom", emphasizeCenterTab: false, membershipTemplate: "split", stampStyle: "squares", stampGoal: 5 },
  store_pets: { buttonStyle: "filled", density: "standard", primaryActionPlacement: "floating", quickActionColumns: 2, navigationPosition: "floating", emphasizeCenterTab: true, membershipTemplate: "pointFocus", stampStyle: "circles", stampGoal: 10 },
  store_clinic: { buttonStyle: "outline", density: "compact", primaryActionPlacement: "hero", quickActionColumns: 4, navigationPosition: "top", emphasizeCenterTab: false, membershipTemplate: "barcodeFocus", stampStyle: "progress", stampGoal: 5 },
  store_market: { buttonStyle: "filled", density: "compact", primaryActionPlacement: "belowHero", quickActionColumns: 3, navigationPosition: "bottom", emphasizeCenterTab: true, membershipTemplate: "pass", stampStyle: "squares", stampGoal: 12 },
  store_dining: { buttonStyle: "filled", density: "standard", primaryActionPlacement: "hero", quickActionColumns: 4, navigationPosition: "bottom", emphasizeCenterTab: false, membershipTemplate: "split", stampStyle: "squares", stampGoal: 5 },
  store_izakaya: { buttonStyle: "filled", density: "standard", primaryActionPlacement: "hero", quickActionColumns: 4, navigationPosition: "bottom", emphasizeCenterTab: false, membershipTemplate: "split", stampStyle: "squares", stampGoal: 5 },
  store_fastfood: { buttonStyle: "filled", density: "compact", primaryActionPlacement: "belowHero", quickActionColumns: 4, navigationPosition: "floating", emphasizeCenterTab: true, membershipTemplate: "pointFocus", stampStyle: "progress", stampGoal: 5 },
};

export function createDefaultCustomization(store: StoreAppConfig): AppCustomization {
  const tabs = [...store.tabs]
    .filter((tab) => tab.enabled)
    .sort((a, b) => a.order - b.order)
    .map((tab) => tab.type)
    .slice(0, 6);
  const experience = storeExperienceMap[store.storeId] ?? storeExperienceMap.store_cafe;

  return {
    ...defaultCustomization,
    designPreset: storePresetMap[store.storeId] ?? defaultCustomization.designPreset,
    colors: {},
    appearance: { ...defaultCustomization.appearance, buttonStyle: experience.buttonStyle, density: experience.density },
    home: { ...defaultCustomization.home, primaryActionPlacement: experience.primaryActionPlacement, quickActionColumns: experience.quickActionColumns },
    header: { ...defaultCustomization.header },
    coupon: { ...defaultCustomization.coupon },
    order: {
      ...defaultCustomization.order,
      ctaPosition: store.storeId === "store_fastfood" ? "sticky" : defaultCustomization.order.ctaPosition,
    },
    membership: {
      ...defaultCustomization.membership,
      template: experience.membershipTemplate,
      alignment: experience.membershipTemplate === "pass" || experience.membershipTemplate === "pointFocus" ? "center" : "left",
    },
    stamp: { ...defaultCustomization.stamp, style: experience.stampStyle, goal: experience.stampGoal },
    navigation: {
      ...defaultCustomization.navigation,
      tabs: tabs.length > 0 ? tabs : defaultCustomization.navigation.tabs,
      position: experience.navigationPosition,
      emphasizeCenterTab: experience.emphasizeCenterTab,
    },
  };
}

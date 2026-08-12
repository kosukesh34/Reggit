import { isLinkTabType, type StoreAppConfig } from "../types/app";
import type { AppCustomization, AppCustomizationPatch, DesignPreset } from "../types/customization";

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
    shortcut: "news",
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
    showNextRank: true,
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
    linkUrls: {},
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
  store_landing_showcase: "editorial",
  store_simple_rewards: "minimal",
  store_counter_operations: "airy",
  store_appointment_operations: "airy",
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
  store_landing_showcase: { buttonStyle: "filled", density: "standard", primaryActionPlacement: "hero", quickActionColumns: 3, navigationPosition: "bottom", emphasizeCenterTab: false, membershipTemplate: "pointFocus", stampStyle: "circles", stampGoal: 10 },
  store_simple_rewards: { buttonStyle: "filled", density: "compact", primaryActionPlacement: "belowHero", quickActionColumns: 3, navigationPosition: "bottom", emphasizeCenterTab: false, membershipTemplate: "barcodeFocus", stampStyle: "squares", stampGoal: 10 },
  store_counter_operations: { buttonStyle: "filled", density: "compact", primaryActionPlacement: "belowHero", quickActionColumns: 2, navigationPosition: "top", emphasizeCenterTab: false, membershipTemplate: "pointFocus", stampStyle: "progress", stampGoal: 5 },
  store_appointment_operations: { buttonStyle: "soft", density: "relaxed", primaryActionPlacement: "belowHero", quickActionColumns: 3, navigationPosition: "bottom", emphasizeCenterTab: false, membershipTemplate: "pass", stampStyle: "circles", stampGoal: 5 },
};

const designPresetExperienceMap: Record<DesignPreset, (typeof storeExperienceMap)[string]> = {
  editorial: storeExperienceMap.store_cafe,
  bold: storeExperienceMap.store_market,
  airy: storeExperienceMap.store_flower,
  minimal: storeExperienceMap.store_apparel,
  luxury: storeExperienceMap.store_salon,
  pop: storeExperienceMap.store_pets,
  natural: storeExperienceMap.store_bakery,
  monochrome: storeExperienceMap.store_clinic,
  glass: storeExperienceMap.store_hotel,
  neo: storeExperienceMap.store_fitness,
  restaurant: storeExperienceMap.store_dining,
  izakaya: storeExperienceMap.store_izakaya,
  fastfood: storeExperienceMap.store_fastfood,
};

/** デザインプリセット選択時に色だけでなく配置も切り替える */
export function designPresetLayoutPatch(designPreset: DesignPreset): AppCustomizationPatch {
  const experience = designPresetExperienceMap[designPreset] ?? storeExperienceMap.store_cafe;
  return {
    designPreset,
    colors: {
      primary: undefined,
      secondary: undefined,
      accent: undefined,
      background: undefined,
      surface: undefined,
      textPrimary: undefined,
      textSecondary: undefined,
    },
    appearance: { buttonStyle: experience.buttonStyle, density: experience.density },
    home: {
      primaryActionPlacement: experience.primaryActionPlacement,
      quickActionColumns: experience.quickActionColumns,
    },
    membership: {
      template: experience.membershipTemplate,
      alignment: experience.membershipTemplate === "pass" || experience.membershipTemplate === "pointFocus" ? "center" : "left",
    },
    stamp: { style: experience.stampStyle, goal: experience.stampGoal },
    navigation: {
      position: experience.navigationPosition,
      emphasizeCenterTab: experience.emphasizeCenterTab,
    },
    order: {
      ctaPosition: designPreset === "fastfood" ? "sticky" : "inline",
    },
  };
}

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
      linkUrls: Object.fromEntries(
        store.tabs
          .filter((tab) => isLinkTabType(tab.type) && typeof tab.url === "string" && tab.url !== "")
          .map((tab) => [tab.type, tab.url as string]),
      ),
    },
  };
}

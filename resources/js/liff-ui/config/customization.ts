import { isLinkTabType, type StoreAppConfig, type TabType } from "../types/app";
import { isIndustryGalleryStoreId, resolveIndustryGalleryTemplate } from "./industryGallery";
import type { AppCustomization, AppCustomizationPatch, AppExperienceId, DesignPreset, HomeLayout } from "../types/customization";

export const SIMPLE_HOME_STORE_ID = "store_simple_home";
export const MEMBER_CRM_STORE_ID = "store_member_crm";

const STORE_EXPERIENCE: Record<string, AppExperienceId> = {
  store_simple_rewards: "simple-rewards",
  store_landing_showcase: "landing-showcase",
  store_counter_operations: "counter-operations",
  store_appointment_operations: "appointment-operations",
};

const THEME_EXPERIENCE: Record<string, AppExperienceId> = {
  "simple-rewards": "simple-rewards",
  "landing-showcase": "landing-showcase",
  "operations-counter": "counter-operations",
  "operations-booking": "appointment-operations",
};

export function experienceIdForStore(store: StoreAppConfig): AppExperienceId {
  if (isIndustryGalleryStoreId(store.storeId) || store.themeId.startsWith("gallery-")) {
    return "industry-gallery";
  }
  return STORE_EXPERIENCE[store.storeId] ?? THEME_EXPERIENCE[store.themeId] ?? "standard";
}

export function resolveAppExperience(store: StoreAppConfig, customization: AppCustomization): AppExperienceId {
  if (customization.experienceId !== undefined) {
    return customization.experienceId;
  }
  return experienceIdForStore(store);
}

const SIMPLE_HOME_HOME: AppCustomization["home"] = {
  layout: "minimal",
  primaryActionPlacement: "belowHero",
  quickActionColumns: 3,
  showHeroImage: false,
  showHeroStatus: false,
  showMembership: true,
  showQuickActions: true,
  showFeatured: false,
  showGallery: false,
  showCoupons: true,
  showNews: false,
  showStoreInfo: false,
};

export const MEMBER_CRM_HOME: AppCustomization["home"] = {
  layout: "crm",
  primaryActionPlacement: "belowHero",
  quickActionColumns: 4,
  showHeroImage: false,
  showHeroStatus: false,
  showMembership: true,
  showQuickActions: false,
  showFeatured: false,
  showGallery: true,
  showCoupons: false,
  showNews: true,
  showStoreInfo: false,
};

const extraHomeByStore: Record<string, Partial<AppCustomization["home"]>> = {
  [MEMBER_CRM_STORE_ID]: MEMBER_CRM_HOME,
  [SIMPLE_HOME_STORE_ID]: SIMPLE_HOME_HOME,
  store_apparel: {
    layout: "magazine",
    showHeroStatus: false,
    showQuickActions: false,
    showStoreInfo: false,
  },
  store_salon: {
    layout: "wallet",
    showStoreInfo: false,
  },
  store_clinic: {
    layout: "minimal",
    showHeroImage: false,
    showHeroStatus: false,
    showFeatured: false,
    showCoupons: false,
    showNews: true,
    showStoreInfo: true,
  },
  store_fitness: {
    layout: "minimal",
    showStoreInfo: false,
    showCoupons: false,
  },
  store_hotel: {
    layout: "magazine",
    showQuickActions: true,
  },
  store_flower: {
    layout: "magazine",
    showHeroStatus: false,
    showStoreInfo: false,
  },
  store_market: {
    layout: "standard",
    showMembership: false,
  },
  store_fastfood: {
    layout: "minimal",
    showMembership: false,
    showNews: false,
    showStoreInfo: false,
  },
  store_izakaya: {
    layout: "wallet",
  },
  store_dining: {
    layout: "wallet",
  },
  store_bakery: {
    layout: "standard",
    showNews: true,
  },
};

function galleryBrand(store: StoreAppConfig): AppCustomization["brand"] {
  if (!isIndustryGalleryStoreId(store.storeId)) {
    return {
      appName: store.storeName,
      heroImage: store.home.heroImage,
    };
  }
  const template = resolveIndustryGalleryTemplate(store.storeId);
  return {
    appName: template.appName,
    heroImage: template.image,
    radius: template.radius,
    visualStyle: template.style,
  };
}

function galleryHome(store: StoreAppConfig): Partial<AppCustomization["home"]> {
  if (!isIndustryGalleryStoreId(store.storeId)) {
    return {};
  }
  return { blocks: [...resolveIndustryGalleryTemplate(store.storeId).blocks] };
}

const clearedColors: AppCustomization["colors"] = {
  primary: undefined,
  secondary: undefined,
  accent: undefined,
  background: undefined,
  surface: undefined,
  textPrimary: undefined,
  textSecondary: undefined,
};

export function appTemplatePatch(store: StoreAppConfig): AppCustomizationPatch {
  const base = createDefaultCustomization(store);
  return {
    designPreset: base.designPreset,
    appTemplateId: store.storeId,
    experienceId: experienceIdForStore(store),
    themeId: store.themeId,
    colors: clearedColors,
    appearance: base.appearance,
    brand: base.brand,
    home: base.home,
    header: base.header,
    coupon: base.coupon,
    order: base.order,
    membership: base.membership,
    stamp: base.stamp,
    navigation: base.navigation,
  };
}

export const defaultCustomization: AppCustomization = {
  designPreset: "editorial",
  experienceId: "standard",
  colors: {},
  appearance: {
    buttonStyle: "filled",
    density: "standard",
  },
  brand: {},
  home: {
    layout: "standard",
    primaryActionPlacement: "hero",
    quickActionColumns: 4,
    showHeroImage: true,
    showHeroStatus: true,
    showMembership: true,
    showQuickActions: true,
    showFeatured: true,
    showGallery: false,
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
    rightButton: {
      mode: "shortcut",
      shortcut: "news",
      label: "お知らせ",
      path: "/news",
      icon: "bell",
    },
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
    showDetailSection: true,
    showWalletActions: true,
    showProfileEdit: true,
    showBarcodeEnlarge: true,
    showUsageNote: true,
  },
  stamp: {
    style: "circles",
    goal: 10,
    showRewardText: true,
  },
  navigation: {
    tabs: ["home", "membership", "profile", "mobileOrder", "coupon", "more"],
    position: "bottom",
    showLabels: true,
    emphasizeCenterTab: false,
    linkUrls: {},
  },
};

export function resolveHomeLayout(value: string | undefined): HomeLayout {
  if (value === "wallet" || value === "magazine" || value === "minimal" || value === "crm") {
    return value;
  }
  return "standard";
}

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
  store_simple_home: "minimal",
  store_member_crm: "luxury",
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
  store_bakery: { buttonStyle: "soft", density: "standard", primaryActionPlacement: "belowHero", quickActionColumns: 2, navigationPosition: "bottom", emphasizeCenterTab: false, membershipTemplate: "ticket", stampStyle: "squares", stampGoal: 10 },
  store_fitness: { buttonStyle: "filled", density: "compact", primaryActionPlacement: "floating", quickActionColumns: 4, navigationPosition: "bottom", emphasizeCenterTab: false, membershipTemplate: "pointFocus", stampStyle: "progress", stampGoal: 5 },
  store_flower: { buttonStyle: "soft", density: "relaxed", primaryActionPlacement: "hero", quickActionColumns: 2, navigationPosition: "bottom", emphasizeCenterTab: false, membershipTemplate: "pass", stampStyle: "circles", stampGoal: 5 },
  store_hotel: { buttonStyle: "outline", density: "relaxed", primaryActionPlacement: "belowHero", quickActionColumns: 4, navigationPosition: "bottom", emphasizeCenterTab: false, membershipTemplate: "wallet", stampStyle: "squares", stampGoal: 5 },
  store_pets: { buttonStyle: "filled", density: "standard", primaryActionPlacement: "floating", quickActionColumns: 2, navigationPosition: "bottom", emphasizeCenterTab: false, membershipTemplate: "pointFocus", stampStyle: "circles", stampGoal: 10 },
  store_clinic: { buttonStyle: "outline", density: "compact", primaryActionPlacement: "hero", quickActionColumns: 4, navigationPosition: "bottom", emphasizeCenterTab: false, membershipTemplate: "barcodeFocus", stampStyle: "progress", stampGoal: 5 },
  store_market: { buttonStyle: "filled", density: "compact", primaryActionPlacement: "belowHero", quickActionColumns: 3, navigationPosition: "bottom", emphasizeCenterTab: true, membershipTemplate: "pass", stampStyle: "squares", stampGoal: 12 },
  store_dining: { buttonStyle: "filled", density: "standard", primaryActionPlacement: "hero", quickActionColumns: 4, navigationPosition: "bottom", emphasizeCenterTab: false, membershipTemplate: "split", stampStyle: "squares", stampGoal: 15 },
  store_izakaya: { buttonStyle: "filled", density: "standard", primaryActionPlacement: "hero", quickActionColumns: 4, navigationPosition: "bottom", emphasizeCenterTab: false, membershipTemplate: "split", stampStyle: "squares", stampGoal: 15 },
  store_fastfood: { buttonStyle: "filled", density: "compact", primaryActionPlacement: "belowHero", quickActionColumns: 4, navigationPosition: "bottom", emphasizeCenterTab: true, membershipTemplate: "pointFocus", stampStyle: "progress", stampGoal: 5 },
  store_landing_showcase: { buttonStyle: "filled", density: "standard", primaryActionPlacement: "hero", quickActionColumns: 3, navigationPosition: "bottom", emphasizeCenterTab: false, membershipTemplate: "pointFocus", stampStyle: "circles", stampGoal: 10 },
  store_simple_rewards: { buttonStyle: "filled", density: "compact", primaryActionPlacement: "belowHero", quickActionColumns: 3, navigationPosition: "bottom", emphasizeCenterTab: false, membershipTemplate: "simple", stampStyle: "squares", stampGoal: 10 },
  store_simple_home: { buttonStyle: "filled", density: "compact", primaryActionPlacement: "belowHero", quickActionColumns: 3, navigationPosition: "bottom", emphasizeCenterTab: false, membershipTemplate: "simple", stampStyle: "squares", stampGoal: 10 },
  store_member_crm: { buttonStyle: "filled", density: "standard", primaryActionPlacement: "belowHero", quickActionColumns: 4, navigationPosition: "bottom", emphasizeCenterTab: false, membershipTemplate: "classic", stampStyle: "circles", stampGoal: 10 },
  store_counter_operations: { buttonStyle: "filled", density: "compact", primaryActionPlacement: "belowHero", quickActionColumns: 2, navigationPosition: "top", emphasizeCenterTab: false, membershipTemplate: "pointFocus", stampStyle: "progress", stampGoal: 5 },
  store_appointment_operations: { buttonStyle: "soft", density: "relaxed", primaryActionPlacement: "belowHero", quickActionColumns: 3, navigationPosition: "bottom", emphasizeCenterTab: false, membershipTemplate: "pass", stampStyle: "circles", stampGoal: 5 },
};

const GALLERY_EXPERIENCE = {
  buttonStyle: "filled" as const,
  density: "compact" as const,
  primaryActionPlacement: "belowHero" as const,
  quickActionColumns: 3 as const,
  navigationPosition: "bottom" as const,
  emphasizeCenterTab: false,
  membershipTemplate: "classic" as const,
  stampStyle: "squares" as const,
  stampGoal: 10,
};

const designPresetExperienceMap: Record<DesignPreset, (typeof storeExperienceMap)[string]> = {
  editorial: storeExperienceMap.store_cafe,
  bold: storeExperienceMap.store_market,
  airy: storeExperienceMap.store_flower,
  minimal: storeExperienceMap.store_apparel,
  luxury: storeExperienceMap.store_member_crm,
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
      ...(designPreset === "luxury" ? { layout: "crm" as const } : {}),
    },
    membership: {
      template: experience.membershipTemplate,
      alignment: experience.membershipTemplate === "pass" || experience.membershipTemplate === "pointFocus" || experience.membershipTemplate === "simple" || experience.membershipTemplate === "wallet" ? "center" : "left",
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

export const MEMBER_CRM_HEADER: AppCustomization["header"] = {
  showLogo: false,
  showSubtitle: false,
  showNotifications: true,
  showStoreTheme: false,
  shortcut: "news",
  rightButton: {
    mode: "shortcut",
    shortcut: "news",
    label: "お知らせ",
    path: "/news",
    icon: "bell",
  },
};

export const MEMBER_CRM_NAV_TABS: TabType[] = ["home", "membership", "point", "coupon", "more"];
const OPERATIONAL_TABS: TabType[] = ["mobileOrder", "dineInOrder", "queue", "reservation"];
const LEGACY_DEFAULT_TABS: TabType[] = ["home", "membership", "profile", "mobileOrder", "coupon", "more"];

export function memberCrmNavigationTabs(savedTabs?: TabType[]): TabType[] {
  if (savedTabs === undefined || savedTabs.length === 0) {
    return [...MEMBER_CRM_NAV_TABS];
  }
  if (savedTabs.join(",") === LEGACY_DEFAULT_TABS.join(",")) {
    return [...MEMBER_CRM_NAV_TABS];
  }
  if (savedTabs.some((tab) => OPERATIONAL_TABS.includes(tab))) {
    return [...MEMBER_CRM_NAV_TABS];
  }
  return savedTabs.slice(0, 6);
}

export function createDefaultCustomization(store: StoreAppConfig): AppCustomization {
  const tabs = [...store.tabs]
    .filter((tab) => tab.enabled)
    .sort((a, b) => a.order - b.order)
    .map((tab) => tab.type)
    .slice(0, 6);
  const experience = storeExperienceMap[store.storeId]
    ?? (isIndustryGalleryStoreId(store.storeId) ? GALLERY_EXPERIENCE : storeExperienceMap.store_cafe);
  const useMemberCrm = store.storeId === MEMBER_CRM_STORE_ID;

  return {
    ...defaultCustomization,
    designPreset: storePresetMap[store.storeId] ?? defaultCustomization.designPreset,
    appTemplateId: store.storeId,
    experienceId: experienceIdForStore(store),
    themeId: store.themeId,
    colors: {},
    appearance: { ...defaultCustomization.appearance, buttonStyle: experience.buttonStyle, density: experience.density },
    brand: galleryBrand(store),
    home: {
      ...defaultCustomization.home,
      primaryActionPlacement: experience.primaryActionPlacement,
      quickActionColumns: experience.quickActionColumns,
      ...(extraHomeByStore[store.storeId] ?? {}),
      ...galleryHome(store),
    },
    header: { ...(useMemberCrm ? MEMBER_CRM_HEADER : defaultCustomization.header) },
    coupon: { ...defaultCustomization.coupon },
    order: {
      ...defaultCustomization.order,
      ctaPosition: store.storeId === "store_fastfood" ? "sticky" : defaultCustomization.order.ctaPosition,
    },
    membership: {
      ...defaultCustomization.membership,
      template: experience.membershipTemplate,
      alignment: experience.membershipTemplate === "pass" || experience.membershipTemplate === "pointFocus" || experience.membershipTemplate === "simple" || experience.membershipTemplate === "wallet" ? "center" : "left",
      ...(experience.membershipTemplate === "simple"
        ? { showDetailSection: false, showWalletActions: false, showUsageNote: false, showNextRank: false }
        : {}),
    },
    stamp: { ...defaultCustomization.stamp, style: experience.stampStyle, goal: experience.stampGoal },
    navigation: {
      ...defaultCustomization.navigation,
      tabs: useMemberCrm ? [...MEMBER_CRM_NAV_TABS] : (tabs.length > 0 ? tabs : defaultCustomization.navigation.tabs),
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

export function resolveAppBrand(store: StoreAppConfig, customization: AppCustomization): { appName: string; heroImage: string } {
  const appName = customization.brand?.appName?.trim();
  const heroImage = customization.brand?.heroImage?.trim();
  return {
    appName: appName !== undefined && appName !== "" ? appName : store.heroTitle || store.storeName,
    heroImage: heroImage !== undefined && heroImage !== "" ? heroImage : store.home.heroImage,
  };
}

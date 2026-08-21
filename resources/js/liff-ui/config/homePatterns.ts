export type HomePattern =
  | "rewards"
  | "checkin"
  | "booking"
  | "stamp"
  | "pass"
  | "coupons"
  | "lookbook"
  | "clinic"
  | "mono"
  | "stay"
  | "order";

const STORE_HOME_PATTERN: Record<string, HomePattern> = {
  store_cafe: "rewards",
  store_gallery_cafe: "rewards",
  store_fitness: "checkin",
  store_gallery_fitness: "checkin",
  store_salon: "booking",
  store_gallery_beauty: "booking",
  store_izakaya: "stamp",
  store_dining: "stamp",
  store_gallery_restaurant: "stamp",
  store_bakery: "pass",
  store_gallery_service: "pass",
  store_flower: "coupons",
  store_gallery_grocery: "coupons",
  store_apparel: "lookbook",
  store_gallery_apparel: "lookbook",
  store_gallery_retail: "lookbook",
  store_clinic: "clinic",
  store_pets: "clinic",
  store_gallery_medical: "clinic",
  store_gallery_pet: "clinic",
  store_simple_home: "mono",
  store_hotel: "stay",
  store_gallery_hotel: "stay",
  store_fastfood: "order",
  store_market: "order",
  store_gallery_entertainment: "coupons",
};

export function resolveHomePattern(storeId: string): HomePattern | null {
  return STORE_HOME_PATTERN[storeId] ?? null;
}

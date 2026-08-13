import type { CouponItem, MemberProfile, NewsItem, StoreSummary } from "./app";

export type DataMode = "live" | "preview" | "offline";

export interface RuntimeProductOption {
  id: string;
  name: string;
  priceDelta: number;
  isDefault: boolean;
}

export interface RuntimeProductOptionGroup {
  id: string;
  name: string;
  selectionType: "single" | "multiple";
  isRequired: boolean;
  minSelect: number;
  maxSelect: number;
  options: RuntimeProductOption[];
}

export interface RuntimeCatalogItem {
  id: string;
  name: string;
  description: string;
  price: number;
  image?: string;
  category?: string;
  optionGroups?: RuntimeProductOptionGroup[];
}

export interface RuntimeHistoryItem {
  id: string;
  type: "order" | "purchase";
  date: string;
  title: string;
  summary: string;
  total: number;
  status: string;
}

export interface RuntimeBootstrap {
  member: MemberProfile;
  coupons: CouponItem[];
  news: NewsItem[];
  stores: StoreSummary[];
  usedCouponIds: string[];
  stampCount: number;
  notificationCount: number;
  commerce?: {
    items: RuntimeCatalogItem[];
    pickupEstimate: string;
    dineInDetail: string;
  };
  reservationAvailability?: {
    dates: string[];
    times: string[];
    partySizes: number[];
  };
  queueStatus?: {
    waitingGroups: number;
    estimatedMinutes: number;
  };
  history?: RuntimeHistoryItem[];
}

export interface OrderLineInput {
  itemId: string;
  name: string;
  quantity: number;
  unitPrice: number;
  optionIds?: string[];
  optionNames?: string[];
  notes?: string;
}

export interface OrderInput {
  storeId: string;
  venueName: string;
  fulfillment: "pickup" | "dine_in";
  /** pickup 時のみ。未指定ならカード決済 */
  paymentMethod?: "stripe" | "cash";
  tableNumber?: string;
  lines: OrderLineInput[];
  total: number;
}

export interface ReservationInput {
  storeId: string;
  venueName: string;
  dateLabel: string;
  time: string;
  partySize: number;
  plan: string;
}

export interface QueueInput {
  storeId: string;
  partySize: number;
}

export interface ContactInput {
  category: string;
  message: string;
  email: string;
}

export interface NotificationSettings {
  news: boolean;
  coupons: boolean;
  reservations: boolean;
  location: boolean;
}

export interface OperationReceipt {
  reference: string;
  acceptedAt: string;
  status: "accepted" | "queued";
  orderId?: number;
  totalAmount?: number;
  needsPayment?: boolean;
  stampCardId?: number;
  currentStamps?: number;
  grantedCount?: number;
  message?: string;
}

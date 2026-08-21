import type { CouponItem, FeatureConfig, FeatureType, MemberProfile, NewsItem, StoreAppConfig, StoreSummary, TabType } from "../types/app";
import type { ThemeConfig } from "../types/theme";

/**
 * ヒーロー画像は Unsplash License（https://unsplash.com/license）で取得し public/images に配置。
 * 商用・非商用ともに利用可。帰属は任意。Unsplash API は使っていない。
 */
export type IndustryVisualStyle =
  | "modern"
  | "editorial"
  | "natural"
  | "dining"
  | "warm"
  | "elegant"
  | "clinical"
  | "active"
  | "luxury"
  | "utility"
  | "playful"
  | "neon";

export type IndustryBlockType =
  | "hero"
  | "products"
  | "lookbook"
  | "flyer"
  | "recipe"
  | "menu"
  | "mobileOrder"
  | "stamp"
  | "memberCard"
  | "queue"
  | "reservation"
  | "staff"
  | "medicalCard"
  | "schedule"
  | "activity"
  | "roomKey"
  | "facilityGuide"
  | "serviceProgress"
  | "petProfile"
  | "vaccination"
  | "ticket"
  | "event"
  | "map"
  | "coupon"
  | "news";

export interface IndustryGalleryItem {
  name: string;
  price: string;
  desc?: string;
}

export interface IndustryGalleryNews {
  date: string;
  title: string;
}

export interface IndustryGalleryTemplate {
  id: string;
  storeId: string;
  themeId: string;
  industry: string;
  name: string;
  description: string;
  appName: string;
  style: IndustryVisualStyle;
  primary: string;
  accent: string;
  background: string;
  surface: string;
  text: string;
  image: string;
  radius: number;
  blocks: IndustryBlockType[];
  tabs: Array<[TabType, string]>;
  items: IndustryGalleryItem[];
  news: IndustryGalleryNews[];
  memberName: string;
  memberId: string;
  memberRank: string;
  memberPoints: number;
  stampCurrent: number;
  stampTotal: number;
  couponTitle: string;
  couponDesc: string;
  address: string;
  phone: string;
  hours: string;
  access: string;
}

export const industryBlockLabels: Record<IndustryBlockType, string> = {
  hero: "メインビジュアル",
  products: "商品一覧",
  lookbook: "ルックブック",
  flyer: "デジタルチラシ",
  recipe: "レシピ提案",
  menu: "メニュー",
  mobileOrder: "モバイルオーダー",
  stamp: "スタンプカード",
  memberCard: "デジタル会員証",
  queue: "順番待ち",
  reservation: "予約",
  staff: "スタッフ紹介",
  medicalCard: "診察券",
  schedule: "スケジュール",
  activity: "活動記録",
  roomKey: "デジタルルームキー",
  facilityGuide: "施設案内",
  serviceProgress: "依頼進捗",
  petProfile: "ペットプロフィール",
  vaccination: "予防接種記録",
  ticket: "デジタルチケット",
  event: "イベント",
  map: "施設マップ",
  coupon: "クーポン",
  news: "お知らせ",
};

const off: FeatureConfig = {
  membership: false,
  mobileOrder: false,
  dineInOrder: false,
  reservation: false,
  queue: false,
  coupon: false,
  stamp: false,
  point: false,
  menu: false,
  gallery: false,
  news: false,
  storeSearch: false,
  purchaseHistory: false,
  orderHistory: false,
  favorite: false,
  ticket: false,
  subscription: false,
};

export const industryGalleryTemplates: IndustryGalleryTemplate[] = [
  {
    id: "retail", storeId: "store_gallery_retail", themeId: "gallery-retail",
    industry: "小売・専門店", name: "Modern Retail", description: "商品、クーポン、会員施策を中心とした店舗アプリ",
    appName: "MACHI STORE", style: "modern",
    primary: "#2557d6", accent: "#f6a623", background: "#f3f6fb", surface: "#ffffff", text: "#182238",
    image: "/images/retail.jpg", radius: 18,
    blocks: ["hero", "coupon", "products", "news"],
    tabs: [["home", "ホーム"], ["menu", "商品"], ["membership", "会員証"], ["news", "お知らせ"]],
    items: [{ name: "新着アイテム", price: "¥1,280" }, { name: "スタッフセレクト", price: "¥980" }],
    news: [{ date: "08.20", title: "新しいキャンペーンが始まりました" }, { date: "08.16", title: "営業時間変更のお知らせ" }],
    memberName: "山田 太郎", memberId: "2048 7813", memberRank: "MEMBER", memberPoints: 1280,
    stampCurrent: 7, stampTotal: 10, couponTitle: "お会計から10%OFF", couponDesc: "今月末まで利用可能",
    address: "東京都渋谷区神宮前1-2-3", phone: "03-1111-2001", hours: "11:00 – 20:00", access: "表参道駅 徒歩4分",
  },
  {
    id: "apparel", storeId: "store_gallery_apparel", themeId: "gallery-apparel",
    industry: "アパレル", name: "Editorial Fashion", description: "雑誌のようなビジュアルとコレクション訴求",
    appName: "LUMIÈRE", style: "editorial",
    primary: "#161616", accent: "#d2385a", background: "#f4f1ee", surface: "#ffffff", text: "#171717",
    image: "/images/apparel.jpg", radius: 2,
    blocks: ["hero", "lookbook", "products"],
    tabs: [["home", "LOOK"], ["menu", "ITEMS"], ["membership", "MEMBER"], ["news", "JOURNAL"]],
    items: [{ name: "ウールチェスターコート", price: "¥52,800" }, { name: "シルクブラウス", price: "¥18,600" }],
    news: [{ date: "08.20", title: "秋冬コレクション入荷" }, { date: "08.15", title: "会員様限定プレセール" }],
    memberName: "佐藤 美咲", memberId: "LM-2048", memberRank: "BLACK", memberPoints: 8420,
    stampCurrent: 4, stampTotal: 8, couponTitle: "会員先行 15%OFF", couponDesc: "新作コレクション対象",
    address: "東京都港区南青山3-1-8", phone: "03-1111-2002", hours: "11:00 – 20:00", access: "表参道駅 徒歩6分",
  },
  {
    id: "grocery", storeId: "store_gallery_grocery", themeId: "gallery-grocery",
    industry: "食品・スーパー", name: "Fresh Market", description: "特売、チラシ、レシピ、ポイントをまとめて提供",
    appName: "まいにち市場", style: "natural",
    primary: "#258249", accent: "#f47b20", background: "#f3f8ee", surface: "#ffffff", text: "#233228",
    image: "/images/grocery.jpg", radius: 18,
    blocks: ["hero", "flyer", "coupon", "recipe"],
    tabs: [["home", "ホーム"], ["menu", "チラシ"], ["membership", "ポイント"], ["news", "お知らせ"]],
    items: [{ name: "とちおとめ 1パック", price: "¥498" }, { name: "本日の惣菜弁当", price: "¥498" }],
    news: [{ date: "08.20", title: "今朝の入荷：旬のさくらんぼ" }, { date: "08.12", title: "ポイント2倍デーのお知らせ" }],
    memberName: "高橋 健", memberId: "MK-2560", memberRank: "GREEN", memberPoints: 2560,
    stampCurrent: 8, stampTotal: 10, couponTitle: "青果 10%OFF", couponDesc: "会員限定価格",
    address: "東京都世田谷区経堂1-4-2", phone: "03-1111-2003", hours: "8:00 – 21:00", access: "経堂駅 徒歩2分",
  },
  {
    id: "restaurant", storeId: "store_gallery_restaurant", themeId: "gallery-restaurant",
    industry: "レストラン・居酒屋", name: "Dining Experience", description: "メニュー、予約、順番待ちに対応した飲食店向け",
    appName: "旬菜ダイニング", style: "dining",
    primary: "#7f1d2d", accent: "#c9a45c", background: "#f8f2e9", surface: "#fffdf8", text: "#35251e",
    image: "/images/restaurant.jpg", radius: 18,
    blocks: ["hero", "menu", "news"],
    tabs: [["home", "ホーム"], ["menu", "メニュー"], ["queue", "順番待ち"], ["stamp", "スタンプ"]],
    items: [{ name: "季節のスペシャル", price: "¥1,280" }, { name: "店舗限定メニュー", price: "¥980" }],
    news: [{ date: "08.20", title: "今夜の仕込み：春野菜の炊き合わせ" }, { date: "08.10", title: "貸切予約の受付を開始" }],
    memberName: "伊藤 裕", memberId: "SD-1980", memberRank: "MEMBER", memberPoints: 1980,
    stampCurrent: 7, stampTotal: 10, couponTitle: "デザート1品サービス", couponDesc: "ディナーご利用のお客様",
    address: "東京都千代田区有楽町2-5-1", phone: "03-1111-2004", hours: "11:30 – 22:00", access: "有楽町駅 徒歩3分",
  },
  {
    id: "cafe", storeId: "store_gallery_cafe", themeId: "gallery-cafe",
    industry: "カフェ・ベーカリー", name: "Warm Cafe", description: "モバイルオーダーとスタンプを中心としたカフェ向け",
    appName: "NEST COFFEE", style: "warm",
    primary: "#704936", accent: "#d59a61", background: "#f7efe4", surface: "#fffaf3", text: "#352923",
    image: "/images/cafe.jpg", radius: 18,
    blocks: ["hero", "mobileOrder", "news"],
    tabs: [["home", "ホーム"], ["mobileOrder", "オーダー"], ["stamp", "スタンプ"], ["membership", "会員証"]],
    items: [{ name: "本日のコーヒー", price: "¥650" }, { name: "カフェラテ", price: "¥580" }],
    news: [{ date: "08.20", title: "Ethiopia Guji の浅煎りを本日解禁" }, { date: "08.08", title: "モーニングセット開始" }],
    memberName: "小林 恵", memberId: "NC-0320", memberRank: "BREW", memberPoints: 320,
    stampCurrent: 7, stampTotal: 10, couponTitle: "10杯で1杯無料", couponDesc: "スタンプカード連動",
    address: "東京都目黒区自由が丘1-9-4", phone: "03-1111-2005", hours: "8:00 – 19:00", access: "自由が丘駅 徒歩3分",
  },
  {
    id: "beauty", storeId: "store_gallery_beauty", themeId: "gallery-beauty",
    industry: "美容・サロン", name: "Beauty Booking", description: "施術事例、スタッフ指名、来店予約を重視",
    appName: "mellow salon", style: "elegant",
    primary: "#9c5c87", accent: "#d6a7bd", background: "#fbf4f7", surface: "#ffffff", text: "#392d35",
    image: "/images/beauty.jpg", radius: 18,
    blocks: ["hero", "lookbook", "reservation", "staff"],
    tabs: [["home", "スタイル"], ["reservation", "予約"], ["membership", "会員"], ["news", "お知らせ"]],
    items: [{ name: "カット", price: "¥6,600" }, { name: "カット＋カラー", price: "¥13,200" }],
    news: [{ date: "08.20", title: "春のカラー新規 20% OFF" }, { date: "08.05", title: "新スタイリスト加入" }],
    memberName: "中村 葵", memberId: "MS-2100", memberRank: "CALM", memberPoints: 2100,
    stampCurrent: 3, stampTotal: 5, couponTitle: "カット500円引き", couponDesc: "5回来店で利用可",
    address: "東京都渋谷区富ヶ谷1-4-7", phone: "03-1111-2006", hours: "10:00 – 19:00", access: "代々木公園駅 徒歩5分",
  },
  {
    id: "medical", storeId: "store_gallery_medical", themeId: "gallery-medical",
    industry: "医療・クリニック", name: "Health Support", description: "診察券、受付、待ち人数を見やすく表示",
    appName: "まちのクリニック", style: "clinical",
    primary: "#1687a7", accent: "#43b48b", background: "#eef8fa", surface: "#ffffff", text: "#18333a",
    image: "/images/medical.jpg", radius: 18,
    blocks: ["queue", "reservation", "news"],
    tabs: [["home", "受付"], ["membership", "診察券"], ["reservation", "予約"], ["news", "お知らせ"]],
    items: [{ name: "一般診療", price: "保険" }, { name: "予防接種", price: "予約必須" }],
    news: [{ date: "08.20", title: "本日の診療について" }, { date: "08.12", title: "オンライン資格確認に対応" }],
    memberName: "加藤 幸子", memberId: "MC-1118", memberRank: "かかりつけ", memberPoints: 0,
    stampCurrent: 0, stampTotal: 10, couponTitle: "健診予約リマインド", couponDesc: "年1回の特定健診",
    address: "東京都杉並区阿佐谷北1-2-1", phone: "03-1111-2007", hours: "9:00 – 18:00", access: "阿佐ヶ谷駅 徒歩4分",
  },
  {
    id: "fitness", storeId: "store_gallery_fitness", themeId: "gallery-fitness",
    industry: "フィットネス", name: "Active Fitness", description: "レッスン予約、チェックイン、活動記録を提供",
    appName: "MOVE+", style: "active",
    primary: "#eb5b22", accent: "#f4c430", background: "#17191d", surface: "#24272d", text: "#ffffff",
    image: "/images/fitness.jpg", radius: 18,
    blocks: ["hero", "schedule", "activity"],
    tabs: [["home", "TODAY"], ["reservation", "レッスン"], ["membership", "入館"], ["news", "お知らせ"]],
    items: [{ name: "Morning Yoga", price: "10:00" }, { name: "Night Training", price: "19:00" }],
    news: [{ date: "08.20", title: "体験トレーニング 60分 無料" }, { date: "08.01", title: "夏のキャンペーン開始" }],
    memberName: "鈴木 蓮", memberId: "MV-8800", memberRank: "REGULAR", memberPoints: 0,
    stampCurrent: 8, stampTotal: 12, couponTitle: "体験レッスン無料", couponDesc: "要予約",
    address: "東京都新宿区西新宿1-8-1", phone: "03-1111-2008", hours: "6:00 – 23:00", access: "新宿駅 徒歩7分",
  },
  {
    id: "hotel", storeId: "store_gallery_hotel", themeId: "gallery-hotel",
    industry: "宿泊・観光", name: "Stay Experience", description: "宿泊予約、ルームキー、館内案内を統合",
    appName: "UMIKAZE HOTEL", style: "luxury",
    primary: "#0c6964", accent: "#c8a464", background: "#edf4f2", surface: "#ffffff", text: "#173330",
    image: "/images/hotel.jpg", radius: 18,
    blocks: ["hero", "reservation", "news"],
    tabs: [["home", "STAY"], ["membership", "ROOM KEY"], ["store", "GUIDE"], ["reservation", "BOOKING"]],
    items: [{ name: "スタンダードダブル", price: "¥18,400" }, { name: "スイート 街側", price: "¥42,000" }],
    news: [{ date: "08.20", title: "週末のレイトチェックアウトを開始" }, { date: "08.08", title: "スパ日帰りプラン" }],
    memberName: "渡辺 博", memberId: "UH-1208", memberRank: "GOLD", memberPoints: 12400,
    stampCurrent: 0, stampTotal: 10, couponTitle: "朝食付きアップグレード", couponDesc: "GOLD会員限定",
    address: "神奈川県鎌倉市由比ヶ浜2-1-1", phone: "0467-111-2009", hours: "チェックイン 15:00", access: "鎌倉駅 徒歩12分",
  },
  {
    id: "service", storeId: "store_gallery_service", themeId: "gallery-service",
    industry: "生活サービス", name: "Service Counter", description: "受付から完了までの進捗を分かりやすく管理",
    appName: "くらしサポート", style: "utility",
    primary: "#315a7d", accent: "#3e9bc7", background: "#f0f4f7", surface: "#ffffff", text: "#223342",
    image: "/images/service.jpg", radius: 18,
    blocks: ["hero", "reservation", "serviceProgress"],
    tabs: [["home", "依頼"], ["queue", "進捗"], ["news", "通知"], ["more", "履歴"]],
    items: [{ name: "宅配クリーニング", price: "集荷可" }, { name: "靴修理", price: "見積無料" }],
    news: [{ date: "08.20", title: "コート早期割引 3月末まで" }, { date: "08.04", title: "集荷エリアを拡大" }],
    memberName: "斎藤 翔", memberId: "KS-1800", memberRank: "MEMBER", memberPoints: 890,
    stampCurrent: 7, stampTotal: 10, couponTitle: "ワイシャツ1点サービス", couponDesc: "10点お預かりで",
    address: "東京都練馬区石神井町3-2-5", phone: "03-1111-2010", hours: "9:00 – 19:00", access: "石神井公園駅 徒歩5分",
  },
  {
    id: "pet", storeId: "store_gallery_pet", themeId: "gallery-pet",
    industry: "ペット", name: "Pet Life", description: "ペット情報、予防接種、トリミング予約を管理",
    appName: "PAW & LIFE", style: "playful",
    primary: "#ee7b35", accent: "#2eb6a5", background: "#fff6e9", surface: "#ffffff", text: "#3c3129",
    image: "/images/pet.jpg", radius: 18,
    blocks: ["hero", "petProfile", "news"],
    tabs: [["home", "ホーム"], ["membership", "ペット"], ["reservation", "予約"], ["menu", "ショップ"]],
    items: [{ name: "国産ドライフード 2kg", price: "¥4,180" }, { name: "トリミング 小型犬", price: "¥5,500" }],
    news: [{ date: "08.20", title: "トリミングの空き枠をアプリから予約" }, { date: "08.02", title: "夏のケアキャンペーン" }],
    memberName: "田中 裕子", memberId: "PL-5500", memberRank: "FAMILY", memberPoints: 420,
    stampCurrent: 5, stampTotal: 10, couponTitle: "おやつ1袋プレゼント", couponDesc: "10回来店で",
    address: "東京都武蔵野市吉祥寺南町1-6-2", phone: "03-1111-2011", hours: "10:00 – 19:00", access: "吉祥寺駅 徒歩6分",
  },
  {
    id: "entertainment", storeId: "store_gallery_entertainment", themeId: "gallery-entertainment",
    industry: "レジャー・イベント", name: "Event Pass", description: "イベント、チケット、施設マップを中心に構成",
    appName: "FUN PARK", style: "neon",
    primary: "#7c3aed", accent: "#06b6d4", background: "#161329", surface: "#24203d", text: "#ffffff",
    image: "/images/entertainment.jpg", radius: 18,
    blocks: ["hero", "event"],
    tabs: [["home", "イベント"], ["membership", "チケット"], ["store", "マップ"], ["news", "お知らせ"]],
    items: [{ name: "SUMMER NIGHT FES", price: "18:30" }, { name: "WATER SHOW", price: "15:00" }],
    news: [{ date: "08.20", title: "ナイトフェス当日券の販売開始" }, { date: "08.11", title: "駐車場混雑のお知らせ" }],
    memberName: "山本 大輔", memberId: "FP-2026", memberRank: "VIP", memberPoints: 540,
    stampCurrent: 0, stampTotal: 10, couponTitle: "ワンデーパス 10%OFF", couponDesc: "平日限定",
    address: "千葉県浦安市舞浜1-1", phone: "047-111-2012", hours: "10:00 – 21:00", access: "舞浜駅 徒歩8分",
  },
];

export const industryGalleryByStoreId = Object.fromEntries(
  industryGalleryTemplates.map((item) => [item.storeId, item]),
) as Record<string, IndustryGalleryTemplate>;

export function resolveIndustryGalleryTemplate(storeId: string, appTemplateId?: string): IndustryGalleryTemplate {
  return industryGalleryByStoreId[appTemplateId ?? ""] ?? industryGalleryByStoreId[storeId] ?? industryGalleryTemplates[0];
}

function galleryFeatures(template: IndustryGalleryTemplate): FeatureConfig {
  const tabTypes = new Set(template.tabs.map(([type]) => type));
  const blocks = new Set(template.blocks);
  return {
    ...off,
    membership: tabTypes.has("membership") || blocks.has("memberCard") || blocks.has("medicalCard") || blocks.has("roomKey") || blocks.has("ticket"),
    mobileOrder: tabTypes.has("mobileOrder") || blocks.has("mobileOrder"),
    reservation: tabTypes.has("reservation") || blocks.has("reservation"),
    queue: tabTypes.has("queue") || blocks.has("queue") || blocks.has("serviceProgress"),
    coupon: tabTypes.has("coupon") || blocks.has("coupon"),
    stamp: tabTypes.has("stamp") || blocks.has("stamp"),
    point: tabTypes.has("point") || tabTypes.has("membership"),
    menu: tabTypes.has("menu") || blocks.has("products") || blocks.has("menu"),
    news: tabTypes.has("news") || blocks.has("news"),
    ticket: blocks.has("ticket") || blocks.has("event"),
    storeSearch: tabTypes.has("store") || blocks.has("map") || blocks.has("facilityGuide"),
    orderHistory: tabTypes.has("more"),
  };
}

function tabFeature(type: TabType): FeatureType {
  if (type === "mobileOrder" || type === "reservation" || type === "menu" || type === "stamp" || type === "coupon" || type === "membership" || type === "queue") {
    return type;
  }
  if (type === "news") {
    return "news";
  }
  if (type === "store") {
    return "storeSearch";
  }
  return "membership";
}

export const industryGalleryStores: StoreAppConfig[] = industryGalleryTemplates.map((template) => ({
  storeId: template.storeId,
  storeName: template.industry,
  brandName: template.name,
  logoText: template.appName.slice(0, 2).toUpperCase(),
  themeId: template.themeId,
  heroTitle: template.appName,
  heroSubtitle: template.description,
  home: {
    heroImage: template.image,
    heroEyebrow: template.industry,
    statusLabel: template.hours,
    primaryActionLabel: template.tabs[1]?.[1] ?? "詳しく見る",
    primaryActionFeature: tabFeature(template.tabs[1]?.[0] ?? "membership"),
    featuredImage: template.image,
    featuredTitle: template.items[0]?.name ?? template.appName,
    featuredDescription: template.description,
    featuredActionLabel: "詳しく見る",
    featuredFeature: tabFeature(template.tabs[1]?.[0] ?? "membership"),
  },
  features: galleryFeatures(template),
  tabs: template.tabs.map(([type, label], index) => ({ id: `${type}-${index}`, type, label, enabled: true, order: index + 1 })),
  quickActions: template.tabs.slice(1, 5).map(([type, label], index) => ({
    id: `qa-${template.id}-${index}`,
    feature: tabFeature(type),
    label,
    icon: "heart",
    order: index + 1,
  })),
  membershipCard: {
    templateId: "standard",
    backgroundType: "gradient",
    gradientFrom: template.primary,
    gradientTo: template.accent,
    textColor: "#FFFFFF",
    logoVisible: true,
    memberNameVisible: true,
    memberNumberVisible: true,
    rankVisible: true,
    pointVisible: true,
    barcodeVisible: true,
    borderRadius: template.radius,
    shadow: "small",
  },
}));

export const industryGalleryThemes: ThemeConfig[] = industryGalleryTemplates.map((template) => {
  const isDark = template.style === "active" || template.style === "neon";
  return {
    id: template.themeId,
    name: template.name,
    colors: {
      primary: template.primary,
      secondary: template.accent,
      accent: template.accent,
      background: template.background,
      surface: template.surface,
      elevated: template.surface,
      textPrimary: template.text,
      textSecondary: isDark ? "rgba(255,255,255,0.62)" : "#5A6B78",
      border: isDark ? "rgba(255,255,255,0.12)" : template.accent,
    },
    radius: { button: Math.max(4, template.radius - 6), card: template.radius, image: template.radius },
    typography: { headingWeight: template.style === "editorial" ? 600 : 700, bodyWeight: 500 },
    appearance: { cardStyle: isDark ? "flat" : "border", buttonStyle: "solid", iconStyle: "outline", density: "compact" },
  };
});

export function industryGalleryMemberProfiles(): Record<string, MemberProfile> {
  return Object.fromEntries(industryGalleryTemplates.map((template) => [template.storeId, {
    name: template.memberName,
    number: template.memberId,
    rank: template.memberRank,
    points: template.memberPoints,
    expiration: "2027-12-31",
    nextRankLabel: "",
    isMember: true,
  }]));
}

export function industryGalleryCoupons(): Record<string, CouponItem[]> {
  return Object.fromEntries(industryGalleryTemplates.map((template) => [template.storeId, [{
    id: `${template.id}-c1`,
    title: template.couponTitle,
    description: template.couponDesc,
    validUntil: "2026-09-30",
    image: template.image,
    featured: true,
  }]]));
}

export function industryGalleryNews(): Record<string, NewsItem[]> {
  return Object.fromEntries(industryGalleryTemplates.map((template) => [template.storeId, template.news.map((item, index) => ({
    id: `${template.id}-n${index}`,
    title: item.title,
    date: `2026-08-${String(20 - index).padStart(2, "0")}`,
    category: "お知らせ",
    image: template.image,
  }))]));
}

export function industryGalleryStoreSummaries(): Record<string, StoreSummary> {
  return Object.fromEntries(industryGalleryTemplates.map((template) => [template.storeId, {
    name: template.appName,
    address: template.address,
    openHours: template.hours,
    access: template.access,
    phone: template.phone,
  }]));
}

export function isIndustryGalleryStoreId(storeId: string): boolean {
  return storeId.startsWith("store_gallery_");
}

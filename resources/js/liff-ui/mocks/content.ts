import type { CouponItem, MemberProfile, NewsItem, StoreSummary } from "../types/app";
import { additionalCoupons, additionalMemberProfiles, additionalNews, additionalStoreSummaries } from "./additionalContent";

export const memberProfileByStore: Record<string, MemberProfile> = {
  store_cafe: {
    name: "山田 太郎",
    number: "000012345",
    rank: "Gold",
    points: 2450,
    expiration: "2027-03-31",
    nextRankLabel: "あと 550pt で Platinum",
  },
  store_apparel: {
    name: "佐藤 花子",
    number: "000045678",
    rank: "Silver",
    points: 980,
    expiration: "2027-01-31",
    nextRankLabel: "あと 120pt で Gold",
  },
  store_salon: {
    name: "鈴木 一郎",
    number: "000098765",
    rank: "Premium",
    points: 0,
    expiration: "2027-06-30",
    nextRankLabel: "次回ご来店で特典が更新されます",
  },
  ...additionalMemberProfiles,
};

export const couponsByStore: Record<string, CouponItem[]> = {
  store_cafe: [
    { id: "c1", title: "ドリンク 1杯 10% OFF", description: "ランチ後のカフェ利用に", validUntil: "2026-09-30", image: "/assets/cafe-hero.webp", featured: true },
    { id: "c2", title: "焼き菓子セット 100円引き", description: "テイクアウトにも利用可", validUntil: "2026-10-10", image: "/assets/cafe-feature.webp" },
    { id: "c3", title: "秋限定ブレンド 15% OFF", description: "9月から使える先行配信クーポン", validFrom: "2026-09-01", validUntil: "2026-10-31", image: "/assets/cafe-hero.webp" },
  ],
  store_apparel: [
    { id: "c1", title: "新作アイテム 500円OFF", description: "店舗・アプリ共通で利用可能", validUntil: "2026-09-15", image: "/assets/apparel-hero.webp", featured: true },
    { id: "c2", title: "2点以上購入でポイント2倍", description: "まとめ買いがお得", validUntil: "2026-09-30", image: "/assets/apparel-feature.webp" },
    { id: "c3", title: "アウター先行フェア", description: "9月から使える会員限定特典", validFrom: "2026-09-01", validUntil: "2026-10-15", image: "/assets/apparel-hero.webp" },
  ],
  store_salon: [
    { id: "c1", title: "前髪カット 15% OFF", description: "はじめての方にもおすすめ", validUntil: "2026-09-20", image: "/assets/salon-hero.webp", featured: true },
    { id: "c2", title: "ヘッドスパ 5分延長無料", description: "予約時に自動適用", validUntil: "2026-10-31", image: "/assets/salon-feature.webp" },
    { id: "c3", title: "秋の集中ケアご優待", description: "9月から利用できる先行特典", validFrom: "2026-09-01", validUntil: "2026-11-30", image: "/assets/salon-hero.webp" },
  ],
  ...additionalCoupons,
};

export const newsByStore: Record<string, NewsItem[]> = {
  store_cafe: [
    { id: "n1", title: "秋の新作ブレンドが登場", date: "2026-08-09", category: "NEWS" },
    { id: "n2", title: "モバイルオーダーの対応メニューを拡充", date: "2026-08-06", category: "INFO" },
  ],
  store_apparel: [
    { id: "n1", title: "週末限定のポイントアップ開催", date: "2026-08-08", category: "CAMPAIGN" },
    { id: "n2", title: "店頭受取サービスが利用可能に", date: "2026-08-04", category: "INFO" },
  ],
  store_salon: [
    { id: "n1", title: "夏の集中ケアメニューを公開", date: "2026-08-07", category: "NEWS" },
    { id: "n2", title: "予約画面をリニューアルしました", date: "2026-08-02", category: "INFO" },
  ],
  ...additionalNews,
};

export const storeSummaryByStore: Record<string, StoreSummary> = {
  store_cafe: {
    name: "渋谷 神南店",
    address: "東京都渋谷区神南1-2-3",
    openHours: "08:00 - 20:00",
    access: "渋谷駅 徒歩5分",
    phone: "03-1234-5678",
  },
  store_apparel: {
    name: "表参道店",
    address: "東京都港区北青山2-4-5",
    openHours: "11:00 - 20:00",
    access: "表参道駅 徒歩4分",
    phone: "03-9876-5432",
  },
  store_salon: {
    name: "銀座店",
    address: "東京都中央区銀座5-6-7",
    openHours: "10:00 - 19:00",
    access: "銀座駅 徒歩2分",
    phone: "03-5555-1212",
  },
  ...additionalStoreSummaries,
};

export const storeLocationsByStore: Record<string, StoreSummary[]> = {
  store_izakaya: [
    storeSummaryByStore.store_izakaya,
    { name: "有楽町 鳥灯り店", address: "東京都千代田区有楽町1-7-3", openHours: "17:00 - 23:30", access: "有楽町駅 日比谷口 徒歩2分", phone: "03-4100-2109" },
    { name: "浜松町 鳥灯り店", address: "東京都港区浜松町2-5-8", openHours: "17:00 - 24:00", access: "浜松町駅 南口 徒歩3分", phone: "03-4100-3109" },
  ],
  store_fastfood: [
    storeSummaryByStore.store_fastfood,
    { name: "原宿竹下口店", address: "東京都渋谷区神宮前1-19-6", openHours: "08:00 - 22:30", access: "原宿駅 竹下口 徒歩2分", phone: "03-4100-2110" },
    { name: "恵比寿駅前店", address: "東京都渋谷区恵比寿西1-8-2", openHours: "07:00 - 23:00", access: "恵比寿駅 西口 徒歩1分", phone: "03-4100-3110" },
  ],
};

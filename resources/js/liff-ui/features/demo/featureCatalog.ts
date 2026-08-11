import type { LucideIcon } from "lucide-react";
import {
  BookOpen,
  CalendarDays,
  Coins,
  Coffee,
  CreditCard,
  Heart,
  History,
  Images,
  MapPinned,
  Newspaper,
  ReceiptText,
  RefreshCcw,
  Stamp,
  Ticket,
  TicketPercent,
  Users,
  UtensilsCrossed,
} from "lucide-react";
import type { FeatureType } from "../../types/app";

export interface FeatureDefinition {
  type: FeatureType;
  label: string;
  shortLabel: string;
  description: string;
  icon: LucideIcon;
}

export const featureDefinitions: FeatureDefinition[] = [
  { type: "membership", label: "会員証", shortLabel: "会員証", description: "会員バーコードを表示", icon: CreditCard },
  { type: "mobileOrder", label: "モバイルオーダー", shortLabel: "注文", description: "受取注文。カード決済または店頭払い", icon: Coffee },
  { type: "dineInOrder", label: "店内注文", shortLabel: "店内注文", description: "テーブルから追加注文", icon: UtensilsCrossed },
  { type: "reservation", label: "予約", shortLabel: "予約", description: "日時を選んで予約", icon: CalendarDays },
  { type: "queue", label: "順番待ち", shortLabel: "順番待ち", description: "受付番号をオンライン発行", icon: Users },
  { type: "coupon", label: "クーポン", shortLabel: "クーポン", description: "会員限定特典を利用", icon: TicketPercent },
  { type: "stamp", label: "スタンプ", shortLabel: "スタンプ", description: "来店スタンプを貯める", icon: Stamp },
  { type: "point", label: "ポイント", shortLabel: "ポイント", description: "残高と利用履歴を確認", icon: Coins },
  { type: "menu", label: "メニュー・商品", shortLabel: "メニュー", description: "商品やサービスを探す", icon: BookOpen },
  { type: "gallery", label: "ギャラリー", shortLabel: "ギャラリー", description: "スタイル・店内写真を見る", icon: Images },
  { type: "news", label: "お知らせ", shortLabel: "お知らせ", description: "最新情報を確認", icon: Newspaper },
  { type: "storeSearch", label: "店舗検索", shortLabel: "店舗", description: "近くの店舗を探す", icon: MapPinned },
  { type: "purchaseHistory", label: "購入履歴", shortLabel: "購入履歴", description: "購入した商品を確認", icon: ReceiptText },
  { type: "orderHistory", label: "注文履歴", shortLabel: "注文履歴", description: "注文状況と履歴を確認", icon: History },
  { type: "favorite", label: "お気に入り", shortLabel: "お気に入り", description: "保存した商品を確認", icon: Heart },
  { type: "ticket", label: "チケット・回数券", shortLabel: "チケット", description: "保有チケットを利用", icon: Ticket },
  { type: "subscription", label: "サブスクリプション", shortLabel: "サブスク", description: "会員プランを管理", icon: RefreshCcw },
];

export const featureDefinitionMap = Object.fromEntries(
  featureDefinitions.map((definition) => [definition.type, definition]),
) as Record<FeatureType, FeatureDefinition>;

export function isFeatureType(value: string | undefined): value is FeatureType {
  return featureDefinitions.some((definition) => definition.type === value);
}

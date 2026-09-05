import { themeMap } from "../themes/themes";
import type { StoreAppConfig } from "../types/app";
import { industryGalleryTemplates } from "./industryGallery";

export type TemplateGroup = "simple" | "gallery" | "brand" | "operations" | "industry";

const STUDIO_TEMPLATE_IDS = ["store_member_crm", "store_simple_home", "store_simple_rewards"] as const;

export interface AppTemplate {
  store: StoreAppConfig;
  group: TemplateGroup;
  description: string;
  palette: [string, string, string];
  thumb: string;
}

const GROUP_ORDER: TemplateGroup[] = ["simple", "gallery", "brand", "operations", "industry"];

export const TEMPLATE_GROUP_LABELS: Record<TemplateGroup, string> = {
  simple: "会員アプリ",
  gallery: "業種ギャラリー",
  brand: "ブランド",
  operations: "店舗運営",
  industry: "店舗デザイン",
};

const TEMPLATE_META: Record<string, { group: TemplateGroup; description: string; shortName?: string; thumb: string }> = {
  store_member_crm: { group: "simple", description: "会員証・ポイント・クーポンの標準会員アプリ", shortName: "会員CRM", thumb: "crm" },
  store_simple_rewards: { group: "simple", description: "会員証・クーポン・スタンプだけの最小構成", shortName: "シンプルリワード", thumb: "simple" },
  store_simple_home: { group: "simple", description: "会員証とサービスボタン中心の簡潔な標準ホーム", shortName: "シンプルホーム", thumb: "minimal" },
  store_landing_showcase: { group: "brand", description: "LP掲載と同じスマホ画面", shortName: "LPスマホUI", thumb: "landing" },
  store_counter_operations: { group: "operations", description: "注文・順番待ち・受取をホームから操作", shortName: "店舗オペレーション・注文型", thumb: "counter" },
  store_appointment_operations: { group: "operations", description: "次回予約と空き枠を最優先にした来店構成", shortName: "店舗オペレーション・予約型", thumb: "appointment" },
  store_cafe: { group: "industry", description: "モバイルオーダーとリワードのカフェ構成", thumb: "standard" },
  store_apparel: { group: "industry", description: "商品と会員証を主役にした店舗構成", thumb: "standard" },
  store_salon: { group: "industry", description: "予約とスタイル提案のサロン構成", thumb: "standard" },
  store_bakery: { group: "industry", description: "取り置きとスタンプのベーカリー構成", thumb: "standard" },
  store_fitness: { group: "industry", description: "予約と記録のフィットネス構成", thumb: "standard" },
  store_flower: { group: "industry", description: "予約とギャラリーのフラワー構成", thumb: "standard" },
  store_hotel: { group: "industry", description: "宿泊予約と館内案内のホテル構成", thumb: "standard" },
  store_pets: { group: "industry", description: "ケア予約と特典のペット構成", thumb: "standard" },
  store_clinic: { group: "industry", description: "診療予約と待ち状況のクリニック構成", thumb: "standard" },
  store_market: { group: "industry", description: "注文と特売のマーケット構成", thumb: "standard" },
  store_dining: { group: "industry", description: "席予約と店内注文のレストラン構成", thumb: "standard" },
  store_izakaya: { group: "industry", description: "席予約と品書きの居酒屋構成", thumb: "standard" },
  store_fastfood: { group: "industry", description: "モバイルオーダー中心のファストフード構成", thumb: "standard" },
  ...Object.fromEntries(industryGalleryTemplates.map((item) => [item.storeId, {
    group: "gallery" as const,
    description: item.description,
    shortName: item.industry,
    thumb: item.style,
  }])),
};

function paletteFor(store: StoreAppConfig): [string, string, string] {
  const theme = themeMap[store.themeId];
  return [
    theme?.colors.primary ?? "#171717",
    theme?.colors.accent ?? "#888888",
    theme?.colors.background ?? "#f5f5f5",
  ];
}

export function designerTemplates(stores: StoreAppConfig[]): AppTemplate[] {
  return stores.map((store) => {
    const meta = TEMPLATE_META[store.storeId];
    return {
      store,
      group: meta?.group ?? "industry",
      description: meta?.description ?? store.brandName,
      palette: paletteFor(store),
      thumb: meta?.thumb ?? "standard",
    };
  });
}

export function studioDesignerTemplates(stores: StoreAppConfig[]): AppTemplate[] {
  const byId = new Map(designerTemplates(stores).map((item) => [item.store.storeId, item]));
  return STUDIO_TEMPLATE_IDS
    .map((id) => byId.get(id))
    .filter((item): item is AppTemplate => item !== undefined);
}

export function templatesByGroup(templates: AppTemplate[]): Array<{ group: TemplateGroup; label: string; items: AppTemplate[] }> {
  return GROUP_ORDER
    .map((group) => ({
      group,
      label: TEMPLATE_GROUP_LABELS[group],
      items: templates.filter((item) => item.group === group),
    }))
    .filter((row) => row.items.length > 0);
}

export function templateThemeLabel(store: StoreAppConfig, presetName?: string): string {
  return TEMPLATE_META[store.storeId]?.shortName ?? presetName ?? store.brandName;
}

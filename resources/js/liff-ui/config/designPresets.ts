import type { DesignPreset } from "../types/customization";

export interface DesignPresetDefinition {
  id: DesignPreset;
  name: string;
  description: string;
  palette: [string, string, string];
}

export const designPresets: DesignPresetDefinition[] = [
  { id: "editorial", name: "ブランド標準", description: "写真と情報をバランスよく見せる標準構成", palette: ["#49352a", "#c6744e", "#fffdf8"] },
  { id: "bold", name: "高コントラスト", description: "売場で見やすい太字と明確なボタン", palette: ["#17130f", "#ef542f", "#fffdf8"] },
  { id: "airy", name: "クリーン", description: "美容・花店に合う明るく清潔な構成", palette: ["#174b55", "#d66f55", "#fbfffe"] },
  { id: "minimal", name: "ミニマル", description: "商品写真を引き立てる無彩色の構成", palette: ["#151515", "#767676", "#ffffff"] },
  { id: "luxury", name: "プレミアム", description: "サロンや専門店向けの上質な構成", palette: ["#31251d", "#ad8650", "#fffcf7"] },
  { id: "pop", name: "カジュアル", description: "ファミリー業態に合う親しみやすい構成", palette: ["#5527d7", "#f2509d", "#fff3aa"] },
  { id: "natural", name: "ナチュラル", description: "食品・ベーカリー向けの穏やかな構成", palette: ["#35543b", "#b86543", "#faf8f0"] },
  { id: "monochrome", name: "モノクロ", description: "業務情報を端的に見せる整理された構成", palette: ["#050505", "#777777", "#ffffff"] },
  { id: "glass", name: "ソフトグラス", description: "宿泊・施設向けの落ち着いた透明感", palette: ["#4c46a8", "#2ea6a8", "#f7f8ff"] },
  { id: "neo", name: "デジタル", description: "フィットネスなど数値重視の構成", palette: ["#101a42", "#2fd7a0", "#ffffff"] },
  { id: "restaurant", name: "レストラン", description: "予約・店内注文を中心にした飲食店向け構成", palette: ["#342019", "#a63d2f", "#f7f1e8"] },
  { id: "izakaya", name: "居酒屋", description: "夜営業・席予約・テーブル注文を中心にした和食酒場向け構成", palette: ["#171c24", "#d48b34", "#f5f0e8"] },
  { id: "fastfood", name: "ファストフード", description: "注文とクーポンを迷わず使えるチェーン店向け構成", palette: ["#c62424", "#ffc93d", "#fff7e8"] },
];

export const designPresetMap = Object.fromEntries(designPresets.map((preset) => [preset.id, preset])) as Record<DesignPreset, DesignPresetDefinition>;

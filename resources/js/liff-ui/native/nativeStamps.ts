/** スタンプカードの見せ方。管理画面の「デザイン」「スタンプの並べ方」「個別スタンプ画像」「達成特典」をここで画面用に組み立てる。 */

export type StampCardDesign = "standard" | "banner" | "membership";
export type StampCardLayout = "grid" | "row" | "circle" | "minimal" | "card";
export type StampSlot = { position: number; filled: boolean; image?: string; reward: boolean };
type StampCardInput = {
  design?: string;
  layout_style?: string;
  required_stamps?: number | string;
  current_stamps?: number | string;
  stamp_images?: Record<string, string> | null;
  stamp_empty_images?: Record<string, string> | null;
  reward_milestones?: { stamp_count?: number | string; description?: string }[];
};

const designs: StampCardDesign[] = ["standard", "banner", "membership"];
const layouts: StampCardLayout[] = ["grid", "row", "circle", "minimal", "card"];
// 25個までは1行5個で全部並べ、それより多いときは1行10個の小さな枠で50個ずつページを分ける（row は横スクロールの1行に全部）
const SMALL_CARD = 25;
const PAGE_SIZE = 50;

/** ページは横にスワイプして切り替える。initialPage は次に押すスタンプがあるページ */
export function stampLayout(card: StampCardInput) {
  const design = (designs as string[]).includes(card.design ?? "") ? card.design as StampCardDesign : "standard";
  const name = (layouts as string[]).includes(card.layout_style ?? "") ? card.layout_style as StampCardLayout : "grid";
  const required = Math.max(1, Number(card.required_stamps) || 1);
  const current = Math.max(0, Number(card.current_stamps) || 0);
  const dense = name !== "row" && required > SMALL_CARD;
  const perPage = name === "row" ? required : dense ? PAGE_SIZE : SMALL_CARD;
  const milestones = (card.reward_milestones ?? [])
    .map((milestone) => ({ count: Number(milestone.stamp_count) || 0, description: milestone.description ?? "" }))
    .filter((milestone) => milestone.count > 0 && milestone.count <= required && milestone.description !== "")
    .sort((a, b) => a.count - b.count);
  const rewards = new Set(milestones.map((milestone) => milestone.count));
  const pages: StampSlot[][] = Array.from({ length: Math.ceil(required / perPage) }, (_, page) => {
    const first = page * perPage + 1;
    return Array.from({ length: Math.min(perPage, required - first + 1) }, (_, index) => {
      const position = first + index;
      const filled = position <= current;
      const image = (filled ? card.stamp_images : card.stamp_empty_images)?.[String(position)] || undefined;
      return { position, filled, image, reward: rewards.has(position) };
    });
  });
  const initialPage = Math.floor(Math.min(current, required - 1) / perPage);
  const columns = name === "row" ? undefined : dense ? 10 : 5;
  // 次にもらえる特典。途中の特典が残っていなければゴールを目標にする（補足テキストは別に出す）
  const upcoming = milestones.find((milestone) => milestone.count > current);
  const next = current >= required ? undefined : upcoming ?? { count: required, description: "" };
  const nextReward = next ? { ...next, remaining: next.count - current, goal: upcoming === undefined } : undefined;
  return { design, name, columns, dense, required, current, pages, initialPage, milestones, nextReward };
}

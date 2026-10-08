export type LotteryGame = 'gacha' | 'scratch' | 'roulette';
export type LotteryEntitlement = { id: number; reward_type: string; expires_at?: string };
export type LotteryState = { gacha_enabled: boolean; scratch_enabled: boolean; roulette_enabled?: boolean; entitlements: LotteryEntitlement[]; prize_labels: Partial<Record<LotteryGame, string[]>> };
export const lotteryGames: LotteryGame[] = ['gacha', 'scratch', 'roulette'];
export function playableEntitlements(state: LotteryState, played: number[]) {
  return (state.entitlements ?? []).filter(item => lotteryGames.includes(item.reward_type as LotteryGame) && state[`${item.reward_type as LotteryGame}_enabled`] && !played.includes(item.id));
}
export function scratchCells(x: number, y: number, width: number, height: number, current: number[]): number[] {
  if (width <= 0 || height <= 0) return current;
  const column = Math.max(0, Math.min(17, Math.floor(x / (width / 18))));
  const row = Math.max(0, Math.min(11, Math.floor(y / (height / 12))));
  const result = new Set(current);
  for (let r = row - 1; r <= row + 1; r++) for (let c = column - 1; c <= column + 1; c++) if (r >= 0 && r < 12 && c >= 0 && c < 18) result.add(r * 18 + c);
  return [...result];
}
export function rouletteLabels(configured: string[]) {
  const base = configured.length ? configured : ['ドリンク', 'デザート', '50pt', 'ハズレ'];
  return Array.from({ length: 8 }, (_, index) => base[index % base.length]);
}
export function rouletteTravel(pickedIndex: number, labelCount: number, currentRotation: number, reduceMotion: boolean) {
  const count = Math.max(1, labelCount), picked = Math.max(0, Math.min(count - 1, pickedIndex));
  return (reduceMotion ? 1 : 6 + picked) * 360 + 360 - (picked * 360 / count + 180 / count) - currentRotation % 360;
}

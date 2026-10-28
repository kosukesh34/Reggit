/** 宣伝ポップアップの型と、今回の起動で出すかの判定。保存先・通信・画面に依存しない。 */

export type PromoPopupFrequency = "once" | "daily" | "every_open";

export type PromoPopup = {
  id: number;
  title: string;
  body?: string | null;
  image_url?: string | null;
  display_type: "fullscreen" | "modal" | "bottom_sheet" | "banner";
  button_label?: string | null;
  link_url?: string | null;
  secondary_button_label?: string | null;
  secondary_link_url?: string | null;
  frequency: PromoPopupFrequency;
  version?: number | null;
};

export type PromoPopupLink = { kind: "none" } | { kind: "app"; path: string } | { kind: "external"; url: string };

export function localDay(date: Date): string {
  return `${date.getFullYear()}-${date.getMonth() + 1}-${date.getDate()}`;
}

/** 内容を編集したら表示済みを引き継がないよう、版を含めたキーにする */
export function popupSeenKey(popup: PromoPopup): string {
  return `${popup.id}:${popup.version ?? 0}`;
}

/** 端末に残した最後の表示日 seen から、今回の起動で表示するかを決める */
export function shouldShowPopup(frequency: PromoPopupFrequency, seen: string | null, today: string): boolean {
  if (frequency === "every_open" || seen === null) return true;
  return frequency === "daily" && seen !== today;
}

/** サーバーは優先度順に返すため、表示条件を満たす先頭の1件を選ぶ */
export function pickPopup(popups: PromoPopup[], today: string, shownThisLaunch: ReadonlySet<number>, lastSeen: (popup: PromoPopup) => string | null): PromoPopup | undefined {
  return popups.find((popup) => !shownThisLaunch.has(popup.id) && shouldShowPopup(popup.frequency, lastSeen(popup), today));
}

/** リンク先はアプリ内パスか https だけを開く（管理画面でも同じ条件で受け付けている） */
export function popupLink(raw: string | null | undefined): PromoPopupLink {
  const value = (raw ?? "").trim();
  if (value.startsWith("/") && !value.startsWith("//")) return { kind: "app", path: value };
  if (/^https:\/\//i.test(value)) return { kind: "external", url: value };
  return { kind: "none" };
}

/** アンケート回答画面の純粋ロジック。「その他」の自由入力をサーバーの形式（その他：本文）にまとめる。 */

export const OTHER_KEY = "__other__";
export const OTHER_PREFIX = "その他：";
export const OTHER_MAX = 200 - OTHER_PREFIX.length;

export type AnswerValue = string | number | string[];

const otherAnswer = (text: string | undefined): string | null => {
  const trimmed = (text ?? "").trim();
  return trimmed ? `${OTHER_PREFIX}${trimmed}` : null;
};

/** 入力中の値を送信用に変換する。「その他」を選んで本文が空なら未回答として扱う。 */
export function answerPayload(value: AnswerValue | undefined, otherText: string | undefined): AnswerValue | null {
  if (value === undefined || value === "") return null;
  if (value === OTHER_KEY) return otherAnswer(otherText);
  if (Array.isArray(value)) {
    const other = otherAnswer(otherText);
    const answers = value.flatMap((item) => (item === OTHER_KEY ? (other ? [other] : []) : [item]));
    return answers.length ? answers : null;
  }
  return value;
}

/** 回答済みとして数えるか（進捗表示用）。 */
export function isAnswered(value: AnswerValue | undefined, otherText: string | undefined): boolean {
  return answerPayload(value, otherText) !== null;
}

/** NPS の点数ごとの色分け（批判者・中立者・推奨者）。 */
export function npsTone(score: number): "detractor" | "passive" | "promoter" {
  return score >= 9 ? "promoter" : score >= 7 ? "passive" : "detractor";
}

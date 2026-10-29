/** 配信の最終確認で、LINEの今月の残り通数と比べる。画面に依存しない。 */

export type LineQuota = { used: number; limit: number | null };

/** LINE の送信先人数と1人あたりの通数（クーポン付きは2通）から、使う通数と残りを文章にする */
export function lineQuotaCheck(recipients: number, messagesPerRecipient: number, quota: LineQuota): { message: string; over: boolean } {
  const needed = recipients * messagesPerRecipient;
  const format = (value: number): string => value.toLocaleString('ja-JP');
  if (quota.limit === null) {
    return { message: `この配信でLINEを約${format(needed)}通使います（上限なし）。`, over: false };
  }
  const remaining = Math.max(0, quota.limit - quota.used);
  if (needed <= remaining) {
    return { message: `この配信でLINEを約${format(needed)}通使います。今月の残りは${format(remaining)}通です。`, over: false };
  }
  return {
    message: `この配信でLINEを約${format(needed)}通使いますが、今月の残りは${format(remaining)}通です。約${format(needed - remaining)}通は送れず、「LINE未送信の履歴」に送信エラーとして残ります。`,
    over: true,
  };
}

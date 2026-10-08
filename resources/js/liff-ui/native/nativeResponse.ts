/** V1 envelopes coexist with flat LIFF responses and Laravel paginators. */
export function unwrapNativeResponse<T>(value: unknown): T {
  if (value === null || value === undefined || typeof value !== 'object') throw new Error('応答を読み込めませんでした');
  const payload = value as { success?: boolean; data?: unknown; error?: string; message?: string };
  if (payload.success === false) throw new Error(payload.error || payload.message || '通信に失敗しました');
  return (payload.success === true && payload.data !== undefined ? payload.data : payload) as T;
}

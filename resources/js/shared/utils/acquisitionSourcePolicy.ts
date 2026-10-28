/** 端末に残した流入経路の記録を、使ってよいか判定する（保存先に依存しない）。 */
export const ACQUISITION_CODE_PATTERN = /^[0-9a-f]{10}$/;
/** QRを読んでから登録までに許す日数。古い経路を後の登録に付けない */
const VALID_DAYS = 30;

export type StoredSource = { code: string; projectKey: string; savedAt: number };

export function parseStoredSource(raw: string | null, projectKey: string, now: number): string | undefined {
    if (raw === null) return undefined;
    try {
        const stored = JSON.parse(raw) as Partial<StoredSource>;
        const fresh = typeof stored.savedAt === 'number' && now - stored.savedAt <= VALID_DAYS * 86_400_000;
        return fresh && stored.projectKey === projectKey && typeof stored.code === 'string' && ACQUISITION_CODE_PATTERN.test(stored.code) ? stored.code : undefined;
    } catch {
        return undefined;
    }
}

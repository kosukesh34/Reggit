/** 流入経路QR（?src=）の code を端末に残し、同じプロジェクトで会員登録するときに送る。 */
import { launchParamFromUrl } from './launchParam';
import { ACQUISITION_CODE_PATTERN, parseStoredSource, type StoredSource } from './acquisitionSourcePolicy';

const STORAGE_KEY = 'reggit_acquisition_source';
function currentProjectKey(): string {
    return String(window.__PROJECT_KEY__ ?? new URLSearchParams(window.location.search).get('project_key') ?? '');
}

export function captureAcquisitionSource(): void {
    const code = launchParamFromUrl(window.location.href, 'src');
    if (!ACQUISITION_CODE_PATTERN.test(code)) {
        return;
    }
    try {
        const stored: StoredSource = { code, projectKey: currentProjectKey(), savedAt: Date.now() };
        window.localStorage.setItem(STORAGE_KEY, JSON.stringify(stored));
    } catch {
        // 保存できない端末では経路を記録しない
    }
}

export function getAcquisitionSource(): string | undefined {
    try {
        return parseStoredSource(window.localStorage.getItem(STORAGE_KEY), currentProjectKey(), Date.now());
    } catch {
        return undefined;
    }
}

/** 登録が済んだら消し、同じ端末の次の登録に引き継がない */
export function clearAcquisitionSource(): void {
    try {
        window.localStorage.removeItem(STORAGE_KEY);
    } catch {
        // 消せなくても期限で使われなくなる
    }
}

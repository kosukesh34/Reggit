/** 起動 URL のクエリ、または LIFF が包んだ liff.state から値を取り出す。 */
export function launchParamFromUrl(href: string, name: string): string {
    try {
        const url = new URL(href);
        const state = url.searchParams.get('liff.state') || url.searchParams.get('state') || '';
        const stateQuery = state.includes('?') ? state.slice(state.indexOf('?') + 1) : state;
        const hashQuery = url.hash.includes('?') ? url.hash.slice(url.hash.indexOf('?') + 1) : '';
        return url.searchParams.get(name) || new URLSearchParams(stateQuery).get(name) || new URLSearchParams(hashQuery).get(name) || '';
    } catch {
        return '';
    }
}

/** アンケートは開く対象を画面のパスに持たせ、起動URLに残った値で一覧に戻れなくならないようにする */
export function withSurveyLaunchQuery(path: string, href: string): string {
    if (path !== '/survey') {
        return path;
    }
    const surveyId = launchParamFromUrl(href, 'survey');
    return /^\d+$/.test(surveyId) ? `/survey?survey=${surveyId}` : path;
}

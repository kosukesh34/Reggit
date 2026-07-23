/** LP / デザイン確認用デモ（サンプルデータ固定）。見た目の ios-fixed-ui とは独立。 */
export function isIosFixedUiDemo(): boolean {
    if (typeof document === 'undefined' || document.body === null) {
        return false;
    }
    if (document.body.classList.contains('ios-demo-data')) {
        return true;
    }
    try {
        const params = new URLSearchParams(window.location.search || '');
        return params.get('demo') === '1';
    } catch (_e) {
        return false;
    }
}

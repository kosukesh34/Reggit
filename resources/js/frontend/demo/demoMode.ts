/**
 * サンプル体験モード（会員・クーポン等をDBではなくサンプル表示）。
 * /liff 固定UIでは常時オン。店舗LIFF（/liff/{project_key}）は通常API/DBを使う。
 */
export function isDemoDataMode(): boolean {
    if (typeof document === 'undefined' || document.body === null) {
        return false;
    }
    if (document.body.classList.contains('liff-demo-data')
        || document.body.classList.contains('ios-demo-data')) {
        return true;
    }
    return false;
}

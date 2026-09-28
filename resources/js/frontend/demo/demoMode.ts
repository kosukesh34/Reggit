/** LIFF 固定UIシェルかどうか（/liff デモ体験用。iOS専用ではない） */
export function isFixedUiShell(): boolean {
    if (typeof document === 'undefined' || document.body === null) {
        return false;
    }
    return document.body.classList.contains('liff-fixed-ui')
        || document.body.classList.contains('ios-fixed-ui');
}

/**
 * クーポン・スタンプ・予約・順番待ちなどの機能画面で、ios-* スタイルを使うか。
 * DesignMock（reggit-liff-ui）では ?demo=1 でなくても有効にする。
 */
export function isFeatureUiShell(): boolean {
    if (typeof document === 'undefined' || document.body === null) {
        return false;
    }
    if (isFixedUiShell()) {
        return true;
    }
    return document.body.classList.contains('reggit-liff-ui');
}

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

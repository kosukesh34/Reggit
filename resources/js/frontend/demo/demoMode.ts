/** LIFF 固定UIシェルかどうか（/liff デモ体験用。iOS専用ではない） */
export function isFixedUiShell(): boolean {
    if (typeof document === 'undefined' || document.body === null) {
        return false;
    }
    return document.body.classList.contains('liff-fixed-ui')
        || document.body.classList.contains('ios-fixed-ui');
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
    try {
        const params = new URLSearchParams(window.location.search || '');
        return params.get('demo') === '1';
    } catch (_e) {
        return false;
    }
}

/** @deprecated isDemoDataMode を使う */
export function isIosFixedUiDemo(): boolean {
    return isDemoDataMode();
}

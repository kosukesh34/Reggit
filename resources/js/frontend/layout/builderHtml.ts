/**
 * Designer ブロック描画の共有入口。
 * マークアップ生成は LiffLayoutManager.renderCustomLayout と同一（見た目ゼロ差分）。
 */
export function paintBuilderLayout(container: HTMLElement, tab: string, blocks: unknown[]): boolean {
    const app = window.app;
    if (app === undefined || app === null || typeof app.layoutManager?.renderCustomLayout !== 'function') {
        return false;
    }
    if (!Array.isArray(blocks) || blocks.length === 0) {
        return false;
    }
    app.layoutManager.renderCustomLayout(container, tab, blocks);
    return true;
}

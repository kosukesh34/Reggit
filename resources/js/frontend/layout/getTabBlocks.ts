/** 店舗 Designer のタブ別ブロック配列を返す。無ければ null。 */
export function getTabBlocks(tab: string): unknown[] | null {
    const layouts = window.__LIFF_TAB_LAYOUTS__;
    if (layouts === undefined || layouts === null || typeof layouts !== 'object') {
        return null;
    }
    const blocks = (layouts as Record<string, unknown>)[tab];
    return Array.isArray(blocks) && blocks.length > 0 ? blocks : null;
}

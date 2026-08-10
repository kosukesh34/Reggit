/** React が所有するマウント（innerHTML で上書きしない） */
export function isReactOwnedMount(el: Element | null | undefined): boolean {
    if (el === null || el === undefined) {
        return false;
    }
    const html = el as HTMLElement;
    if (html.dataset.reactOwned === '1') {
        return true;
    }
    return html.closest('[data-react-owned="1"]') !== null;
}

import type { SelectedCoupon } from './types';

/** お知らせ配信画面の右側に、入力中の内容が LINE・アプリでどう届くかを表示する。 */
export function initMessagePreview(form: HTMLFormElement, selectedCoupons: () => SelectedCoupon[]): void {
    const root = document.getElementById('messagePreview');
    if (!root) return;
    const title = form.querySelector<HTMLInputElement>('#title');
    const body = form.querySelector<HTMLTextAreaElement>('#body');
    const images = form.querySelector<HTMLInputElement>('#ann_image');
    const notify = form.querySelector<HTMLInputElement>('#send_notification');
    const pinned = form.querySelector<HTMLInputElement>('#is_pinned');
    const all = (selector: string): HTMLElement[] => Array.from(root.querySelectorAll<HTMLElement>(selector));
    let imageUrl = '';

    function render(): void {
        const titleText = title?.value.trim() ?? '';
        const bodyText = body?.value.trim() ?? '';
        all('[data-preview-title]').forEach((element) => {
            element.textContent = titleText || 'タイトルを入力してください';
            element.classList.toggle('is-placeholder', titleText === '');
        });
        all('[data-preview-body]').forEach((element) => {
            element.textContent = bodyText;
            element.hidden = bodyText === '';
        });

        const imageCount = images?.files?.length ?? 0;
        all('[data-preview-media]').forEach((element) => { element.hidden = imageUrl === ''; });
        all('[data-preview-image]').forEach((element) => { if (imageUrl !== '') element.setAttribute('src', imageUrl); });
        all('[data-preview-more]').forEach((element) => {
            element.hidden = imageCount < 2;
            element.textContent = `全${imageCount}枚`;
        });

        const sending = notify?.checked !== false;
        all('[data-preview-notify-on]').forEach((element) => { element.hidden = !sending; });
        all('[data-preview-notify-off]').forEach((element) => { element.hidden = sending; });
        all('[data-preview-pinned]').forEach((element) => { element.hidden = pinned?.checked !== true; });

        const couponNames = selectedCoupons().map((coupon) => coupon.name).join('・');
        all('[data-preview-coupon]').forEach((element) => { element.hidden = couponNames === ''; });
        all('[data-preview-coupon-name]').forEach((element) => { element.textContent = couponNames; });
    }

    images?.addEventListener('change', () => {
        if (imageUrl !== '') URL.revokeObjectURL(imageUrl);
        const first = images.files?.[0];
        imageUrl = first ? URL.createObjectURL(first) : '';
        render();
    });
    form.addEventListener('input', render);
    form.addEventListener('change', render);

    const tabs = all('[data-preview-tab]');
    tabs.forEach((tab) => tab.addEventListener('click', () => {
        tabs.forEach((other) => other.setAttribute('aria-pressed', String(other === tab)));
        all('[data-preview-pane]').forEach((pane) => { pane.hidden = pane.dataset.previewPane !== tab.dataset.previewTab; });
    }));

    render();
}

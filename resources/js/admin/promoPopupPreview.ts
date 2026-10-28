/** ポップアップ作成画面のプレビュー。入力に合わせて、スマホでの見え方を4形式で描き分ける。 */

type PreviewState = {
    displayType: string;
    title: string;
    body: string;
    imageUrl: string;
    buttonLabel: string;
    hasLink: boolean;
    secondaryLabel: string;
};

function element<K extends keyof HTMLElementTagNameMap>(tag: K, className: string, text?: string): HTMLElementTagNameMap[K] {
    const node = document.createElement(tag);
    node.className = className;
    // 入力された文字は textContent で入れ、HTML として解釈させない
    if (text !== undefined) node.textContent = text;
    return node;
}

function render(screen: HTMLElement, state: PreviewState): void {
    screen.className = `promo-preview-screen is-${state.displayType}`;
    const panel = element('div', 'promo-preview-panel');
    const close = element('span', 'promo-preview-close', '×');
    const title = element('strong', 'promo-preview-title', state.title || 'タイトル');
    if (state.displayType === 'banner') {
        if (state.imageUrl) {
            const thumb = element('img', 'promo-preview-thumb');
            thumb.src = state.imageUrl;
            thumb.alt = '';
            panel.append(thumb);
        }
        const text = element('div', 'promo-preview-text');
        text.append(title);
        if (state.body) text.append(element('small', 'promo-preview-body', state.body));
        panel.append(text, close);
        screen.replaceChildren(element('div', 'promo-preview-app', 'アプリの画面'), panel);
        return;
    }
    const header = element('div', 'promo-preview-header');
    header.append(title, close);
    panel.append(header);
    if (state.imageUrl) {
        const image = element('img', 'promo-preview-image');
        image.src = state.imageUrl;
        image.alt = '';
        panel.append(image);
    }
    if (state.body) panel.append(element('p', 'promo-preview-body', state.body));
    panel.append(element('span', 'promo-preview-button', state.buttonLabel || (state.hasLink ? '詳しく見る' : '閉じる')));
    if (state.secondaryLabel) panel.append(element('span', 'promo-preview-button is-secondary', state.secondaryLabel));
    screen.replaceChildren(panel);
}

function mountPromoPopupPreview(screen: HTMLElement): void {
    const form = screen.closest('form');
    if (!form) return;
    const field = (name: string) => form.querySelector<HTMLInputElement | HTMLTextAreaElement>(`[name="${name}"]`);
    let uploadedUrl = '';
    const imageInput = form.querySelector<HTMLInputElement>('input[type="file"][name="image"]');
    imageInput?.addEventListener('change', () => {
        if (uploadedUrl) URL.revokeObjectURL(uploadedUrl);
        const file = imageInput.files?.[0];
        uploadedUrl = file ? URL.createObjectURL(file) : '';
        update();
    });
    function update(): void {
        const removeImage = form?.querySelector<HTMLInputElement>('[name="remove_image"]')?.checked ?? false;
        render(screen, {
            displayType: form?.querySelector<HTMLInputElement>('[name="display_type"]:checked')?.value ?? 'modal',
            title: field('title')?.value.trim() ?? '',
            body: field('body')?.value.trim() ?? '',
            imageUrl: uploadedUrl || (removeImage ? '' : screen.dataset.imageUrl ?? ''),
            buttonLabel: field('button_label')?.value.trim() ?? '',
            hasLink: (field('link_url')?.value.trim() ?? '') !== '',
            secondaryLabel: field('secondary_button_label')?.value.trim() ?? '',
        });
    }
    form.addEventListener('input', update);
    form.addEventListener('change', update);
    update();
}

document.querySelectorAll<HTMLElement>('[data-promo-popup-preview]').forEach(mountPromoPopupPreview);

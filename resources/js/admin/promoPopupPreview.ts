/** ポップアップ作成画面のプレビュー。入力内容を実際の会員アプリ（x-admin.app-preview）へ送り、アプリのポップアップで表示する。 */

function mountPromoPopupPreview(section: HTMLElement): void {
    const form = section.closest('form');
    const frame = section.querySelector('iframe');
    if (!form || !frame) return;
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
        frame?.dispatchEvent(new CustomEvent('app-preview:draft', { detail: {
            id: 0,
            title: field('title')?.value.trim() || 'タイトル',
            body: field('body')?.value.trim() ?? '',
            image_url: uploadedUrl || (removeImage ? '' : section.dataset.imageUrl ?? ''),
            display_type: form?.querySelector<HTMLInputElement>('[name="display_type"]:checked')?.value ?? 'modal',
            button_label: field('button_label')?.value.trim() ?? '',
            link_url: field('link_url')?.value.trim() ?? '',
            secondary_button_label: field('secondary_button_label')?.value.trim() ?? '',
            frequency: 'every_open',
        } }));
    }
    form.addEventListener('input', update);
    form.addEventListener('change', update);
    update();
}

document.querySelectorAll<HTMLElement>('[data-promo-popup-preview]').forEach(mountPromoPopupPreview);

class GalleryManager {
    constructor(apiClient) {
        this.apiClient = apiClient;
        this.currentCategoryId = null;
    }

    getSection() {
        return DomHelper.getTabMount(
            AppConstants.TAB_MOUNTS.GALLERY,
            AppConstants.ELEMENT_IDS.GALLERY_SECTION,
            { silent: true }
        );
    }

    async load(categoryId) {
        const section = this.getSection();
        if (!section) return;
        section.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i><p>読み込み中...</p></div>';
        try {
            const url = categoryId ? `${AppConstants.API_ENDPOINTS.GALLERY}?category_id=${encodeURIComponent(categoryId)}` : AppConstants.API_ENDPOINTS.GALLERY;
            const result = await this.apiClient.get(url);
            const data = result.data || {};
            const categories = data.categories || [];
            const items = data.items || [];
            this.render(section, categories, items);
        } catch (error) {
            section.innerHTML = '<div class="gallery-error"><p>ギャラリーの読み込みに失敗しました</p></div>';
        }
    }

    async loadEmbedded(container) {
        if (!container) return;
        container.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i><p>読み込み中...</p></div>';
        try {
            const result = await this.apiClient.get(AppConstants.API_ENDPOINTS.GALLERY);
            const data = result.data || {};
            const items = data.items || [];
            container.innerHTML = `
                <div class="gallery-grid">
                    ${items.map((item) => `
                        <div class="gallery-item gallery-item--${DomHelper.escapeHtml(item.size || 'medium')}">
                            <img src="${DomHelper.escapeHtml(item.image_url)}" alt="${DomHelper.escapeHtml(item.title || '')}">
                            ${item.title ? `<div class="gallery-item-title">${DomHelper.escapeHtml(item.title)}</div>` : ''}
                        </div>
                    `).join('')}
                </div>
            `;
        } catch (error) {
            container.innerHTML = '<div class="gallery-error"><p>ギャラリーの読み込みに失敗しました</p></div>';
        }
    }

    render(section, categories, items) {
        const categoryTabs = `
            <div class="gallery-tabs">
                <button class="gallery-tab ${!this.currentCategoryId ? 'active' : ''}" data-category="">すべて</button>
                ${categories.map((cat) => `<button class="gallery-tab ${String(this.currentCategoryId == null ? '' : this.currentCategoryId) === String(cat.id) ? 'active' : ''}" data-category="${cat.id}">${DomHelper.escapeHtml(cat.name)}</button>`).join('')}
            </div>
        `;
        const grid = `
            <div class="gallery-grid">
                ${items.map((item) => `
                    <div class="gallery-item gallery-item--${DomHelper.escapeHtml(item.size || 'medium')}">
                        <img src="${DomHelper.escapeHtml(item.image_url)}" alt="${DomHelper.escapeHtml(item.title || '')}">
                        ${item.title ? `<div class="gallery-item-title">${DomHelper.escapeHtml(item.title)}</div>` : ''}
                        ${item.description ? `<div class="gallery-item-desc">${DomHelper.escapeHtml(item.description)}</div>` : ''}
                    </div>
                `).join('')}
            </div>
        `;
        section.innerHTML = `
            <div class="gallery-header">
                <h2 class="section-title"><i class="fas fa-images"></i> ギャラリー</h2>
            </div>
            ${categoryTabs}
            ${grid}
        `;
        section.querySelectorAll('.gallery-tab').forEach((btn) => {
            btn.addEventListener('click', () => {
                this.currentCategoryId = btn.dataset.category || null;
                this.load(this.currentCategoryId);
            });
        });
    }
}

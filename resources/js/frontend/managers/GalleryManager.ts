import type { ApiClient } from '../../shared/utils/apiClient';
import { AppConstants } from '../../shared/constants';
import { DomHelper } from '../../shared/utils/domHelper';
import { mountDetailLayoutChrome } from '../utils/detailLayoutMount';

type GalleryCategory = {
    id: string | number;
    name: string;
};

type GalleryItem = {
    id?: string | number;
    size?: string;
    image_url?: string;
    title?: string;
    description?: string;
};

export class GalleryManager {
    apiClient: ApiClient;
    currentCategoryId: string | number | null;
    private cache: GalleryItem[] = [];

    constructor(apiClient: ApiClient) {
        this.apiClient = apiClient;
        this.currentCategoryId = null;
    }

    getSection(): Element | null {
        return DomHelper.getTabMount(
            AppConstants.TAB_MOUNTS.GALLERY,
            AppConstants.ELEMENT_IDS.GALLERY_SECTION,
            { silent: true },
        );
    }

    async load(categoryId?: string | number | null): Promise<void> {
        const section = this.getSection();
        if (!section) {
            return;
        }
        section.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i><p>読み込み中...</p></div>';
        try {
            const url = categoryId
                ? `${AppConstants.API_ENDPOINTS.GALLERY}?category_id=${encodeURIComponent(String(categoryId))}`
                : AppConstants.API_ENDPOINTS.GALLERY;
            const result = await this.apiClient.get(url);
            const data = (result.success ? result.data : null) as {
                categories?: GalleryCategory[];
                items?: GalleryItem[];
            } | null;
            const categories = data?.categories || [];
            const items = data?.items || [];
            this.cache = items.map((item, index) => ({
                ...item,
                id: item.id ?? index,
            }));
            this.render(section, categories, this.cache);
        } catch (_error) {
            section.innerHTML = '<div class="gallery-error"><p>ギャラリーの読み込みに失敗しました</p></div>';
        }
    }

    async loadEmbedded(container: Element | null): Promise<void> {
        if (!container) {
            return;
        }
        container.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i><p>読み込み中...</p></div>';
        try {
            const result = await this.apiClient.get(AppConstants.API_ENDPOINTS.GALLERY);
            const data = (result.success ? result.data : null) as { items?: GalleryItem[] } | null;
            const items = data?.items || [];
            this.cache = items.map((item, index) => ({
                ...item,
                id: item.id ?? index,
            }));
            container.innerHTML = `
                <div class="gallery-grid">
                    ${this.cache.map((item) => `
                        <button type="button" class="gallery-item gallery-item--${DomHelper.escapeHtml(item.size || 'medium')}" data-gallery-id="${DomHelper.escapeHtml(String(item.id ?? ''))}">
                            <img src="${DomHelper.escapeHtml(item.image_url || '')}" alt="${DomHelper.escapeHtml(item.title || '')}">
                            ${item.title ? `<div class="gallery-item-title">${DomHelper.escapeHtml(item.title)}</div>` : ''}
                        </button>
                    `).join('')}
                </div>
            `;
            this.bindDetailClicks(container);
        } catch (_error) {
            container.innerHTML = '<div class="gallery-error"><p>ギャラリーの読み込みに失敗しました</p></div>';
        }
    }

    async openDetail(id: number | string, returnHash?: string): Promise<void> {
        if (this.cache.length === 0) {
            await this.load(this.currentCategoryId);
        }
        const item = this.cache.find((g) => String(g.id) === String(id));
        if (!item) {
            return;
        }
        const router = typeof window !== 'undefined' && window.app && window.app.pageRouter
            ? window.app.pageRouter
            : null;
        if (router === null) {
            return;
        }
        const section = router.openPage({
            key: 'gallery_detail',
            hash: 'gallery/' + String(id),
            returnHash: router.resolveReturnHash('gallery', returnHash),
            title: item.title || 'ギャラリー',
            bodyClass: 'liff-page-gallery-detail',
        });
        if (section === null) {
            return;
        }
        const bodyHtml = `
            <div class="liff-entity-detail gallery-detail-page">
                <div class="liff-entity-detail__image">
                    <img src="${DomHelper.escapeHtml(item.image_url || '')}" alt="${DomHelper.escapeHtml(item.title || '')}">
                </div>
                ${item.title ? `<h1 class="liff-entity-detail__title">${DomHelper.escapeHtml(item.title)}</h1>` : ''}
                ${item.description ? `<div class="liff-entity-detail__body">${DomHelper.escapeHtml(item.description)}</div>` : ''}
            </div>
        `;
        mountDetailLayoutChrome({
            section,
            layoutKey: 'gallery_detail',
            dataSource: 'gallery_detail',
            bodyHtml,
            slotClassName: 'liff-detail-slot gallery-detail-slot',
        });
    }

    render(section: Element, categories: GalleryCategory[], items: GalleryItem[]): void {
        const categoryTabs = `
            <div class="gallery-tabs">
                <button class="gallery-tab ${!this.currentCategoryId ? 'active' : ''}" data-category="">すべて</button>
                ${categories.map((cat) => `<button class="gallery-tab ${String(this.currentCategoryId == null ? '' : this.currentCategoryId) === String(cat.id) ? 'active' : ''}" data-category="${cat.id}">${DomHelper.escapeHtml(cat.name)}</button>`).join('')}
            </div>
        `;
        const grid = `
            <div class="gallery-grid">
                ${items.map((item) => `
                    <button type="button" class="gallery-item gallery-item--${DomHelper.escapeHtml(item.size || 'medium')}" data-gallery-id="${DomHelper.escapeHtml(String(item.id ?? ''))}">
                        <img src="${DomHelper.escapeHtml(item.image_url || '')}" alt="${DomHelper.escapeHtml(item.title || '')}">
                        ${item.title ? `<div class="gallery-item-title">${DomHelper.escapeHtml(item.title)}</div>` : ''}
                        ${item.description ? `<div class="gallery-item-desc">${DomHelper.escapeHtml(item.description)}</div>` : ''}
                    </button>
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
                const el = btn as HTMLElement;
                this.currentCategoryId = el.dataset.category || null;
                void this.load(this.currentCategoryId);
            });
        });
        this.bindDetailClicks(section);
    }

    private bindDetailClicks(root: Element): void {
        root.querySelectorAll('[data-gallery-id]').forEach((el) => {
            el.addEventListener('click', (event) => {
                event.preventDefault();
                const id = (el as HTMLElement).dataset.galleryId;
                if (id === undefined || id === '') {
                    return;
                }
                void this.openDetail(id);
            });
        });
    }
}

import type { ApiClient } from '../../shared/utils/apiClient';
import { AppConstants } from '../../shared/constants';
import { DomHelper } from '../../shared/utils/domHelper';
import { isDemoDataMode, isFixedUiShell } from '../demo/demoMode';
import { buildBuiltinSampleGallery } from '../demo/sampleData';
import { mountDetailLayoutChrome } from '../utils/detailLayoutMount';
import { isReactOwnedMount } from '../utils/reactOwnedMount';

type GalleryCategory = {
    id: string | number;
    name: string;
};

type GalleryItem = {
    id?: string | number;
    category_id?: string | number;
    size?: string;
    image_url?: string;
    title?: string;
    description?: string;
};

type GalleryPayload = {
    categories: GalleryCategory[];
    items: GalleryItem[];
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
        if (isReactOwnedMount(section)) {
            return;
        }
        this.currentCategoryId = categoryId ?? null;
        section.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i><p>読み込み中...</p></div>';
        try {
            const data = await this.fetchGallery(this.currentCategoryId);
            this.cache = data.items;
            this.render(section, data.categories, this.cache);
        } catch (_error) {
            if (isDemoDataMode()) {
                const sample = this.samplePayload(this.currentCategoryId);
                this.cache = sample.items;
                this.render(section, sample.categories, this.cache);
                return;
            }
            section.innerHTML = '<div class="gallery-error"><p>ギャラリーの読み込みに失敗しました</p></div>';
        }
    }

    async loadEmbedded(container: Element | null): Promise<void> {
        if (!container) {
            return;
        }
        container.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i><p>読み込み中...</p></div>';
        try {
            const data = await this.fetchGallery(null);
            this.cache = data.items;
            if (this.cache.length === 0) {
                container.innerHTML = this.emptyStateHtml();
                return;
            }
            container.innerHTML = `
                <div class="gallery-grid">
                    ${this.cache.map((item) => this.itemButtonHtml(item)).join('')}
                </div>
            `;
            this.bindDetailClicks(container);
        } catch (_error) {
            if (isDemoDataMode()) {
                const sample = this.samplePayload(null);
                this.cache = sample.items;
                container.innerHTML = `
                    <div class="gallery-grid">
                        ${this.cache.map((item) => this.itemButtonHtml(item)).join('')}
                    </div>
                `;
                this.bindDetailClicks(container);
                return;
            }
            container.innerHTML = '<div class="gallery-error"><p>ギャラリーの読み込みに失敗しました</p></div>';
        }
    }

    /** ギャラリー画像をカルーセルとして描画する（slider source=gallery 用） */
    async loadCarousel(
        container: Element | null,
        options: {
            design?: string;
            slideHeight?: number;
            autoPlay?: boolean;
            showDots?: boolean;
            onReady?: () => void;
        } = {},
    ): Promise<void> {
        if (!container) {
            return;
        }
        const design = options.design || 'hero';
        const slideHeight = options.slideHeight || 200;
        const autoPlay = options.autoPlay !== false;
        const showDots = options.showDots !== false;
        const head = container.querySelector('.builder-section-head');
        const headHtml = head !== null ? head.outerHTML : '';
        try {
            let data = await this.fetchGallery(null);
            if (data.items.length === 0 && isDemoDataMode()) {
                data = this.samplePayload(null);
            }
            const items = data.items
                .filter((item) => typeof item.image_url === 'string' && item.image_url !== '')
                .slice(0, 12)
                .map((item, index) => ({
                    ...item,
                    id: item.id ?? index,
                }));
            this.cache = items;
            if (items.length === 0) {
                container.innerHTML = `${headHtml}
                    <div class="builder-carousel builder-carousel--empty builder-carousel--design-${DomHelper.escapeHtml(design)}" style="--carousel-h:${slideHeight}px">
                        <div class="builder-carousel__empty"><i class="fas fa-images" aria-hidden="true"></i><p>ギャラリー画像がありません</p></div>
                    </div>`;
                if (typeof options.onReady === 'function') {
                    options.onReady();
                }
                return;
            }
            const slideHtml = items.map((item, i) => {
                const display = i === 0 ? ' is-active' : '';
                const caption = item.title
                    ? `<div class="builder-carousel__caption"><span>${DomHelper.escapeHtml(item.title)}</span></div>`
                    : '';
                return `<div class="builder-carousel__slide${display}" data-slide-index="${i}" data-gallery-id="${DomHelper.escapeHtml(String(item.id ?? ''))}">
                    <img class="builder-carousel__img" src="${DomHelper.escapeHtml(item.image_url || '')}" alt="${DomHelper.escapeHtml(item.title || '')}" loading="lazy">
                    ${caption}
                </div>`;
            }).join('');
            const dots = (showDots && items.length > 1)
                ? `<div class="builder-carousel__dots">${items.map((_, i) =>
                    `<button type="button" class="builder-carousel__dot${i === 0 ? ' is-active' : ''}" data-slide-index="${i}" aria-label="スライド ${i + 1}"></button>`
                ).join('')}</div>`
                : '';
            container.innerHTML = `${headHtml}
                <div class="builder-carousel builder-carousel--design-${DomHelper.escapeHtml(design)}" style="--carousel-h:${slideHeight}px" data-auto-play="${autoPlay ? '1' : '0'}" data-slide-count="${items.length}">
                    <div class="builder-carousel__track">${slideHtml}</div>
                    ${dots}
                </div>`;
            this.bindDetailClicks(container);
            if (typeof options.onReady === 'function') {
                options.onReady();
            }
        } catch (_error) {
            if (isDemoDataMode()) {
                const sample = this.samplePayload(null);
                this.cache = sample.items;
                container.innerHTML = `${headHtml}
                    <div class="builder-carousel builder-carousel--design-${DomHelper.escapeHtml(design)}" style="--carousel-h:${slideHeight}px" data-auto-play="0" data-slide-count="${sample.items.length}">
                        <div class="builder-carousel__track">
                            ${sample.items.slice(0, 1).map((item) => `
                                <div class="builder-carousel__slide is-active" data-gallery-id="${DomHelper.escapeHtml(String(item.id ?? ''))}">
                                    <img class="builder-carousel__img" src="${DomHelper.escapeHtml(item.image_url || '')}" alt="${DomHelper.escapeHtml(item.title || '')}">
                                </div>
                            `).join('')}
                        </div>
                    </div>`;
                this.bindDetailClicks(container);
                if (typeof options.onReady === 'function') {
                    options.onReady();
                }
                return;
            }
            container.innerHTML = `${headHtml}<div class="gallery-error"><p>ギャラリーの読み込みに失敗しました</p></div>`;
        }
    }

    async openDetail(id: number | string, returnHash?: string): Promise<void> {
        let item = this.cache.find((g) => String(g.id) === String(id));
        if (item === undefined) {
            const data = await this.fetchGallery(null);
            this.cache = data.items;
            item = this.cache.find((g) => String(g.id) === String(id));
        }
        if (item === undefined) {
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
        const showHeader = !isFixedUiShell();
        const categoryTabs = `
            <div class="gallery-tabs">
                <button type="button" class="gallery-tab ${this.currentCategoryId === null || this.currentCategoryId === '' ? 'active' : ''}" data-category="">すべて</button>
                ${categories.map((cat) => `<button type="button" class="gallery-tab ${String(this.currentCategoryId ?? '') === String(cat.id) ? 'active' : ''}" data-category="${cat.id}">${DomHelper.escapeHtml(cat.name)}</button>`).join('')}
            </div>
        `;
        const grid = items.length === 0
            ? this.emptyStateHtml()
            : `
            <div class="gallery-grid">
                ${items.map((item) => this.itemButtonHtml(item)).join('')}
            </div>
        `;
        section.innerHTML = `
            ${showHeader ? `<div class="gallery-header"><h2 class="section-title"><i class="fas fa-images"></i> ギャラリー</h2></div>` : ''}
            ${categoryTabs}
            ${grid}
        `;
        section.querySelectorAll('.gallery-tab').forEach((btn) => {
            btn.addEventListener('click', () => {
                const el = btn as HTMLElement;
                const next = el.dataset.category;
                this.currentCategoryId = next === undefined || next === '' ? null : next;
                void this.load(this.currentCategoryId);
            });
        });
        this.bindDetailClicks(section);
    }

    private async fetchGallery(categoryId: string | number | null): Promise<GalleryPayload> {
        const url = categoryId !== null && categoryId !== ''
            ? `${AppConstants.API_ENDPOINTS.GALLERY}?category_id=${encodeURIComponent(String(categoryId))}`
            : AppConstants.API_ENDPOINTS.GALLERY;
        const result = await this.apiClient.get(url);
        const data = (result.success ? result.data : null) as {
            categories?: GalleryCategory[];
            items?: GalleryItem[];
        } | null;
        let categories = data?.categories || [];
        let items = (data?.items || []).map((item, index) => ({
            ...item,
            id: item.id ?? index,
        }));
        if (items.length === 0 && isDemoDataMode()) {
            return this.samplePayload(categoryId);
        }
        if (categories.length === 0 && isDemoDataMode()) {
            categories = buildBuiltinSampleGallery().categories;
        }
        return { categories, items };
    }

    private samplePayload(categoryId: string | number | null): GalleryPayload {
        const sample = buildBuiltinSampleGallery();
        const items = categoryId === null || categoryId === ''
            ? sample.items
            : sample.items.filter((item) => String(item.category_id) === String(categoryId));
        return {
            categories: sample.categories,
            items: items.map((item) => ({ ...item })),
        };
    }

    private emptyStateHtml(): string {
        return `
            <div class="gallery-empty">
                <span class="gallery-empty__icon" aria-hidden="true"><i class="fas fa-images"></i></span>
                <p class="gallery-empty__title">ギャラリー画像はまだありません</p>
                <p class="gallery-empty__hint">店舗の写真が登録されるとここに表示されます</p>
            </div>
        `;
    }

    private itemButtonHtml(item: GalleryItem): string {
        return `
            <button type="button" class="gallery-item gallery-item--${DomHelper.escapeHtml(item.size || 'medium')}" data-gallery-id="${DomHelper.escapeHtml(String(item.id ?? ''))}">
                <img src="${DomHelper.escapeHtml(item.image_url || '')}" alt="${DomHelper.escapeHtml(item.title || '')}" loading="lazy">
                ${item.title ? `<div class="gallery-item-title">${DomHelper.escapeHtml(item.title)}</div>` : ''}
                ${item.description ? `<div class="gallery-item-desc">${DomHelper.escapeHtml(item.description)}</div>` : ''}
            </button>
        `;
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

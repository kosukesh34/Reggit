import { isFixedUiShell } from '../demo/demoMode';
import type { ApiClient } from '../../shared/utils/apiClient';
import { AppConstants } from '../../shared/constants';
import { DomHelper } from '../../shared/utils/domHelper';
import { formatPublishedAt } from '../features/announcements/api';
import { mountDetailLayoutChrome } from '../utils/detailLayoutMount';
import { isReactOwnedMount } from '../utils/reactOwnedMount';

type Announcement = {
    id?: number | string;
    title?: string;
    body?: string;
    image_url?: string | null;
    published_at?: string;
    is_pinned?: boolean;
    is_read?: boolean;
};

/**
 * お知らせ一覧・詳細の表示。
 * 一覧はタブ `#announcements`、詳細はページ `#announcement/{id}` として画面遷移する。
 */
export class AnnouncementManager {
    apiClient: ApiClient;
    private cache: Announcement[] = [];
    private openDetailId: string | null = null;

    constructor(apiClient: ApiClient) {
        this.apiClient = apiClient;
    }

    getSection(): Element | null {
        return DomHelper.getTabMount(
            AppConstants.TAB_MOUNTS.ANNOUNCEMENTS,
            AppConstants.ELEMENT_IDS.ANNOUNCEMENTS_SECTION,
            { silent: true },
        );
    }

    async load(): Promise<void> {
        const section = this.getSection();
        if (!section) {
            return;
        }
        if (isReactOwnedMount(section)) {
            return;
        }
        const isIosHome = section.classList.contains('ios-announce-list') || section.id === 'iosHomeAnnouncements';
        try {
            const list = await this.fetchList();
            if (isIosHome) {
                this.renderIosHomeList(section, list);
                return;
            }
            const design = this.resolveListDesign(section);
            if (!list.length) {
                section.innerHTML = `
                    <div class="announcements announcements--empty announcements--design-${design}">
                        <div class="announcement-empty">
                            <span class="announcement-empty__icon" aria-hidden="true"><i class="fas fa-bullhorn"></i></span>
                            <p class="announcement-empty__title">お知らせはまだありません</p>
                            <p class="announcement-empty__hint">新しいお知らせが届くとここに表示されます</p>
                        </div>
                    </div>
                `;
                return;
            }
            section.innerHTML = `
                <div class="announcements announcements--design-${design}">
                    <div class="announcements-list">
                        ${list.map((a, index) => this.renderListItem(a, design, index)).join('')}
                    </div>
                </div>
            `;
            this.bindAdaptiveMedia(section);
            this.bindListClicks(section);
        } catch (error) {

            console.error('Failed to load announcements:', error);
            if (isIosHome) {
                section.innerHTML = `
                    <div class="ios-row"><div class="ios-row__body"><div class="ios-row__title">読み込みに失敗しました</div></div></div>
                `;
                return;
            }
            section.innerHTML = `
                <div class="announcements announcements--empty">
                    <div class="announcement-empty">
                        <span class="announcement-empty__icon" aria-hidden="true"><i class="fas fa-exclamation-circle"></i></span>
                        <p class="announcement-empty__title">読み込みに失敗しました</p>
                    </div>
                </div>
            `;
        }
    }

    private renderIosHomeList(section: Element, list: Announcement[]): void {
        if (!list.length) {
            section.innerHTML = `
                <div class="ios-row">
                    <div class="ios-row__body">
                        <div class="ios-row__title">お知らせはまだありません</div>
                        <div class="ios-row__subtitle">新しいお知らせが届くとここに表示されます</div>
                    </div>
                </div>
            `;
            return;
        }
        const preview = list.slice(0, 5);
        section.innerHTML = preview.map((a) => {
            const date = formatPublishedAt(a.published_at);
            const href = `#announcement/${DomHelper.escapeHtml(String(a.id ?? ''))}`;
            return `
                <a href="${href}" class="ios-row" data-announcement-id="${DomHelper.escapeHtml(String(a.id ?? ''))}">
                    <div class="ios-row__body">
                        <div class="ios-row__title">${DomHelper.escapeHtml(a.title || '')}</div>
                        ${date !== '' ? `<div class="ios-row__subtitle">${DomHelper.escapeHtml(date)}</div>` : ''}
                    </div>
                    <span class="ios-row__chevron" aria-hidden="true">›</span>
                </a>
            `;
        }).join('');
        this.bindListClicks(section);
    }

    async openDetail(id: number | string, returnHash?: string): Promise<void> {
        const list = this.cache.length > 0 ? this.cache : await this.fetchList();
        const item = list.find((a) => String(a.id) === String(id));
        if (!item) {
            return;
        }

        const router = this.getRouter();
        if (router === null) {
            return;
        }

        const section = router.openPage({
            key: 'announcement_detail',
            hash: 'announcement/' + String(id),
            returnHash: router.resolveReturnHash('announcements', returnHash),
            title: item.title || 'お知らせ',
            bodyClass: 'liff-page-announcement-detail',
        });
        if (section === null) {
            return;
        }

        this.openDetailId = String(id);
        this.renderDetailPage(item, section);
        this.markAsRead(item);

        try {
            await this.apiClient.post(AppConstants.API_ENDPOINTS.ANNOUNCEMENT_READ(id), {});
        } catch {
            /* ignore */
        }
    }

    closeDetail(updateHash = true): void {
        this.openDetailId = null;
        const router = this.getRouter();
        if (router !== null && router.getCurrentKey() === 'announcement_detail') {
            if (updateHash) {
                router.closePage(true);
            } else {
                router.closePageQuiet();
            }
        }
    }

    isDetailOpen(): boolean {
        return this.openDetailId !== null;
    }

    private async fetchList(): Promise<Announcement[]> {
        const result = await this.apiClient.get(AppConstants.API_ENDPOINTS.ANNOUNCEMENTS);
        const data = result.success ? result.data as { announcements?: Announcement[] } : null;
        const list = data && Array.isArray(data.announcements) ? data.announcements : [];
        this.cache = list;
        return list;
    }

    private resolveListDesign(section: Element): string {
        const fromBlock = section.closest('[data-design]')?.getAttribute('data-design');
        if (fromBlock !== null && fromBlock !== undefined && fromBlock !== '') {
            return fromBlock;
        }
        return 'cards';
    }

    private renderListItem(a: Announcement, design: string, index: number): string {
        const date = formatPublishedAt(a.published_at);
        const isPinned = a.is_pinned === true;
        const isUnread = a.is_read !== true;
        const hasImage = typeof a.image_url === 'string' && a.image_url !== '';
        const href = `#announcement/${DomHelper.escapeHtml(String(a.id ?? ''))}`;
        const excerpt = this.excerpt(a.body || '', design === 'compact' ? 48 : 90);
        const featured = design === 'cards' && index === 0 && hasImage;
        const classes = [
            'announcement-item',
            'announcement-item--link',
            `announcement-item--${design}`,
            hasImage ? 'announcement-item--has-image' : 'announcement-item--no-image',
            isPinned ? 'announcement-item--pinned' : '',
            isUnread ? 'announcement-item--unread' : '',
            featured ? 'announcement-item--featured' : '',
        ].filter((c) => c !== '').join(' ');

        const media = hasImage
            ? `<div class="announcement-item__media"><img src="${DomHelper.escapeHtml(a.image_url || '')}" alt="" loading="lazy"></div>`
            : `<div class="announcement-item__media announcement-item__media--placeholder" aria-hidden="true"><i class="fas fa-bullhorn"></i></div>`;

        const badges: string[] = [];
        if (isPinned) {
            badges.push('<span class="announcement-badge announcement-badge--pinned"><i class="fas fa-thumbtack" aria-hidden="true"></i>固定</span>');
        }
        if (isUnread) {
            badges.push('<span class="announcement-badge announcement-badge--new">NEW</span>');
        }

        return `
            <a href="${href}" class="${classes}" data-announcement-id="${DomHelper.escapeHtml(String(a.id ?? ''))}">
                ${media}
                <div class="announcement-item__content">
                    ${badges.length > 0 ? `<div class="announcement-item__badges">${badges.join('')}</div>` : ''}
                    <div class="announcement-title">${DomHelper.escapeHtml(a.title || '')}</div>
                    ${excerpt !== '' ? `<div class="announcement-body">${DomHelper.escapeHtml(excerpt)}</div>` : ''}
                    <div class="announcement-item__meta">
                        ${date !== '' ? `<time class="announcement-date" datetime="${DomHelper.escapeHtml(a.published_at || '')}">${DomHelper.escapeHtml(date)}</time>` : ''}
                        <span class="announcement-item__more">詳しく見る</span>
                    </div>
                </div>
            </a>
        `;
    }

    private excerpt(body: string, maxLen: number): string {
        const text = body.replace(/\s+/g, ' ').trim();
        if (text.length <= maxLen) {
            return text;
        }
        return text.slice(0, maxLen) + '…';
    }

    private bindListClicks(root: Element): void {
        root.querySelectorAll('[data-announcement-id]').forEach((el) => {
            el.addEventListener('click', (event) => {
                const id = (el as HTMLElement).dataset.announcementId;
                if (id === undefined || id === '') {
                    return;
                }
                event.preventDefault();
                void this.openDetail(id);
            });
        });
    }

    private bindAdaptiveMedia(root: Element): void {
        root.querySelectorAll<HTMLImageElement>('.announcement-item__media img').forEach((image) => {
            const applyRatio = (): void => {
                const media = image.parentElement;
                if (media === null || image.naturalWidth <= 0 || image.naturalHeight <= 0) return;
                const ratio = Math.min(3.2, Math.max(0.6, image.naturalWidth / image.naturalHeight));
                media.style.setProperty('--announcement-media-aspect', String(ratio));
            };
            if (image.complete) applyRatio();
            else image.addEventListener('load', applyRatio, { once: true });
        });
    }

    private renderDetailPage(item: Announcement, section: HTMLElement): void {
        mountDetailLayoutChrome({
            section,
            layoutKey: 'announcement_detail',
            dataSource: 'announcement_detail',
            bodyHtml: this.renderDetailBody(item),
            slotClassName: 'announcement-detail-slot',
        });
    }

    private renderDetailBody(item: Announcement): string {
        if (isFixedUiShell()) {
            return this._renderFixedUiDetailBody(item);
        }
        return this._renderClassicDetailBody(item);
    }

    private _renderClassicDetailBody(item: Announcement): string {
        const date = formatPublishedAt(item.published_at);
        const hasImage = typeof item.image_url === 'string' && item.image_url !== '';
        const isPinned = item.is_pinned === true;
        const image = hasImage
            ? `<div class="announcement-detail__hero"><img src="${DomHelper.escapeHtml(item.image_url || '')}" alt=""></div>`
            : '';
        const body = DomHelper.escapeHtml(item.body || '').replace(/\n/g, '<br>');
        const badges: string[] = [];
        if (isPinned) {
            badges.push('<span class="announcement-badge announcement-badge--pinned"><i class="fas fa-thumbtack" aria-hidden="true"></i>固定</span>');
        }

        return `
            <article class="announcement-detail${hasImage ? ' announcement-detail--has-image' : ''}">
                ${image}
                <div class="announcement-detail__panel">
                    ${badges.length > 0 ? `<div class="announcement-detail__badges">${badges.join('')}</div>` : ''}
                    <h1 class="announcement-detail__title">${DomHelper.escapeHtml(item.title || '')}</h1>
                    ${date !== '' ? `<p class="announcement-detail__date"><i class="far fa-clock" aria-hidden="true"></i>${DomHelper.escapeHtml(date)}</p>` : ''}
                    ${body !== '' ? `<div class="announcement-detail__body">${body}</div>` : ''}
                </div>
            </article>
        `;
    }

    private _renderFixedUiDetailBody(item: Announcement): string {
        const date = formatPublishedAt(item.published_at);
        const rawImage = typeof item.image_url === 'string' && item.image_url !== ''
            ? item.image_url
            : '/images/liff/announce-hero.jpg';
        const isPinned = item.is_pinned === true;
        const body = DomHelper.escapeHtml(item.body || '').replace(/\n/g, '<br>');

        return `
            <article class="ios-detail ios-detail--announcement ios-detail--has-hero">
                <div class="ios-detail__hero">
                    <img src="${DomHelper.escapeHtml(rawImage)}" alt="" onerror="this.src='/images/liff/announce-hero.jpg'">
                    <div class="ios-detail__hero-shade"></div>
                </div>
                <div class="ios-detail__panel">
                    <div class="ios-detail__chips">
                        <span class="ios-detail__chip ios-detail__chip--status">お知らせ</span>
                        ${isPinned ? '<span class="ios-detail__chip">固定</span>' : ''}
                    </div>
                    <h1 class="ios-detail__title">${DomHelper.escapeHtml(item.title || '')}</h1>
                    ${date !== '' ? `<p class="ios-detail__meta"><i class="far fa-clock" aria-hidden="true"></i>${DomHelper.escapeHtml(date)}</p>` : ''}
                    ${body !== '' ? `<div class="ios-detail__body ios-detail__body--rich">${body}</div>` : ''}
                </div>
            </article>
        `;
    }

    private markAsRead(item: Announcement): void {
        item.is_read = true;
        const cached = this.cache.find((a) => String(a.id) === String(item.id));
        if (cached !== undefined) {
            cached.is_read = true;
        }
    }

    private getRouter(): import('../navigation/LiffPageRouter').LiffPageRouter | null {
        if (typeof window === 'undefined' || !window.app || !window.app.pageRouter) {
            return null;
        }
        return window.app.pageRouter;
    }
}

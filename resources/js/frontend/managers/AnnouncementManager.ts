import type { ApiClient } from '../../shared/utils/apiClient';
import { AppConstants } from '../../shared/constants';
import { DomHelper } from '../../shared/utils/domHelper';
import { mountDetailLayoutChrome } from '../utils/detailLayoutMount';

type Announcement = {
    id?: number | string;
    title?: string;
    body?: string;
    image_url?: string | null;
    published_at?: string;
    is_pinned?: boolean;
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
        try {
            const list = await this.fetchList();
            const design = this.resolveListDesign(section);
            if (!list.length) {
                section.innerHTML = `
                    <div class="announcements announcements--empty announcements--design-${design}">
                        <p class="announcement-empty">お知らせはまだありません</p>
                    </div>
                `;
                return;
            }
            section.innerHTML = `
                <div class="announcements announcements--design-${design}">
                    <div class="announcements-list">
                        ${list.map((a) => this.renderListItem(a, design)).join('')}
                    </div>
                </div>
            `;
            this.bindListClicks(section);
        } catch (error) {
            // eslint-disable-next-line no-console
            console.error('Failed to load announcements:', error);
            section.innerHTML = '<div class="announcements announcements--empty"><p class="announcement-empty">読み込みに失敗しました</p></div>';
        }
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

    private renderListItem(a: Announcement, design: string): string {
        const date = a.published_at ? new Date(a.published_at).toLocaleDateString('ja-JP') : '';
        const pinned = a.is_pinned === true ? ' announcement-item--pinned' : '';
        const thumb = a.image_url
            ? `<div class="announcement-thumb"><img src="${DomHelper.escapeHtml(a.image_url)}" alt="" loading="lazy"></div>`
            : '';
        const href = `#announcement/${DomHelper.escapeHtml(String(a.id ?? ''))}`;
        return `
            <a href="${href}" class="announcement-item announcement-item--link${pinned} announcement-item--${design}" data-announcement-id="${DomHelper.escapeHtml(String(a.id ?? ''))}">
                ${thumb}
                <div class="announcement-item__body">
                    <div class="announcement-title">${DomHelper.escapeHtml(a.title || '')}</div>
                    <div class="announcement-body">${DomHelper.escapeHtml(this.excerpt(a.body || ''))}</div>
                    <div class="announcement-date">${DomHelper.escapeHtml(date)}</div>
                </div>
                <i class="fas fa-chevron-right announcement-item__chevron" aria-hidden="true"></i>
            </a>
        `;
    }

    private excerpt(body: string): string {
        const text = body.replace(/\s+/g, ' ').trim();
        if (text.length <= 80) {
            return text;
        }
        return text.slice(0, 80) + '…';
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
        const date = item.published_at ? new Date(item.published_at).toLocaleString('ja-JP') : '';
        const image = item.image_url
            ? `<div class="announcement-detail__image"><img src="${DomHelper.escapeHtml(item.image_url)}" alt=""></div>`
            : '';
        return `
            <article class="announcement-detail">
                <h1 class="announcement-detail__title">${DomHelper.escapeHtml(item.title || '')}</h1>
                <div class="announcement-detail__date">${DomHelper.escapeHtml(date)}</div>
                ${image}
                <div class="announcement-detail__body">${DomHelper.escapeHtml(item.body || '').replace(/\n/g, '<br>')}</div>
            </article>
        `;
    }

    private getRouter(): import('../navigation/LiffPageRouter').LiffPageRouter | null {
        if (typeof window === 'undefined' || !window.app || !window.app.pageRouter) {
            return null;
        }
        return window.app.pageRouter;
    }
}

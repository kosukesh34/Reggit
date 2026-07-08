import type { ApiClient } from '../../shared/utils/apiClient';
import { AppConstants } from '../../shared/constants';
import { DomHelper } from '../../shared/utils/domHelper';

type Announcement = {
    title?: string;
    body?: string;
    published_at?: string;
};

export class AnnouncementManager {
    apiClient: ApiClient;

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
            const result = await this.apiClient.get(AppConstants.API_ENDPOINTS.ANNOUNCEMENTS);
            const data = result.success ? result.data as { announcements?: Announcement[] } : null;
            const list = data && data.announcements ? data.announcements : [];
            if (!list.length) {
                section.innerHTML = '';
                return;
            }
            const items = list.map((a) => {
                const date = a.published_at ? new Date(a.published_at).toLocaleDateString('ja-JP') : '';
                return `
                    <div class="announcement-item">
                        <div class="announcement-title">${DomHelper.escapeHtml(a.title || '')}</div>
                        <div class="announcement-body">${DomHelper.escapeHtml(a.body || '')}</div>
                        <div class="announcement-date">${DomHelper.escapeHtml(date)}</div>
                    </div>
                `;
            }).join('');
            section.innerHTML = `
                <div class="announcements">
                    <h2 class="section-title"><i class="fas fa-bullhorn"></i> お知らせ</h2>
                    ${items}
                </div>
            `;
        } catch (error) {
            // eslint-disable-next-line no-console
            console.error('Failed to load announcements:', error);
        }
    }
}

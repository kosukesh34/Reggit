class AnnouncementManager {
    constructor(apiClient) {
        this.apiClient = apiClient;
    }

    getSection() {
        return DomHelper.getElementById(AppConstants.ELEMENT_IDS.ANNOUNCEMENTS_SECTION);
    }

    async load() {
        const section = this.getSection();
        if (!section) return;
        try {
            const result = await this.apiClient.get(AppConstants.API_ENDPOINTS.ANNOUNCEMENTS);
            const list = (result.data && result.data.announcements) ? result.data.announcements : [];
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
            console.error('Failed to load announcements:', error);
        }
    }
}

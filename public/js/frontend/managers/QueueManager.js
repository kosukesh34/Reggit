class QueueManager {
    constructor(apiClient) {
        this.apiClient = apiClient;
    }

    getSection() {
        return DomHelper.getElementById(AppConstants.ELEMENT_IDS.QUEUE_SECTION);
    }

    getLoginTemplate() {
        return `
            <div class="queue-card">
                <h2 class="section-title"><i class="fas fa-users"></i> 順番待ち</h2>
                <p>ログインすると順番待ちを利用できます。</p>
            </div>
        `;
    }

    getEmptyTemplate() {
        return `
            <div class="queue-card">
                <h2 class="section-title"><i class="fas fa-users"></i> 順番待ち</h2>
                <p>現在の順番待ちはありません。</p>
            </div>
        `;
    }

    async load() {
        const section = this.getSection();
        if (!section) return;
        if (typeof window === 'undefined' || !window.__LINE_USER_ID__) {
            section.innerHTML = this.getLoginTemplate();
            return;
        }
        section.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i><p>読み込み中...</p></div>';
        try {
            const result = await this.apiClient.get(AppConstants.API_ENDPOINTS.QUEUE_ME);
            const data = result.data || {};
            if (!data || Object.keys(data).length === 0) {
                section.innerHTML = this.getEmptyTemplate();
                return;
            }
            section.innerHTML = `
                <div class="queue-card">
                    <h2 class="section-title"><i class="fas fa-users"></i> 順番待ち</h2>
                    <div class="queue-info">
                        <div>受付番号: <strong>${DomHelper.escapeHtml(data.queue_number || '-')}</strong></div>
                        <div>状態: <strong>${DomHelper.escapeHtml(data.status || '-')}</strong></div>
                        <div>人数: <strong>${DomHelper.escapeHtml(data.party_size || '-')}</strong></div>
                    </div>
                </div>
            `;
        } catch (error) {
            console.error('Failed to load queue:', error);
            section.innerHTML = this.getEmptyTemplate();
        }
    }
}

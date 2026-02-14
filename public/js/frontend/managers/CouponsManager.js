class CouponsManager {
    constructor(apiClient) {
        this.apiClient = apiClient;
    }

    getSection() {
        return DomHelper.getElementById(AppConstants.ELEMENT_IDS.COUPONS_SECTION);
    }

    getLoginTemplate() {
        return `
            <div class="member-card-container">
                <div class="member-card member-card--line-only">
                    <div class="member-card-header">
                        <h2 class="member-card-title"><i class="fas fa-ticket-alt"></i> マイクーポン</h2>
                    </div>
                    <div class="member-card-line-only-body">
                        <p class="member-card-line-only-message">ログインするとクーポンをご利用いただけます。</p>
                    </div>
                </div>
            </div>
        `;
    }

    getErrorTemplate(error) {
        const message = error && error.message ? error.message : 'クーポンの読み込みに失敗しました';
        return `
            <div class="coupons-error">
                <i class="fas fa-exclamation-circle"></i>
                <p>${DomHelper.escapeHtml(message)}</p>
                <button type="button" class="btn btn-secondary" onclick="window.app.couponsManager.load()">
                    <i class="fas fa-redo"></i> 再読み込み
                </button>
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
        section.innerHTML =
            '<div class="loading"><i class="fas fa-spinner fa-spin"></i><p>読み込み中...</p></div>';
        try {
            const result = await this.apiClient.get(AppConstants.API_ENDPOINTS.MY_COUPONS);
            const coupons = (result.data && result.data.coupons) ? result.data.coupons : [];
            const defaultImage = result.data && result.data.default_image_url ? result.data.default_image_url : null;
            this.render(coupons, defaultImage);
        } catch (error) {
            console.error('Failed to load coupons:', error);
            section.innerHTML = this.getErrorTemplate(error);
        }
    }

    render(coupons, defaultImage) {
        const section = this.getSection();
        if (!section) return;
        if (!coupons || coupons.length === 0) {
            section.innerHTML = `
                <div class="coupons-empty">
                    <i class="fas fa-ticket-alt"></i>
                    <p>クーポンはありません</p>
                    <p class="coupons-empty-sub">お店から発行されるとここに表示されます</p>
                </div>
            `;
            return;
        }
        const statusLabels = { available: '利用可能', used: '使用済み', expired: '期限切れ' };
        const statusIcons = { available: 'fa-check-circle', used: 'fa-receipt', expired: 'fa-clock' };
        const statusGroups = { available: [], used: [], expired: [] };

        coupons.forEach(cp => {
            const status = cp.status || 'available';
            if (!statusGroups[status]) statusGroups[status] = [];
            statusGroups[status].push(cp);
        });

        const renderCoupon = (cp) => {
            const status = cp.status || 'available';
            const label = statusLabels[status] || status;
            const validUntil = cp.valid_until
                ? new Date(cp.valid_until).toLocaleDateString('ja-JP', {
                      year: 'numeric', month: 'short', day: 'numeric',
                  })
                : '';
            const imageUrl = cp.image_url || defaultImage;
            return `
                <div class="coupon-item coupon-item--${status}">
                    ${imageUrl ? `<div class="coupon-item-image"><img src="${DomHelper.escapeHtml(imageUrl)}" alt="クーポン"></div>` : ''}
                    <div class="coupon-item-header">
                        <span class="coupon-item-name">${DomHelper.escapeHtml(cp.coupon_name || 'クーポン')}</span>
                        <span class="coupon-item-status">${DomHelper.escapeHtml(label)}</span>
                    </div>
                    ${validUntil ? `<p class="coupon-item-valid">有効期限: ${DomHelper.escapeHtml(validUntil)}</p>` : ''}
                </div>
            `;
        };

        let html = '<div class="coupons-header"><h2 class="section-title"><i class="fas fa-ticket-alt"></i> マイクーポン</h2></div>';

        // 利用可能なクーポンを優先表示
        if (statusGroups.available.length > 0) {
            html += `<div class="coupon-group">
                <div class="coupon-group-header coupon-group-header--available">
                    <i class="fas fa-check-circle"></i>
                    <span>利用可能</span>
                    <span class="coupon-group-count">${statusGroups.available.length}枚</span>
                </div>
                <div class="coupons-list">${statusGroups.available.map(renderCoupon).join('')}</div>
            </div>`;
        }

        if (statusGroups.used.length > 0) {
            html += `<div class="coupon-group">
                <div class="coupon-group-header coupon-group-header--used">
                    <i class="fas fa-receipt"></i>
                    <span>使用済み</span>
                    <span class="coupon-group-count">${statusGroups.used.length}枚</span>
                </div>
                <div class="coupons-list">${statusGroups.used.map(renderCoupon).join('')}</div>
            </div>`;
        }

        if (statusGroups.expired.length > 0) {
            html += `<div class="coupon-group">
                <div class="coupon-group-header coupon-group-header--expired">
                    <i class="fas fa-clock"></i>
                    <span>期限切れ</span>
                    <span class="coupon-group-count">${statusGroups.expired.length}枚</span>
                </div>
                <div class="coupons-list">${statusGroups.expired.map(renderCoupon).join('')}</div>
            </div>`;
        }

        section.innerHTML = html;
    }
}

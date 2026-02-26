class StampsManager {
    constructor(apiClient) {
        this.apiClient = apiClient;
    }

    getSection() {
        return DomHelper.getElementById(AppConstants.ELEMENT_IDS.STAMPS_SECTION);
    }

    getGuestTemplate() {
        return `
            <div class="member-card-container">
                <div class="member-card member-card--line-only">
                    <div class="member-card-header">
                        <h2 class="member-card-title"><i class="fas fa-stamp"></i> スタンプカード</h2>
                    </div>
                    <div class="member-card-line-only-body">
                        <p class="member-card-line-only-message">この端末はゲスト状態です。</p>
                        <p class="member-card-line-only-sub">会員登録するとスタンプ履歴を引き継げます。</p>
                        <button type="button" class="btn btn-secondary mt-2" onclick="app.switchTab('${AppConstants.TABS.MEMBER}')">
                            <i class="fas fa-user-plus"></i> 会員登録へ
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    getMemberRequiredTemplate(message = '会員登録が必要です') {
        return `
            <div class="stamps-empty">
                <i class="fas fa-id-card"></i>
                <p>${DomHelper.escapeHtml(message)}</p>
                <p class="stamps-empty-sub">会員登録後にスタンプカードを利用できます</p>
                <button type="button" class="btn btn-primary mt-2" onclick="app.switchTab('${AppConstants.TABS.MEMBER}')">
                    <i class="fas fa-user-plus"></i> 会員登録する
                </button>
            </div>
        `;
    }

    getErrorTemplate(error) {
        const message = error && error.message ? error.message : 'スタンプの読み込みに失敗しました';
        return `
            <div class="stamps-error">
                <i class="fas fa-exclamation-circle"></i>
                <p>${DomHelper.escapeHtml(message)}</p>
                <button type="button" class="btn btn-secondary" onclick="window.app.stampsManager.load()">
                    <i class="fas fa-redo"></i> 再読み込み
                </button>
            </div>
        `;
    }

    async load() {
        const section = this.getSection();
        if (!section) return;
        section.innerHTML =
            '<div class="loading"><i class="fas fa-spinner fa-spin"></i><p>読み込み中...</p></div>';
        try {
            const result = await this.apiClient.get(AppConstants.API_ENDPOINTS.STAMPS);
            if (!result.success) {
                section.innerHTML = this.getErrorTemplate({ message: result.error });
                return;
            }

            const cards = (result.data && result.data.stamp_cards) ? result.data.stamp_cards : [];
            const defaultImage = result.data && result.data.default_image_url ? result.data.default_image_url : null;
            const message = result.data && result.data.message ? result.data.message : null;
            if ((!cards || cards.length === 0) && message) {
                section.innerHTML = this.getMemberRequiredTemplate(message);
                return;
            }

            this.render(cards, defaultImage);
        } catch (error) {
            console.error('Failed to load stamps:', error);
            section.innerHTML = this.getErrorTemplate(error);
        }
    }

    renderSlot(i, filled) {
        return `<div class="stamp-slot ${filled ? 'stamp-slot--filled' : ''}" aria-hidden="true">${filled ? '<i class="fas fa-stamp"></i>' : ''}</div>`;
    }

    renderSlots(current, required, layoutStyle) {
        const slots = [];
        for (let i = 0; i < required; i++) {
            slots.push(this.renderSlot(i, i < current));
        }
        return `<div class="stamp-slots stamp-slots--${layoutStyle || 'grid'}" role="img" aria-label="${current}/${required} スタンプ">${slots.join('')}</div>`;
    }

    formatValidUntil(isoString) {
        if (!isoString) return null;
        const d = new Date(isoString);
        const now = new Date();
        if (d <= now) return null;
        const days = Math.ceil((d - now) / (1000 * 60 * 60 * 24));
        if (days <= 0) return null;
        return days === 1 ? 'あと1日' : `あと${days}日`;
    }

    render(cards, defaultImage) {
        const section = this.getSection();
        if (!section) return;
        if (!cards || cards.length === 0) {
            section.innerHTML = `
                <div class="stamps-empty">
                    <i class="fas fa-stamp"></i>
                    <p>スタンプカードはありません</p>
                    <p class="stamps-empty-sub">お店でスタンプを集めましょう</p>
                </div>
            `;
            return;
        }
        const html = cards
            .map((card) => {
                const current = card.current_stamps || 0;
                const required = card.required_stamps || 10;
                const progress = Math.min(100, (current / required) * 100);
                const completed = !!card.completed_at;
                const layoutStyle = card.layout_style || 'grid';
                const imageUrl = card.image_url || defaultImage;
                const validDaysLabel = this.formatValidUntil(card.member_stamp_valid_until);
                return `
                    <div class="stamp-card-item stamp-card-item--layout-${layoutStyle} ${completed ? 'stamp-card-item--completed' : ''}">
                        ${imageUrl ? `<div class="stamp-card-image"><img src="${DomHelper.escapeHtml(imageUrl)}" alt="スタンプカード" loading="lazy" onerror="this.closest('.stamp-card-image').style.display='none'"></div>` : '<div class="stamp-card-image stamp-card-image--icon"><i class="fas fa-stamp" aria-hidden="true"></i></div>'}
                        <div class="stamp-card-header">
                            <h3 class="stamp-card-name"><i class="fas fa-stamp stamp-card-name-icon" aria-hidden="true"></i>${DomHelper.escapeHtml(card.name || 'スタンプカード')}</h3>
                            ${completed ? '<span class="stamp-card-badge"><i class="fas fa-check-circle"></i> 達成</span>' : ''}
                        </div>
                        ${this.renderSlots(current, required, layoutStyle)}
                        <div class="stamp-card-meta">
                            <p class="stamp-card-count">${current} / ${required} スタンプ</p>
                            ${validDaysLabel ? `<p class="stamp-card-valid">${DomHelper.escapeHtml(validDaysLabel)}</p>` : ''}
                        </div>
                        ${layoutStyle !== 'minimal' ? `<div class="stamp-card-progress"><div class="stamp-card-progress-bar" style="width: ${progress}%"></div></div>` : ''}
                        ${card.reward_description ? `<p class="stamp-card-reward">${DomHelper.escapeHtml(card.reward_description)}</p>` : ''}
                    </div>
                `;
            })
            .join('');
        section.innerHTML = `
            <div class="stamps-header">
                <h2 class="section-title"><i class="fas fa-stamp"></i> スタンプカード</h2>
            </div>
            <div class="stamps-list">${html}</div>
        `;
    }
}

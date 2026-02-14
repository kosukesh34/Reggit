class MemberManager {
    constructor(apiClient, orderManager) {
        this.apiClient = apiClient;
        this.orderManager = orderManager;
        this.memberData = null;
    }

    getMemberData() {
        return this.memberData;
    }

    setMemberData(data) {
        this.memberData = data;
    }

    preload() {
        if (typeof window === 'undefined' || !window.__LINE_USER_ID__) return;
        const self = this;
        this.apiClient.get(AppConstants.API_ENDPOINTS.MEMBERS_ME).then(function (result) {
            if (result.success && result.data && typeof result.data.is_member !== 'undefined') {
                self.memberData = result.data;
            }
        });
    }

    getContainer() {
        return DomHelper.getElementById(AppConstants.ELEMENT_IDS.MEMBER_SECTION);
    }

    getLoginOptionalTemplate(title, message) {
        const t = title || '会員証';
        const m = message || 'ログインするとご利用いただけます。';
        return `
            <div class="member-card-container">
                <div class="member-card member-card--line-only">
                    <div class="member-card-header">
                        <h2 class="member-card-title"><i class="fas fa-id-card"></i> ${DomHelper.escapeHtml(t)}</h2>
                    </div>
                    <div class="member-card-line-only-body">
                        <p class="member-card-line-only-message">${DomHelper.escapeHtml(m)}</p>
                    </div>
                </div>
            </div>
        `;
    }

    getErrorTemplate(error) {
        const message = error && error.message ? error.message : 'エラーが発生しました';
        return `
            <div class="member-card-container">
                <div class="member-card member-card--error">
                    <div class="member-card-header">
                        <h2 class="member-card-title"><i class="fas fa-id-card"></i> 会員証</h2>
                    </div>
                    <div class="member-card-error-body">
                        <p class="member-card-error-icon"><i class="fas fa-exclamation-circle"></i></p>
                        <p class="member-card-error-message">${DomHelper.escapeHtml(message)}</p>
                        <p class="member-card-error-hint">しばらくしてから再度お試しください。</p>
                        <button type="button" class="register-member-btn" onclick="window.app.memberManager.load()">
                            <i class="fas fa-redo"></i> 再読み込み
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    getSkeletonHtml() {
        return `
            <div class="member-card-container">
                <div class="member-card">
                    <div class="member-card-header">
                        <div class="skeleton-line skeleton-header"></div>
                    </div>
                    <div class="barcode-container">
                        <div class="skeleton-barcode">
                            <div class="skeleton-shimmer"></div>
                        </div>
                    </div>
                    <div class="points-display">
                        <div class="skeleton-line skeleton-points-value"></div>
                        <div class="skeleton-line skeleton-points-label"></div>
                    </div>
                </div>
            </div>
        `;
    }

    async load() {
        const container = this.getContainer();
        if (!container) return;

        if (typeof window === 'undefined' || !window.__LINE_USER_ID__) {
            container.innerHTML = this.getLoginOptionalTemplate('会員証', 'ログインすると会員証をご利用いただけます。');
            this.memberData = null;
            return;
        }

        if (this.memberData && this.memberData.is_member) {
            this.render(this.memberData);
            this.preload();
            return;
        }

        container.innerHTML = this.getSkeletonHtml();

        try {
            const result = await this.apiClient.get(AppConstants.API_ENDPOINTS.MEMBERS_ME);
            if (!result.success) {
                throw new Error(result.error || result.data?.message || '会員情報の取得に失敗しました');
            }
            const data = result.data;
            if (!data || typeof data.is_member === 'undefined') {
                throw new Error('会員情報の形式が正しくありません');
            }
            if (!data.is_member) {
                await this.autoRegister();
                return;
            }
            this.memberData = data;
            this.render(data);
        } catch (error) {
            console.error('Failed to load member card:', error);
            container.innerHTML = this.getErrorTemplate(error);
        }
    }

    async autoRegister() {
        const payload = {};
        if (typeof window !== 'undefined' && window.__LINE_PROFILE__ && window.__LINE_PROFILE__.displayName) {
            payload.name = window.__LINE_PROFILE__.displayName;
        }
        try {
            const result = await this.apiClient.post(AppConstants.API_ENDPOINTS.MEMBERS_REGISTER, payload);
            if (result.success && result.data && result.data.is_member) {
                this.memberData = result.data;
                this.render(result.data);
                ToastManager.success('会員証を作成しました');
            } else if (result.success) {
                await this.load();
            } else {
                throw new Error(result.error);
            }
        } catch (error) {
            console.error('Auto register error:', error);
            const container = this.getContainer();
            if (container) container.innerHTML = this.getErrorTemplate(error);
        }
    }

    render(data) {
        const container = this.getContainer();
        if (!container) return;

        const member = data.member;
        const user = data.user || (member && member.user);
        const displayName =
            (user && user.name) ||
            (typeof window !== 'undefined' && window.__LINE_PROFILE__ && window.__LINE_PROFILE__.displayName) ||
            '';
        const points = data.points || member.points || 0;
        const memberNumber = member.member_number || '';
        const currentRankLabel = data.current_rank_label || 'ブロンズ';
        const currentRankIcon = data.current_rank_icon || null;
        const nextRankLabel = data.next_rank_label || null;
        const pointsToNextRank = data.points_to_next_rank != null ? data.points_to_next_rank : null;
        const pointsExpiry = data.points_expiry || null;
        const currentRankKey = data.current_rank || 'bronze';

        let expiryText = '';
        if (pointsExpiry && pointsExpiry.expires_at_label) {
            expiryText =
                'ポイント有効期限: ' +
                pointsExpiry.expires_at_label +
                (pointsExpiry.points_expiring ? ' (' + pointsExpiry.points_expiring + ' pt)' : '');
        }

        const barcodeId = `barcode-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
        const permissionDeniedNotice =
            typeof window !== 'undefined' && window.__LIFF_PERMISSION_DENIED__
                ? '<p class="member-card-profile-notice">LINEの表示名は取得していません。<button type="button" class="link-btn" onclick="location.reload()">再読み込み</button></p>'
                : '';

        container.innerHTML = `
            <div class="member-card-container">
                <div class="member-card-idnect-header">
                    <h2 class="member-card-title"><i class="fas fa-id-card"></i> 会員証</h2>
                    <button type="button" class="member-card-edit-btn" onclick="window.app.memberManager.openProfileEdit()" aria-label="プロフィール編集"><i class="fas fa-pen"></i></button>
                </div>
                ${permissionDeniedNotice}
                <div class="idnect-card-wrapper" role="button" tabindex="0" aria-label="カードをタップで裏返します">
                    <div class="idnect-card" data-rank="${DomHelper.escapeHtml(currentRankKey)}">
                        <div class="idnect-card-shimmer" aria-hidden="true"></div>
                        <div class="idnect-card-face idnect-card-face--front">
                            <div class="idnect-card-front-inner">
                                ${displayName ? '<p class="idnect-card-display-name">' + DomHelper.escapeHtml(displayName) + '</p>' : ''}
                                <div class="idnect-card-rank-wrap">
                                    ${currentRankIcon ? `<img src="${DomHelper.escapeHtml(currentRankIcon)}" class="idnect-card-rank-icon" alt="ランク">` : ''}
                                    <p class="idnect-card-rank-badge" data-rank="${DomHelper.escapeHtml(currentRankKey)}">${DomHelper.escapeHtml(currentRankLabel)}</p>
                                </div>
                                <div class="idnect-card-barcode-wrap">
                                    <svg id="${barcodeId}" class="idnect-card-barcode-svg"></svg>
                                </div>
                                <p class="idnect-card-tap-hint">タップで裏面を見る</p>
                            </div>
                        </div>
                        <div class="idnect-card-face idnect-card-face--back">
                            <div class="idnect-card-back-inner">
                                <p class="idnect-card-back-title">会員情報</p>
                                <div class="idnect-card-points idnect-card-points--large">
                                    <span class="idnect-card-points-value">${points.toLocaleString()}</span>
                                    <span class="idnect-card-points-label">ポイント</span>
                                </div>
                                ${expiryText ? '<p class="idnect-card-expiry">' + DomHelper.escapeHtml(expiryText) + '</p>' : ''}
                                ${nextRankLabel && pointsToNextRank !== null ? '<p class="idnect-card-next-rank">次のランク（' + DomHelper.escapeHtml(nextRankLabel) + '）まで <strong>' + pointsToNextRank.toLocaleString() + '</strong> pt</p>' : ''}
                                <p class="idnect-card-tap-hint">タップで表に戻る</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="member-purchase-history">
                    <button type="button" class="member-purchase-history-toggle" aria-expanded="false" aria-controls="memberPurchaseHistoryList" id="memberPurchaseHistoryToggle">
                        <span class="member-purchase-history-toggle-label">購入履歴</span>
                        <i class="fas fa-chevron-down member-purchase-history-toggle-icon" aria-hidden="true"></i>
                    </button>
                    <div id="memberPurchaseHistoryList" class="member-purchase-history-list" hidden aria-labelledby="memberPurchaseHistoryToggle"></div>
                </div>
                <div class="member-point-history">
                    <button type="button" class="member-purchase-history-toggle" aria-expanded="false" aria-controls="memberPointHistoryList" id="memberPointHistoryToggle">
                        <span class="member-purchase-history-toggle-label">ポイント履歴</span>
                        <i class="fas fa-chevron-down member-purchase-history-toggle-icon" aria-hidden="true"></i>
                    </button>
                    <div id="memberPointHistoryList" class="member-purchase-history-list" hidden aria-labelledby="memberPointHistoryToggle"></div>
                </div>
            </div>
        `;

        this._generateBarcode(barcodeId, memberNumber);
        this._setupFlip();
        this._setupPurchaseHistoryToggle();
        this._setupPointHistoryToggle();
    }

    _generateBarcode(barcodeId, memberNumber) {
        if (typeof JsBarcode === 'undefined' || !memberNumber) return;
        setTimeout(() => {
            try {
                const el = document.getElementById(barcodeId);
                if (el) {
                    JsBarcode(`#${barcodeId}`, memberNumber, {
                        format: 'CODE128',
                        width: 3,
                        height: 150,
                        displayValue: true,
                        fontSize: 18,
                        margin: 14,
                        background: '#ffffff',
                        lineColor: '#000000',
                    });
                }
            } catch (err) {
                console.error('Barcode generation error:', err);
            }
        }, AppConstants.ANIMATION_DURATION.BARCODE_DELAY);
    }

    _setupFlip() {
        const wrapper = document.querySelector('.idnect-card-wrapper');
        const card = document.querySelector('.idnect-card');
        if (!wrapper || !card) return;
        const flip = () => card.classList.toggle('is-flipped');
        wrapper.addEventListener('click', (e) => {
            e.preventDefault();
            if (e.target.closest('.member-card-edit-btn')) return;
            flip();
        });
        wrapper.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                flip();
            }
        });
    }

    _setupPurchaseHistoryToggle() {
        const toggle = document.getElementById('memberPurchaseHistoryToggle');
        const listEl = document.getElementById('memberPurchaseHistoryList');
        const icon = document.querySelector('.member-purchase-history-toggle-icon');
        if (!toggle || !listEl) return;

        toggle.addEventListener('click', () => {
            const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
            if (isExpanded) {
                toggle.setAttribute('aria-expanded', 'false');
                listEl.hidden = true;
                if (icon) {
                    icon.classList.remove('fa-chevron-up');
                    icon.classList.add('fa-chevron-down');
                }
                return;
            }
            toggle.setAttribute('aria-expanded', 'true');
            listEl.hidden = false;
            if (icon) {
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            }
            if (!listEl.hasAttribute('data-loaded')) {
                this._loadPurchaseHistory(listEl);
            }
        });
    }

    _setupPointHistoryToggle() {
        const toggle = document.getElementById('memberPointHistoryToggle');
        const listEl = document.getElementById('memberPointHistoryList');
        const icon = toggle ? toggle.querySelector('.member-purchase-history-toggle-icon') : null;
        if (!toggle || !listEl) return;

        toggle.addEventListener('click', () => {
            const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
            if (isExpanded) {
                toggle.setAttribute('aria-expanded', 'false');
                listEl.hidden = true;
                if (icon) {
                    icon.classList.remove('fa-chevron-up');
                    icon.classList.add('fa-chevron-down');
                }
                return;
            }
            toggle.setAttribute('aria-expanded', 'true');
            listEl.hidden = false;
            if (icon) {
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            }
            if (!listEl.hasAttribute('data-loaded')) {
                this._loadPointHistory(listEl);
            }
        });
    }

    async _loadPointHistory(listEl) {
        listEl.innerHTML =
            '<div class="loading member-purchase-history-loading"><i class="fas fa-spinner fa-spin"></i><p>読み込み中...</p></div>';
        try {
            const result = await this.apiClient.get(AppConstants.API_ENDPOINTS.MEMBERS_POINTS);
            listEl.setAttribute('data-loaded', 'true');
            const transactions = (result.data && result.data.transactions) ? result.data.transactions : [];
            if (!transactions.length) {
                listEl.innerHTML = '<div class="member-purchase-history-empty">ポイント履歴がありません</div>';
                return;
            }
            const rows = transactions.map((tx) => {
                const date = tx.created_at ? new Date(tx.created_at).toLocaleDateString('ja-JP', {
                    year: 'numeric', month: 'short', day: 'numeric',
                }) : '';
                const points = tx.points || 0;
                const label = points >= 0 ? '獲得' : '使用';
                const cls = points >= 0 ? 'member-point-history-earned' : 'member-point-history-used';
                return `
                    <div class="member-point-history-item ${cls}">
                        <div class="member-point-history-row">
                            <span class="member-point-history-label">${DomHelper.escapeHtml(label)}</span>
                            <span class="member-point-history-points">${points > 0 ? '+' : ''}${points.toLocaleString()}pt</span>
                        </div>
                        <div class="member-point-history-meta">
                            <span>${DomHelper.escapeHtml(date)}</span>
                            ${tx.description ? `<span>${DomHelper.escapeHtml(tx.description)}</span>` : ''}
                        </div>
                    </div>
                `;
            }).join('');
            listEl.innerHTML = rows;
        } catch (error) {
            console.error('Failed to load point history:', error);
            listEl.innerHTML = '<div class="member-purchase-history-error"><p>ポイント履歴の読み込みに失敗しました</p></div>';
        }
    }

    async _loadPurchaseHistory(listEl) {
        listEl.innerHTML =
            '<div class="loading member-purchase-history-loading"><i class="fas fa-spinner fa-spin"></i><p>読み込み中...</p></div>';
        try {
            const orders = await this.orderManager.fetchOrders();
            listEl.setAttribute('data-loaded', 'true');
            this.orderManager.renderOrderListOnly(listEl, orders);
        } catch (error) {
            console.error('Failed to load purchase history:', error);
            listEl.innerHTML =
                '<div class="member-purchase-history-error"><p>購入履歴の読み込みに失敗しました</p></div>';
        }
    }

    openProfileEdit() {
        if (!this.memberData) return;
        const user = this.memberData.user || (this.memberData.member && this.memberData.member.user);
        const member = this.memberData.member;
        const content = document.getElementById('profileEditModalContent');
        const modal = document.getElementById('profileEditModal');
        if (!content || !modal) return;
        content.innerHTML = this._getProfileEditFormHtml(user, member);
        const form = document.getElementById('profileEditForm');
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                window.app.memberManager.saveProfileEdit(e);
            });
        }
        modal.setAttribute('aria-hidden', 'false');
        modal.classList.add('member-modal--open');
    }

    closeProfileEdit() {
        const modal = document.getElementById('profileEditModal');
        if (modal) {
            modal.setAttribute('aria-hidden', 'true');
            modal.classList.remove('member-modal--open');
        }
    }

    _getProfileEditFormHtml(user, member) {
        const name = (user && user.name) || '';
        const phone = (user && user.phone) || '';
        const birthday = member && member.birthday ? member.birthday.split('T')[0] : '';
        const address = (member && member.address) || '';
        return `
            <div class="member-modal-inner">
                <h3 class="member-modal-title"><i class="fas fa-user-edit"></i> プロフィール編集</h3>
                <form id="profileEditForm" class="member-form">
                    <div class="member-form-group">
                        <label for="profileEditName">お名前</label>
                        <input type="text" id="profileEditName" name="name" value="${DomHelper.escapeHtml(name)}" maxlength="255">
                    </div>
                    <div class="member-form-group">
                        <label for="profileEditPhone">電話番号</label>
                        <input type="tel" id="profileEditPhone" name="phone" value="${DomHelper.escapeHtml(phone)}" maxlength="50">
                    </div>
                    <div class="member-form-group">
                        <label for="profileEditBirthday">誕生日</label>
                        <input type="date" id="profileEditBirthday" name="birthday" value="${DomHelper.escapeHtml(birthday)}">
                    </div>
                    <div class="member-form-group">
                        <label for="profileEditAddress">住所</label>
                        <input type="text" id="profileEditAddress" name="address" value="${DomHelper.escapeHtml(address)}" maxlength="500">
                    </div>
                    <div class="member-form-actions">
                        <button type="button" class="member-btn member-btn--secondary" onclick="window.app.memberManager.closeProfileEdit()">キャンセル</button>
                        <button type="submit" class="member-btn member-btn--primary"><i class="fas fa-check"></i> 保存</button>
                    </div>
                </form>
            </div>
        `;
    }

    async saveProfileEdit(e) {
        if (e) e.preventDefault();
        const form = document.getElementById('profileEditForm');
        if (!form) return;
        const payload = {
            name: (form.querySelector('#profileEditName') || {}).value || '',
            phone: (form.querySelector('#profileEditPhone') || {}).value || '',
            birthday: (form.querySelector('#profileEditBirthday') || {}).value || null,
            address: (form.querySelector('#profileEditAddress') || {}).value || null,
        };
        if (!payload.birthday) payload.birthday = null;
        try {
            const result = await this.apiClient.request(AppConstants.API_ENDPOINTS.MEMBERS_ME, {
                method: 'PUT',
                body: payload,
            });
            if (result.success) {
                this.memberData = result.data;
                this.render(result.data);
                this.closeProfileEdit();
                ToastManager.success('プロフィールを更新しました');
            } else {
                throw new Error(result.error);
            }
        } catch (error) {
            console.error('Profile update error:', error);
            ToastManager.error('更新に失敗しました: ' + (error.message || 'エラーが発生しました'));
        }
    }
}

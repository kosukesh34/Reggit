class CouponsManager {
    constructor(apiClient) {
        this.apiClient = apiClient;
        this._receivedToastTimer = null;
    }

    getSection() {
        return DomHelper.getElementById(AppConstants.ELEMENT_IDS.COUPONS_SECTION);
    }

    getLoginTemplate() {
        const nativeHint = (typeof MemberManager !== 'undefined' && MemberManager.isMobileAppMode())
            ? '会員登録するとクーポンをご利用いただけます。'
            : 'ログインするとクーポンをご利用いただけます。';
        const actionBlock = (typeof MemberManager !== 'undefined' && MemberManager.isMobileAppMode())
            ? `<button type="button" class="btn btn-primary mt-2" onclick="app.switchTab('${AppConstants.TABS.MEMBER}')">
                    <i class="fas fa-user-plus"></i> 会員登録へ
                </button>`
            : '';
        return `
            <div class="member-card-container">
                <div class="member-card member-card--line-only">
                    <div class="member-card-header">
                        <h2 class="member-card-title"><i class="fas fa-ticket-alt"></i> マイクーポン</h2>
                    </div>
                    <div class="member-card-line-only-body">
                        <p class="member-card-line-only-message">${DomHelper.escapeHtml(nativeHint)}</p>
                        ${actionBlock}
                    </div>
                </div>
            </div>
        `;
    }

    getMemberRequiredTemplate(message) {
        const text = message && String(message).trim() ? String(message).trim() : '会員登録が必要です';
        return `
            <div class="coupons-empty">
                <i class="fas fa-id-card"></i>
                <p>${DomHelper.escapeHtml(text)}</p>
                <p class="coupons-empty-sub">会員登録後にクーポンをご利用いただけます</p>
                <button type="button" class="btn btn-primary mt-2" onclick="app.switchTab('${AppConstants.TABS.MEMBER}')">
                    <i class="fas fa-user-plus"></i> 会員登録する
                </button>
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

    async _waitForNativeDeviceInfo(maxWaitMs) {
        if (typeof window === 'undefined' || typeof MemberManager === 'undefined' || !MemberManager.isMobileAppMode()) {
            return;
        }
        const limit = typeof maxWaitMs === 'number' ? maxWaitMs : 2500;
        const start = Date.now();
        while (Date.now() - start < limit) {
            const deviceId = window.__MOBILE_DEVICE_INFO__
                && String(window.__MOBILE_DEVICE_INFO__.device_identifier || '').trim();
            if (deviceId) return;
            await new Promise((resolve) => setTimeout(resolve, 50));
        }
    }

    async load() {
        const section = this.getSection();
        if (!section) return;

        const memberManager = (typeof window !== 'undefined' && window.app && window.app.memberManager)
            ? window.app.memberManager
            : null;

        if (memberManager) {
            if (typeof MemberManager !== 'undefined' && MemberManager.isMobileAppMode()) {
                await this._waitForNativeDeviceInfo(2500);
                await memberManager.ensureIdentityReady(2500);
            } else if (typeof window !== 'undefined' && window.__IS_LIFF__) {
                if (!window.__LINE_USER_ID__) {
                    await memberManager.resolveLineUserIdForClient();
                }
                if (!window.__LINE_USER_ID__) {
                    section.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i><p>読み込み中...</p></div>';
                    return;
                }
            } else {
                if (!window.__LINE_USER_ID__) {
                    await memberManager.resolveLineUserIdForClient();
                }
                const hasIdentity = typeof memberManager.hasAuthenticatedIdentity === 'function'
                    ? memberManager.hasAuthenticatedIdentity()
                    : !!(typeof window !== 'undefined' && window.__LINE_USER_ID__);
                if (!hasIdentity) {
                    section.innerHTML = this.getLoginTemplate();
                    return;
                }
            }
        }

        section.innerHTML =
            '<div class="loading"><i class="fas fa-spinner fa-spin"></i><p>読み込み中...</p></div>';
        try {
            const result = await this.apiClient.get(AppConstants.API_ENDPOINTS.MY_COUPONS);
            if (!result.success) {
                section.innerHTML = this.getErrorTemplate({ message: result.error });
                return;
            }
            const coupons = (result.data && result.data.coupons) ? result.data.coupons : [];
            const defaultImage = result.data && result.data.default_image_url ? result.data.default_image_url : null;
            const message = result.data && result.data.message ? result.data.message : null;
            if ((!coupons || coupons.length === 0) && message) {
                section.innerHTML = this.getMemberRequiredTemplate(message);
                return;
            }
            this.render(coupons, defaultImage);
        } catch (error) {
            console.error('Failed to load coupons:', error);
            section.innerHTML = this.getErrorTemplate(error);
        }
    }

    /** クーポン通知受信時: 一覧を更新し、必要ならトースト表示 */
    async onCouponReceived(options) {
        options = options || {};
        const wasOnCouponsTab = typeof window !== 'undefined'
            && window.app
            && window.app.currentTab === AppConstants.TABS.COUPONS;
        await this.load();
        if (options.silent) return;
        if (wasOnCouponsTab || options.showToast === false) return;
        this.showReceivedToast(options.message || '新しいクーポンが届きました');
    }

    showReceivedToast(message) {
        if (typeof document === 'undefined') return;
        let toast = document.getElementById('coupon-received-toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'coupon-received-toast';
            toast.className = 'coupon-received-toast';
            toast.setAttribute('role', 'status');
            document.body.appendChild(toast);
        }
        toast.innerHTML = '<i class="fas fa-ticket-alt"></i><span>' + DomHelper.escapeHtml(message) + '</span>';
        toast.classList.add('is-visible');
        if (this._receivedToastTimer) clearTimeout(this._receivedToastTimer);
        this._receivedToastTimer = setTimeout(() => {
            toast.classList.remove('is-visible');
        }, 4000);
    }

    isRecentlyIssued(issuedAt) {
        if (!issuedAt) return false;
        const issued = new Date(issuedAt);
        if (Number.isNaN(issued.getTime())) return false;
        const diffMs = Date.now() - issued.getTime();
        return diffMs >= 0 && diffMs <= 72 * 60 * 60 * 1000;
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
            const design = cp.design_template || 'classic';
            const themeColor = cp.theme_color && /^#[0-9A-Fa-f]{6}$/.test(cp.theme_color) ? cp.theme_color : '';
            const label = statusLabels[status] || status;
            const validUntil = cp.valid_until
                ? new Date(cp.valid_until).toLocaleDateString('ja-JP', {
                      year: 'numeric', month: 'short', day: 'numeric',
                  })
                : '';
            const imageUrl = cp.image_url || defaultImage;
            const accentStyle = themeColor ? ` style="--coupon-accent: ${DomHelper.escapeHtml(themeColor)}"` : '';
            const iconClass = design === 'barcode' ? 'fa-barcode' : design === 'qr' ? 'fa-qrcode' : 'fa-ticket-alt';
            let imageBlock;
            if (design === 'barcode') {
                imageBlock = '<div class="coupon-item-barcode"><div class="coupon-item-barcode-lines"></div><span class="coupon-item-barcode-code">' + DomHelper.escapeHtml(cp.coupon_name || 'クーポン') + '</span></div>';
            } else if (design === 'qr') {
                imageBlock = '<div class="coupon-item-qr"><div class="coupon-item-qr-grid"></div></div>';
            } else if (imageUrl) {
                imageBlock = '<div class="coupon-item-image"><img src="' + DomHelper.escapeHtml(imageUrl) + '" alt="クーポン"></div>';
            } else {
                imageBlock = '<div class="coupon-item-image coupon-item-image--icon"><i class="fas ' + iconClass + '" aria-hidden="true"></i></div>';
            }
            const descriptionBlock = (cp.description && cp.description.trim()) ? '<p class="coupon-item-description">' + DomHelper.escapeHtml(cp.description) + '</p>' : '';
            const useDisplay = cp.use_display || 'none';
            const code = (cp.code && String(cp.code).trim()) ? cp.code : '';
            const showUseButton = status === 'available' && (useDisplay !== 'none' || code);
            const useBtnBlock = showUseButton
                ? '<div class="coupon-item-actions"><button type="button" class="btn btn-primary coupon-use-btn" data-member-coupon-id="' + DomHelper.escapeHtml(String(cp.id)) + '" data-use-display="' + DomHelper.escapeHtml(useDisplay) + '" data-code="' + DomHelper.escapeHtml(code) + '" data-coupon-name="' + DomHelper.escapeHtml(cp.coupon_name || 'クーポン') + '"><i class="fas fa-hand-holding-usd me-1"></i>使用する</button></div>'
                : '';
            const showNewBadge = status === 'available' && this.isRecentlyIssued(cp.issued_at);
            const newBadge = showNewBadge ? '<span class="coupon-item-badge coupon-item-badge--new">NEW</span>' : '';
            return `
                <div class="coupon-item coupon-item--${status} coupon-item--design-${design}"${accentStyle}>
                    ${imageBlock}
                    <div class="coupon-item-header">
                        <span class="coupon-item-name"><i class="fas ${iconClass} coupon-item-name-icon" aria-hidden="true"></i>${DomHelper.escapeHtml(cp.coupon_name || 'クーポン')}${newBadge}</span>
                        <span class="coupon-item-status">${DomHelper.escapeHtml(label)}</span>
                    </div>
                    ${validUntil ? '<p class="coupon-item-valid">有効期限: ' + DomHelper.escapeHtml(validUntil) + '</p>' : ''}
                    ${descriptionBlock}
                    ${useBtnBlock}
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

        html += `
            <div id="coupon-use-modal" class="coupon-use-modal" aria-hidden="true">
                <div class="coupon-use-modal-backdrop"></div>
                <div class="coupon-use-modal-content">
                    <div class="coupon-use-modal-header">
                        <h3 class="coupon-use-modal-title">クーポンを使用</h3>
                        <button type="button" class="coupon-use-modal-close" aria-label="閉じる">&times;</button>
                    </div>
                    <div class="coupon-use-modal-body">
                        <p class="coupon-use-modal-name" id="coupon-use-modal-name"></p>
                        <div id="coupon-use-modal-code-area"></div>
                    </div>
                </div>
            </div>`;
        section.innerHTML = html;

        this._bindUseButtons(section);
    }

    _bindUseButtons(section) {
        const modal = section.querySelector('#coupon-use-modal');
        if (!modal) return;
        const backdrop = modal.querySelector('.coupon-use-modal-backdrop');
        const closeBtn = modal.querySelector('.coupon-use-modal-close');
        const nameEl = modal.querySelector('#coupon-use-modal-name');
        const codeArea = modal.querySelector('#coupon-use-modal-code-area');
        const showModal = (name, useDisplay, code) => {
            nameEl.textContent = name;
            codeArea.innerHTML = '';
            if (useDisplay === 'barcode' && code && typeof JsBarcode !== 'undefined') {
                const svg = document.createElement('svg');
                svg.id = 'coupon-use-barcode-svg';
                codeArea.appendChild(svg);
                try {
                    JsBarcode('#coupon-use-barcode-svg', code, { format: 'CODE128', width: 2, height: 60, displayValue: true });
                } catch (e) {
                    codeArea.innerHTML = '<p class="coupon-use-code-text">' + DomHelper.escapeHtml(code) + '</p>';
                }
            } else if (useDisplay === 'qr' && code && typeof QRCode !== 'undefined') {
                const div = document.createElement('div');
                div.id = 'coupon-use-qr-container';
                div.className = 'coupon-use-qr-wrap';
                codeArea.appendChild(div);
                try {
                    new QRCode(div, { text: code, width: 160, height: 160 });
                } catch (e) {
                    codeArea.innerHTML = '<p class="coupon-use-code-text">' + DomHelper.escapeHtml(code) + '</p>';
                }
            } else if (code) {
                codeArea.innerHTML = '<p class="coupon-use-code-label">クーポンコード</p><p class="coupon-use-code-text">' + DomHelper.escapeHtml(code) + '</p>';
            } else {
                codeArea.innerHTML = '<p class="coupon-use-code-text text-muted">表示するコードはありません</p>';
            }
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
        };
        const hideModal = () => {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
        };
        section.querySelectorAll('.coupon-use-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const name = btn.getAttribute('data-coupon-name') || 'クーポン';
                const useDisplay = btn.getAttribute('data-use-display') || 'none';
                const code = btn.getAttribute('data-code') || '';
                showModal(name, useDisplay, code);
            });
        });
        if (backdrop) backdrop.addEventListener('click', hideModal);
        if (closeBtn) closeBtn.addEventListener('click', hideModal);
    }
}

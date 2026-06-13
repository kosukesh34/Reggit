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
                this._fixNativeCouponsScroll();
                return;
            }
            this.render(coupons, defaultImage);
            this._fixNativeCouponsScroll();
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

    normalizeStatus(status) {
        const value = status ? String(status) : 'available';
        if (value === 'unused') return 'available';
        if (value === 'upcoming' || value === 'available' || value === 'used' || value === 'expired') {
            return value;
        }
        return 'available';
    }

    formatDate(value) {
        if (!value) return '';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return '';
        return date.toLocaleDateString('ja-JP', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    }

    getDaysUntil(value) {
        if (!value) return null;
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return null;
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        date.setHours(0, 0, 0, 0);
        return Math.ceil((date.getTime() - today.getTime()) / (24 * 60 * 60 * 1000));
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
            this._fixNativeCouponsScroll();
            return;
        }
        const statusLabels = { available: '利用可能', upcoming: '開始前', used: '使用済み', expired: '期限切れ' };
        const statusGroups = { available: [], upcoming: [], used: [], expired: [] };

        coupons.forEach(cp => {
            const status = this.normalizeStatus(cp.status);
            if (!statusGroups[status]) statusGroups[status] = [];
            statusGroups[status].push(cp);
        });

        const renderCoupon = (cp) => {
            const status = this.normalizeStatus(cp.status);
            const design = cp.design_template || 'classic';
            const themeColor = cp.theme_color && /^#[0-9A-Fa-f]{6}$/.test(cp.theme_color) ? cp.theme_color : '';
            const label = statusLabels[status] || status;
            const validFrom = this.formatDate(cp.valid_from);
            const validUntil = this.formatDate(cp.valid_until);
            const issuedAt = this.formatDate(cp.issued_at);
            const usedAt = this.formatDate(cp.used_at);
            const daysUntilExpiry = status === 'available' ? this.getDaysUntil(cp.valid_until) : null;
            const expiryClass = daysUntilExpiry !== null && daysUntilExpiry >= 0 && daysUntilExpiry <= 7
                ? ' coupon-item-valid--urgent'
                : '';
            let validText = '';
            if (validFrom && validUntil) {
                validText = validFrom + ' - ' + validUntil;
            } else if (validUntil) {
                validText = '有効期限: ' + validUntil;
            } else if (validFrom) {
                validText = validFrom + ' から利用可能';
            }
            const expiryBadge = daysUntilExpiry !== null && daysUntilExpiry >= 0 && daysUntilExpiry <= 7
                ? '<span class="coupon-item-badge coupon-item-badge--urgent">あと' + daysUntilExpiry + '日</span>'
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
            const code = (cp.code && String(cp.code).trim()) ? cp.code : '';
            const useDisplay = this._normalizeUseDisplay(cp.use_display, code);
            const canShowCode = useDisplay === 'barcode' || useDisplay === 'qr' || useDisplay === 'both';
            const showUseButton = status === 'available' && (canShowCode || code);
            const unavailableHint = status === 'upcoming' && validFrom
                ? '<p class="coupon-item-unavailable"><i class="fas fa-clock" aria-hidden="true"></i>' + DomHelper.escapeHtml(validFrom) + 'から利用できます</p>'
                : '';
            const useBtnBlock = showUseButton
                ? '<div class="coupon-item-actions"><button type="button" class="btn btn-primary coupon-use-btn" data-member-coupon-id="' + DomHelper.escapeHtml(String(cp.id)) + '" data-use-display="' + DomHelper.escapeHtml(useDisplay) + '" data-code="' + DomHelper.escapeHtml(code) + '" data-coupon-name="' + DomHelper.escapeHtml(cp.coupon_name || 'クーポン') + '"><i class="fas fa-hand-holding-usd me-1"></i>使用する</button></div>'
                : '';
            const showNewBadge = status === 'available' && this.isRecentlyIssued(cp.issued_at);
            const newBadge = showNewBadge ? '<span class="coupon-item-badge coupon-item-badge--new">NEW</span>' : '';
            const usedMeta = status === 'used' && usedAt
                ? '<span><i class="fas fa-check" aria-hidden="true"></i> 使用日: ' + DomHelper.escapeHtml(usedAt) + '</span>'
                : '';
            const issuedMeta = issuedAt
                ? '<span><i class="fas fa-plus-circle" aria-hidden="true"></i> 発行日: ' + DomHelper.escapeHtml(issuedAt) + '</span>'
                : '';
            const metaBlock = (issuedMeta || usedMeta)
                ? '<div class="coupon-item-meta">' + usedMeta + issuedMeta + '</div>'
                : '';
            return `
                <div class="coupon-item coupon-item--${status} coupon-item--design-${design}"${accentStyle}>
                    ${imageBlock}
                    <div class="coupon-item-header">
                        <span class="coupon-item-name"><i class="fas ${iconClass} coupon-item-name-icon" aria-hidden="true"></i>${DomHelper.escapeHtml(cp.coupon_name || 'クーポン')}${newBadge}${expiryBadge}</span>
                        <span class="coupon-item-status coupon-item-status--${status}">${DomHelper.escapeHtml(label)}</span>
                    </div>
                    ${validText ? '<p class="coupon-item-valid' + expiryClass + '">' + DomHelper.escapeHtml(validText) + '</p>' : ''}
                    ${descriptionBlock}
                    ${metaBlock}
                    ${unavailableHint}
                    ${useBtnBlock}
                </div>
            `;
        };

        const visibleCoupons = statusGroups.available.concat(statusGroups.upcoming).sort((a, b) => {
            const weight = { available: 0, upcoming: 1 };
            const statusA = weight[this.normalizeStatus(a.status)] || 0;
            const statusB = weight[this.normalizeStatus(b.status)] || 0;
            if (statusA !== statusB) return statusA - statusB;
            const dateA = a.valid_until || a.valid_from || a.issued_at || '';
            const dateB = b.valid_until || b.valid_from || b.issued_at || '';
            return String(dateA).localeCompare(String(dateB));
        });

        const historyCoupons = statusGroups.used.concat(statusGroups.expired).sort((a, b) => {
            const dateA = a.used_at || a.valid_until || a.issued_at || '';
            const dateB = b.used_at || b.valid_until || b.issued_at || '';
            return String(dateB).localeCompare(String(dateA));
        });

        const availableCount = visibleCoupons.length;
        const historyCount = historyCoupons.length;
        const defaultTab = availableCount > 0 ? 'available' : 'history';

        const renderEmpty = (message) =>
            '<div class="coupons-tab-empty"><i class="fas fa-ticket-alt"></i><p>' + DomHelper.escapeHtml(message) + '</p></div>';

        let html =
            '<div class="coupons-header"><h2 class="section-title"><i class="fas fa-ticket-alt"></i> マイクーポン</h2></div>' +
            '<div class="coupons-list-tabs" role="tablist" aria-label="クーポン一覧">' +
            '<button type="button" class="coupons-list-tab' + (defaultTab === 'available' ? ' is-active' : '') + '" role="tab" data-tab="available" aria-selected="' + (defaultTab === 'available' ? 'true' : 'false') + '">' +
            '<i class="fas fa-check-circle" aria-hidden="true"></i> 利用可能' +
            '<span class="coupons-list-tab-count">' + availableCount + '</span>' +
            '</button>' +
            '<button type="button" class="coupons-list-tab' + (defaultTab === 'history' ? ' is-active' : '') + '" role="tab" data-tab="history" aria-selected="' + (defaultTab === 'history' ? 'true' : 'false') + '">' +
            '<i class="fas fa-history" aria-hidden="true"></i> 利用済み・期限切れ' +
            '<span class="coupons-list-tab-count">' + historyCount + '</span>' +
            '</button>' +
            '</div>' +
            '<div class="coupons-tab-panel' + (defaultTab === 'available' ? ' is-active' : '') + '" data-panel="available" role="tabpanel">' +
            (availableCount > 0
                ? '<div class="coupons-list">' + visibleCoupons.map(renderCoupon).join('') + '</div>'
                : renderEmpty('利用可能なクーポンはありません')) +
            '</div>' +
            '<div class="coupons-tab-panel' + (defaultTab === 'history' ? ' is-active' : '') + '" data-panel="history" role="tabpanel">' +
            (historyCount > 0
                ? '<div class="coupons-list">' + historyCoupons.map(renderCoupon).join('') + '</div>'
                : renderEmpty('利用済み・期限切れのクーポンはありません')) +
            '</div>';

        section.innerHTML = html;

        this._bindCouponTabs(section);
        this._bindUseButtons(section);
    }

    _bindCouponTabs(section) {
        const tabs = section.querySelectorAll('.coupons-list-tab');
        const panels = section.querySelectorAll('.coupons-tab-panel');
        if (!tabs.length || !panels.length) return;

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const target = tab.getAttribute('data-tab');
                if (!target) return;
                tabs.forEach(btn => {
                    const active = btn === tab;
                    btn.classList.toggle('is-active', active);
                    btn.setAttribute('aria-selected', active ? 'true' : 'false');
                });
                panels.forEach(panel => {
                    panel.classList.toggle('is-active', panel.getAttribute('data-panel') === target);
                });
            });
        });
    }

    async _confirmCouponUse(couponName) {
        const name = (couponName && String(couponName).trim()) ? String(couponName).trim() : 'クーポン';
        if (typeof ConfirmDialog !== 'undefined') {
            const safeName = DomHelper.escapeHtml(name);
            return ConfirmDialog.show(
                '「' + safeName + '」を使用しますか？<br><br>使用後は再度ご利用いただけません。<br>店舗スタッフにバーコードを提示してください。',
                'クーポンの使用確認'
            );
        }
        return window.confirm(
            '「' + name + '」を使用しますか？\n\n使用後は再度ご利用いただけません。\n店舗スタッフにバーコードを提示してください。'
        );
    }

    _showCouponCodeModal(couponName, useDisplay, code) {
        const display = this._normalizeUseDisplay(useDisplay, code);
        const isMobile = this._isMobileAppMode();
        const overlay = document.createElement('div');
        overlay.className = 'member-code-enlarge-overlay coupon-use-enlarge-overlay';
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');
        overlay.setAttribute('aria-label', 'クーポン ' + ((couponName && String(couponName).trim()) ? String(couponName).trim() : 'クーポン'));

        const tabsWrapId = 'coupon-use-tabs-' + String(Date.now());
        const codeAreaId = 'coupon-use-code-' + String(Date.now());
        const title = (couponName && String(couponName).trim()) ? String(couponName).trim() : 'クーポン';

        overlay.innerHTML =
            '<div class="member-code-enlarge-backdrop"></div>' +
            '<div class="member-code-enlarge-content">' +
            '<div class="member-code-enlarge-header">' +
            '<h3 class="member-code-enlarge-title">' + DomHelper.escapeHtml(title) + '</h3>' +
            '<button type="button" class="member-code-enlarge-close" aria-label="閉じる"><i class="fas fa-times" aria-hidden="true"></i></button>' +
            '</div>' +
            '<div class="member-code-enlarge-body">' +
            '<p class="coupon-use-modal-complete"><i class="fas fa-check-circle" aria-hidden="true"></i> 使用済みにしました</p>' +
            '<p class="coupon-use-modal-hint"><i class="fas fa-store" aria-hidden="true"></i> 店舗スタッフにこの画面を提示してください</p>' +
            '<div id="' + tabsWrapId + '"></div>' +
            '<div id="' + codeAreaId + '" class="coupon-use-code-area"></div>' +
            '</div>' +
            '</div>';

        document.body.appendChild(overlay);

        const tabsWrap = overlay.querySelector('#' + tabsWrapId);
        const codeArea = overlay.querySelector('#' + codeAreaId);
        const manager = this;

        const close = () => overlay.remove();
        overlay.querySelector('.member-code-enlarge-backdrop').addEventListener('click', close);
        overlay.querySelector('.member-code-enlarge-close').addEventListener('click', close);
        overlay.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                e.preventDefault();
                close();
            }
        });

        const renderCodeDisplay = (mode, value) => manager._renderCouponCodeDisplay(codeArea, mode, value, isMobile, true);

        const bindDisplayTabs = (value, initialMode) => {
            if (!tabsWrap) return;
            tabsWrap.innerHTML =
                '<div class="coupon-use-display-tabs" role="tablist" aria-label="表示形式">' +
                '<button type="button" class="coupon-use-display-tab' + (initialMode === 'barcode' ? ' is-active' : '') + '" role="tab" data-mode="barcode" aria-selected="' + (initialMode === 'barcode' ? 'true' : 'false') + '">' +
                '<i class="fas fa-barcode" aria-hidden="true"></i> バーコード</button>' +
                '<button type="button" class="coupon-use-display-tab' + (initialMode === 'qr' ? ' is-active' : '') + '" role="tab" data-mode="qr" aria-selected="' + (initialMode === 'qr' ? 'true' : 'false') + '">' +
                '<i class="fas fa-qrcode" aria-hidden="true"></i> QRコード</button>' +
                '</div>';
            tabsWrap.querySelectorAll('.coupon-use-display-tab').forEach(tab => {
                tab.addEventListener('click', () => {
                    const mode = tab.getAttribute('data-mode') || 'barcode';
                    tabsWrap.querySelectorAll('.coupon-use-display-tab').forEach(btn => {
                        const active = btn === tab;
                        btn.classList.toggle('is-active', active);
                        btn.setAttribute('aria-selected', active ? 'true' : 'false');
                    });
                    renderCodeDisplay(mode, value).catch(() => {});
                });
            });
        };

        codeArea.innerHTML = '<p class="coupon-use-code-text text-muted"><i class="fas fa-spinner fa-spin"></i> 読み込み中...</p>';

        let renderPromise;
        if (display === 'both' && code) {
            bindDisplayTabs(code, 'barcode');
            renderPromise = renderCodeDisplay('barcode', code);
        } else if (display === 'barcode' && code) {
            renderPromise = renderCodeDisplay('barcode', code);
        } else if (display === 'qr' && code) {
            renderPromise = renderCodeDisplay('qr', code);
        } else if (code) {
            renderPromise = renderCodeDisplay('barcode', code);
        } else {
            renderPromise = renderCodeDisplay('none', '');
        }

        renderPromise.catch(() => {});
        return renderPromise;
    }

    _isMobileAppMode() {
        return typeof MemberManager !== 'undefined' && MemberManager.isMobileAppMode();
    }

    _fixNativeCouponsScroll() {
        if (!this._isMobileAppMode() || typeof document === 'undefined') return;
        if (document.getElementById('native-coupons-scroll-fix')) return;
        const style = document.createElement('style');
        style.id = 'native-coupons-scroll-fix';
        style.textContent =
            'html.native-app-embed .tab-content.active,' +
            'html.native-app-embed .tab-content.active .tab-section,' +
            'html.native-app-embed #couponsTab.tab-content.active,' +
            'html.native-app-embed #couponsSection.tab-section {' +
            'overflow-y: auto !important;' +
            'overflow-x: hidden !important;' +
            '-webkit-overflow-scrolling: touch !important;' +
            'touch-action: pan-y !important;' +
            'height: auto !important;' +
            'max-height: none !important;' +
            '}';
        document.head.appendChild(style);
    }

    _normalizeUseDisplay(useDisplay, code) {
        const display = (useDisplay && String(useDisplay).trim()) || 'barcode';
        if (display === 'none' && code) return 'barcode';
        return display;
    }

    _getCodeLibraryAssetVersion() {
        const hint = document.querySelector('script[src*="JsBarcode"], script[src*="app.js"], script[src*="CouponsManager"]');
        if (!hint || !hint.src) return '';
        const q = hint.src.indexOf('?');
        return q >= 0 ? hint.src.slice(q) : '';
    }

    _codeLibraryPaths(libraryName) {
        const file = libraryName === 'qr' ? '/js/vendor/qrcode.min.js' : '/js/vendor/JsBarcode.all.min.js';
        const version = this._getCodeLibraryAssetVersion();
        return [
            file + version,
            file,
            '/public' + file + version,
            '/public' + file,
        ];
    }

    _waitForCodeLibraryGlobal(globalName, timeoutMs) {
        const limit = typeof timeoutMs === 'number' ? timeoutMs : 5000;
        return new Promise((resolve, reject) => {
            const start = Date.now();
            const tick = () => {
                if (typeof window !== 'undefined' && typeof window[globalName] !== 'undefined') {
                    resolve(window[globalName]);
                    return;
                }
                if (Date.now() - start >= limit) {
                    reject(new Error(globalName + ' unavailable'));
                    return;
                }
                setTimeout(tick, 50);
            };
            tick();
        });
    }

    _loadCodeLibraryScript(paths, libraryName) {
        let index = 0;
        const tryNext = () => {
            if (index >= paths.length) {
                return Promise.reject(new Error('Failed to load code library'));
            }
            const src = paths[index++];
            return new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = src;
                script.setAttribute('data-coupon-code-lib', libraryName);
                script.onload = () => resolve();
                script.onerror = () => {
                    script.remove();
                    tryNext().then(resolve).catch(reject);
                };
                document.head.appendChild(script);
            });
        };
        return tryNext();
    }

    _ensureCodeLibrary(libraryName) {
        const globalName = libraryName === 'qr' ? 'QRCode' : 'JsBarcode';
        if (typeof window !== 'undefined' && typeof window[globalName] !== 'undefined') {
            return Promise.resolve(window[globalName]);
        }
        const srcHint = libraryName === 'qr' ? 'qrcode.min.js' : 'JsBarcode.all.min.js';
        const existing = document.querySelector(
            'script[data-coupon-code-lib="' + libraryName + '"], script[src*="' + srcHint + '"], script[data-inline-path*="' + srcHint + '"]'
        );
        if (existing) {
            return this._waitForCodeLibraryGlobal(globalName, 5000);
        }
        return this._loadCodeLibraryScript(this._codeLibraryPaths(libraryName), libraryName)
            .then(() => this._waitForCodeLibraryGlobal(globalName, 3000));
    }

    _barcodeRenderDelay() {
        return (typeof AppConstants !== 'undefined' && AppConstants.ANIMATION_DURATION)
            ? AppConstants.ANIMATION_DURATION.BARCODE_DELAY
            : 100;
    }

    _getJsBarcodeLib() {
        if (typeof JsBarcode !== 'undefined') return JsBarcode;
        if (typeof window !== 'undefined' && typeof window.JsBarcode !== 'undefined') return window.JsBarcode;
        return null;
    }

    _getQrCodeLib() {
        if (typeof QRCode !== 'undefined') return QRCode;
        if (typeof window !== 'undefined' && typeof window.QRCode !== 'undefined') return window.QRCode;
        return null;
    }

    _renderCouponCodeTextFallback(codeArea, code) {
        codeArea.innerHTML = '<p class="coupon-use-code-label">クーポンコード</p><p class="coupon-use-code-text">' + DomHelper.escapeHtml(code) + '</p>';
    }

    _isBarcodeElementRendered(element) {
        if (!element) return false;
        const tag = (element.tagName || '').toLowerCase();
        if (tag === 'canvas') {
            return element.width > 0 && element.height > 0;
        }
        if (tag === 'svg') {
            if (element.children && element.children.length > 0) return true;
            try {
                return typeof element.getBBox === 'function' && element.getBBox().width > 0;
            } catch (e) {
                return false;
            }
        }
        return false;
    }

    _drawCouponBarcode(codeArea, code, isMobile, enlarged) {
        const value = String(code || '').trim();
        if (!value) return false;

        const lib = this._getJsBarcodeLib();
        if (typeof lib !== 'function') return false;

        const barcodeId = 'coupon-use-barcode-' + String(Date.now()) + '-' + Math.random().toString(36).slice(2, 7);
        const wrapClass = enlarged ? 'member-code-enlarge-barcode-wrap' : 'coupon-use-barcode-wrap';
        const svgClass = enlarged ? 'member-code-enlarge-barcode-svg' : 'coupon-use-barcode-svg';
        const options = enlarged
            ? {
                format: 'CODE128',
                width: isMobile ? 4 : 3,
                height: isMobile ? 220 : 180,
                displayValue: true,
                fontSize: isMobile ? 24 : 20,
                margin: 20,
                background: '#ffffff',
                lineColor: '#000000',
            }
            : {
                format: 'CODE128',
                width: isMobile ? 3 : 2,
                height: isMobile ? 100 : 72,
                displayValue: true,
                fontSize: isMobile ? 16 : 14,
                margin: isMobile ? 10 : 8,
                background: '#ffffff',
                lineColor: '#000000',
            };

        codeArea.innerHTML =
            '<div class="' + wrapClass + '">' +
            '<svg id="' + barcodeId + '" class="' + svgClass + '" role="img" aria-label="クーポンバーコード ' + DomHelper.escapeHtml(value) + '"></svg>' +
            '</div>';

        try {
            const selector = '#' + barcodeId;
            lib(selector, value, options);
            let element = document.getElementById(barcodeId);
            if (this._isBarcodeElementRendered(element)) {
                return true;
            }
            lib(selector, value, options);
            element = document.getElementById(barcodeId);
            return this._isBarcodeElementRendered(element);
        } catch (e) {
            console.error('Coupon barcode error:', e);
            return false;
        }
    }

    _renderCouponBarcode(codeArea, code, isMobile, enlarged) {
        const value = String(code || '').trim();
        if (!value) {
            codeArea.innerHTML = '<p class="coupon-use-code-text text-muted">表示するコードはありません</p>';
            return Promise.resolve();
        }

        const renderWithLibrary = () => new Promise((resolve) => {
            const draw = () => {
                if (this._drawCouponBarcode(codeArea, value, isMobile, enlarged)) {
                    resolve();
                    return;
                }
                this._renderCouponCodeTextFallback(codeArea, value);
                resolve();
            };
            setTimeout(draw, this._barcodeRenderDelay());
        });

        if (this._getJsBarcodeLib()) {
            return renderWithLibrary();
        }

        return this._ensureCodeLibrary('barcode')
            .then(() => renderWithLibrary())
            .catch(() => {
                this._renderCouponCodeTextFallback(codeArea, value);
            });
    }

    _drawCouponQr(codeArea, code, isMobile, enlarged) {
        const value = String(code || '').trim();
        if (!value) return false;

        codeArea.innerHTML = '';
        const div = document.createElement('div');
        div.className = enlarged ? 'member-code-enlarge-qr-wrap coupon-use-qr-wrap' : 'coupon-use-qr-wrap';
        codeArea.appendChild(div);

        try {
            const QRCodeLib = this._getQrCodeLib();
            if (typeof QRCodeLib !== 'function') {
                return false;
            }
            const qrSize = enlarged ? (isMobile ? 280 : 240) : (isMobile ? 200 : 160);
            new QRCodeLib(div, {
                text: value,
                width: qrSize,
                height: qrSize,
                colorDark: '#000000',
                colorLight: '#ffffff',
            });
            return div.childNodes.length > 0;
        } catch (e) {
            return false;
        }
    }

    _renderCouponQr(codeArea, code, isMobile, enlarged) {
        const value = String(code || '').trim();
        if (!value) {
            codeArea.innerHTML = '<p class="coupon-use-code-text text-muted">表示するコードはありません</p>';
            return Promise.resolve();
        }

        const renderWithLibrary = () => new Promise((resolve) => {
            setTimeout(() => {
                if (this._drawCouponQr(codeArea, value, isMobile, enlarged)) {
                    resolve();
                    return;
                }
                this._renderCouponCodeTextFallback(codeArea, value);
                resolve();
            }, this._barcodeRenderDelay());
        });

        if (this._getQrCodeLib()) {
            return renderWithLibrary();
        }

        return this._ensureCodeLibrary('qr')
            .then(() => renderWithLibrary())
            .catch(() => {
                this._renderCouponCodeTextFallback(codeArea, value);
            });
    }

    _renderCouponCodeDisplay(codeArea, mode, code, isMobile, enlarged) {
        if (!code) {
            codeArea.innerHTML = '<p class="coupon-use-code-text text-muted">表示するコードはありません</p>';
            return Promise.resolve();
        }
        if (mode === 'qr') {
            return this._renderCouponQr(codeArea, code, isMobile, enlarged);
        }
        return this._renderCouponBarcode(codeArea, code, isMobile, enlarged);
    }

    _bindUseButtons(section) {
        const manager = this;

        section.querySelectorAll('.coupon-use-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                const memberCouponId = btn.getAttribute('data-member-coupon-id');
                if (!memberCouponId) return;

                const couponName = btn.getAttribute('data-coupon-name') || 'クーポン';
                const confirmed = await manager._confirmCouponUse(couponName);
                if (!confirmed) return;

                const originalHtml = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>処理中...';

                const result = await manager.apiClient.post(
                    AppConstants.API_ENDPOINTS.MY_COUPON_USE(memberCouponId),
                    {}
                );

                if (!result.success) {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                    window.alert(result.error || 'クーポンの使用に失敗しました');
                    return;
                }

                const payload = result.data && result.data.data ? result.data.data : result.data;
                const name = (payload && payload.coupon_name) || couponName;
                const code = (payload && payload.code) || btn.getAttribute('data-code') || '';
                const useDisplay = manager._normalizeUseDisplay(
                    (payload && payload.use_display) || btn.getAttribute('data-use-display'),
                    code
                );

                manager._showCouponCodeModal(name, useDisplay, code)
                    .catch(() => {})
                    .then(() => manager.load());
            });
        });
    }
}

class MemberManager {
    constructor(apiClient, orderManager) {
        this.apiClient = apiClient;
        this.orderManager = orderManager;
        this.memberData = null;
    }

    static isIOS() {
        if (typeof navigator === 'undefined') return false;
        return /iPad|iPhone|iPod/.test(navigator.userAgent) ||
            (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    }

    static isAndroid() {
        if (typeof navigator === 'undefined') return false;
        return /Android/.test(navigator.userAgent);
    }

    static isWalletPassEnabled() {
        return typeof window !== 'undefined' && window.__WALLET_PASS_ENABLED__ === true;
    }

    static isGoogleWalletEnabled() {
        return typeof window !== 'undefined' && window.__GOOGLE_WALLET_ENABLED__ === true;
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

    /** 会員未登録（ゲスト）で会員証を表示しない設定のときの表示 */
    getGuestNoCardTemplate() {
        return `
            <div class="member-card-container">
                <div class="member-card member-card--line-only">
                    <div class="member-card-header">
                        <h2 class="member-card-title"><i class="fas fa-id-card"></i> 会員証</h2>
                    </div>
                    <div class="member-card-line-only-body">
                        <p class="member-card-line-only-message">会員登録されていません。</p>
                        <p class="member-card-line-only-sub">会員登録すると会員証が表示されます。</p>
                        <button type="button" class="btn btn-primary mt-2" onclick="window.app.memberManager.openRegisterModal()">
                            <i class="fas fa-user-plus"></i> 会員登録する
                        </button>
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
                throw new Error(result.error || (result.data && result.data.message) || '会員情報の取得に失敗しました');
            }
            const data = result.data;
            const payload = (data && data.member) ? data.member : data;
            if (!payload || typeof payload.is_member === 'undefined') {
                throw new Error('会員情報の形式が正しくありません');
            }
            if (!payload.is_member) {
                if (payload.show_member_card_for_guest === false) {
                    container.innerHTML = this.getGuestNoCardTemplate();
                    this.memberData = payload;
                    return;
                }
                await this.autoRegister();
                return;
            }
            this.memberData = payload;
            this.render(payload);
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
        const currentRankColor = data.current_rank_color || null;
        const nextRankLabel = data.next_rank_label || null;
        const pointsToNextRank = data.points_to_next_rank != null ? data.points_to_next_rank : null;
        const daysToNextRank = data.days_to_next_rank != null ? data.days_to_next_rank : null;
        const daysRequiredForNextRank = data.days_required_for_next_rank != null ? data.days_required_for_next_rank : null;
        const rankUpHistory = Array.isArray(data.rank_up_history) ? data.rank_up_history : [];
        const pointsExpiry = data.points_expiry || null;
        const currentRankKey = data.current_rank || 'bronze';
        const showPointsDisplay = data.show_points_display !== false;
        const showRankProgress = data.show_rank_progress !== false;
        const memberCardDesign = data.member_card_design || 'default';
        const memberCardLogoUrl = data.member_card_logo_url || null;

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
        const showAddToAppleWallet = MemberManager.isIOS() && MemberManager.isWalletPassEnabled();
        const showAddToGoogleWallet = MemberManager.isAndroid() && MemberManager.isGoogleWalletEnabled();
        const walletPassImageUrl = (typeof window !== 'undefined' && window.__WALLET_PASS_ADD_BUTTON_IMAGE__) || '/images/add-to-apple-wallet-logo.png';
        const projectKey = (typeof window !== 'undefined' && window.__PROJECT_KEY__) ? window.__PROJECT_KEY__ : 'default';
        const walletPassUrl = AppConstants.API_ENDPOINTS.MEMBERS_WALLET_PASS + '?project_key=' + encodeURIComponent(projectKey);
        const cardStyle = currentRankColor ? ` data-rank-color="true" style="--idnect-card-rank-color: ${DomHelper.escapeHtml(currentRankColor)}"` : '';
        const cardDesignClass = 'idnect-card-wrapper--design-' + (['default', 'logo_top', 'logo_center'].indexOf(memberCardDesign) >= 0 ? memberCardDesign : 'default');
        const showLogoOnCard = (memberCardDesign === 'logo_top' || memberCardDesign === 'logo_center') && memberCardLogoUrl;
        const logoBlock = showLogoOnCard
            ? `<div class="idnect-card-logo-wrap"><img src="${DomHelper.escapeHtml(memberCardLogoUrl)}" class="idnect-card-logo" alt="" onerror="this.parentElement.style.display='none'"></div>`
            : '';
        let addToWalletBlock = '';
        if (showAddToAppleWallet) {
            addToWalletBlock += `<div class="member-card-add-to-wallet-wrap">
                <button type="button" class="member-card-add-to-wallet-btn member-card-add-to-wallet-btn--apple" data-wallet-pass-url="${DomHelper.escapeHtml(walletPassUrl)}" aria-label="Apple Walletに追加">
                    <img src="${DomHelper.escapeHtml(walletPassImageUrl)}" alt="Apple Walletに追加" class="member-card-add-to-wallet-img">
                </button>
            </div>`;
        }
        if (showAddToGoogleWallet) {
            addToWalletBlock += `<div class="member-card-add-to-wallet-wrap">
                <button type="button" class="member-card-add-to-wallet-btn member-card-add-to-wallet-btn--google" data-google-wallet aria-label="Google Walletに追加">
                    <img src="https://pay.google.com/static/images/badges/ja/add_to_google_wallet.svg" alt="Google Walletに追加" class="member-card-add-to-wallet-img" style="height:40px;width:auto;" onerror="this.parentElement.innerHTML='Google Walletに追加'">
                </button>
            </div>`;
        }

        container.innerHTML = `
            <div class="member-card-container">
                <div class="member-card-idnect-header">
                    <h2 class="member-card-title"><i class="fas fa-id-card"></i> 会員証</h2>
                    <button type="button" class="member-card-edit-btn" onclick="window.app.memberManager.openProfileEdit()" aria-label="プロフィール編集"><i class="fas fa-pen"></i></button>
                </div>
                ${permissionDeniedNotice}
                <div class="idnect-card-wrapper ${cardDesignClass}" role="button" tabindex="0" aria-label="カードをタップで裏返します"${cardStyle}>
                    <div class="idnect-card" data-rank="${DomHelper.escapeHtml(currentRankKey)}">
                        <div class="idnect-card-shimmer" aria-hidden="true"></div>
                        <div class="idnect-card-face idnect-card-face--front">
                            <div class="idnect-card-front-inner">
                                ${logoBlock}
                                ${displayName ? '<p class="idnect-card-display-name">' + DomHelper.escapeHtml(displayName) + '</p>' : ''}
                                <div class="idnect-card-meta-row">
                                    <div class="idnect-card-rank-wrap">
                                        ${currentRankIcon ? `<img src="${DomHelper.escapeHtml(currentRankIcon)}" class="idnect-card-rank-icon" alt="ランク">` : ''}
                                        <span class="idnect-card-rank-badge" data-rank="${DomHelper.escapeHtml(currentRankKey)}">${DomHelper.escapeHtml(currentRankLabel)}</span>
                                    </div>
                                    ${showPointsDisplay ? '<div class="idnect-card-points idnect-card-points--front"><span class="idnect-card-points-value">' + points.toLocaleString() + '</span><span class="idnect-card-points-label">pt</span></div>' : ''}
                                </div>
                                <div class="idnect-card-barcode-wrap">
                                    <svg id="${barcodeId}" class="idnect-card-barcode-svg"></svg>
                                </div>
                                <p class="idnect-card-tap-hint"><i class="fas fa-sync-alt idnect-card-tap-hint-icon" aria-hidden="true"></i> タップで裏面を見る</p>
                            </div>
                        </div>
                        <div class="idnect-card-face idnect-card-face--back">
                            <div class="idnect-card-back-inner">
                                <p class="idnect-card-back-title">会員情報</p>
                                ${showPointsDisplay ? '<div class="idnect-card-points idnect-card-points--large"><span class="idnect-card-points-value">' + points.toLocaleString() + '</span><span class="idnect-card-points-label">ポイント</span></div>' : ''}
                                ${expiryText ? '<p class="idnect-card-expiry">' + DomHelper.escapeHtml(expiryText) + '</p>' : ''}
                                ${showRankProgress && nextRankLabel && pointsToNextRank !== null ? '<p class="idnect-card-next-rank">次のランク（' + DomHelper.escapeHtml(nextRankLabel) + '）まで <strong>' + pointsToNextRank.toLocaleString() + '</strong> pt</p>' : ''}
                                ${showRankProgress && daysRequiredForNextRank !== null && daysToNextRank !== null ? '<p class="idnect-card-next-rank idnect-card-days-to-rank">次のランクまで あと <strong>' + daysToNextRank + '</strong> 日</p>' : ''}
                                <p class="idnect-card-tap-hint"><i class="fas fa-sync-alt idnect-card-tap-hint-icon" aria-hidden="true"></i> タップで表に戻る</p>
                            </div>
                        </div>
                    </div>
                </div>
                ${addToWalletBlock}
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
                <div class="member-rank-history">
                    <button type="button" class="member-purchase-history-toggle" aria-expanded="false" aria-controls="memberRankHistoryList" id="memberRankHistoryToggle">
                        <span class="member-purchase-history-toggle-label">ランクアップ履歴</span>
                        <i class="fas fa-chevron-down member-purchase-history-toggle-icon" aria-hidden="true"></i>
                    </button>
                    <div id="memberRankHistoryList" class="member-purchase-history-list member-rank-history-list" hidden aria-labelledby="memberRankHistoryToggle">${this._formatRankUpHistoryHtml(rankUpHistory)}</div>
                </div>
            </div>
        `;

        this._generateBarcode(barcodeId, memberNumber);
        this._setupFlip();
        this._setupAddToWallet();
        this._setupPurchaseHistoryToggle();
        this._setupPointHistoryToggle();
        this._setupRankHistoryToggle();
    }

    _formatRankUpHistoryHtml(history) {
        if (!history || history.length === 0) {
            return '<div class="member-rank-history-empty">まだランクアップ履歴がありません</div>';
        }
        return history.map(function (item) {
            const date = item.achieved_at || '';
            const fromLabel = (item.from_rank_label || '—');
            const toLabel = (item.to_rank_label || '—');
            return '<div class="member-rank-history-item">' +
                '<span class="member-rank-history-from">' + DomHelper.escapeHtml(fromLabel) + '</span>' +
                ' <i class="fas fa-arrow-right member-rank-history-arrow" aria-hidden="true"></i> ' +
                '<span class="member-rank-history-to">' + DomHelper.escapeHtml(toLabel) + '</span>' +
                '<span class="member-rank-history-date">' + DomHelper.escapeHtml(date) + '</span>' +
                '</div>';
        }).join('');
    }

    _setupRankHistoryToggle() {
        const toggle = document.getElementById('memberRankHistoryToggle');
        const listEl = document.getElementById('memberRankHistoryList');
        const icon = toggle ? toggle.querySelector('.member-purchase-history-toggle-icon') : null;
        if (!toggle || !listEl) return;
        toggle.addEventListener('click', function () {
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
        });
    }

    _setupAddToWallet() {
        const projectKey = (typeof window !== 'undefined' && window.__PROJECT_KEY__) ? window.__PROJECT_KEY__ : 'default';
        const appleBtn = document.querySelector('.member-card-add-to-wallet-btn[data-wallet-pass-url]');
        if (appleBtn) appleBtn.addEventListener('click', (e) => this._onAddToAppleWallet(e, appleBtn, projectKey));
        const googleBtn = document.querySelector('.member-card-add-to-wallet-btn[data-google-wallet]');
        if (googleBtn) googleBtn.addEventListener('click', (e) => this._onAddToGoogleWallet(e, googleBtn, projectKey));
    }

    _onAddToAppleWallet(e, btn, projectKey) {
        e.preventDefault();
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="member-card-add-to-wallet-loading"><i class="fas fa-spinner fa-spin"></i> 取得中...</span>';

        if (typeof window !== 'undefined' && window.__IS_LIFF__ && typeof liff !== 'undefined' && liff.openWindow) {
            const passUrlApi = (window.location.origin + (AppConstants.API_ENDPOINTS.MEMBERS_WALLET_PASS_URL.startsWith('/') ? '' : '/') + AppConstants.API_ENDPOINTS.MEMBERS_WALLET_PASS_URL) + '?project_key=' + encodeURIComponent(projectKey);
            fetch(passUrlApi, { credentials: 'include', method: 'GET' })
                .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
                .then(function (result) {
                    if (!result.ok || !result.data || !result.data.url) {
                        throw new Error(result.data && result.data.error ? result.data.error : 'URLの取得に失敗しました');
                    }
                    liff.openWindow({ url: result.data.url, external: true });
                    if (typeof ToastManager !== 'undefined') ToastManager.success('ブラウザで開きます。表示されたら「追加」をタップしてください。');
                })
                .catch(function (err) {
                    if (typeof ToastManager !== 'undefined') ToastManager.error(err.message || 'Wallet Pass の取得に失敗しました');
                })
                .finally(function () {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                });
            return;
        }

        const url = btn.getAttribute('data-wallet-pass-url');
        if (!url) {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            return;
        }
        const fullUrl = url.startsWith('http') ? url : (window.location.origin + (url.startsWith('/') ? '' : '/') + url);
        fetch(fullUrl, { credentials: 'include', method: 'GET' })
            .then(function (res) {
                if (!res.ok) {
                    return res.text().then(function (text) {
                        const msg = text && text.length < 200 ? text.trim() : (res.status === 503 ? 'Wallet Pass を利用できません' : '取得に失敗しました');
                        throw new Error(msg);
                    });
                }
                const ct = res.headers.get('Content-Type') || '';
                if (ct.indexOf('application/vnd.apple.pkpass') === -1 && ct.indexOf('application/zip') === -1) {
                    return res.text().then(function (text) { throw new Error(text && text.length < 200 ? text.trim() : '不正なレスポンスです'); });
                }
                return res.blob();
            })
            .then(function (blob) {
                const blobUrl = URL.createObjectURL(blob);
                if (MemberManager.isIOS()) {
                    const w = window.open(blobUrl, '_blank');
                    if (!w || w.closed) window.location.href = blobUrl;
                    setTimeout(function () { URL.revokeObjectURL(blobUrl); }, 2000);
                } else {
                    const a = document.createElement('a');
                    a.href = blobUrl;
                    a.download = 'membership.pkpass';
                    a.style.display = 'none';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    setTimeout(function () { URL.revokeObjectURL(blobUrl); }, 500);
                }
                if (typeof ToastManager !== 'undefined') ToastManager.success('パスを開きました。表示されたら「追加」をタップしてください。');
            })
            .catch(function (err) {
                if (typeof ToastManager !== 'undefined') ToastManager.error(err.message || 'Wallet Pass の取得に失敗しました');
            })
            .finally(function () {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            });
    }

    _onAddToGoogleWallet(e, btn, projectKey) {
        e.preventDefault();
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="member-card-add-to-wallet-loading"><i class="fas fa-spinner fa-spin"></i> 取得中...</span>';
        const googleWalletUrlApi = (window.location.origin + (AppConstants.API_ENDPOINTS.MEMBERS_GOOGLE_WALLET_URL.startsWith('/') ? '' : '') + AppConstants.API_ENDPOINTS.MEMBERS_GOOGLE_WALLET_URL) + '?project_key=' + encodeURIComponent(projectKey);
        fetch(googleWalletUrlApi, { credentials: 'include', method: 'GET' })
            .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
            .then(function (result) {
                if (!result.ok || !result.data || !result.data.url) {
                    throw new Error(result.data && result.data.error ? result.data.error : 'URLの取得に失敗しました');
                }
                if (typeof window !== 'undefined' && window.__IS_LIFF__ && typeof liff !== 'undefined' && liff.openWindow) {
                    liff.openWindow({ url: result.data.url, external: true });
                    if (typeof ToastManager !== 'undefined') ToastManager.success('Google Wallet を開きます。表示されたら「追加」をタップしてください。');
                } else {
                    window.location.href = result.data.url;
                }
            })
            .catch(function (err) {
                if (typeof ToastManager !== 'undefined') ToastManager.error(err.message || 'Google Wallet の取得に失敗しました');
            })
            .finally(function () {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            });
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
        this._setupPostalCodeLookup();
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

    _setupPostalCodeLookup(container) {
        const doc = container || document;
        const postalInput = doc.getElementById('profileEdit_postal_code') || doc.getElementById('profileEditPostalCode');
        const addressInput = doc.getElementById('profileEdit_address') || doc.getElementById('profileEditAddress');
        if (!postalInput || !addressInput) return;

        const fetchAddress = () => {
            const raw = (postalInput.value || '').trim().replace(/[‐－−ー]/g, '-');
            const digits = raw.replace(/\D/g, '');
            if (digits.length < 3) return;

            const url = AppConstants.API_ENDPOINTS.POSTAL_CODE + '?postal_code=' + encodeURIComponent(digits);
            postalInput.disabled = true;
            this.apiClient.get(url)
                .then((result) => {
                    if (result && result.success === false) {
                        if (typeof ToastManager !== 'undefined') ToastManager.info(result.error || '住所を自動入力できませんでした。手入力してください。');
                        return;
                    }
                    const data = (result && result.data) || result || {};
                    const serverError = data.error || null;
                    const addresses = Array.isArray(data.addresses) ? data.addresses : (Array.isArray(data) ? data : []);
                    if (addresses.length > 0) {
                        const first = addresses[0];
                        const full = (first && (first.full_address || first.fullAddress)) ? (first.full_address || first.fullAddress) : '';
                        if (full) {
                            addressInput.value = full;
                            const prefectureInput = doc.getElementById('profileEdit_prefecture') || doc.getElementById('profileEditPrefecture');
                            const cityInput = doc.getElementById('profileEdit_city') || doc.getElementById('profileEditCity');
                            const streetInput = doc.getElementById('profileEdit_street') || doc.getElementById('profileEditStreet');
                            const pref = first.pref_name || first.prefName || '';
                            const city = first.city_name || first.cityName || '';
                            const town = first.town_name || first.townName || '';
                            const block = first.block_name || first.blockName || '';
                            const streetPart = [town, block].filter(Boolean).join('');
                            if (prefectureInput) prefectureInput.value = pref;
                            if (cityInput) cityInput.value = city;
                            if (streetInput) streetInput.value = streetPart;
                            if (addresses.length > 1 && typeof ToastManager !== 'undefined') {
                                ToastManager.success('住所を入力しました。複数候補がある場合は修正してください。');
                            } else if (typeof ToastManager !== 'undefined') {
                                ToastManager.success('住所を入力しました');
                            }
                        } else if (digits.length >= 7 && typeof ToastManager !== 'undefined') {
                            ToastManager.info(serverError || '該当する住所がありません。手入力してください。');
                        }
                    } else if (digits.length >= 7 && typeof ToastManager !== 'undefined') {
                        ToastManager.info(serverError || '住所を自動入力できませんでした。手入力してください。');
                    }
                })
                .catch(() => {
                    if (typeof ToastManager !== 'undefined') ToastManager.info('住所を自動入力できませんでした。手入力してください。');
                })
                .finally(() => { postalInput.disabled = false; });
        };

        postalInput.addEventListener('blur', fetchAddress);
        postalInput.addEventListener('input', function () {
            const digits = (this.value || '').replace(/\D/g, '');
            if (digits.length === 7) fetchAddress();
        });
    }

    _getProfileEditFormHtml(user, member, options) {
        options = options || {};
        const isRegister = options.isRegister === true;
        const root = (this.memberData && this.memberData.member) ? this.memberData.member : this.memberData;
        const fields = (root && root.profile_field_settings) ? root.profile_field_settings : [
            { key: 'name', label: 'お名前', required: true },
            { key: 'kana', label: 'お名前（カナ）', required: false },
            { key: 'phone', label: '電話番号', required: false },
            { key: 'email', label: 'メールアドレス', required: false },
            { key: 'birthday', label: '誕生日', required: false },
            { key: 'postal_code', label: '郵便番号', required: false },
            { key: 'prefecture', label: '都道府県', required: false },
            { key: 'city', label: '市区町村', required: false },
            { key: 'street', label: '番地・建物名', required: false },
            { key: 'address', label: '住所', required: false },
            { key: 'sex', label: '性別', required: false },
        ];
        let profile = (root && root.profile) ? root.profile : {};
        if (isRegister && options.initialProfile && typeof options.initialProfile === 'object') {
            profile = Object.assign({}, profile, options.initialProfile);
        }
        const birthdayVal = (member && member.birthday) ? String(member.birthday).split('T')[0] : (profile.birthday || '');
        const nameParts = (profile.name != null && String(profile.name).trim() !== '') ? String(profile.name).trim().split(/\s+/) : ['', ''];
        const nameLast = nameParts[0] || '';
        const nameFirst = nameParts.slice(1).join(' ') || '';
        const rows = fields.flatMap(function (f) {
            const key = f.key;
            const label = f.label || key;
            const required = f.required ? ' required' : '';
            const val = profile[key] != null ? String(profile[key]) : '';
            if (key === 'name') {
                const idLast = 'profileEdit_name_last';
                const idFirst = 'profileEdit_name_first';
                const inputLast = '<input type="text" id="' + idLast + '" name="name_last" value="' + DomHelper.escapeHtml(nameLast) + '" maxlength="128" placeholder="例: 山田" autocomplete="family-name"' + required + '>';
                const inputFirst = '<input type="text" id="' + idFirst + '" name="name_first" value="' + DomHelper.escapeHtml(nameFirst) + '" maxlength="128" placeholder="例: 太郎" autocomplete="given-name"' + required + '>';
                return [
                    '<div class="member-form-group"><label for="' + idLast + '">姓' + (f.required ? ' <span class="required">*</span>' : '') + '</label>' + inputLast + '</div>',
                    '<div class="member-form-group"><label for="' + idFirst + '">名' + (f.required ? ' <span class="required">*</span>' : '') + '</label>' + inputFirst + '</div>',
                ];
            }
            const id = 'profileEdit_' + key.replace(/[^a-z0-9_]/gi, '_');
            let input = '';
            if (key === 'sex') {
                input = '<select id="' + id + '" name="' + DomHelper.escapeHtml(key) + '"' + required + '>' +
                    '<option value="">選択してください</option>' +
                    '<option value="1"' + (val === '1' ? ' selected' : '') + '>男性</option>' +
                    '<option value="2"' + (val === '2' ? ' selected' : '') + '>女性</option></select>';
            } else if (key === 'birthday') {
                input = '<input type="date" id="' + id + '" name="' + DomHelper.escapeHtml(key) + '" value="' + DomHelper.escapeHtml(val || birthdayVal) + '"' + required + '>';
            } else if (key === 'email') {
                input = '<input type="email" id="' + id + '" name="' + DomHelper.escapeHtml(key) + '" value="' + DomHelper.escapeHtml(val) + '" maxlength="255"' + required + '>';
            } else if (key === 'postal_code') {
                input = '<input type="text" id="' + id + '" name="' + DomHelper.escapeHtml(key) + '" value="' + DomHelper.escapeHtml(val) + '" placeholder="例: 100-0001" maxlength="8" inputmode="numeric" autocomplete="postal-code"' + required + '>' +
                    '<p class="member-form-hint">郵便番号を入力すると住所が自動で入力されます</p>';
            } else if (key === 'phone') {
                input = '<input type="tel" id="' + id + '" name="' + DomHelper.escapeHtml(key) + '" value="' + DomHelper.escapeHtml(val) + '" maxlength="50"' + required + '>';
            } else {
                const maxLen = (key === 'address' || key === 'street') ? 500 : 255;
                input = '<input type="text" id="' + id + '" name="' + DomHelper.escapeHtml(key) + '" value="' + DomHelper.escapeHtml(val) + '" maxlength="' + maxLen + '"' + (key === 'address' ? ' autocomplete="street-address"' : '') + required + '>';
            }
            return ['<div class="member-form-group"><label for="' + id + '">' + DomHelper.escapeHtml(label) + (f.required ? ' <span class="required">*</span>' : '') + '</label>' + input + '</div>'];
        });
        const title = isRegister ? '会員登録' : 'プロフィール編集';
        const formId = isRegister ? 'registerForm' : 'profileEditForm';
        const submitLabel = isRegister ? '登録する' : '保存';
        const titleIcon = isRegister ? 'fa-user-plus' : 'fa-user-edit';
        return `
            <div class="member-modal-inner">
                <h3 class="member-modal-title"><i class="fas ${titleIcon}"></i> ${DomHelper.escapeHtml(title)}</h3>
                <form id="${formId}" class="member-form">
                    ${rows.join('')}
                    <div class="member-form-actions">
                        <button type="button" class="member-btn member-btn--secondary" onclick="window.app.memberManager.closeProfileEdit()">キャンセル</button>
                        <button type="submit" class="member-btn member-btn--primary"><i class="fas fa-check"></i> ${DomHelper.escapeHtml(submitLabel)}</button>
                    </div>
                </form>
            </div>
        `;
    }

    openRegisterModal() {
        if (!this.memberData || this.memberData.is_member) return;
        const content = document.getElementById('profileEditModalContent');
        const modal = document.getElementById('profileEditModal');
        if (!content || !modal) return;
        const initialProfile = (typeof window !== 'undefined' && window.__LINE_PROFILE__ && window.__LINE_PROFILE__.displayName)
            ? { name: window.__LINE_PROFILE__.displayName } : {};
        content.innerHTML = this._getProfileEditFormHtml(null, null, { isRegister: true, initialProfile: initialProfile });
        const form = document.getElementById('registerForm');
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                window.app.memberManager.saveRegisterForm(e);
            });
        }
        this._setupPostalCodeLookup(document);
        modal.setAttribute('aria-hidden', 'false');
        modal.classList.add('member-modal--open');
    }

    async saveRegisterForm(e) {
        if (e) e.preventDefault();
        const form = document.getElementById('registerForm');
        if (!form) return;
        const payload = {};
        const root = this.memberData || {};
        const fields = (root.profile_field_settings) ? root.profile_field_settings : [];
        const keys = fields.map(function (f) { return f.key; });
        keys.forEach(function (key) {
            if (key === 'name') {
                const elLast = form.querySelector('[name="name_last"]');
                const elFirst = form.querySelector('[name="name_first"]');
                const lastVal = elLast ? (elLast.value || '').trim() : '';
                const firstVal = elFirst ? (elFirst.value || '').trim() : '';
                payload.name = (lastVal + ' ' + firstVal).trim() || null;
                return;
            }
            const el = form.querySelector('[name="' + key.replace(/"/g, '\\"') + '"]');
            if (el) {
                let v = (el.value || '').trim();
                if (key === 'birthday' && !v) v = null;
                payload[key] = v || null;
            }
        });
        if (Object.keys(payload).length === 0 && form.elements.length) {
            for (let i = 0; i < form.elements.length; i++) {
                const el = form.elements[i];
                if (el.name && el.name !== '_token') {
                    let v = (el.value || '').trim();
                    if (el.name === 'birthday' && !v) v = null;
                    payload[el.name] = v || null;
                }
            }
        }
        const nameLast = form.querySelector('[name="name_last"]');
        const nameFirst = form.querySelector('[name="name_first"]');
        if (nameLast || nameFirst) {
            const lastVal = nameLast ? (nameLast.value || '').trim() : '';
            const firstVal = nameFirst ? (nameFirst.value || '').trim() : '';
            payload.name = (lastVal + ' ' + firstVal).trim() || null;
        }
        if (typeof window !== 'undefined' && window.__LINE_PROFILE__ && window.__LINE_PROFILE__.displayName && !payload.name) {
            payload.name = window.__LINE_PROFILE__.displayName;
        }
        try {
            const result = await this.apiClient.post(AppConstants.API_ENDPOINTS.MEMBERS_REGISTER, payload);
            const data = (result && result.data) || result;
            if (data && data.is_member) {
                this.memberData = data;
                this.render(data);
                this.closeProfileEdit();
                ToastManager.success('会員登録が完了しました');
            } else if (data) {
                this.memberData = data;
                this.render(data);
                this.closeProfileEdit();
                ToastManager.success(result.message || '会員登録が完了しました');
            } else {
                throw new Error(result.error || result.message || '登録に失敗しました');
            }
        } catch (error) {
            console.error('Register error:', error);
            ToastManager.error('登録に失敗しました: ' + (error.message || 'エラーが発生しました'));
        }
    }

    async saveProfileEdit(e) {
        if (e) e.preventDefault();
        const form = document.getElementById('profileEditForm');
        if (!form) return;
        const payload = {};
        const root = (this.memberData && this.memberData.member) ? this.memberData.member : this.memberData;
        const fields = (root && root.profile_field_settings) ? root.profile_field_settings : [];
        const keys = fields.map(function (f) { return f.key; });
        keys.forEach(function (key) {
            if (key === 'name') {
                const elLast = form.querySelector('[name="name_last"]');
                const elFirst = form.querySelector('[name="name_first"]');
                const lastVal = elLast ? (elLast.value || '').trim() : '';
                const firstVal = elFirst ? (elFirst.value || '').trim() : '';
                payload.name = (lastVal + ' ' + firstVal).trim() || null;
                return;
            }
            const el = form.querySelector('[name="' + key.replace(/"/g, '\\"') + '"]');
            if (el) {
                let v = (el.value || '').trim();
                if (key === 'birthday' && !v) v = null;
                payload[key] = v || null;
            }
        });
        if (Object.keys(payload).length === 0 && form.elements.length) {
            for (let i = 0; i < form.elements.length; i++) {
                const el = form.elements[i];
                if (el.name && el.name !== '_token') {
                    let v = (el.value || '').trim();
                    if (el.name === 'birthday' && !v) v = null;
                    payload[el.name] = v || null;
                }
            }
        }
        const nameLast = form.querySelector('[name="name_last"]');
        const nameFirst = form.querySelector('[name="name_first"]');
        if (nameLast || nameFirst) {
            const lastVal = nameLast ? (nameLast.value || '').trim() : '';
            const firstVal = nameFirst ? (nameFirst.value || '').trim() : '';
            payload.name = (lastVal + ' ' + firstVal).trim() || null;
        }
        try {
            const result = await this.apiClient.request(AppConstants.API_ENDPOINTS.MEMBERS_ME, {
                method: 'PUT',
                body: JSON.stringify(payload),
                headers: { 'Content-Type': 'application/json' },
            });
            if (result.success) {
                const next = (result.data && result.data.member) ? result.data.member : result.data;
                this.memberData = next;
                this.render(next);
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

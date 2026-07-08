import type { ApiClient } from '../../shared/utils/apiClient';
import { AppConstants } from '../../shared/constants';
import { DomHelper } from '../../shared/utils/domHelper';

type QueueStats = {
    waiting_count?: number | null;
    waitingCount?: number | null;
    estimated_wait_minutes?: number | null;
    estimatedWaitMinutes?: number | null;
    updated_at?: string | null;
    queue_entry?: QueueEntry | null;
    [key: string]: unknown;
};

type QueueEntry = {
    id?: number | string;
    queue_number?: number | string;
    queueNumber?: number | string;
    status?: string;
    line_linked?: boolean;
    lineLinked?: boolean;
    party_size?: number | string;
    partySize?: number | string;
    waiting_count?: number | string;
    waitingCount?: number | string;
    ahead_count?: number | string;
    aheadCount?: number | string;
    estimated_wait_minutes?: number | string;
    estimatedWaitMinutes?: number | string;
};

type QueueLinkResult = {
    linked: boolean;
    reason?: string;
    error?: string;
    entry?: QueueEntry;
};

export class QueueManager {
    static PENDING_QUEUE_TOKEN_KEY = 'mobile_order_pending_queue_token';

    apiClient: ApiClient;
    _waitingLiffRetryRegistered: boolean;

    constructor(apiClient: ApiClient) {
        this.apiClient = apiClient;
        this._waitingLiffRetryRegistered = false;
    }

    getSection(): Element | null {
        return DomHelper.getTabMount(
            AppConstants.TAB_MOUNTS.QUEUE,
            AppConstants.ELEMENT_IDS.QUEUE_SECTION,
            { silent: true },
        );
    }

    getGuestTemplate(): string {
        return `
            <div class="queue-card">
                <h2 class="section-title"><i class="fas fa-users"></i> 順番待ち受付</h2>
                <p class="queue-subtitle">ゲストでも受付できます。会員登録すると履歴を引き継げます。</p>
                ${this.getCreateFormTemplate(null)}
                ${this.getHelpTemplate()}
            </div>
        `;
    }

    getEmptyTemplate(updatedAt: string | null = null, stats: QueueStats | null = null): string {
        const updated = updatedAt
            ? `<div class="queue-updated-at">最終更新: ${DomHelper.escapeHtml(this.formatUpdatedAt(updatedAt))}</div>`
            : '';
        return `
            <div class="queue-card">
                <h2 class="section-title"><i class="fas fa-users"></i> 順番待ち受付</h2>
                <p>現在の順番待ちはありません。</p>
                ${this.getCreateFormTemplate(stats)}
                ${this.getHelpTemplate()}
                <div class="queue-actions">
                    <button class="queue-button queue-button-secondary" data-queue-action="refresh">更新</button>
                </div>
                ${updated}
            </div>
        `;
    }

    getErrorTemplate(message?: string): string {
        const msg = message || '順番待ちの情報を取得できませんでした。';
        return `
            <div class="queue-card">
                <h2 class="section-title"><i class="fas fa-users"></i> 順番待ち受付</h2>
                <p class="queue-error">${DomHelper.escapeHtml(msg)}</p>
                ${this.getHelpTemplate()}
                <div class="queue-actions">
                    <button class="queue-button queue-button-secondary" data-queue-action="refresh">更新</button>
                </div>
            </div>
        `;
    }

    getCreateFormTemplate(stats: QueueStats | null = null): string {
        const waitingCount = stats
            ? (stats.waiting_count != null
                ? stats.waiting_count
                : (stats.waitingCount != null ? stats.waitingCount : null))
            : null;
        const estimated = stats
            ? (stats.estimated_wait_minutes != null
                ? stats.estimated_wait_minutes
                : (stats.estimatedWaitMinutes != null ? stats.estimatedWaitMinutes : null))
            : null;
        const statsHtml =
            waitingCount !== null || estimated !== null
                ? `<div class="queue-stats">
                    <div>待ち人数: <strong>${DomHelper.escapeHtml(waitingCount != null ? waitingCount : '-')}</strong> 組</div>
                    <div>予想待ち時間: <strong>${DomHelper.escapeHtml(estimated != null ? estimated : '-')}</strong> 分</div>
               </div>`
                : '';
        return `
            <div class="queue-form">
                ${statsHtml}
                <label class="queue-label" for="queuePartySize">人数</label>
                <div class="queue-form-row">
                    <input id="queuePartySize" class="queue-input" type="number" min="1" max="20" value="1" />
                    <button class="queue-button" data-queue-action="create">受付する</button>
                </div>
                <p class="queue-hint">人数を入力して順番待ちに登録できます。</p>
            </div>
        `;
    }

    getQueueTokenFromUrl(): string | null {
        if (typeof window === 'undefined') {
            return null;
        }
        const params = new URLSearchParams(window.location.search || '');
        const direct = params.get('queue_token') || params.get('queue_id');
        if (direct) {
            this.persistQueueToken(direct);
            return direct;
        }

        const fromHash = this.extractQueueTokenFromString(window.location.hash || '');
        if (fromHash) {
            this.persistQueueToken(fromHash);
            return fromHash;
        }

        const fromLiffState = this.extractQueueTokenFromEncodedParam(params.get('liff.state'));
        if (fromLiffState) {
            this.persistQueueToken(fromLiffState);
            return fromLiffState;
        }

        const fromState = this.extractQueueTokenFromEncodedParam(params.get('state'));
        if (fromState) {
            this.persistQueueToken(fromState);
            return fromState;
        }

        const fromRedirect = this.extractQueueTokenFromEncodedParam(params.get('liffRedirectUri'));
        if (fromRedirect) {
            this.persistQueueToken(fromRedirect);
            return fromRedirect;
        }

        return this.getPersistedQueueToken();
    }

    extractQueueTokenFromEncodedParam(raw: string | null): string | null {
        if (!raw) {
            return null;
        }
        try {
            const decoded = decodeURIComponent(raw);
            const value = this.extractQueueTokenFromString(decoded);
            if (value) {
                return value;
            }
        } catch (_e) {
            // ignore decode errors
        }
        return this.extractQueueTokenFromString(raw);
    }

    extractQueueTokenFromString(str: string | null): string | null {
        if (!str) {
            return null;
        }
        const normalized = str.startsWith('#') ? str.slice(1) : str;
        try {
            const full = new URL(normalized);
            const token = full.searchParams.get('queue_token') || full.searchParams.get('queue_id');
            if (token) {
                return token;
            }
        } catch (_e) {
            // relative query style or plain query
        }

        const query = normalized.includes('?') ? normalized.split('?')[1] : normalized;
        const params = new URLSearchParams(query);
        return params.get('queue_token') || params.get('queue_id');
    }

    clearQueueTokenFromUrl(): void {
        if (typeof window === 'undefined' || !window.history) {
            return;
        }
        const url = new URL(window.location.href);
        url.searchParams.delete('queue_token');
        url.searchParams.delete('queue_id');
        url.searchParams.delete('state');
        url.searchParams.delete('liff.state');
        url.searchParams.delete('liffRedirectUri');
        window.history.replaceState({}, '', url.toString());
        this.clearPersistedQueueToken();
    }

    persistQueueToken(token: string): void {
        if (typeof window === 'undefined' || typeof window.sessionStorage === 'undefined') {
            return;
        }
        if (!token) {
            return;
        }
        try {
            window.sessionStorage.setItem(QueueManager.PENDING_QUEUE_TOKEN_KEY, token);
        } catch (_e) {
            // ignore
        }
    }

    getPersistedQueueToken(): string | null {
        if (typeof window === 'undefined' || typeof window.sessionStorage === 'undefined') {
            return null;
        }
        try {
            return window.sessionStorage.getItem(QueueManager.PENDING_QUEUE_TOKEN_KEY);
        } catch (_e) {
            return null;
        }
    }

    clearPersistedQueueToken(): void {
        if (typeof window === 'undefined' || typeof window.sessionStorage === 'undefined') {
            return;
        }
        try {
            window.sessionStorage.removeItem(QueueManager.PENDING_QUEUE_TOKEN_KEY);
        } catch (_e) {
            // ignore
        }
    }

    async load(): Promise<void> {
        const section = this.getSection();
        if (!section) {
            return;
        }
        const queueToken = this.getQueueTokenFromUrl();
        if (queueToken) {
            await this.loadByToken(queueToken, section);
            return;
        }
        section.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i><p>読み込み中...</p></div>';
        try {
            const result = await this.apiClient.get(AppConstants.API_ENDPOINTS.QUEUE_ME);
            if (result.success !== true) {
                section.innerHTML = this.getErrorTemplate(result.error);
                this.attachQueueHandlers(section);
                return;
            }
            const data = (result.data || {}) as QueueStats;
            if (!data || Object.keys(data).length === 0 || data.queue_entry === null) {
                section.innerHTML = this.getEmptyTemplate(data.updated_at || null, data);
                this.attachQueueHandlers(section);
                return;
            }
            const entry = (data.queue_entry || data) as QueueEntry;
            if (entry.status === 'cancelled' || entry.status === 'entered') {
                section.innerHTML = this.getEmptyTemplate(data.updated_at || null, data);
                this.attachQueueHandlers(section);
                return;
            }
            section.innerHTML = this.renderQueueCard(entry, false, data.updated_at || null);
            this.attachQueueHandlers(section, entry);
        } catch (error) {
            // eslint-disable-next-line no-console
            console.error('Failed to load queue:', error);
            const message = error instanceof Error ? error.message : undefined;
            section.innerHTML = this.getErrorTemplate(message);
            this.attachQueueHandlers(section);
        }
    }

    async loadByToken(token: string, section: Element): Promise<void> {
        if (typeof window !== 'undefined' && window.__IS_LIFF__ && !window.__LINE_USER_ID__) {
            section.innerHTML = `
                <div class="queue-card">
                    <h2 class="section-title"><i class="fas fa-users"></i> 順番待ち受付</h2>
                    <p class="queue-subtitle">LINEログインを確認中です。数秒後に自動で反映されます。</p>
                    <div class="queue-actions">
                        <button class="queue-button queue-button-secondary" data-queue-action="refresh">再読み込み</button>
                    </div>
                </div>
            `;
            this.attachQueueHandlers(section);
            this.registerLiffReadyRetry();
            return;
        }
        section.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i><p>読み込み中...</p></div>';
        try {
            const endpointBase = `${AppConstants.API_ENDPOINTS.QUEUE_QR}/${encodeURIComponent(token)}`;
            const lineUserId =
                typeof window !== 'undefined' && window.__LINE_USER_ID__
                    ? encodeURIComponent(window.__LINE_USER_ID__)
                    : '';
            const endpoint = lineUserId ? `${endpointBase}?line_user_id=${lineUserId}` : endpointBase;
            const result = await this.apiClient.get(endpoint);
            if (result.success !== true) {
                section.innerHTML = this.getErrorTemplate(result.error);
                this.attachQueueHandlers(section);
                return;
            }
            const data = result.data as QueueStats;
            const entry = data && data.queue_entry;
            if (!entry) {
                section.innerHTML = this.getErrorTemplate('順番待ちが見つかりませんでした。');
                this.attachQueueHandlers(section);
                return;
            }
            if (entry.status === 'cancelled' || entry.status === 'entered') {
                section.innerHTML = this.getEmptyTemplate((data && data.updated_at) || null, data);
                this.attachQueueHandlers(section);
                return;
            }
            if (entry.line_linked === true || entry.lineLinked === true) {
                this.clearQueueTokenFromUrl();
            }
            section.innerHTML = this.renderQueueCard(entry, true, (data && data.updated_at) || null);
            this.attachQueueHandlers(section, entry);
        } catch (error) {
            // eslint-disable-next-line no-console
            console.error('Failed to load queue by token:', error);
            const message = error instanceof Error ? error.message : undefined;
            section.innerHTML = this.getErrorTemplate(message);
            this.attachQueueHandlers(section);
        }
    }

    async linkPendingQueueOnStartup(): Promise<QueueLinkResult> {
        const token = this.getQueueTokenFromUrl();
        if (!token) {
            return { linked: false, reason: 'no_token' };
        }
        if (typeof window !== 'undefined' && window.__IS_LIFF__ && !window.__LINE_USER_ID__) {
            this.registerLiffReadyRetry();
            return { linked: false, reason: 'line_not_ready' };
        }

        try {
            const endpointBase = `${AppConstants.API_ENDPOINTS.QUEUE_QR}/${encodeURIComponent(token)}`;
            const lineUserId =
                typeof window !== 'undefined' && window.__LINE_USER_ID__
                    ? encodeURIComponent(window.__LINE_USER_ID__)
                    : '';
            const endpoint = lineUserId ? `${endpointBase}?line_user_id=${lineUserId}` : endpointBase;
            const result = await this.apiClient.get(endpoint);
            if (result.success !== true || !(result.data as QueueStats)?.queue_entry) {
                return {
                    linked: false,
                    reason: 'api_failed',
                    error: (result.success !== true && result.error) || 'link failed',
                };
            }

            const entry = (result.data as QueueStats).queue_entry as QueueEntry;
            if (entry.line_linked === true || entry.lineLinked === true) {
                this.clearQueueTokenFromUrl();
            }
            return { linked: true, entry };
        } catch (error) {
            return {
                linked: false,
                reason: 'exception',
                error: (error instanceof Error && error.message) || 'link failed',
            };
        }
    }

    registerLiffReadyRetry(): void {
        if (this._waitingLiffRetryRegistered) {
            return;
        }
        this._waitingLiffRetryRegistered = true;

        const rerun = (): void => {
            this._waitingLiffRetryRegistered = false;
            void this.load();
        };

        if (typeof document !== 'undefined') {
            document.addEventListener('liff-ready', rerun, { once: true });
        }
        setTimeout(rerun, 2000);
    }

    renderQueueCard(entry: QueueEntry, isQrView = false, updatedAt: string | null = null): string {
        const queueNumber =
            entry.queue_number != null
                ? entry.queue_number
                : (entry.queueNumber != null
                    ? entry.queueNumber
                    : (entry.id != null ? entry.id : '-'));
        const status = entry.status || '-';
        const lineLinked = entry.line_linked === true || entry.lineLinked === true;
        const partySize =
            entry.party_size != null
                ? entry.party_size
                : (entry.partySize != null ? entry.partySize : '-');
        const waitingCount =
            entry.waiting_count != null
                ? entry.waiting_count
                : (entry.waitingCount != null ? entry.waitingCount : '-');
        const aheadCount =
            entry.ahead_count != null
                ? entry.ahead_count
                : (entry.aheadCount != null ? entry.aheadCount : '-');
        const estimated =
            entry.estimated_wait_minutes != null
                ? entry.estimated_wait_minutes
                : (entry.estimatedWaitMinutes != null ? entry.estimatedWaitMinutes : '-');
        const canCancel = entry.id && (status === 'waiting' || status === 'called');
        const updated = updatedAt
            ? `<div class="queue-updated-at">最終更新: ${DomHelper.escapeHtml(this.formatUpdatedAt(updatedAt))}</div>`
            : '';
        const statusInfo = this.getStatusInfo(status);
        return `
            <div class="queue-card">
                <h2 class="section-title"><i class="fas fa-users"></i> 順番待ち受付</h2>
                ${isQrView ? '<p class="queue-subtitle">QRから登録済みの待ち状況です。</p>' : ''}
                <div class="queue-info">
                    <div class="queue-row">
                        <div class="queue-label">受付番号</div>
                        <div class="queue-value"><strong class="queue-ticket-no">${DomHelper.escapeHtml(queueNumber)}</strong></div>
                    </div>
                    <div class="queue-row">
                        <div class="queue-label">状態</div>
                        <div class="queue-value"><span class="queue-badge ${statusInfo.className}">${DomHelper.escapeHtml(statusInfo.label)}</span></div>
                    </div>
                    <div class="queue-row">
                        <div class="queue-label">人数</div>
                        <div class="queue-value">${DomHelper.escapeHtml(partySize)} 名</div>
                    </div>
                    <div class="queue-row">
                        <div class="queue-label">待ち人数</div>
                        <div class="queue-value">${DomHelper.escapeHtml(waitingCount)} 組</div>
                    </div>
                    <div class="queue-row">
                        <div class="queue-label">あと</div>
                        <div class="queue-value">${DomHelper.escapeHtml(aheadCount)} 組</div>
                    </div>
                    <div class="queue-row">
                        <div class="queue-label">予想待ち時間</div>
                        <div class="queue-value">${DomHelper.escapeHtml(estimated)} 分</div>
                    </div>
                    <div class="queue-row">
                        <div class="queue-label">LINE連携</div>
                        <div class="queue-value">${lineLinked ? '連携済み' : '未連携'}</div>
                    </div>
                </div>
                <div class="queue-actions">
                    ${canCancel ? `<button class="queue-button queue-button-secondary" data-queue-action="cancel" data-queue-id="${DomHelper.escapeHtml(entry.id)}">キャンセル</button>` : ''}
                    <button class="queue-button queue-button-secondary" data-queue-action="refresh">更新</button>
                    <button class="queue-button queue-button-ghost" data-queue-action="go-member">会員証</button>
                </div>
                ${updated}
            </div>
        `;
    }

    attachQueueHandlers(section: Element, entry: QueueEntry | null = null): void {
        const createButton = section.querySelector('[data-queue-action="create"]') as HTMLButtonElement | null;
        if (createButton) {
            createButton.addEventListener('click', async () => {
                const input = section.querySelector('#queuePartySize') as HTMLInputElement | null;
                const value = input ? Number(input.value) : Number.NaN;
                if (!Number.isInteger(value) || value < 1 || value > 20) {
                    alert('人数は1〜20で入力してください。');
                    return;
                }
                createButton.disabled = true;
                createButton.classList.add('is-loading');
                const result = await this.apiClient.post(AppConstants.API_ENDPOINTS.QUEUE, {
                    party_size: value,
                });
                createButton.disabled = false;
                createButton.classList.remove('is-loading');
                if (result.success !== true) {
                    section.innerHTML = this.getErrorTemplate(result.error);
                    this.attachQueueHandlers(section);
                    return;
                }
                const created = (result.data as QueueStats)?.queue_entry;
                if (!created) {
                    section.innerHTML = this.getErrorTemplate();
                    this.attachQueueHandlers(section);
                    return;
                }
                section.innerHTML = this.renderQueueCard(created);
                this.attachQueueHandlers(section, created);
            });
        }

        const cancelButton = section.querySelector('[data-queue-action="cancel"]') as HTMLButtonElement | null;
        if (cancelButton && entry && entry.id) {
            cancelButton.addEventListener('click', async () => {
                if (!confirm('この順番待ちをキャンセルしますか？')) {
                    return;
                }
                cancelButton.disabled = true;
                cancelButton.classList.add('is-loading');
                const result = await this.apiClient.post(
                    `${AppConstants.API_ENDPOINTS.QUEUE}/${entry.id}/cancel`,
                    {},
                );
                cancelButton.disabled = false;
                cancelButton.classList.remove('is-loading');
                if (result.success !== true) {
                    section.innerHTML = this.getErrorTemplate(result.error);
                    this.attachQueueHandlers(section, entry);
                    return;
                }
                this.clearQueueTokenFromUrl();
                await this.load();
            });
        }

        const refreshButton = section.querySelector('[data-queue-action="refresh"]') as HTMLButtonElement | null;
        if (refreshButton) {
            refreshButton.addEventListener('click', async () => {
                refreshButton.disabled = true;
                refreshButton.classList.add('is-loading');
                await this.load();
            });
        }

        const goMember = section.querySelector('[data-queue-action="go-member"]');
        if (goMember) {
            goMember.addEventListener('click', () => {
                if (window.app && typeof window.app.switchTab === 'function') {
                    window.app.switchTab(AppConstants.TABS.MEMBER);
                }
            });
        }
    }

    getStatusInfo(status: string): { label: string; className: string } {
        const map: Record<string, { label: string; className: string }> = {
            waiting: { label: '待機中', className: 'queue-badge-waiting' },
            called: { label: '呼び出し済', className: 'queue-badge-called' },
            entered: { label: '入店済', className: 'queue-badge-entered' },
            cancelled: { label: 'キャンセル', className: 'queue-badge-cancelled' },
        };
        return map[status] || { label: status || '-', className: 'queue-badge-default' };
    }

    getHelpTemplate(): string {
        return `
            <div class="queue-help">
                <div class="queue-help-title">使い方</div>
                <ol class="queue-help-list">
                    <li>人数を入力して「受付する」を押します。</li>
                    <li>表示されたQRをLINEで読み取ると通知対象になります。</li>
                    <li>店舗側で呼び出すとLINEに通知が届きます。</li>
                </ol>
                <p class="queue-help-note">同じLINEユーザーで複数の順番待ち登録が可能です。</p>
            </div>
        `;
    }

    formatUpdatedAt(value: string): string {
        try {
            const date = new Date(value);
            if (Number.isNaN(date.getTime())) {
                return value;
            }
            return date.toLocaleString('ja-JP', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
            });
        } catch (_e) {
            return value;
        }
    }
}

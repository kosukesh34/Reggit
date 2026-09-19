import React, { useCallback, useEffect, useState } from 'react';
import { subscribeProjectDataUpdates } from '../../realtime/liffEcho';
import { isDemoDataMode } from '../../demo/demoMode';
import {
    cancelQueue,
    clearQueueTokenFromUrl,
    createQueue,
    ensureMemberContextForGate,
    fetchQueueByToken,
    fetchCurrentQueue,
    formatQueueNumber,
    formatUpdatedAt,
    getQueueTokenFromUrl,
    getSampleQueueEntry,
    getStatusInfo,
    isCurrentUserMember,
    isQueueMembershipRequired,
    switchToMemberTab,
    type QueueEntry,
    type QueueStats,
} from './api';

type ViewState =
    | { kind: 'loading' }
    | { kind: 'waiting_liff' }
    | { kind: 'waiting_device' }
    | { kind: 'membership' }
    | { kind: 'guest' }
    | { kind: 'empty'; stats: QueueStats | null; updatedAt: string | null }
    | { kind: 'entry'; entry: QueueEntry; isQrView: boolean; updatedAt: string | null }
    | { kind: 'error'; message: string };

function HelpBlock(): JSX.Element {
    return (
        <div className="queue-help">
            <div className="queue-help-title">使い方</div>
            <ol className="queue-help-list">
                <li>人数を入力して「受付する」を押します。</li>
                <li>表示されたQRを読み取ると通知対象になります。</li>
                <li>店舗側で呼び出すと通知が届きます。</li>
            </ol>
            <p className="queue-help-note">同じLINEユーザーで複数の順番待ち登録が可能です。</p>
        </div>
    );
}

function CreateForm({
    stats,
    busy,
    onCreate,
}: {
    stats: QueueStats | null;
    busy: boolean;
    onCreate: (partySize: number) => void;
}): JSX.Element {
    const [partySize, setPartySize] = useState(1);
    const waitingCount = stats
        ? (stats.waiting_count != null ? stats.waiting_count : (stats.waitingCount != null ? stats.waitingCount : null))
        : null;
    const estimated = stats
        ? (stats.estimated_wait_minutes != null
            ? stats.estimated_wait_minutes
            : (stats.estimatedWaitMinutes != null ? stats.estimatedWaitMinutes : null))
        : null;

    return (
        <div className="queue-form">
            {waitingCount !== null || estimated !== null ? (
                <div className="queue-stats">
                    <div>
                        待ち人数: <strong>{waitingCount != null ? waitingCount : '-'}</strong> 組
                    </div>
                    <div>
                        予想待ち時間: <strong>{estimated != null ? estimated : '-'}</strong> 分
                    </div>
                </div>
            ) : null}
            <label className="queue-label" htmlFor="queuePartySize">人数</label>
            <div className="queue-form-row">
                <input
                    id="queuePartySize"
                    className="queue-input"
                    type="number"
                    min={1}
                    max={20}
                    value={partySize}
                    onChange={(e) => setPartySize(Number(e.target.value))}
                />
                <button
                    type="button"
                    className={`queue-button${busy ? ' is-loading' : ''}`}
                    data-queue-action="create"
                    disabled={busy}
                    onClick={() => onCreate(partySize)}
                >
                    受付する
                </button>
            </div>
            <p className="queue-hint">人数を入力して順番待ちに登録できます。</p>
        </div>
    );
}

function QueueTicket({
    entry,
    isQrView,
    updatedAt,
    busy,
    onCancel,
    onRefresh,
}: {
    entry: QueueEntry;
    isQrView: boolean;
    updatedAt: string | null;
    busy: boolean;
    onCancel: () => void;
    onRefresh: () => void;
}): JSX.Element {
    const isSample = entry.is_sample_preview === true;
    const rawQueueNumber =
        entry.formatted_queue_number != null && entry.formatted_queue_number !== ''
            ? entry.formatted_queue_number
            : (entry.formattedQueueNumber != null && entry.formattedQueueNumber !== ''
                ? entry.formattedQueueNumber
                : (entry.queue_number != null
                    ? entry.queue_number
                    : (entry.queueNumber != null
                        ? entry.queueNumber
                        : (entry.id != null ? entry.id : '-'))));
    const queueNumber = formatQueueNumber(rawQueueNumber);
    const status = entry.status || '-';
    const lineLinked = entry.line_linked === true || entry.lineLinked === true;
    const partySize = entry.party_size != null ? entry.party_size : (entry.partySize != null ? entry.partySize : '-');
    const waitingCount = entry.waiting_count != null
        ? entry.waiting_count
        : (entry.waitingCount != null ? entry.waitingCount : '-');
    const aheadCount = entry.ahead_count != null
        ? entry.ahead_count
        : (entry.aheadCount != null ? entry.aheadCount : '-');
    const estimated = entry.estimated_wait_minutes != null
        ? entry.estimated_wait_minutes
        : (entry.estimatedWaitMinutes != null ? entry.estimatedWaitMinutes : '-');
    const canCancel = Boolean(entry.id) && (status === 'waiting' || status === 'called');
    const statusInfo = getStatusInfo(status);

    return (
        <div className="ios-queue">
            {isQrView ? <p className="ios-queue__note">QRから登録済みの待ち状況です</p> : null}
            <div className="ios-queue__ticket">
                <p className="ios-queue__label">受付番号</p>
                <p className="ios-queue__number">{queueNumber}</p>
                <span className={`ios-queue__status ${statusInfo.className}`}>{statusInfo.label}</span>
            </div>
            <div className="ios-queue__stats">
                <div className="ios-queue__stat">
                    <span className="ios-queue__stat-value">{estimated}</span>
                    <span className="ios-queue__stat-label">予想待ち（分）</span>
                </div>
                <div className="ios-queue__stat">
                    <span className="ios-queue__stat-value">{aheadCount}</span>
                    <span className="ios-queue__stat-label">あと（組）</span>
                </div>
                <div className="ios-queue__stat">
                    <span className="ios-queue__stat-value">{partySize}</span>
                    <span className="ios-queue__stat-label">人数</span>
                </div>
            </div>
            <div className="ios-queue__meta">
                <span>待ち {waitingCount} 組</span>
                <span className={lineLinked ? 'is-linked' : 'is-unlinked'}>
                    {lineLinked ? 'アプリ連携済み' : '未連携'}
                </span>
            </div>
            <div className="ios-queue__actions">
                {canCancel && !isSample ? (
                    <button
                        type="button"
                        className={`ios-queue__btn ios-queue__btn--ghost${busy ? ' is-loading' : ''}`}
                        data-queue-action="cancel"
                        data-queue-id={String(entry.id)}
                        disabled={busy}
                        onClick={onCancel}
                    >
                        キャンセル
                    </button>
                ) : null}
                <button
                    type="button"
                    className={`ios-queue__btn ios-queue__btn--ghost${busy ? ' is-loading' : ''}`}
                    data-queue-action="refresh"
                    disabled={busy}
                    onClick={onRefresh}
                >
                    更新
                </button>
                <button type="button" className="ios-queue__btn" data-queue-action="go-member" onClick={switchToMemberTab}>
                    会員証
                </button>
            </div>
            {updatedAt !== null ? (
                <div className="queue-updated-at">最終更新: {formatUpdatedAt(updatedAt)}</div>
            ) : null}
        </div>
    );
}

export function QueueView(): JSX.Element {
    const [view, setView] = useState<ViewState>({ kind: 'loading' });
    const [busy, setBusy] = useState(false);

    const load = useCallback(async (silent = false) => {
        if (isDemoDataMode()) {
            setView({
                kind: 'entry',
                entry: getSampleQueueEntry(),
                isQrView: false,
                updatedAt: new Date().toISOString(),
            });
            return;
        }

        const token = getQueueTokenFromUrl();
        if (token) {
            if (typeof window !== 'undefined' && window.__IS_LIFF__ && !window.__LINE_USER_ID__) {
                setView({ kind: 'waiting_liff' });
                return;
            }
            if (window.__MOBILE_APP_MODE__ === true) {
                const deviceId = window.__MOBILE_DEVICE_INFO__
                    && String(window.__MOBILE_DEVICE_INFO__.device_identifier || '').trim();
                if (!deviceId) {
                    setView({ kind: 'waiting_device' });
                    return;
                }
            }
            if (!silent) {
                setView({ kind: 'loading' });
            }
            const result = await fetchQueueByToken(token);
            if (result.success !== true) {
                setView({ kind: 'error', message: result.error });
                return;
            }
            const entry = result.data.queue_entry;
            if (entry === undefined || entry === null) {
                setView({ kind: 'error', message: '順番待ちが見つかりませんでした。' });
                return;
            }
            if (entry.status === 'cancelled' || entry.status === 'entered') {
                setView({ kind: 'empty', stats: result.data, updatedAt: result.data.updated_at || null });
                return;
            }
            if (entry.line_linked === true || entry.lineLinked === true) {
                clearQueueTokenFromUrl();
            }
            setView({
                kind: 'entry',
                entry,
                isQrView: true,
                updatedAt: result.data.updated_at || null,
            });
            return;
        }

        if (!silent) {
            setView({ kind: 'loading' });
        }
        try {
            if (isQueueMembershipRequired()) {
                await ensureMemberContextForGate();
                if (!isCurrentUserMember()) {
                    setView({ kind: 'membership' });
                    return;
                }
            }
            const result = await fetchCurrentQueue();
            if (result.success !== true) {
                setView({ kind: 'error', message: result.error });
                return;
            }
            const data = result.data;
            if (!data || Object.keys(data).length === 0 || data.queue_entry === null) {
                if (isQueueMembershipRequired() && !isCurrentUserMember()) {
                    setView({ kind: 'membership' });
                    return;
                }
                // Manager の getEmptyTemplate 相当（会員不要時はゲスト向け create フォーム付き）
                if (!isQueueMembershipRequired() && !isCurrentUserMember()) {
                    setView({ kind: 'guest' });
                    return;
                }
                setView({ kind: 'empty', stats: data, updatedAt: data?.updated_at || null });
                return;
            }
            const entry = (data.queue_entry || data) as QueueEntry;
            if (entry.status === 'cancelled' || entry.status === 'entered') {
                setView({ kind: 'empty', stats: data, updatedAt: data.updated_at || null });
                return;
            }
            setView({
                kind: 'entry',
                entry,
                isQrView: false,
                updatedAt: data.updated_at || null,
            });
        } catch (error) {
            const message = error instanceof Error ? error.message : '順番待ちの情報を取得できませんでした。';
            setView({ kind: 'error', message });
        }
    }, []);

    useEffect(() => {
        void load();
        const unsubscribeRealtime = subscribeProjectDataUpdates((payload) => {
            if (payload.topic === 'queue' || payload.topic === 'all') {
                void load(true);
            }
        });
        const onReady = (): void => {
            void load();
        };
        document.addEventListener('liff-ready', onReady);
        document.addEventListener('mobile-device-info-injected', onReady);
        const intervalId = window.setInterval(() => {
            if (document.visibilityState === 'visible') {
                void load(true);
            }
        }, 10_000);
        const onVisibilityChange = (): void => {
            if (document.visibilityState === 'visible') {
                void load(true);
            }
        };
        document.addEventListener('visibilitychange', onVisibilityChange);
        return () => {
            unsubscribeRealtime();
            document.removeEventListener('liff-ready', onReady);
            document.removeEventListener('mobile-device-info-injected', onReady);
            document.removeEventListener('visibilitychange', onVisibilityChange);
            window.clearInterval(intervalId);
        };
    }, [load]);

    const onCreate = async (partySize: number): Promise<void> => {
        if (!Number.isInteger(partySize) || partySize < 1 || partySize > 20) {
            alert('人数は1〜20で入力してください。');
            return;
        }
        setBusy(true);
        const result = await createQueue(partySize);
        setBusy(false);
        if (result.success !== true) {
            if (String(result.error || '').includes('会員登録が必要')) {
                setView({ kind: 'membership' });
                return;
            }
            setView({ kind: 'error', message: result.error });
            return;
        }
        setView({ kind: 'entry', entry: result.entry, isQrView: false, updatedAt: null });
    };

    const onCancel = async (): Promise<void> => {
        if (view.kind !== 'entry' || !view.entry.id) {
            return;
        }
        if (!confirm('この順番待ちをキャンセルしますか？')) {
            return;
        }
        setBusy(true);
        const result = await cancelQueue(view.entry.id);
        setBusy(false);
        if (result.success !== true) {
            setView({ kind: 'error', message: result.error });
            return;
        }
        clearQueueTokenFromUrl();
        await load();
    };

    if (view.kind === 'loading') {
        return (
            <div className="loading">
                <i className="fas fa-spinner fa-spin" />
                <p>読み込み中...</p>
            </div>
        );
    }

    if (view.kind === 'waiting_liff') {
        return (
            <div className="queue-card">
                <h2 className="section-title"><i className="fas fa-users" /> 順番待ち受付</h2>
                <p className="queue-subtitle">LINEログインを確認中です。数秒後に自動で反映されます。</p>
                <div className="queue-actions">
                    <button type="button" className="queue-button queue-button-secondary" data-queue-action="refresh" onClick={() => void load()}>
                        再読み込み
                    </button>
                </div>
            </div>
        );
    }

    if (view.kind === 'waiting_device') {
        return (
            <div className="queue-card">
                <h2 className="section-title"><i className="fas fa-users" /> 順番待ち受付</h2>
                <p className="queue-subtitle">アプリ情報を確認中です。数秒後に自動で反映されます。</p>
                <div className="queue-actions">
                    <button type="button" className="queue-button queue-button-secondary" data-queue-action="refresh" onClick={() => void load()}>
                        再読み込み
                    </button>
                </div>
            </div>
        );
    }

    if (view.kind === 'membership') {
        return (
            <div className="queue-card">
                <h2 className="section-title"><i className="fas fa-users" /> 順番待ち受付</h2>
                <div className="empty-state">
                    <div className="empty-state-icon"><i className="fas fa-id-card" /></div>
                    <h3 className="empty-state-title">会員登録が必要です</h3>
                    <p className="empty-state-message">順番待ちをご利用いただくには、会員登録が必要です。</p>
                    <button type="button" className="btn btn-primary btn-empty-action" data-queue-action="go-member" onClick={switchToMemberTab}>
                        <i className="fas fa-user-plus" /> 会員登録へ
                    </button>
                </div>
            </div>
        );
    }

    if (view.kind === 'guest') {
        return (
            <div className="queue-card">
                <h2 className="section-title"><i className="fas fa-users" /> 順番待ち受付</h2>
                <p className="queue-subtitle">ゲストでも受付できます。会員登録すると履歴を引き継げます。</p>
                <CreateForm stats={null} busy={busy} onCreate={(n) => void onCreate(n)} />
                <HelpBlock />
            </div>
        );
    }

    if (view.kind === 'error') {
        return (
            <div className="queue-card">
                <h2 className="section-title"><i className="fas fa-users" /> 順番待ち受付</h2>
                <p className="queue-error">{view.message}</p>
                <HelpBlock />
                <div className="queue-actions">
                    <button type="button" className="queue-button queue-button-secondary" data-queue-action="refresh" onClick={() => void load()}>
                        更新
                    </button>
                </div>
            </div>
        );
    }

    if (view.kind === 'empty') {
        return (
            <div className="queue-card">
                <h2 className="section-title"><i className="fas fa-users" /> 順番待ち受付</h2>
                <p>現在の順番待ちはありません。</p>
                <CreateForm stats={view.stats} busy={busy} onCreate={(n) => void onCreate(n)} />
                <HelpBlock />
                <div className="queue-actions">
                    <button type="button" className="queue-button queue-button-secondary" data-queue-action="refresh" onClick={() => void load()}>
                        更新
                    </button>
                </div>
                {view.updatedAt !== null ? (
                    <div className="queue-updated-at">最終更新: {formatUpdatedAt(view.updatedAt)}</div>
                ) : null}
            </div>
        );
    }

    return (
        <QueueTicket
            entry={view.entry}
            isQrView={view.isQrView}
            updatedAt={view.updatedAt}
            busy={busy}
            onCancel={() => void onCancel()}
            onRefresh={() => void load()}
        />
    );
}

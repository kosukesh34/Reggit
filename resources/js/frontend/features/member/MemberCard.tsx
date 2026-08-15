import React, { useEffect, useId, useRef } from 'react';
import {
    getMemberManager,
    openRegister,
    openProfileEdit,
    pendingLotteryCount,
    resolveCardDesign,
    shouldShowAppleWallet,
    shouldShowGoogleWallet,
    walletPassUrl,
    type MemberPayload,
} from './api';

function bindCardEffects(
    container: HTMLElement,
    data: MemberPayload,
    codeId: string,
    isQR: boolean,
    isSheet: boolean,
): void {
    const mm = getMemberManager() as Record<string, unknown> | undefined;
    if (mm === undefined) {
        return;
    }
    const generateQR = mm._generateQR as ((id: string, n: string) => void) | undefined;
    const generateBarcode = mm._generateBarcode as ((id: string, n: string) => void) | undefined;
    const setupFlip = mm._setupFlip as (() => void) | undefined;
    const setupEnlarge = mm._setupCodeEnlargeButton as (() => void) | undefined;
    const attachRegister = mm._attachRegisterTrigger as ((el: Element) => void) | undefined;
    const setupWallet = mm._setupAddToWallet as (() => void) | undefined;
    const registerWalletRefresh = mm._registerWalletVisibilityRefresh as (() => void) | undefined;
    const setupPurchase = mm._setupPurchaseHistoryToggle as (() => void) | undefined;
    const setupPoint = mm._setupPointHistoryToggle as (() => void) | undefined;
    const setupRank = mm._setupRankHistoryToggle as (() => void) | undefined;
    const setupWithdraw = mm._setupWithdrawButton as (() => void) | undefined;
    const renderLottery = mm._renderLotteryCta as ((el: Element, n: number) => void) | undefined;
    const maybeGacha = mm._maybeOpenGachaPopupForPending as ((n: number) => void) | undefined;

    const member = data.member && typeof data.member === 'object' ? data.member : null;
    const memberNumber = member && member.member_number ? String(member.member_number) : '';
    if (isQR && typeof generateQR === 'function') {
        generateQR(codeId, memberNumber);
    } else if (typeof generateBarcode === 'function') {
        generateBarcode(codeId, memberNumber);
    }
    if (!isSheet && typeof setupFlip === 'function') {
        setupFlip();
    }
    if (typeof setupEnlarge === 'function') {
        setupEnlarge();
    }
    if (data.is_sample_preview === true) {
        if (typeof attachRegister === 'function') {
            attachRegister(container);
        }
        return;
    }
    if (typeof setupWallet === 'function') {
        setupWallet();
    }
    if (typeof registerWalletRefresh === 'function') {
        registerWalletRefresh();
    }
    if (typeof setupPurchase === 'function') {
        setupPurchase();
    }
    if (typeof setupPoint === 'function') {
        setupPoint();
    }
    if (typeof setupRank === 'function') {
        setupRank();
    }
    if (typeof setupWithdraw === 'function') {
        setupWithdraw();
    }
    const pending = pendingLotteryCount();
    if (typeof renderLottery === 'function') {
        renderLottery(container, pending);
    }
    if (typeof maybeGacha === 'function') {
        maybeGacha(pending);
    }
}

function RankHistoryRows({ history }: { history: MemberPayload['rank_up_history'] }): JSX.Element {
    if (!history || history.length === 0) {
        return <div className="member-rank-history-empty">まだランクアップ履歴がありません</div>;
    }
    return (
        <>
            {history.map((item, index) => (
                <div key={`${item.achieved_at || ''}-${index}`} className="member-rank-history-item">
                    <span className="member-rank-history-from">{item.from_rank_label || '—'}</span>
                    {' '}
                    <i className="fas fa-arrow-right member-rank-history-arrow" aria-hidden="true" />
                    {' '}
                    <span className="member-rank-history-to">{item.to_rank_label || '—'}</span>
                    <span className="member-rank-history-date">{item.achieved_at || ''}</span>
                </div>
            ))}
        </>
    );
}

export function ClassicMemberCard({ data }: { data: MemberPayload }): JSX.Element {
    const rootRef = useRef<HTMLDivElement>(null);
    const reactId = useId().replace(/:/g, '');
    const codeId = `member-code-${reactId}`;
    const design = resolveCardDesign(data, rootRef.current);
    const member = data.member && typeof data.member === 'object' ? data.member : null;
    const user = data.user || (member && member.user) || null;
    const displayName = (user && typeof user.name === 'string' && user.name.trim() !== '')
        ? user.name.trim()
        : (data.is_member === true ? '会員' : '');
    const pointsRaw = data.points != null ? Number(data.points) : (member && member.points != null ? Number(member.points) : 0);
    const points = Number.isFinite(pointsRaw) ? pointsRaw : 0;
    const memberNumber = member && member.member_number ? String(member.member_number) : '';
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
    const rankUnit = data.rank_basis === 'stamps' ? '個' : 'pt';
    const periodDays = data.days_required_for_next_rank != null ? Number(data.days_required_for_next_rank) : null;
    const isQR = design.codeType === 'qrcode';
    const isSheet = design.displayType === 'sheet';
    const isSample = data.is_sample_preview === true;

    let expiryText = '';
    if (pointsExpiry && pointsExpiry.expires_at_label) {
        expiryText = `ポイント有効期限: ${pointsExpiry.expires_at_label}`
            + (pointsExpiry.points_expiring ? ` (${pointsExpiry.points_expiring} pt)` : '');
    }

    const cardStyle: React.CSSProperties = {};
    if (currentRankColor) {
        (cardStyle as Record<string, string>)['--idnect-card-rank-color'] = currentRankColor;
    }
    if (design.accent !== '') {
        (cardStyle as Record<string, string>)['--member-card-accent-color'] = design.accent;
    }
    const cardDesignClass = `idnect-card-wrapper--design-${design.design} idnect-card-wrapper--bg-${design.bgStyle}`;
    const showLogo = ['logo_top', 'logo_center', 'logo_header', 'split_banner', 'emblem'].includes(design.design)
        && design.logoUrl;
    const enlargeBtnLabel = isQR ? '会員証（QRコード）を拡大' : '会員証（バーコード）を拡大';
    const showApple = !isSample && shouldShowAppleWallet();
    const showGoogle = !isSample && shouldShowGoogleWallet();

    useEffect(() => {
        const el = rootRef.current;
        if (el === null) {
            return;
        }
        bindCardEffects(el, data, codeId, isQR, isSheet);
        const onWalletSummary = (): void => {
            const mm = getMemberManager() as Record<string, unknown> | undefined;
            const renderLottery = mm?._renderLotteryCta as ((c: Element, n: number) => void) | undefined;
            const maybeGacha = mm?._maybeOpenGachaPopupForPending as ((n: number) => void) | undefined;
            const pending = pendingLotteryCount();
            if (typeof renderLottery === 'function') {
                renderLottery(el, pending);
            }
            if (typeof maybeGacha === 'function') {
                maybeGacha(pending);
            }
        };
        document.addEventListener('liff-wallet-summary-updated', onWalletSummary);
        return () => {
            document.removeEventListener('liff-wallet-summary-updated', onWalletSummary);
        };
    }, [data, codeId, isQR, isSheet]);

    const codeBlock = isQR
        ? (
            <div className="idnect-card-barcode-wrap idnect-card-code-wrap--qrcode">
                <div id={codeId} className="idnect-card-qrcode-wrap" aria-label="会員番号QRコード" />
            </div>
        )
        : (
            <div className="idnect-card-barcode-wrap idnect-card-code-wrap--barcode">
                <svg id={codeId} className="idnect-card-barcode-svg" />
            </div>
        );

    const logoBlock = showLogo
        ? (
            <div className="idnect-card-logo-wrap">
                <img
                    src={design.logoUrl || ''}
                    className="idnect-card-logo"
                    alt=""
                    onError={(e) => {
                        const wrap = (e.target as HTMLElement).parentElement;
                        if (wrap !== null) {
                            wrap.style.display = 'none';
                        }
                    }}
                />
            </div>
        )
        : null;

    const frontInner = (
        <div className="idnect-card-front-inner">
            {logoBlock}
            {displayName ? <p className="idnect-card-display-name">{displayName}</p> : null}
            <div className="idnect-card-meta-row">
                <div className="idnect-card-rank-wrap">
                    {currentRankIcon ? (
                        <img src={currentRankIcon} className="idnect-card-rank-icon" alt="ランク" />
                    ) : null}
                    <span className="idnect-card-rank-badge" data-rank={currentRankKey}>{currentRankLabel}</span>
                </div>
                {showPointsDisplay ? (
                    <div className="idnect-card-points idnect-card-points--front">
                        <span className="idnect-card-points-value">{points.toLocaleString()}</span>
                        <span className="idnect-card-points-label">pt</span>
                    </div>
                ) : null}
            </div>
            {codeBlock}
            {!isSheet ? (
                <p className="idnect-card-tap-hint">
                    <i className="fas fa-sync-alt idnect-card-tap-hint-icon" aria-hidden="true" /> タップで裏面を見る
                </p>
            ) : null}
        </div>
    );

    const cardBlock = isSheet
        ? (
            <div className="member-card-sheet-wrap">
                <div
                    className={`idnect-card-wrapper idnect-card-wrapper--sheet ${cardDesignClass}`}
                    data-rank-color={currentRankColor ? 'true' : undefined}
                    style={cardStyle}
                >
                    <div className="idnect-card idnect-card--sheet" data-rank={currentRankKey}>
                        <div className="idnect-card-shimmer" aria-hidden="true" />
                        <div className="idnect-card-face idnect-card-face--front">{frontInner}</div>
                    </div>
                </div>
            </div>
        )
        : (
            <div
                className={`idnect-card-wrapper ${cardDesignClass}`}
                role="button"
                tabIndex={0}
                aria-label="カードをタップで裏返します"
                data-rank-color={currentRankColor ? 'true' : undefined}
                style={cardStyle}
            >
                <div className="idnect-card" data-rank={currentRankKey}>
                    <div className="idnect-card-shimmer" aria-hidden="true" />
                    <div className="idnect-card-face idnect-card-face--front">{frontInner}</div>
                    <div className="idnect-card-face idnect-card-face--back">
                        <div className="idnect-card-back-inner">
                            <p className="idnect-card-back-title">会員情報</p>
                            {showPointsDisplay ? (
                                <div className="idnect-card-points idnect-card-points--large">
                                    <span className="idnect-card-points-value">{points.toLocaleString()}</span>
                                    <span className="idnect-card-points-label">ポイント</span>
                                </div>
                            ) : null}
                            {showPointsDisplay && expiryText ? <p className="idnect-card-expiry">{expiryText}</p> : null}
                            {showRankProgress && nextRankLabel && pointsToNextRank !== null ? (
                                <p className="idnect-card-next-rank">
                                    次のランク（{nextRankLabel}）まで <strong>{Number(pointsToNextRank).toLocaleString()}</strong> {rankUnit}
                                </p>
                            ) : null}
                            {showRankProgress && periodDays !== null && Number.isFinite(periodDays) ? (
                                <p className="idnect-card-next-rank idnect-card-days-to-rank">
                                    集計期間: 過去 <strong>{periodDays}</strong> 日
                                </p>
                            ) : null}
                            {showRankProgress && daysRequiredForNextRank !== null && daysToNextRank !== null ? (
                                <p className="idnect-card-next-rank idnect-card-days-to-rank">
                                    次のランクまで あと <strong>{daysToNextRank}</strong> 日
                                </p>
                            ) : null}
                            <p className="idnect-card-tap-hint">
                                <i className="fas fa-sync-alt idnect-card-tap-hint-icon" aria-hidden="true" /> タップで表に戻る
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        );

    return (
        <div ref={rootRef} className={`member-card-container${isSample ? ' member-card-container--demo' : ''}`}>
            <div className="member-card-idnect-header">
                <h2 className="member-card-title">会員証</h2>
                {!isSample ? (
                    <button type="button" className="member-card-edit-btn" onClick={openProfileEdit} aria-label="プロフィール編集">
                        <i className="fas fa-pen" />
                    </button>
                ) : null}
            </div>
            {typeof window !== 'undefined' && window.__LIFF_PERMISSION_DENIED__ ? (
                <p className="member-card-profile-notice">
                    LINEの表示名は取得していません。
                    <button type="button" className="link-btn" onClick={() => location.reload()}>再読み込み</button>
                </p>
            ) : null}
            <div className="member-card-visual-wrap">
                {cardBlock}
                <button
                    type="button"
                    className="member-card-enlarge-code-btn"
                    data-member-number={memberNumber}
                    data-code-type={design.codeType}
                    aria-label={enlargeBtnLabel}
                >
                    <i className="fas fa-search-plus" aria-hidden="true" /> 会員証拡大
                </button>
            </div>
            {showApple ? (
                <div className="member-card-add-to-wallet-wrap">
                    <button
                        type="button"
                        className="member-card-add-to-wallet-btn member-card-add-to-wallet-btn--apple"
                        data-wallet-pass-url={walletPassUrl()}
                        aria-label="Apple Walletに追加"
                    >
                        <span className="member-card-add-to-wallet-text">
                            <i className="fab fa-apple" aria-hidden="true" /> Apple Walletに追加
                        </span>
                    </button>
                </div>
            ) : null}
            {showGoogle ? (
                <div className="member-card-add-to-wallet-wrap">
                    <button
                        type="button"
                        className="member-card-add-to-wallet-btn member-card-add-to-wallet-btn--google"
                        data-google-wallet
                        aria-label="Google Walletに追加"
                    >
                        <span className="member-card-add-to-wallet-text">
                            <i className="fab fa-google" aria-hidden="true" /> Google Walletに追加
                        </span>
                    </button>
                </div>
            ) : null}
            {isSample ? (
                <div className="member-demo-actions">
                    <button
                        type="button"
                        className="btn btn-primary member-register-trigger"
                        data-action="member-open-register"
                        onClick={() => openRegister(false)}
                    >
                        会員登録する
                    </button>
                </div>
            ) : (
                <>
                    <div className="member-purchase-history">
                        <button
                            type="button"
                            className="member-purchase-history-toggle"
                            aria-expanded="false"
                            aria-controls="memberPurchaseHistoryList"
                            id="memberPurchaseHistoryToggle"
                        >
                            <span className="member-purchase-history-toggle-label">購入履歴</span>
                            <i className="fas fa-chevron-down member-purchase-history-toggle-icon" aria-hidden="true" />
                        </button>
                        <div id="memberPurchaseHistoryList" className="member-purchase-history-list" hidden aria-labelledby="memberPurchaseHistoryToggle" />
                    </div>
                    <div className="member-point-history">
                        <button
                            type="button"
                            className="member-purchase-history-toggle"
                            aria-expanded="false"
                            aria-controls="memberPointHistoryList"
                            id="memberPointHistoryToggle"
                        >
                            <span className="member-purchase-history-toggle-label">ポイント履歴</span>
                            <i className="fas fa-chevron-down member-purchase-history-toggle-icon" aria-hidden="true" />
                        </button>
                        <div id="memberPointHistoryList" className="member-purchase-history-list" hidden aria-labelledby="memberPointHistoryToggle" />
                    </div>
                    <div className="member-rank-history">
                        <button
                            type="button"
                            className="member-purchase-history-toggle"
                            aria-expanded="false"
                            aria-controls="memberRankHistoryList"
                            id="memberRankHistoryToggle"
                        >
                            <span className="member-purchase-history-toggle-label">ランクアップ履歴</span>
                            <i className="fas fa-chevron-down member-purchase-history-toggle-icon" aria-hidden="true" />
                        </button>
                        <div
                            id="memberRankHistoryList"
                            className="member-purchase-history-list member-rank-history-list"
                            hidden
                            aria-labelledby="memberRankHistoryToggle"
                        >
                            <RankHistoryRows history={rankUpHistory} />
                        </div>
                    </div>
                    <div className="member-withdraw-wrap">
                        <button type="button" className="member-withdraw-btn" id="memberWithdrawBtn" aria-label="退会する">
                            退会する
                        </button>
                    </div>
                </>
            )}
        </div>
    );
}

export function IosWalletMemberCard({ data }: { data: MemberPayload }): JSX.Element {
    const rootRef = useRef<HTMLDivElement>(null);
    const reactId = useId().replace(/:/g, '');
    const codeId = `member-code-${reactId}`;
    const member = data.member && typeof data.member === 'object' ? data.member : null;
    const user = data.user || (member && member.user) || null;
    const displayName = (user && typeof user.name === 'string' && user.name.trim() !== '')
        ? user.name.trim()
        : (data.is_member === true ? '会員' : 'ゲスト');
    const pointsRaw = data.points != null ? Number(data.points) : (member && member.points != null ? Number(member.points) : 0);
    const points = Number.isFinite(pointsRaw) ? pointsRaw : 0;
    const memberNumber = member && member.member_number ? String(member.member_number) : '';
    const currentRankLabel = data.current_rank_label || 'MEMBER';
    const nextRankLabel = data.next_rank_label || null;
    const pointsToNextRank = data.points_to_next_rank != null ? Number(data.points_to_next_rank) : null;
    const showPointsDisplay = data.show_points_display !== false;
    const showRankProgress = data.show_rank_progress !== false;
    const rankUnit = data.rank_basis === 'stamps' ? '個' : 'pt';
    const periodDays = data.days_required_for_next_rank != null ? Number(data.days_required_for_next_rank) : null;
    const rankMetricRaw = data.rank_metric != null ? Number(data.rank_metric) : points;
    const rankMetric = Number.isFinite(rankMetricRaw) ? rankMetricRaw : points;
    const isSample = data.is_sample_preview === true;
    const progressTotal = pointsToNextRank !== null ? rankMetric + pointsToNextRank : Math.max(rankMetric, 1);
    const progressPct = pointsToNextRank !== null
        ? Math.max(8, Math.min(100, Math.round((rankMetric / progressTotal) * 100)))
        : 100;
    const progressText = nextRankLabel && pointsToNextRank !== null
        ? `${nextRankLabel}まであと ${pointsToNextRank.toLocaleString('ja-JP')} ${rankUnit}`
            + (periodDays !== null && Number.isFinite(periodDays) ? `（過去${periodDays}日）` : '')
        : '最高ランクに到達しています';

    useEffect(() => {
        const el = rootRef.current;
        if (el === null) {
            return;
        }
        const mm = getMemberManager() as Record<string, unknown> | undefined;
        if (mm === undefined) {
            return;
        }
        const generateBarcode = mm._generateBarcode as ((id: string, n: string) => void) | undefined;
        const setupEnlarge = mm._setupCodeEnlargeButton as (() => void) | undefined;
        const attachRegister = mm._attachRegisterTrigger as ((c: Element) => void) | undefined;
        const setupPurchase = mm._setupPurchaseHistoryToggle as (() => void) | undefined;
        const setupPoint = mm._setupPointHistoryToggle as (() => void) | undefined;
        const setupWithdraw = mm._setupWithdrawButton as (() => void) | undefined;
        const setupWallet = mm._setupAddToWallet as (() => void) | undefined;
        const renderLottery = mm._renderLotteryCta as ((c: Element, n: number) => void) | undefined;
        const maybeGacha = mm._maybeOpenGachaPopupForPending as ((n: number) => void) | undefined;

        if (typeof generateBarcode === 'function') {
            generateBarcode(codeId, memberNumber);
        }
        if (typeof setupEnlarge === 'function') {
            setupEnlarge();
        }
        const enlargeBtn = el.querySelector('.ios-wallet-member__enlarge') as HTMLElement | null;
        const hiddenEnlarge = el.querySelector('.member-card-enlarge-code-btn') as HTMLElement | null;
        if (enlargeBtn !== null && hiddenEnlarge !== null) {
            const onClick = (): void => {
                hiddenEnlarge.click();
            };
            enlargeBtn.addEventListener('click', onClick);
        }
        el.querySelectorAll('[data-ios-member-nav]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const tab = (btn as HTMLElement).getAttribute('data-ios-member-nav');
                if (tab && typeof window.app?.switchTab === 'function') {
                    void window.app.switchTab(tab);
                }
            });
        });
        if (isSample) {
            if (typeof attachRegister === 'function') {
                attachRegister(el);
            }
            return;
        }
        if (typeof setupWallet === 'function') {
            setupWallet();
        }
        if (typeof setupPurchase === 'function') {
            setupPurchase();
        }
        if (typeof setupPoint === 'function') {
            setupPoint();
        }
        if (typeof setupWithdraw === 'function') {
            setupWithdraw();
        }
        const pending = pendingLotteryCount();
        if (typeof renderLottery === 'function') {
            renderLottery(el, pending);
        }
        if (typeof maybeGacha === 'function') {
            maybeGacha(pending);
        }
        const onWalletSummary = (): void => {
            const nextPending = pendingLotteryCount();
            if (typeof renderLottery === 'function') {
                renderLottery(el, nextPending);
            }
            if (typeof maybeGacha === 'function') {
                maybeGacha(nextPending);
            }
        };
        document.addEventListener('liff-wallet-summary-updated', onWalletSummary);
        return () => {
            document.removeEventListener('liff-wallet-summary-updated', onWalletSummary);
        };
    }, [data, codeId, memberNumber, isSample]);

    const showApple = !isSample && shouldShowAppleWallet();
    const showGoogle = !isSample && shouldShowGoogleWallet();

    return (
        <div ref={rootRef} className={`ios-wallet-member${isSample ? ' ios-wallet-member--demo' : ''}`}>
            <header className="ios-wallet-member__hello">
                <span className="ios-wallet-member__avatar" aria-hidden="true">{String(displayName).slice(0, 1)}</span>
                <div className="ios-wallet-member__hello-copy">
                    <p className="ios-wallet-member__hello-title">Hello, {displayName}</p>
                    <p className="ios-wallet-member__hello-sub">いつもご来店ありがとうございます</p>
                </div>
                {!isSample ? (
                    <button type="button" className="ios-wallet-member__edit" onClick={openProfileEdit} aria-label="プロフィール編集">
                        <i className="fas fa-pen" />
                    </button>
                ) : null}
            </header>
            <article className="ios-wallet-card" aria-label="会員証">
                <div className="ios-wallet-card__top">
                    <div className="ios-wallet-card__brand">
                        <span className="ios-wallet-card__logo" aria-hidden="true">R</span>
                        <div>
                            <p className="ios-wallet-card__brand-name">Reggit Rewards</p>
                            <p className="ios-wallet-card__brand-sub">MEMBER</p>
                        </div>
                    </div>
                    <span className="ios-wallet-card__tier">{currentRankLabel}</span>
                </div>
                {showPointsDisplay ? (
                    <div className="ios-wallet-card__points">
                        <p className="ios-wallet-card__points-label">Available Points</p>
                        <p className="ios-wallet-card__points-value">
                            {points.toLocaleString('ja-JP')} <small>Points</small>
                        </p>
                    </div>
                ) : null}
                {showRankProgress ? (
                    <div className="ios-wallet-card__progress">
                        <div className="ios-wallet-card__progress-bar" aria-hidden="true">
                            <span style={{ width: `${progressPct}%` }} />
                        </div>
                        <p className="ios-wallet-card__progress-text">{progressText}</p>
                    </div>
                ) : null}
                <div className="ios-wallet-card__scan">
                    <p className="ios-wallet-card__scan-label">SCAN TO EARN OR REDEEM</p>
                    <div className="ios-wallet-card__code-wrap">
                        <svg id={codeId} className="ios-wallet-card__barcode" aria-label="会員番号バーコード" />
                    </div>
                    <p className="ios-wallet-card__number">{memberNumber.replace(/(.{4})/g, '$1 ').trim()}</p>
                </div>
            </article>
            <div className="ios-wallet-member__meta">
                {showPointsDisplay ? <p>ポイント有効期限は店舗設定に従います</p> : <p />}
                <button
                    type="button"
                    className="ios-wallet-member__enlarge"
                    data-member-number={memberNumber}
                    data-code-type="barcode"
                >
                    会員証を拡大
                </button>
            </div>
            {showApple ? (
                <div className="member-card-add-to-wallet-wrap">
                    <button
                        type="button"
                        className="member-card-add-to-wallet-btn member-card-add-to-wallet-btn--apple"
                        data-wallet-pass-url={walletPassUrl()}
                        aria-label="Apple Walletに追加"
                    >
                        <span className="member-card-add-to-wallet-text">
                            <i className="fab fa-apple" aria-hidden="true" /> Apple Walletに追加
                        </span>
                    </button>
                </div>
            ) : null}
            {showGoogle ? (
                <div className="member-card-add-to-wallet-wrap">
                    <button
                        type="button"
                        className="member-card-add-to-wallet-btn member-card-add-to-wallet-btn--google"
                        data-google-wallet
                        aria-label="Google Walletに追加"
                    >
                        <span className="member-card-add-to-wallet-text">
                            <i className="fab fa-google" aria-hidden="true" /> Google Walletに追加
                        </span>
                    </button>
                </div>
            ) : null}
            <nav className="ios-wallet-member__quick" aria-label="会員メニュー">
                <button type="button" className="ios-wallet-member__quick-item" data-ios-member-nav="coupons">
                    <span className="ios-wallet-member__quick-icon" aria-hidden="true"><i className="fas fa-ticket-alt" /></span>クーポン
                </button>
                <button type="button" className="ios-wallet-member__quick-item" data-ios-member-nav="stamps">
                    <span className="ios-wallet-member__quick-icon" aria-hidden="true"><i className="fas fa-stamp" /></span>スタンプ
                </button>
                <button type="button" className="ios-wallet-member__quick-item" data-ios-member-nav="orders">
                    <span className="ios-wallet-member__quick-icon" aria-hidden="true"><i className="fas fa-receipt" /></span>履歴
                </button>
                <button
                    type="button"
                    className="ios-wallet-member__quick-item"
                    data-action="member-open-register"
                    onClick={() => openRegister(false)}
                >
                    <span className="ios-wallet-member__quick-icon" aria-hidden="true"><i className="fas fa-user-plus" /></span>登録
                </button>
            </nav>
            <button
                type="button"
                className="member-card-enlarge-code-btn is-visually-hidden"
                data-member-number={memberNumber}
                data-code-type="barcode"
                tabIndex={-1}
                aria-hidden="true"
            >
                会員証拡大
            </button>
            {isSample ? (
                <div className="member-demo-actions">
                    <button
                        type="button"
                        className="btn btn-primary member-register-trigger"
                        data-action="member-open-register"
                        onClick={() => openRegister(false)}
                    >
                        会員登録する
                    </button>
                </div>
            ) : (
                <>
                    <div className="member-purchase-history">
                        <button type="button" className="member-purchase-history-toggle" aria-expanded="false" aria-controls="memberPurchaseHistoryList" id="memberPurchaseHistoryToggle">
                            <span className="member-purchase-history-toggle-label">購入履歴</span>
                            <i className="fas fa-chevron-down member-purchase-history-toggle-icon" aria-hidden="true" />
                        </button>
                        <div id="memberPurchaseHistoryList" className="member-purchase-history-list" hidden aria-labelledby="memberPurchaseHistoryToggle" />
                    </div>
                    <div className="member-point-history">
                        <button type="button" className="member-purchase-history-toggle" aria-expanded="false" aria-controls="memberPointHistoryList" id="memberPointHistoryToggle">
                            <span className="member-purchase-history-toggle-label">ポイント履歴</span>
                            <i className="fas fa-chevron-down member-purchase-history-toggle-icon" aria-hidden="true" />
                        </button>
                        <div id="memberPointHistoryList" className="member-purchase-history-list" hidden aria-labelledby="memberPointHistoryToggle" />
                    </div>
                    <div className="member-withdraw-wrap">
                        <button type="button" className="member-withdraw-btn" id="memberWithdrawBtn" aria-label="退会する">退会する</button>
                    </div>
                </>
            )}
        </div>
    );
}

import React, { useEffect, useMemo, useRef } from 'react';
import { useLiffApp } from '../../context/LiffAppContext';
import { isDemoDataMode } from '../../demo/demoMode';
import { isFeatureEnabled, readFeatureFlags } from '../../demo/navigation';
import { AnnouncementView } from '../announcements/AnnouncementView';

type HomeAction = {
    tab: string;
    en: string;
    ja: string;
    svg: JSX.Element;
};

function headerTitle(): string {
    const title = window.__FRONTEND_HEADER__?.title;
    if (typeof title === 'string' && title.trim() !== '') {
        return title;
    }
    return document.title || 'Reggit';
}

function buildActions(): HomeAction[] {
    const flags = readFeatureFlags();
    const actions: HomeAction[] = [];
    if (flags.feature_orders_enabled !== false) {
        actions.push({
            tab: 'products',
            en: 'Order',
            ja: '事前に注文する',
            svg: (
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M8 4h8v3H8V4zM5 8h14l-1.2 11.2A2 2 0 0 1 15.8 21H8.2a2 2 0 0 1-2-1.8L5 8z" stroke="currentColor" strokeWidth="1.6" strokeLinejoin="round" />
                    <path d="M9 12h6M9 15h4" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
                </svg>
            ),
        });
    }
    if (flags.feature_members_enabled !== false) {
        actions.push({
            tab: 'member',
            en: 'Membership',
            ja: '会員証を表示する',
            svg: (
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <rect x="3" y="6" width="18" height="12" rx="2.5" stroke="currentColor" strokeWidth="1.6" />
                    <path d="M3 10h18M7 15h5" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
                </svg>
            ),
        });
    }
    if (flags.feature_queue_enabled !== false) {
        actions.push({
            tab: 'queue',
            en: 'Queue',
            ja: '順番待ち状況を確認',
            svg: (
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle cx="8" cy="9" r="2.2" stroke="currentColor" strokeWidth="1.6" />
                    <circle cx="16" cy="9" r="2.2" stroke="currentColor" strokeWidth="1.6" />
                    <path d="M4 18c.6-2.4 2.4-3.6 4-3.6s3.4 1.2 4 3.6M12 18c.6-2.4 2.4-3.6 4-3.6s3.4 1.2 4 3.6" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
                </svg>
            ),
        });
    }
    return actions.slice(0, 3);
}

function MoreIcon({ kind }: { kind: string }): JSX.Element {
    if (kind === 'history') {
        return (
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M12 7v5l3 2" stroke="#fff" strokeWidth="1.8" strokeLinecap="round" />
                <circle cx="12" cy="12" r="8" stroke="#fff" strokeWidth="1.8" />
            </svg>
        );
    }
    if (kind === 'photos') {
        return (
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <rect x="3.5" y="5" width="17" height="14" rx="2" stroke="#fff" strokeWidth="1.8" />
                <circle cx="9" cy="10" r="1.6" fill="#fff" />
                <path d="M3.5 16l4.5-4 3.5 3 3-2.5 6 3.5" stroke="#fff" strokeWidth="1.8" strokeLinejoin="round" />
            </svg>
        );
    }
    return (
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M10 13a5 5 0 0 0 7.1.1l2-2a5 5 0 0 0-7.1-7.1l-1.1 1.1" stroke="#fff" strokeWidth="1.8" strokeLinecap="round" />
            <path d="M14 11a5 5 0 0 0-7.1-.1l-2 2a5 5 0 0 0 7.1 7.1l1.1-1.1" stroke="#fff" strokeWidth="1.8" strokeLinecap="round" />
        </svg>
    );
}

function iconColorClass(kind: string): string {
    if (kind === 'history') {
        return 'ios-row__icon--gray';
    }
    if (kind === 'photos') {
        return 'ios-row__icon--pink';
    }
    return 'ios-row__icon--gray';
}

/**
 * /liff 固定デモUI のホーム（LiffLayoutManager._buildIosHomeHtml 相当）。
 */
export function FixedHomeView(): JSX.Element {
    const sectionRef = useRef<HTMLDivElement>(null);
    const { switchTab } = useLiffApp();
    const title = useMemo(() => headerTitle(), []);
    const actions = useMemo(() => buildActions(), []);
    const queueEnabled = isFeatureEnabled('queue');
    const membersEnabled = isFeatureEnabled('member');
    const galleryEnabled = isFeatureEnabled('gallery');
    const ordersEnabled = isFeatureEnabled('orders');
    const queueNo = String(new Date().getDate()) + '2';
    const demo = isDemoDataMode();

    useEffect(() => {
        const section = sectionRef.current;
        if (section === null) {
            return;
        }
        section.dataset.reactOwned = '1';
        section.dataset.layoutRendered = '1';
    }, []);

    const go = (tab: string): void => {
        void switchTab(tab);
    };

    const moreRows: Array<{ tab: string; label: string; iconKind: string }> = [];
    if (galleryEnabled) {
        moreRows.push({ tab: 'gallery', label: 'ギャラリー', iconKind: 'photos' });
    }
    if (ordersEnabled) {
        moreRows.push({ tab: 'orders', label: '注文履歴', iconKind: 'history' });
    }

    return (
        <div
            ref={sectionRef}
            className="tab-section"
            id="homeSection"
            data-tab="home"
            data-react-owned="1"
            data-layout-rendered="1"
        >
            <div className="home-content ios-home lp-home" data-ios-home-version="6">
                <section className="lp-home-brand">
                    <p className="lp-home-brand__name">{title}</p>
                    <p className="lp-home-brand__tag">スマートに、心地よく、あなたの時間を。</p>
                </section>

                {queueEnabled ? (
                    demo ? (
                        <button type="button" className="lp-home-queue" onClick={() => go('queue')}>
                            <span className="lp-home-queue__ticket" aria-hidden="true">{queueNo}</span>
                            <span className="lp-home-queue__body">
                                <span className="lp-home-queue__label">あなたの現在の番号</span>
                                <span className="lp-home-queue__num">
                                    {queueNo}
                                    {' '}
                                    <small>番</small>
                                </span>
                            </span>
                            <span className="lp-home-queue__meta">
                                <span className="lp-home-queue__badge">ご案内中</span>
                                <span className="lp-home-queue__wait">あと 5 組 · 目安 25 分</span>
                            </span>
                            <span className="lp-home-queue__chevron" aria-hidden="true">›</span>
                        </button>
                    ) : (
                        <button type="button" className="lp-home-queue" onClick={() => go('queue')}>
                            <span className="lp-home-queue__body">
                                <span className="lp-home-queue__label">順番待ち</span>
                                <span className="lp-home-queue__num">状況を確認する</span>
                            </span>
                            <span className="lp-home-queue__chevron" aria-hidden="true">›</span>
                        </button>
                    )
                ) : null}

                {actions.length > 0 ? (
                    <div className="lp-home-actions">
                        {actions.map((a) => (
                            <button
                                key={a.tab}
                                type="button"
                                className="lp-home-action"
                                onClick={() => go(a.tab)}
                            >
                                <span className="lp-home-action__icon" aria-hidden="true">{a.svg}</span>
                                <span className="lp-home-action__en">{a.en}</span>
                                <span className="lp-home-action__ja">{a.ja}</span>
                                <span className="lp-home-action__chevron" aria-hidden="true">›</span>
                            </button>
                        ))}
                    </div>
                ) : null}

                {membersEnabled ? (
                    <button type="button" className="lp-home-promo" onClick={() => go('member')}>
                        <span className="lp-home-promo__eyebrow">Reggit Members</span>
                        <span className="lp-home-promo__title">会員登録で特典を受け取ろう</span>
                        <span className="lp-home-promo__text">登録すると、ドリンク1杯サービス！</span>
                        <span className="lp-home-promo__chevron" aria-hidden="true">›</span>
                    </button>
                ) : null}

                {moreRows.length > 0 ? (
                    <>
                        <p className="ios-section-label">もっと見る</p>
                        <div className="ios-group lp-home-more">
                            {moreRows.map((row) => (
                                <button
                                    key={row.tab}
                                    type="button"
                                    className="ios-row"
                                    onClick={() => go(row.tab)}
                                >
                                    <span className={`ios-row__icon ${iconColorClass(row.iconKind)}`} aria-hidden="true">
                                        <MoreIcon kind={row.iconKind} />
                                    </span>
                                    <span className="ios-row__body">
                                        <span className="ios-row__title">{row.label}</span>
                                    </span>
                                    <span className="ios-row__chevron" aria-hidden="true">›</span>
                                </button>
                            ))}
                        </div>
                    </>
                ) : null}

                <div className="ios-status-card" id="iosHomeStatus" hidden>
                    <p className="ios-status-card__title" id="iosHomeStatusTitle" />
                    <p className="ios-status-card__text" id="iosHomeStatusText" />
                </div>
                <p className="ios-section-label">お知らせ</p>
                <div
                    className="ios-group ios-announce-list announcements-data-mount"
                    id="iosHomeAnnouncements"
                    data-react-owned="1"
                >
                    <AnnouncementView iosHome />
                </div>
            </div>
        </div>
    );
}

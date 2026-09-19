import React, { useEffect, useState } from 'react';
import { marginBottomStyle } from './blockStyles';
import { fetchMemberProfile } from './memberBind';
import type { BuilderBlockData } from './types';

type MemberSummaryBlockProps = {
    block: BuilderBlockData;
};

function MemberSummaryRing({ pct }: { pct: number }): JSX.Element {
    const p = Math.max(0, Math.min(100, pct));
    const dash = `${p * 0.88} 88`;
    return (
        <div className="ms-ring" aria-hidden="true">
            <svg viewBox="0 0 36 36" className="ms-ring__svg">
                <path className="ms-ring__track" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                <path className="ms-ring__fill" strokeDasharray={dash} d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
            </svg>
            <span className="ms-ring__label">{Math.round(p)}%</span>
        </div>
    );
}

function MemberSummaryCard({ data, greeting, design }: {
    data: Record<string, unknown> | null;
    greeting: string;
    design: string;
}): JSX.Element {
    const variant = design || 'rewards';
    const isMember = !!(data && data.is_member === true);
    if (!isMember) {
        return (
            <div className={`member-summary-card member-summary-card--guest member-summary-card--design-${variant}`}>
                <div className="ms-guest-copy">
                    <p className="ms-guest-eyebrow">Members</p>
                    <p className="ms-guest-title">{greeting || 'ようこそ'}</p>
                    <p className="ms-guest-sub">会員登録でポイント・クーポン・先行予約が使えます</p>
                </div>
                <a href="#member" className="builder-nav ms-guest-cta">
                    <i className="fas fa-user-plus" aria-hidden="true" /> 会員になる
                </a>
            </div>
        );
    }

    const user = data.user as { name?: string } | undefined;
    const member = data.member as { points?: number } | undefined;
    const name = user && user.name ? String(user.name) : '';
    const points = Number(data.points != null ? data.points : (member && member.points) || 0);
    const pointsLabel = points.toLocaleString('ja-JP');
    const rankLabel = data.current_rank_label != null ? String(data.current_rank_label) : '';
    const showPoints = data.show_points_display !== false;
    const showRank = data.show_rank_progress !== false;
    const nextRankLabel = data.next_rank_label != null ? String(data.next_rank_label) : '';
    const toNext = Number(data.points_to_next_rank != null ? data.points_to_next_rank : 0);
    const rankUnit = data.rank_basis === 'stamps' ? '個' : 'pt';
    const rankMetric = Number(data.rank_metric != null ? data.rank_metric : points);
    const hello = greeting !== '' ? greeting : (name !== '' ? `こんにちは、${name} さん` : 'おかえりなさい');

    let pct = 0;
    let progress: JSX.Element | null = null;
    let ring: JSX.Element | null = null;
    if (showRank && nextRankLabel !== '' && toNext > 0) {
        const span = rankMetric + toNext;
        pct = span > 0 ? Math.max(4, Math.min(100, Math.round((rankMetric / span) * 100))) : 0;
        if (variant === 'rewards' || variant === 'glass') {
            ring = <MemberSummaryRing pct={pct} />;
        }
        progress = (
            <div className="ms-progress">
                <div className="ms-progress-track">
                    <span className="ms-progress-fill" style={{ width: `${pct}%` }} />
                </div>
                <p className="ms-progress-text">
                    <span className="ms-progress-goal">
                        次の<strong>{nextRankLabel}</strong>まで
                    </span>
                    {' '}あと <strong>{toNext.toLocaleString('ja-JP')}</strong> {rankUnit}
                </p>
            </div>
        );
    }

    const designClass = `member-summary-card--design-${variant}`;
    const rankBadge = rankLabel !== ''
        ? (
            <span className="ms-rank">
                <i className="fas fa-crown" aria-hidden="true" /> {rankLabel}
            </span>
        )
        : null;
    const pointsBlock = showPoints
        ? (
            <div className="ms-points">
                <span className="ms-points-value">{pointsLabel}</span>
                <span className="ms-points-unit">pt</span>
            </div>
        )
        : <div />;
    const cardLink = (
        <a href="#member" className="builder-nav ms-card-link" aria-label="会員証を表示">
            <i className="fas fa-qrcode" aria-hidden="true" />
        </a>
    );

    if (variant === 'wallet') {
        return (
            <div className={`member-summary-card member-summary-card--member ${designClass}`}>
                <div className="ms-wallet-row">
                    <div className="ms-greeting">
                        <p className="ms-hello">{hello}</p>
                        {rankBadge}
                    </div>
                    <div className="ms-wallet-points">{pointsBlock}</div>
                    {cardLink}
                </div>
                {progress}
            </div>
        );
    }
    if (variant === 'compact') {
        return (
            <div className={`member-summary-card member-summary-card--member ${designClass}`}>
                <div className="ms-compact-row">
                    {rankBadge}
                    <p className="ms-hello">{hello}</p>
                    {showPoints ? <span className="ms-compact-pt">{pointsLabel} pt</span> : null}
                </div>
            </div>
        );
    }
    if (variant === 'emblem') {
        return (
            <div className={`member-summary-card member-summary-card--member ${designClass}`}>
                <div className="ms-emblem">
                    <i className="fas fa-crown ms-emblem-icon" aria-hidden="true" />
                    <p className="ms-hello">{hello}</p>
                    {rankBadge}
                    {showPoints ? <div className="ms-points ms-points--center">{pointsBlock}</div> : null}
                </div>
                {progress}
            </div>
        );
    }
    if (variant === 'minimal') {
        return (
            <div className={`member-summary-card member-summary-card--member ${designClass}`}>
                <div className="ms-minimal">
                    <p className="ms-hello">{hello}</p>
                    {showPoints ? (
                        <p className="ms-minimal-pt">
                            {pointsLabel} <span>pt</span>
                        </p>
                    ) : null}
                    {rankBadge}
                </div>
            </div>
        );
    }
    if (variant === 'split') {
        return (
            <div className={`member-summary-card member-summary-card--member ${designClass}`}>
                <div className="ms-split">
                    <div className="ms-split-left">
                        <p className="ms-hello">{hello}</p>
                        {rankBadge}
                    </div>
                    <div className="ms-split-right">
                        {showPoints ? pointsBlock : null}
                        {cardLink}
                    </div>
                </div>
                {progress}
            </div>
        );
    }
    if (variant === 'glass') {
        return (
            <div className={`member-summary-card member-summary-card--member ${designClass}`}>
                <div className="ms-top">
                    <div className="ms-greeting">
                        <p className="ms-hello">{hello}</p>
                        {rankBadge}
                    </div>
                </div>
                <div className="ms-points-row">{pointsBlock}</div>
                {progress}
            </div>
        );
    }
    if (variant === 'tier') {
        const tierLabel = rankLabel !== '' ? rankLabel : 'Member';
        return (
            <div className={`member-summary-card member-summary-card--member ${designClass}`}>
                <div className="ms-tier">
                    <div className="ms-tier__badge">
                        <i className="fas fa-gem" aria-hidden="true" />
                        <span>{tierLabel}</span>
                    </div>
                    <p className="ms-hello">{hello}</p>
                    {showPoints ? <div className="ms-points ms-points--center">{pointsBlock}</div> : null}
                </div>
                {progress}
            </div>
        );
    }
    return (
        <div className={`member-summary-card member-summary-card--member ${designClass}`}>
            <div className="ms-top">
                <div className="ms-greeting">
                    <p className="ms-eyebrow">Rewards</p>
                    <p className="ms-hello">{hello}</p>
                    {rankBadge}
                </div>
                {cardLink}
            </div>
            <div className="ms-points-row">
                {pointsBlock}
                {ring}
            </div>
            {progress}
        </div>
    );
}

export function MemberSummaryBlock({ block }: MemberSummaryBlockProps): JSX.Element {
    const greeting = block.greeting != null ? String(block.greeting) : '';
    const design = block.design != null ? String(block.design) : 'rewards';
    const [data, setData] = useState<Record<string, unknown> | null | undefined>(undefined);

    useEffect(() => {
        let cancelled = false;
        void fetchMemberProfile().then((next) => {
            if (!cancelled) {
                setData(next);
            }
        });
        return () => {
            cancelled = true;
        };
    }, []);

    return (
        <section
            className="builder-block builder-member-summary"
            data-source="member_summary"
            data-design={design}
            {...(greeting !== '' ? { 'data-greeting': greeting } : {})}
            style={marginBottomStyle(block)}
        >
            {data === undefined ? (
                <div className={`member-summary-card member-summary-card--loading member-summary-card--design-${design}`}>
                    <div className="ms-skeleton ms-skeleton--label" />
                    <div className="ms-skeleton ms-skeleton--value" />
                    <div className="ms-skeleton ms-skeleton--bar" />
                </div>
            ) : (
                <MemberSummaryCard data={data} greeting={greeting} design={design} />
            )}
        </section>
    );
}

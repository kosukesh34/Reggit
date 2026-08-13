import React, { useCallback, useEffect, useState } from 'react';
import { StampScannerPopup } from '../../../liff-ui/components/stamp/StampScannerPopup';
import { useStampGrantFromUrl } from '../../../liff-ui/hooks/useStampGrantFromUrl';
import { isFeatureUiShell } from '../../demo/demoMode';
import {
    formatValidUntil,
    getStampDesignSettings,
    loadStamps,
    openStampDetail,
    switchToMemberTab,
    type StampCard,
    type StampDesignSettings,
    type StampRewardMilestone,
} from './api';

type ViewState =
    | { kind: 'loading' }
    | { kind: 'guest' }
    | { kind: 'member_required'; message: string }
    | { kind: 'error'; message: string }
    | { kind: 'ready'; cards: StampCard[]; defaultImage: string | null };

function ProgressSummary({
    current,
    required,
    completed,
}: {
    current: number;
    required: number;
    completed: boolean;
}): JSX.Element {
    const progress = Math.min(100, required > 0 ? (current / required) * 100 : 0);
    return (
        <div className={`stamp-progress-summary${completed ? ' stamp-progress-summary--completed' : ''}`}>
            <div className="stamp-progress-summary__ring" style={{ ['--stamp-progress' as string]: progress }}>
                <span className="stamp-progress-summary__value">
                    {current}
                    <small>/{required}</small>
                </span>
            </div>
            <div className="stamp-progress-summary__copy">
                <strong>{completed ? '達成しました' : `あと ${Math.max(0, required - current)} 個`}</strong>
                <span>スタンプを集めて特典をゲット</span>
            </div>
        </div>
    );
}

function resolveStampImage(stampImages: Record<string, string> | null, index: number): string | null {
    if (!stampImages || typeof stampImages !== 'object') {
        return null;
    }
    const url = stampImages[String(index)] || stampImages[String(index + 1)] || null;
    return typeof url === 'string' && url !== '' ? url : null;
}

function StampSlots({
    current,
    required,
    layoutStyle,
    stampImages,
    stampEmptyImages,
    design,
    newFrom,
    newTo,
}: {
    current: number;
    required: number;
    layoutStyle?: string;
    stampImages?: Record<string, string> | null;
    stampEmptyImages?: Record<string, string> | null;
    design: StampDesignSettings;
    newFrom?: number;
    newTo?: number;
}): JSX.Element {
    const layout = layoutStyle || 'grid';
    const accentStyle = design.accentColor !== ''
        ? { ['--stamp-accent-color' as string]: design.accentColor } as React.CSSProperties
        : undefined;
    const slots = [];
    for (let i = 0; i < required; i++) {
        const filled = i < current;
        const isNext = !filled && i === current && current < required;
        const number = i + 1;
        const classes = ['stamp-slot', `stamp-slot--shape-${design.slotStyle}`];
        if (filled) {
            classes.push('stamp-slot--filled');
        } else {
            classes.push('stamp-slot--empty');
        }
        if (isNext) {
            classes.push('stamp-slot--next');
        }
        if (filled && newFrom !== undefined && newTo !== undefined && i >= newFrom && i < newTo) {
            classes.push('stamp-slot--new');
        }
        const label = filled
            ? `${number}番目（押印済み）`
            : isNext
                ? `${number}番目（次はここ）`
                : `${number}番目`;
        let content: React.ReactNode;
        if (!filled) {
            const emptyImageUrl = resolveStampImage(stampEmptyImages || null, i);
            if (emptyImageUrl !== null) {
                classes.push('stamp-slot--has-image');
                content = <img src={emptyImageUrl} alt="" className="stamp-slot-image" loading="lazy" />;
            } else {
                content = <span className="stamp-slot-number">{number}</span>;
            }
        } else {
            const imageUrl = design.filledStyle === 'image'
                ? resolveStampImage(stampImages || null, i)
                : null;
            if (imageUrl) {
                content = <img src={imageUrl} alt="" className="stamp-slot-image" loading="lazy" />;
            } else if (design.filledStyle === 'icon') {
                content = <i className="fas fa-stamp" aria-hidden="true" />;
            } else {
                content = <i className="fas fa-check" aria-hidden="true" />;
            }
        }
        slots.push(
            <div key={`${i}-${newFrom ?? 0}-${newTo ?? 0}`} className={classes.join(' ')} aria-label={label}>
                {content}
            </div>,
        );
    }
    return (
        <div
            className={`stamp-slots stamp-slots--${layout} stamp-slots--filled-${design.filledStyle}`}
            role="img"
            aria-label={`${current}/${required} スタンプ`}
            style={accentStyle}
        >
            {slots}
        </div>
    );
}

function RewardMilestones({
    milestones,
    current,
}: {
    milestones: StampRewardMilestone[];
    current: number;
}): JSX.Element | null {
    const items = milestones
        .filter(
            (m) =>
                m
                && Number.parseInt(String(m.stamp_count), 10) > 0
                && String(m.description || '').trim() !== '',
        )
        .sort((a, b) => Number(a.stamp_count) - Number(b.stamp_count));
    if (items.length === 0) {
        return null;
    }
    return (
        <ul className="stamp-card-milestones">
            {items.map((m) => {
                const stampCount = Number.parseInt(String(m.stamp_count), 10);
                const achieved = current >= stampCount;
                return (
                    <li
                        key={stampCount}
                        className={`stamp-card-milestone${achieved ? ' stamp-card-milestone--achieved' : ''}`}
                    >
                        <span className="stamp-card-milestone-count">{stampCount}個</span>
                        <span className="stamp-card-milestone-text">{String(m.description)}</span>
                    </li>
                );
            })}
        </ul>
    );
}

function IosStampDots({
    current,
    required,
    newFrom,
    newTo,
}: {
    current: number;
    required: number;
    newFrom?: number;
    newTo?: number;
}): JSX.Element {
    const slots = [];
    for (let i = 0; i < required; i++) {
        const filled = i < current;
        const isNew = filled && newFrom !== undefined && newTo !== undefined && i >= newFrom && i < newTo;
        slots.push(
            <span
                key={`${i}-${isNew ? 'new' : 'base'}`}
                className={`ios-stamp-dot${filled ? ' is-filled' : ''}${!filled && i === current ? ' is-next' : ''}${isNew ? ' is-new' : ''}`}
                aria-hidden="true"
            >
                {filled ? <i className="fas fa-check" /> : (i + 1)}
            </span>,
        );
    }
    return (
        <div className="ios-stamp-dots" role="img" aria-label={`${current}/${required} スタンプ`}>
            {slots}
        </div>
    );
}

function ClassicStampCard({
    card,
    defaultImage,
    design,
    onOpen,
    newFrom,
    newTo,
}: {
    card: StampCard;
    defaultImage: string | null;
    design: StampDesignSettings;
    onOpen: (id: string) => void;
    newFrom?: number;
    newTo?: number;
}): JSX.Element {
    const current = card.current_stamps || 0;
    const required = card.required_stamps || 10;
    const progress = Math.min(100, (current / required) * 100);
    const completed = !!card.completed_at;
    const layoutStyle = card.layout_style || 'grid';
    const imageUrl = card.image_url || defaultImage;
    const validUntilDate = formatValidUntil(card.member_stamp_valid_until || card.valid_until);
    const id = String(card.id ?? '');
    const accentStyle = design.accentColor !== ''
        ? { ['--stamp-accent-color' as string]: design.accentColor } as React.CSSProperties
        : undefined;

    return (
        <button
            type="button"
            className={`stamp-card-item stamp-card-item--layout-${layoutStyle} stamp-card-item--screen-${design.screenDesign}${completed ? ' stamp-card-item--completed' : ''}`}
            data-stamp-id={id}
            style={accentStyle}
            onClick={() => {
                if (id !== '') {
                    onOpen(id);
                }
            }}
        >
            <div className="stamp-card-item__hero">
                {imageUrl ? (
                    <div className="stamp-card-image">
                        <img
                            src={imageUrl}
                            alt="スタンプカード"
                            loading="lazy"
                            onError={(e) => {
                                const wrap = (e.target as HTMLElement).closest('.stamp-card-image') as HTMLElement | null;
                                if (wrap !== null) {
                                    wrap.style.display = 'none';
                                }
                            }}
                        />
                    </div>
                ) : (
                    <div className="stamp-card-image stamp-card-image--badge">
                        <span className="stamp-card-image__mark">
                            <i className="fas fa-stamp" aria-hidden="true" />
                        </span>
                    </div>
                )}
                <div className="stamp-card-item__headline">
                    <h3 className="stamp-card-name">{card.name || 'スタンプカード'}</h3>
                    {completed ? (
                        <span className="stamp-card-badge">
                            <i className="fas fa-check-circle" /> 達成
                        </span>
                    ) : null}
                </div>
            </div>
            <ProgressSummary current={current} required={required} completed={completed} />
            <StampSlots
                current={current}
                required={required}
                layoutStyle={layoutStyle}
                stampImages={card.stamp_images || null}
                stampEmptyImages={card.stamp_empty_images || null}
                design={design}
                newFrom={newFrom}
                newTo={newTo}
            />
            <div className="stamp-card-meta">
                <p className="stamp-card-count">{current} / {required} スタンプ</p>
                {validUntilDate ? <p className="stamp-card-valid">有効期限: {validUntilDate}</p> : null}
            </div>
            {layoutStyle !== 'minimal' ? (
                <div className="stamp-card-progress">
                    <div className="stamp-card-progress-bar" style={{ width: `${progress}%` }} />
                </div>
            ) : null}
            <RewardMilestones
                milestones={Array.isArray(card.reward_milestones) ? card.reward_milestones : []}
                current={current}
            />
            {card.reward_description ? <p className="stamp-card-reward">{card.reward_description}</p> : null}
        </button>
    );
}

function FixedStampCard({
    card,
    defaultImage,
    onOpen,
    newFrom,
    newTo,
}: {
    card: StampCard;
    defaultImage: string | null;
    onOpen: (id: string) => void;
    newFrom?: number;
    newTo?: number;
}): JSX.Element {
    const current = card.current_stamps || 0;
    const required = card.required_stamps || 10;
    const remaining = Math.max(0, required - current);
    const progress = Math.min(100, required > 0 ? (current / required) * 100 : 0);
    const completed = !!card.completed_at;
    const imageUrl = card.image_url || defaultImage || '/images/liff/stamp-card-bg.jpg';
    const validUntilDate = formatValidUntil(card.member_stamp_valid_until || card.valid_until);
    const reward = card.reward_description || '特典をゲット';
    const id = String(card.id ?? '');

    return (
        <button
            type="button"
            className={`ios-stamp-card${completed ? ' is-completed' : ''}`}
            data-stamp-id={id}
            onClick={() => {
                if (id !== '') {
                    onOpen(id);
                }
            }}
        >
            <div className="ios-stamp-card__hero" style={{ backgroundImage: `url('${imageUrl}')` }}>
                <div className="ios-stamp-card__hero-shade" />
                <div className="ios-stamp-card__hero-top">
                    <span className="ios-stamp-card__eyebrow">スタンプカード</span>
                    {completed ? (
                        <span className="ios-stamp-card__done">達成</span>
                    ) : (
                        <span className="ios-stamp-card__remain">あと {remaining}</span>
                    )}
                </div>
                <h3 className="ios-stamp-card__title">{card.name || 'スタンプカード'}</h3>
                <p className="ios-stamp-card__reward">{reward}</p>
            </div>
            <div className="ios-stamp-card__body">
                <div className="ios-stamp-card__meter">
                    <div className="ios-stamp-card__count">
                        <strong>{current}</strong>
                        <span>/{required}</span>
                    </div>
                    <div className="ios-stamp-card__bar">
                        <i style={{ width: `${progress}%` }} />
                    </div>
                    {validUntilDate ? (
                        <p className="ios-stamp-card__expiry">有効期限 {validUntilDate}</p>
                    ) : null}
                </div>
                <IosStampDots current={current} required={required} newFrom={newFrom} newTo={newTo} />
            </div>
        </button>
    );
}

function popRangeFor(
    cardId: string,
    stampCardId: number | null,
    currentStamps: number | null,
    grantedCount: number,
    phase: string,
): { from: number; to: number } | undefined {
    if (phase !== 'success' || stampCardId === null || currentStamps === null) {
        return undefined;
    }
    if (String(stampCardId) !== cardId) {
        return undefined;
    }
    return {
        from: Math.max(0, currentStamps - grantedCount),
        to: currentStamps,
    };
}

export function StampsView(): JSX.Element {
    const [view, setView] = useState<ViewState>({ kind: 'loading' });
    const [scannerOpen, setScannerOpen] = useState(false);
    const grant = useStampGrantFromUrl();
    const design = getStampDesignSettings();

    const reload = useCallback(async (silent = false) => {
        if (!silent) {
            setView({ kind: 'loading' });
        }
        const result = await loadStamps();
        if (result.kind === 'demo' || result.kind === 'ok') {
            setView({ kind: 'ready', cards: result.cards, defaultImage: result.defaultImage });
            return;
        }
        if (result.kind === 'guest') {
            setView({ kind: 'guest' });
            return;
        }
        if (result.kind === 'member_required') {
            setView({ kind: 'member_required', message: result.message });
            return;
        }
        setView({ kind: 'error', message: result.message });
    }, []);

    useEffect(() => {
        void reload();
    }, [reload]);

    useEffect(() => {
        if (grant.phase !== 'success' || grant.animationKey === 0) {
            return;
        }
        void reload(true);
    }, [grant.animationKey, grant.phase, reload]);

    const onOpen = (id: string): void => {
        if (view.kind !== 'ready') {
            return;
        }
        openStampDetail(id, view.cards, view.defaultImage);
    };

    const grantBanner = (
        <>
            {grant.phase === 'granting' ? (
                <p className="stamp-grant-status" role="status">スタンプを付与しています…</p>
            ) : null}
            {grant.phase === 'success' ? (
                <div className="stamp-grant-success" role="status" key={grant.animationKey}>
                    <span><i className="fas fa-check" aria-hidden="true" /></span>
                    <div>
                        <strong>スタンプ付与完了</strong>
                        <p>{grant.message}</p>
                    </div>
                </div>
            ) : null}
            {grant.phase === 'error' ? (
                <p className="form-error" role="alert">{grant.message}</p>
            ) : null}
        </>
    );
    const scanControls = (
        <>
            <button
                type="button"
                className="btn btn-primary stamp-grant-scan"
                disabled={grant.phase === 'granting'}
                onClick={() => setScannerOpen(true)}
            >
                <i className="fas fa-camera" aria-hidden="true" /> カメラでQRを読み取る
            </button>
            <p className="stamps-empty-sub">LINEのコードリーダーでも読み取れます</p>
            <StampScannerPopup
                open={scannerOpen}
                onClose={() => setScannerOpen(false)}
                onDetected={(value) => {
                    setScannerOpen(false);
                    void grant.grantFromRaw(value);
                }}
            />
        </>
    );

    if (view.kind === 'loading') {
        return (
            <div className="loading">
                {grantBanner}
                <i className="fas fa-spinner fa-spin" />
                <p>{grant.phase === 'granting' ? 'スタンプを付与しています...' : '読み込み中...'}</p>
            </div>
        );
    }

    if (view.kind === 'guest') {
        return (
            <div className="member-card-container">
                {grantBanner}
                <div className="member-card member-card--line-only">
                    <div className="member-card-header">
                        <h2 className="member-card-title">
                            <i className="fas fa-stamp" /> スタンプカード
                        </h2>
                    </div>
                    <div className="member-card-line-only-body">
                        <p className="member-card-line-only-message">この端末はゲスト状態です。</p>
                        <p className="member-card-line-only-sub">会員登録するとスタンプ履歴を引き継げます。</p>
                        <button type="button" className="btn btn-primary mt-2" onClick={switchToMemberTab}>
                            <i className="fas fa-user-plus" /> 会員登録へ
                        </button>
                    </div>
                </div>
            </div>
        );
    }

    if (view.kind === 'member_required') {
        return (
            <div className="stamps-empty">
                {grantBanner}
                <i className="fas fa-id-card" />
                <p>{view.message}</p>
                <p className="stamps-empty-sub">会員登録後にスタンプカードを利用できます</p>
                <button type="button" className="btn btn-primary mt-2" onClick={switchToMemberTab}>
                    <i className="fas fa-user-plus" /> 会員登録する
                </button>
            </div>
        );
    }

    if (view.kind === 'error') {
        return (
            <div className="stamps-error">
                {grantBanner}
                <i className="fas fa-exclamation-circle" />
                <p>{view.message}</p>
                <button type="button" className="btn btn-secondary" onClick={() => void reload()}>
                    <i className="fas fa-redo" /> 再読み込み
                </button>
            </div>
        );
    }

    const { cards, defaultImage } = view;
    const fixed = isFeatureUiShell();
    const rangeOf = (cardId: string) => popRangeFor(
        cardId,
        grant.stampCardId,
        grant.currentStamps,
        grant.grantedCount,
        grant.phase,
    );

    if (cards.length === 0) {
        if (fixed) {
            return (
                <div className="ios-empty">
                    {grantBanner}
                    <div className="ios-empty__icon"><i className="fas fa-stamp" /></div>
                    <h3 className="ios-empty__title">スタンプカードはありません</h3>
                    <p className="ios-empty__text">お店でスタンプを集めましょう</p>
                    {scanControls}
                </div>
            );
        }
        return (
            <div className="stamps-empty">
                {grantBanner}
                <i className="fas fa-stamp" />
                <p>スタンプカードはありません</p>
                <p className="stamps-empty-sub">お店でスタンプを集めましょう</p>
                {scanControls}
            </div>
        );
    }

    if (fixed) {
        return (
            <>
                <div className="ios-panel-head">
                    <h2 className="ios-panel-head__title">スタンプ</h2>
                    <p className="ios-panel-head__sub">来店するたび貯まる</p>
                </div>
                {grantBanner}
                <div className="ios-stamp-stack">
                    {cards.map((card) => {
                        const range = rangeOf(String(card.id ?? ''));
                        return (
                            <FixedStampCard
                                key={String(card.id)}
                                card={card}
                                defaultImage={defaultImage}
                                onOpen={onOpen}
                                newFrom={range?.from}
                                newTo={range?.to}
                            />
                        );
                    })}
                </div>
                {scanControls}
            </>
        );
    }

    const accentStyle = design.accentColor !== ''
        ? { ['--stamp-accent-color' as string]: design.accentColor } as React.CSSProperties
        : undefined;

    return (
        <>
            <div className="stamps-header">
                <h2 className="section-title">
                    <i className="fas fa-stamp" /> スタンプカード
                </h2>
            </div>
            {grantBanner}
            <div className={`stamps-list stamp-screen--${design.screenDesign}`} style={accentStyle}>
                {cards.map((card) => {
                    const range = rangeOf(String(card.id ?? ''));
                    return (
                        <ClassicStampCard
                            key={String(card.id)}
                            card={card}
                            defaultImage={defaultImage}
                            design={design}
                            onOpen={onOpen}
                            newFrom={range?.from}
                            newTo={range?.to}
                        />
                    );
                })}
            </div>
            {scanControls}
        </>
    );
}

import type { JSX } from 'react';
import React, { useCallback, useEffect, useState } from 'react';
import { isFixedUiShell } from '../../demo/demoMode';
import { getMemberRegistrationMode } from '../../utils/membershipPolicy';
import {
    guestRegisterCopy,
    loadMemberView,
    openRegister,
    type MemberLoadResult,
    type MemberPayload,
} from './api';
import { ClassicMemberCard, IosWalletMemberCard } from './MemberCard';
import { MemberRegisterHost } from './MemberRegisterHost';

type ViewState =
    | MemberLoadResult
    | { kind: 'edit' };

function Skeleton(): JSX.Element {
    return (
        <div className="member-card-container">
            <div className="member-card">
                <div className="member-card-header">
                    <div className="skeleton-line skeleton-header" />
                </div>
                <div className="barcode-container">
                    <div className="skeleton-barcode">
                        <div className="skeleton-shimmer" />
                    </div>
                </div>
                <div className="points-display">
                    <div className="skeleton-line skeleton-points-value" />
                    <div className="skeleton-line skeleton-points-label" />
                </div>
            </div>
        </div>
    );
}

function LineOnlyCard({
    title,
    message,
    sub,
    action,
}: {
    title?: string;
    message: string;
    sub?: string;
    action?: { label: string; buttonClass: string; onClick: () => void };
}): JSX.Element {
    return (
        <div className="member-card-container">
            <div className="member-card member-card--line-only">
                <div className="member-card-line-only-body">
                    <p className="member-card-line-only-kicker">{title || 'MEMBER PASS'}</p>
                    <p className="member-card-line-only-message">{message}</p>
                    {sub ? <p className="member-card-line-only-sub">{sub}</p> : null}
                    {action ? (
                        <button
                            type="button"
                            className={`btn ${action.buttonClass} mt-2 member-register-trigger`}
                            data-action="member-open-register"
                            onClick={action.onClick}
                        >
                            {action.label}
                        </button>
                    ) : null}
                </div>
            </div>
        </div>
    );
}

function MemberReady({ data }: { data: MemberPayload }): JSX.Element {
    if (isFixedUiShell()) {
        return <IosWalletMemberCard data={data} />;
    }
    return <ClassicMemberCard data={data} />;
}

export function MemberView(): JSX.Element {
    const [view, setView] = useState<ViewState>({ kind: 'loading' });

    const reload = useCallback(async () => {
        setView({ kind: 'loading' });
        const result = await loadMemberView();
        setView(result);
    }, []);

    useEffect(() => {
        void reload();
    }, [reload]);

    useEffect(() => {
        const onRegister = (event: Event): void => {
            const detail = (event as CustomEvent<{ forced?: boolean }>).detail;
            const forced = detail?.forced === true || getMemberRegistrationMode() === 'force';
            setView({ kind: 'register', forced });
        };
        const onView = (): void => {
            void reload();
        };
        const onEdit = (): void => {
            setView({ kind: 'edit' });
        };
        const onReload = (): void => {
            void reload();
        };
        const onWalletSummary = (event: Event): void => {
            const nextPoints = Number((event as CustomEvent<{ points?: number }>).detail?.points);
            if (!Number.isFinite(nextPoints)) {
                return;
            }
            setView((prev) => {
                if (prev.kind !== 'member' || Number(prev.data.points) === nextPoints) {
                    return prev;
                }
                return { kind: 'member', data: { ...prev.data, points: nextPoints } };
            });
        };
        document.addEventListener('liff-member-open-register', onRegister);
        document.addEventListener('liff-member-open-view', onView);
        document.addEventListener('liff-member-open-edit', onEdit);
        document.addEventListener('liff-member-reload', onReload);
        document.addEventListener('liff-ready', onReload);
        document.addEventListener('liff-wallet-summary-updated', onWalletSummary);
        return () => {
            document.removeEventListener('liff-member-open-register', onRegister);
            document.removeEventListener('liff-member-open-view', onView);
            document.removeEventListener('liff-member-open-edit', onEdit);
            document.removeEventListener('liff-member-reload', onReload);
            document.removeEventListener('liff-ready', onReload);
            document.removeEventListener('liff-wallet-summary-updated', onWalletSummary);
        };
    }, [reload]);

    if (view.kind === 'loading') {
        return <Skeleton />;
    }

    if (view.kind === 'login') {
        return (
            <LineOnlyCard
                message="LINEでログインすると、会員パスをご利用いただけます。"
            />
        );
    }

    if (view.kind === 'guest_nocard') {
        return (
            <LineOnlyCard
                message="会員パスはまだありません"
                sub="発行すると、店舗でポイントやクーポンをご利用いただけます。"
                action={{
                    label: '会員パスを発行',
                    buttonClass: 'btn-primary',
                    onClick: () => openRegister(false),
                }}
            />
        );
    }

    if (view.kind === 'guest_prompt') {
        const copy = guestRegisterCopy();
        return (
            <LineOnlyCard
                message={copy.title}
                sub={copy.subtitle}
                action={{
                    label: copy.buttonLabel,
                    buttonClass: copy.buttonClass,
                    onClick: () => openRegister(false),
                }}
            />
        );
    }

    if (view.kind === 'error') {
        return (
            <div className="member-card-container">
                <div className="member-card member-card--error">
                    <div className="member-card-header">
                        <h2 className="member-card-title"><i className="fas fa-id-card" /> 会員証</h2>
                    </div>
                    <div className="member-card-error-body">
                        <p className="member-card-error-icon"><i className="fas fa-exclamation-circle" /></p>
                        <p className="member-card-error-message">{view.message}</p>
                        <p className="member-card-error-hint">しばらくしてから再度お試しください。</p>
                        <button type="button" className="register-member-btn" onClick={() => void reload()}>
                            <i className="fas fa-redo" /> 再読み込み
                        </button>
                    </div>
                </div>
            </div>
        );
    }

    if (view.kind === 'register') {
        return <MemberRegisterHost mode="register" forced={view.forced} />;
    }

    if (view.kind === 'edit') {
        return <MemberRegisterHost mode="edit" />;
    }

    return <MemberReady data={view.data} />;
}

import type { JSX } from 'react';
import React, { useCallback, useEffect, useState } from 'react';
import { AppConstants } from '../../../shared/constants';
import { ConfirmDialog } from '../../../shared/utils/confirmDialog';
import { ToastManager } from '../../../shared/utils/toast';
import { ApiClient } from '../../../shared/utils/apiClient';
import { isFeatureUiShell } from '../../demo/demoMode';
import {
    canEditReservation,
    dayOfWeekLabel,
    formatRefNumber,
    formatReservedDate,
    formatReservedTime,
    getScreenDesign,
    getStatusClass,
    getStatusIcon,
    getStatusLabel,
    loadReservations,
    primeReservationCache,
    showCreateForm,
    showEditForm,
    switchToMemberTab,
    type Reservation,
} from './api';

type ViewState =
    | { kind: 'loading' }
    | { kind: 'membership' }
    | { kind: 'error'; message: string }
    | { kind: 'empty' }
    | { kind: 'list'; reservations: Reservation[]; isSample: boolean };

function Skeleton(): JSX.Element {
    return (
        <>
            {[0, 1, 2].map((i) => (
                <div key={i} className="reservation-card skeleton">
                    <div className="reservation-card-header">
                        <div className="skeleton-line skeleton-title" />
                        <div className="skeleton-line skeleton-badge" />
                    </div>
                    <div className="reservation-card-body">
                        <div className="skeleton-line skeleton-text" />
                        <div className="skeleton-line skeleton-text" />
                        <div className="skeleton-line skeleton-text-short" />
                    </div>
                </div>
            ))}
        </>
    );
}

function ClassicCard({
    reservation,
    onEdit,
    onCancel,
}: {
    reservation: Reservation;
    onEdit: (r: Reservation) => void;
    onCancel: (id: number) => void;
}): JSX.Element {
    const reservedAt = new Date(reservation.reserved_at);
    const canEdit = canEditReservation(reservation, false);
    const refNumber = formatRefNumber(reservation);
    const screen = getScreenDesign();

    return (
        <div className={`reservation-card reservation-card--screen-${screen}`}>
            <div className="reservation-card-header">
                <span className={`reservation-status-badge ${getStatusClass(reservation.status)}`}>
                    <i className={`fas ${getStatusIcon(reservation.status)}`} />
                    {getStatusLabel(reservation.status)}
                </span>
                {refNumber ? <span className="ref-number" aria-label="予約番号">{refNumber}</span> : null}
            </div>
            <div className="reservation-card-body">
                <div className="reservation-info-item">
                    <div className="reservation-info-icon"><i className="fas fa-calendar-alt" /></div>
                    <div className="reservation-info-content">
                        <div className="reservation-info-label">{AppConstants.RESERVATION_LABELS.DATE_TIME}</div>
                        <div className="reservation-info-value">
                            <div className="reservation-date-display">
                                <span className="reservation-date-main">{formatReservedDate(reservedAt)}</span>
                                <span className="reservation-date-day">({dayOfWeekLabel(reservedAt)})</span>
                            </div>
                            <div className="reservation-time-display">
                                <i className="fas fa-clock" />
                                <span>{formatReservedTime(reservedAt)}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div className="reservation-info-item">
                    <div className="reservation-info-icon"><i className="fas fa-users" /></div>
                    <div className="reservation-info-content">
                        <div className="reservation-info-label">{AppConstants.RESERVATION_LABELS.NUMBER_OF_PEOPLE}</div>
                        <div className="reservation-info-value">
                            <span className="reservation-people-count">{reservation.number_of_people}</span>
                        </div>
                    </div>
                </div>
                {reservation.notes ? (
                    <div className="reservation-info-item">
                        <div className="reservation-info-icon"><i className="fas fa-comment-dots" /></div>
                        <div className="reservation-info-content">
                            <div className="reservation-info-label">{AppConstants.RESERVATION_LABELS.NOTES}</div>
                            <div className="reservation-info-value reservation-notes">{reservation.notes}</div>
                        </div>
                    </div>
                ) : null}
            </div>
            {canEdit ? (
                <div className="reservation-card-footer">
                    <button type="button" className="btn btn-primary btn-edit-reservation" onClick={() => onEdit(reservation)}>
                        <i className="fas fa-edit" /> {AppConstants.RESERVATION_LABELS.EDIT_BUTTON}
                    </button>
                    <button type="button" className="btn btn-danger btn-cancel-reservation" onClick={() => onCancel(reservation.id)}>
                        <i className="fas fa-times-circle" /> {AppConstants.RESERVATION_LABELS.CANCEL_BUTTON}
                    </button>
                </div>
            ) : null}
        </div>
    );
}

function FixedCard({
    reservation,
    onEdit,
    onCancel,
}: {
    reservation: Reservation;
    onEdit: (r: Reservation) => void;
    onCancel: (id: number) => void;
}): JSX.Element {
    const reservedAt = new Date(reservation.reserved_at);
    const canEdit = canEditReservation(reservation, true);
    const refNumber = formatRefNumber(reservation);

    return (
        <article className={`ios-resv${reservation.is_sample_preview === true ? ' ios-resv--sample' : ''}`}>
            <div className="ios-resv__when">
                <div className="ios-resv__date">
                    <span className="ios-resv__month">{reservedAt.getMonth() + 1}月</span>
                    <strong className="ios-resv__day">{reservedAt.getDate()}</strong>
                    <span className="ios-resv__weekday">{dayOfWeekLabel(reservedAt)}</span>
                </div>
                <div className="ios-resv__time">
                    <strong>{formatReservedTime(reservedAt)}</strong>
                    <span>{getStatusLabel(reservation.status)}</span>
                </div>
            </div>
            <div className="ios-resv__details">
                <div className="ios-resv__row"><span>人数</span><strong>{reservation.number_of_people}名</strong></div>
                {refNumber ? (
                    <div className="ios-resv__row"><span>予約番号</span><strong>{refNumber}</strong></div>
                ) : null}
                {reservation.notes ? <div className="ios-resv__notes">{reservation.notes}</div> : null}
            </div>
            {canEdit ? (
                <div className="ios-resv__actions">
                    <button type="button" className="ios-resv__btn" onClick={() => onEdit(reservation)}>変更</button>
                    <button type="button" className="ios-resv__btn ios-resv__btn--danger" onClick={() => onCancel(reservation.id)}>
                        キャンセル
                    </button>
                </div>
            ) : null}
        </article>
    );
}

export function ReservationsView(): JSX.Element {
    const [view, setView] = useState<ViewState>({ kind: 'loading' });
    const fixed = isFeatureUiShell();

    const reload = useCallback(async () => {
        setView({ kind: 'loading' });
        const result = await loadReservations();
        if (result.kind === 'membership') {
            setView({ kind: 'membership' });
            return;
        }
        if (result.kind === 'error') {
            setView({ kind: 'error', message: result.message });
            return;
        }
        const reservations = result.reservations;
        primeReservationCache(reservations);
        if (reservations.length === 0) {
            setView({ kind: 'empty' });
            return;
        }
        setView({
            kind: 'list',
            reservations,
            isSample: result.kind === 'sample',
        });
    }, []);

    useEffect(() => {
        void reload();
    }, [reload]);

    const onCancel = async (id: number): Promise<void> => {
        const confirmed = await ConfirmDialog.show(
            AppConstants.RESERVATION_MESSAGES.CANCEL_CONFIRM_TITLE,
            AppConstants.RESERVATION_MESSAGES.CANCEL_CONFIRM_MESSAGE,
        );
        if (!confirmed) {
            return;
        }
        try {
            const app = window.app as { apiClient?: ApiClient } | undefined;
            const client = app?.apiClient ?? new ApiClient();
            const response = await client.post(`${AppConstants.API_ENDPOINTS.RESERVATIONS}/${id}/cancel`, {});
            if (response.success === true) {
                ToastManager.success(AppConstants.RESERVATION_MESSAGES.CANCEL_SUCCESS);
                await reload();
                return;
            }
            throw new Error(response.error);
        } catch (error) {
            const message = error instanceof Error ? error.message : '';
            ToastManager.error(message || AppConstants.RESERVATION_MESSAGES.CANCEL_ERROR);
        }
    };

    if (view.kind === 'loading') {
        return <Skeleton />;
    }

    if (view.kind === 'membership') {
        return (
            <div className="empty-state">
                <div className="empty-state-icon"><i className="fas fa-id-card" /></div>
                <h3 className="empty-state-title">会員登録が必要です</h3>
                <p className="empty-state-message">
                    予約機能をご利用いただくには、会員登録が必要です。会員登録をすると、予約機能とポイント機能をご利用いただけます。
                </p>
                <button type="button" className="btn btn-primary btn-empty-action" onClick={switchToMemberTab}>
                    <i className="fas fa-user-plus" /> 会員登録へ
                </button>
            </div>
        );
    }

    if (view.kind === 'error') {
        return (
            <div className="error-state">
                <div className="error-state-icon"><i className="fas fa-exclamation-triangle" /></div>
                <h3 className="error-state-title">{AppConstants.RESERVATION_MESSAGES.ERROR_TITLE}</h3>
                <p className="error-message">{view.message}</p>
                <button type="button" className="btn btn-primary btn-error-action" onClick={() => void reload()}>
                    <i className="fas fa-redo" /> {AppConstants.RESERVATION_MESSAGES.RELOAD_BUTTON}
                </button>
            </div>
        );
    }

    if (view.kind === 'empty') {
        return (
            <div className="empty-state">
                <div className="empty-state-icon"><i className="fas fa-calendar-times" /></div>
                <h3 className="empty-state-title">{AppConstants.RESERVATION_MESSAGES.EMPTY_TITLE}</h3>
                <p className="empty-state-message">{AppConstants.RESERVATION_MESSAGES.EMPTY_MESSAGE}</p>
                <button type="button" className="btn btn-primary btn-empty-action" onClick={showCreateForm}>
                    <i className="fas fa-plus" /> {AppConstants.RESERVATION_MESSAGES.EMPTY_BUTTON}
                </button>
            </div>
        );
    }

    if (fixed) {
        return (
            <>
                <div className="ios-panel-head">
                    <h2 className="ios-panel-head__title">予約</h2>
                    <p className="ios-panel-head__sub">来店予定を確認</p>
                </div>
                <div className="ios-resv-create-wrap">
                    <button type="button" className="ios-resv-create" onClick={showCreateForm}>
                        <i className="fas fa-plus" /> 新しい予約
                    </button>
                </div>
                <div className="ios-resv-stack">
                    {view.reservations.map((reservation) => (
                        <FixedCard
                            key={reservation.id}
                            reservation={reservation}
                            onEdit={showEditForm}
                            onCancel={(id) => void onCancel(id)}
                        />
                    ))}
                </div>
            </>
        );
    }

    return (
        <>
            <div className="reservations-header-section">
                <h2 className="section-title">
                    <i className="fas fa-calendar-check" /> {AppConstants.RESERVATION_LABELS.LIST_TITLE}
                </h2>
                <button type="button" className="btn btn-primary btn-create-reservation" onClick={showCreateForm}>
                    <i className="fas fa-plus" /> {AppConstants.RESERVATION_LABELS.CREATE_BUTTON}
                </button>
            </div>
            <div className="reservations-grid">
                {view.reservations.map((reservation) => (
                    <ClassicCard
                        key={reservation.id}
                        reservation={reservation}
                        onEdit={showEditForm}
                        onCancel={(id) => void onCancel(id)}
                    />
                ))}
            </div>
        </>
    );
}

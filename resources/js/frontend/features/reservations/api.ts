import { ApiClient } from '../../../shared/utils/apiClient';
import { AppConstants } from '../../../shared/constants';
import { DomHelper } from '../../../shared/utils/domHelper';
import { isDemoDataMode } from '../../demo/demoMode';
import { buildBuiltinSampleReservations } from '../../demo/sampleData';
import { isMembershipRequired } from '../../utils/membershipPolicy';

export type Reservation = {
    id: number;
    reserved_at: string;
    status: string;
    reservation_number?: string | number | null;
    number_of_people: number;
    notes?: string | null;
    is_sample_preview?: boolean;
};

export type ReservationsLoadResult =
    | { kind: 'sample'; reservations: Reservation[] }
    | { kind: 'ok'; reservations: Reservation[] }
    | { kind: 'membership' }
    | { kind: 'error'; message: string };

type ReservationManagerLike = {
    reservations?: Reservation[];
    showCreateForm?: () => void | Promise<void>;
    showEditForm?: (reservation: Reservation | null) => void | Promise<void>;
    cancelReservation?: (id: number) => void | Promise<void>;
    getReservationById?: (id: number) => Reservation | null;
};

function client(): ApiClient {
    const app = window.app as { apiClient?: ApiClient } | undefined;
    return app?.apiClient ?? new ApiClient();
}

function reservationManager(): ReservationManagerLike | undefined {
    return (window.app as { reservationManager?: ReservationManagerLike } | undefined)?.reservationManager;
}

function memberManager(): {
    hasAuthenticatedIdentity?: () => boolean;
    resolveLineUserIdForClient?: () => Promise<unknown>;
    setMemberData?: (data: unknown) => void;
} | undefined {
    return (window.app as {
        memberManager?: {
            hasAuthenticatedIdentity?: () => boolean;
            resolveLineUserIdForClient?: () => Promise<unknown>;
            setMemberData?: (data: unknown) => void;
        };
    } | undefined)?.memberManager;
}

export function getSampleReservations(): Reservation[] {
    const app = window.app as { getDesignerSampleReservations?: () => Reservation[] } | undefined;
    const list = typeof app?.getDesignerSampleReservations === 'function'
        ? app.getDesignerSampleReservations()
        : buildBuiltinSampleReservations();
    return list.map((item) => ({ ...item, is_sample_preview: true }));
}

export function primeReservationCache(reservations: Reservation[]): void {
    const mgr = reservationManager();
    if (mgr !== undefined) {
        mgr.reservations = reservations;
    }
}

export async function loadReservations(): Promise<ReservationsLoadResult> {
    if (isDemoDataMode()) {
        return { kind: 'sample', reservations: getSampleReservations() };
    }

    const mm = memberManager();
    if (typeof window !== 'undefined' && !window.__LINE_USER_ID__ && mm !== undefined) {
        if (typeof mm.resolveLineUserIdForClient === 'function') {
            await mm.resolveLineUserIdForClient();
        }
    }
    const hasIdentity = mm !== undefined && typeof mm.hasAuthenticatedIdentity === 'function'
        ? mm.hasAuthenticatedIdentity() === true
        : (typeof window !== 'undefined' && !!window.__LINE_USER_ID__);
    if (!hasIdentity) {
        return { kind: 'sample', reservations: getSampleReservations() };
    }

    try {
        const memberResult = await client().get(AppConstants.API_ENDPOINTS.MEMBERS_ME);
        if (memberResult.success === true) {
            const memberPayload = memberResult.data as { is_member?: boolean } | null | undefined;
            if (memberPayload?.is_member === true && mm !== undefined && typeof mm.setMemberData === 'function') {
                mm.setMemberData(memberPayload);
            } else if (memberPayload?.is_member !== true && isMembershipRequired()) {
                return { kind: 'sample', reservations: getSampleReservations() };
            }
        } else if (isMembershipRequired()) {
            return { kind: 'sample', reservations: getSampleReservations() };
        }
    } catch {
        if (isMembershipRequired()) {
            return { kind: 'sample', reservations: getSampleReservations() };
        }
    }

    try {
        const response = await client().get(AppConstants.API_ENDPOINTS.RESERVATIONS);
        if (response.success === true) {
            const data = response.data as { reservations?: Reservation[] } | undefined;
            return { kind: 'ok', reservations: (data && data.reservations) || [] };
        }
        const data = (response as { data?: { requires_membership?: boolean } }).data;
        if (data && data.requires_membership === true) {
            if (!isMembershipRequired()) {
                return { kind: 'sample', reservations: getSampleReservations() };
            }
            return { kind: 'membership' };
        }
        return { kind: 'error', message: response.error || AppConstants.RESERVATION_MESSAGES.ERROR_DEFAULT };
    } catch (error) {
        const message = error instanceof Error ? error.message : '';
        if (message.includes('会員登録が必要')) {
            if (!isMembershipRequired()) {
                return { kind: 'sample', reservations: getSampleReservations() };
            }
            return { kind: 'membership' };
        }
        return { kind: 'error', message: message || AppConstants.RESERVATION_MESSAGES.ERROR_DEFAULT };
    }
}

export function getScreenDesign(): string {
    return DomHelper.getLiffGlobalSetting('reservation_screen_design', 'cards');
}

export function getStatusIcon(status: string): string {
    const iconMap: Record<string, string> = {
        [AppConstants.RESERVATION_STATUS.PENDING]: AppConstants.RESERVATION_STATUS_ICONS.PENDING,
        [AppConstants.RESERVATION_STATUS.CONFIRMED]: AppConstants.RESERVATION_STATUS_ICONS.CONFIRMED,
        [AppConstants.RESERVATION_STATUS.CANCELLED]: AppConstants.RESERVATION_STATUS_ICONS.CANCELLED,
        [AppConstants.RESERVATION_STATUS.COMPLETED]: AppConstants.RESERVATION_STATUS_ICONS.COMPLETED,
    };
    return iconMap[status] || AppConstants.RESERVATION_STATUS_ICONS.DEFAULT;
}

export function getStatusClass(status: string): string {
    const classMap: Record<string, string> = {
        [AppConstants.RESERVATION_STATUS.PENDING]: AppConstants.RESERVATION_STATUS_CLASSES.PENDING,
        [AppConstants.RESERVATION_STATUS.CONFIRMED]: AppConstants.RESERVATION_STATUS_CLASSES.CONFIRMED,
        [AppConstants.RESERVATION_STATUS.CANCELLED]: AppConstants.RESERVATION_STATUS_CLASSES.CANCELLED,
        [AppConstants.RESERVATION_STATUS.COMPLETED]: AppConstants.RESERVATION_STATUS_CLASSES.COMPLETED,
    };
    return classMap[status] || AppConstants.RESERVATION_STATUS_CLASSES.DEFAULT;
}

export function getStatusLabel(status: string): string {
    const labelMap: Record<string, string> = {
        [AppConstants.RESERVATION_STATUS.PENDING]: AppConstants.RESERVATION_STATUS_LABELS.PENDING,
        [AppConstants.RESERVATION_STATUS.CONFIRMED]: AppConstants.RESERVATION_STATUS_LABELS.CONFIRMED,
        [AppConstants.RESERVATION_STATUS.CANCELLED]: AppConstants.RESERVATION_STATUS_LABELS.CANCELLED,
        [AppConstants.RESERVATION_STATUS.COMPLETED]: AppConstants.RESERVATION_STATUS_LABELS.COMPLETED,
    };
    return labelMap[status] || status;
}

export function canEditReservation(reservation: Reservation, fixedUi: boolean): boolean {
    if (fixedUi && reservation.is_sample_preview === true) {
        return false;
    }
    return reservation.status !== AppConstants.RESERVATION_STATUS.CANCELLED
        && reservation.status !== AppConstants.RESERVATION_STATUS.COMPLETED;
}

export function showCreateForm(): void {
    const mgr = reservationManager();
    if (typeof mgr?.showCreateForm === 'function') {
        void mgr.showCreateForm();
    }
}

export function showEditForm(reservation: Reservation): void {
    const mgr = reservationManager();
    if (typeof mgr?.showEditForm === 'function') {
        void mgr.showEditForm(reservation);
    }
}

export function switchToMemberTab(): void {
    if (typeof window.app?.switchTab === 'function') {
        window.app.switchTab(AppConstants.TABS.MEMBER);
    }
}

export function formatReservedDate(reservedAt: Date): string {
    return reservedAt.toLocaleDateString(
        AppConstants.DATE_FORMAT.LOCALE,
        AppConstants.DATE_FORMAT.DATE_OPTIONS as Intl.DateTimeFormatOptions,
    );
}

export function formatReservedTime(reservedAt: Date): string {
    return reservedAt.toLocaleTimeString(
        AppConstants.DATE_FORMAT.LOCALE,
        AppConstants.DATE_FORMAT.TIME_OPTIONS as Intl.DateTimeFormatOptions,
    );
}

export function dayOfWeekLabel(reservedAt: Date): string {
    return ['日', '月', '火', '水', '木', '金', '土'][reservedAt.getDay()];
}

export function formatRefNumber(reservation: Reservation): string | null {
    return DomHelper.formatReferenceNumber(reservation.reservation_number, reservation.id);
}

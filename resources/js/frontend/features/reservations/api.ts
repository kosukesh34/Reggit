import { AppConstants } from '../../../shared/constants';

export type Reservation = {
    id: number;
    reserved_at: string;
    status: string;
    reservation_number?: string | number | null;
    number_of_people: number;
    notes?: string | null;
    reservation_course_id?: number | null;
    course_name?: string | null;
    /** 会員が変更・キャンセルできる最後の時刻と、いまできるか */
    change_deadline?: string | null;
    can_change?: boolean;
    is_sample_preview?: boolean;
};

export function getStatusLabel(status: string): string {
    const labelMap: Record<string, string> = {
        [AppConstants.RESERVATION_STATUS.PENDING]: AppConstants.RESERVATION_STATUS_LABELS.PENDING,
        [AppConstants.RESERVATION_STATUS.CONFIRMED]: AppConstants.RESERVATION_STATUS_LABELS.CONFIRMED,
        [AppConstants.RESERVATION_STATUS.CANCELLED]: AppConstants.RESERVATION_STATUS_LABELS.CANCELLED,
        [AppConstants.RESERVATION_STATUS.COMPLETED]: AppConstants.RESERVATION_STATUS_LABELS.COMPLETED,
    };
    return labelMap[status] || status;
}

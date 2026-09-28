import { AppConstants } from '../../shared/constants';
import { ApiClient } from '../../shared/utils/apiClient';
import { DomHelper } from '../../shared/utils/domHelper';
import { ConfirmDialog } from '../../shared/utils/confirmDialog';
import { ToastManager } from '../../shared/utils/toast';
import { isDemoDataMode, isFixedUiShell } from '../demo/demoMode';
import { buildBuiltinSampleReservations } from '../demo/sampleData';
import { isMembershipRequired } from '../utils/membershipPolicy';
import { isReactOwnedMount } from '../utils/reactOwnedMount';

const DEFAULT_ADVANCE_DAYS = 30;

type Reservation = {
    id: number;
    reserved_at: string;
    status: string;
    reservation_number?: string | number | null;
    number_of_people: number;
    notes?: string | null;
    reservation_course_id?: number | null;
    course_name?: string | null;
    course_price?: number | null;
    is_sample_preview?: boolean;
};

type AvailableDateInfo = {
    date: string;
    display?: string;
    time_slots?: string[];
};

type ReservationCourse = { id: number; name: string; price: number; description?: string | null };

export class ReservationManager {
    app: any;
    reservations: Reservation[];
    availableDates: AvailableDateInfo[];
    courses: ReservationCourse[];
    selectedDate: string | null;
    selectedTime: string | null;
    availableDateMap: Map<string, AvailableDateInfo>;
    calendarStartDate: Date | null;
    calendarEndDate: Date | null;
    currentCalendarMonth: Date | null;
    currentModal: HTMLElement | null;
    editingReservationId: number | null;
    apiClient: ApiClient;
    reservationsSection: Element | null;
    private pageMode = false;
    private pendingPeople = 1;
    private pendingNotes: string | null = null;
    private pendingCourseId: number | null = null;
    private lastCompletedLabel: string | null = null;

    constructor(appInstance: any) {
        this.app = appInstance;
        this.reservations = [];
        this.availableDates = [];
        this.courses = [];
        this.selectedDate = null;
        this.selectedTime = null;
        this.availableDateMap = new Map();
        this.calendarStartDate = null;
        this.calendarEndDate = null;
        this.currentCalendarMonth = null;
        this.currentModal = null;
        this.editingReservationId = null;
        this.apiClient = new ApiClient();
        this.reservationsSection = null;
    }

    getGlobalSetting(key: string, fallback: string): string {
        return DomHelper.getLiffGlobalSetting(key, fallback);
    }

    _getSection(): Element | null {
        return DomHelper.getTabMount(
            AppConstants.TAB_MOUNTS.RESERVATIONS,
            AppConstants.ELEMENT_IDS.RESERVATIONS_SECTION,
            { silent: true },
        );
    }

    getReservationById(id: number): Reservation | null {
        return this.reservations.find((r) => r.id === id) || null;
    }

    async loadReservations(): Promise<void> {
        this.reservationsSection = this._getSection();
        if (!this.reservationsSection) return;
        if (isReactOwnedMount(this.reservationsSection)) {
            return;
        }

        if (isDemoDataMode()) {
            this.renderSampleReservations();
            return;
        }

        this.reservationsSection.innerHTML = this.getReservationSkeleton();

        try {
            const response = await this.apiClient.get(AppConstants.API_ENDPOINTS.RESERVATIONS);
            if (response.success === true) {
                const data = response.data as { reservations?: Reservation[] } | undefined;
                this.reservations = (data && data.reservations) || [];
                this.renderReservations();
            } else {
                const data = (response as { data?: { requires_membership?: boolean } }).data;
                if (data && data.requires_membership === true) {
                    if (isDemoDataMode() || !isMembershipRequired()) {
                        this.renderSampleReservations();
                        return;
                    }
                    this.renderMembershipRequired();
                    return;
                }
                throw new Error(response.error);
            }
        } catch (error) {

            console.error(AppConstants.RESERVATION_MESSAGES.LOAD_ERROR, error);
            if (this.reservationsSection) {
                const message = error instanceof Error ? error.message : '';
                if (message && message.includes('会員登録が必要')) {
                    if (isDemoDataMode() || !isMembershipRequired()) {
                        this.renderSampleReservations();
                    } else {
                        this.renderMembershipRequired();
                    }
                } else {
                    this.reservationsSection.innerHTML = this.getErrorTemplate(error);
                }
            }
        }
    }

    renderMembershipRequired(): void {
        this.reservationsSection = this._getSection();
        if (!this.reservationsSection) return;
        if (isReactOwnedMount(this.reservationsSection)) {
            return;
        }
        this.reservationsSection.innerHTML = this.getMembershipRequiredTemplate();
    }

    getDesignerSampleReservations(): Reservation[] {
        if (typeof window !== 'undefined'
            && window.app
            && typeof (window.app as { getDesignerSampleReservations?: () => Reservation[] }).getDesignerSampleReservations === 'function') {
            return (window.app as { getDesignerSampleReservations: () => Reservation[] }).getDesignerSampleReservations();
        }
        return buildBuiltinSampleReservations();
    }

    /** 固定UIデモ用サンプル予約を描画 */
    renderSampleReservations(): void {
        this.reservations = this.getDesignerSampleReservations().map((item) => ({
            ...item,
            is_sample_preview: true,
        }));
        this.renderReservations(true);
    }

    renderReservations(isSample = false): void {
        this.reservationsSection = this._getSection();
        if (isReactOwnedMount(this.reservationsSection)) {
            return;
        }
        if (isFixedUiShell()) {
            this._renderFixedUiReservations(isSample);
            return;
        }
        this._renderClassicReservations();
    }

    getReservationCardHtml(reservation: Reservation): string {
        if (isFixedUiShell()) {
            return this._getFixedUiReservationCardHtml(reservation);
        }
        return this._getClassicReservationCardHtml(reservation);
    }

    _renderClassicReservations(): void {
        this.reservationsSection = this._getSection();
        if (!this.reservationsSection) return;

        if (this.reservations.length === 0) {
            this.reservationsSection.innerHTML = this.getEmptyTemplate();
            return;
        }

        const reservationsHtml = this.reservations.map((reservation) => this.getReservationCardHtml(reservation)).join('');

        this.reservationsSection.innerHTML = `
            <div class="reservations-header-section">
                <h2 class="section-title">
                    <i class="fas fa-calendar-check"></i> ${AppConstants.RESERVATION_LABELS.LIST_TITLE}
                </h2>
                <button class="btn btn-primary btn-create-reservation" onclick="app.reservationManager.showCreateForm()">
                    <i class="fas fa-plus"></i> ${AppConstants.RESERVATION_LABELS.CREATE_BUTTON}
                </button>
            </div>
            <div class="reservations-grid">
                ${reservationsHtml}
            </div>
        `;
    }

    _getClassicReservationCardHtml(reservation: Reservation): string {
        const reservedAt = new Date(reservation.reserved_at);
        const dateStr = reservedAt.toLocaleDateString(AppConstants.DATE_FORMAT.LOCALE, AppConstants.DATE_FORMAT.DATE_OPTIONS as Intl.DateTimeFormatOptions);
        const timeStr = reservedAt.toLocaleTimeString(AppConstants.DATE_FORMAT.LOCALE, AppConstants.DATE_FORMAT.TIME_OPTIONS as Intl.DateTimeFormatOptions);
        const statusClass = this.getStatusClass(reservation.status);
        const statusLabel = this.getStatusLabel(reservation.status);
        const canEdit = reservation.status !== AppConstants.RESERVATION_STATUS.CANCELLED
            && reservation.status !== AppConstants.RESERVATION_STATUS.COMPLETED;
        const dayOfWeek = ['日', '月', '火', '水', '木', '金', '土'][reservedAt.getDay()];
        const refNumber = DomHelper.formatReferenceNumber(reservation.reservation_number, reservation.id);

        return `
            <div class="reservation-card reservation-card--screen-${DomHelper.escapeHtml(this.getGlobalSetting('reservation_screen_design', 'cards'))}">
                <div class="reservation-card-header">
                    <span class="reservation-status-badge ${statusClass}">
                        <i class="fas ${this.getStatusIcon(reservation.status)}"></i>
                        ${statusLabel}
                    </span>
                    ${refNumber ? `<span class="ref-number" aria-label="予約番号">${DomHelper.escapeHtml(refNumber)}</span>` : ''}
                </div>
                <div class="reservation-card-body">
                    <div class="reservation-info-item">
                        <div class="reservation-info-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="reservation-info-content">
                            <div class="reservation-info-label">${AppConstants.RESERVATION_LABELS.DATE_TIME}</div>
                            <div class="reservation-info-value">
                                <div class="reservation-date-display">
                                    <span class="reservation-date-main">${dateStr}</span>
                                    <span class="reservation-date-day">(${dayOfWeek})</span>
                                </div>
                                <div class="reservation-time-display">
                                    <i class="fas fa-clock"></i>
                                    <span>${timeStr}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="reservation-info-item">
                        <div class="reservation-info-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="reservation-info-content">
                            <div class="reservation-info-label">${AppConstants.RESERVATION_LABELS.NUMBER_OF_PEOPLE}</div>
                            <div class="reservation-info-value">
                                <span class="reservation-people-count">${reservation.number_of_people}</span>
                            </div>
                        </div>
                    </div>
                    ${reservation.notes ? `
                        <div class="reservation-info-item">
                            <div class="reservation-info-icon">
                                <i class="fas fa-comment-dots"></i>
                            </div>
                            <div class="reservation-info-content">
                                <div class="reservation-info-label">${AppConstants.RESERVATION_LABELS.NOTES}</div>
                                <div class="reservation-info-value reservation-notes">${DomHelper.escapeHtml(reservation.notes)}</div>
                            </div>
                        </div>
                    ` : ''}
                </div>
                ${canEdit ? `
                    <div class="reservation-card-footer">
                        <button class="btn btn-primary btn-edit-reservation" onclick="app.reservationManager.showEditForm(app.reservationManager.getReservationById(${reservation.id}))">
                            <i class="fas fa-edit"></i> ${AppConstants.RESERVATION_LABELS.EDIT_BUTTON}
                        </button>
                        <button class="btn btn-danger btn-cancel-reservation" onclick="app.reservationManager.cancelReservation(${reservation.id})">
                            <i class="fas fa-times-circle"></i> ${AppConstants.RESERVATION_LABELS.CANCEL_BUTTON}
                        </button>
                    </div>
                ` : ''}
            </div>
        `;
    }

    _renderFixedUiReservations(isSample = false): void {
        this.reservationsSection = this._getSection();
        if (!this.reservationsSection) return;

        if (this.reservations.length === 0) {
            this.reservationsSection.innerHTML = this.getEmptyTemplate();
            return;
        }

        const reservationsHtml = this.reservations.map((reservation) => this._getFixedUiReservationCardHtml(reservation)).join('');

        this.reservationsSection.innerHTML = `
            <div class="ios-panel-head">
                <h2 class="ios-panel-head__title">予約</h2>
                <p class="ios-panel-head__sub">来店予定を確認</p>
            </div>
            <div class="ios-resv-create-wrap">
                <button class="ios-resv-create" onclick="app.reservationManager.showCreateForm()">
                    <i class="fas fa-plus"></i> 新しい予約
                </button>
            </div>
            <div class="ios-resv-stack">
                ${reservationsHtml}
            </div>
        `;
    }

    _getFixedUiReservationCardHtml(reservation: Reservation): string {
        const reservedAt = new Date(reservation.reserved_at);
        const timeStr = reservedAt.toLocaleTimeString(AppConstants.DATE_FORMAT.LOCALE, AppConstants.DATE_FORMAT.TIME_OPTIONS as Intl.DateTimeFormatOptions);
        const statusLabel = this.getStatusLabel(reservation.status);
        const canEdit = reservation.is_sample_preview !== true
            && reservation.status !== AppConstants.RESERVATION_STATUS.CANCELLED
            && reservation.status !== AppConstants.RESERVATION_STATUS.COMPLETED;
        const dayOfWeek = ['日', '月', '火', '水', '木', '金', '土'][reservedAt.getDay()];
        const refNumber = DomHelper.formatReferenceNumber(reservation.reservation_number, reservation.id);

        return `
            <article class="ios-resv${reservation.is_sample_preview === true ? ' ios-resv--sample' : ''}">
                <div class="ios-resv__when">
                    <div class="ios-resv__date">
                        <span class="ios-resv__month">${reservedAt.getMonth() + 1}月</span>
                        <strong class="ios-resv__day">${reservedAt.getDate()}</strong>
                        <span class="ios-resv__weekday">${dayOfWeek}</span>
                    </div>
                    <div class="ios-resv__time">
                        <strong>${timeStr}</strong>
                        <span>${DomHelper.escapeHtml(statusLabel)}</span>
                    </div>
                </div>
                <div class="ios-resv__details">
                    <div class="ios-resv__row"><span>人数</span><strong>${reservation.number_of_people}名</strong></div>
                    ${refNumber ? `<div class="ios-resv__row"><span>予約番号</span><strong>${DomHelper.escapeHtml(refNumber)}</strong></div>` : ''}
                    ${reservation.notes ? `<div class="ios-resv__notes">${DomHelper.escapeHtml(reservation.notes)}</div>` : ''}
                </div>
                ${canEdit ? `
                    <div class="ios-resv__actions">
                        <button class="ios-resv__btn" onclick="app.reservationManager.showEditForm(app.reservationManager.getReservationById(${reservation.id}))">変更</button>
                        <button class="ios-resv__btn ios-resv__btn--danger" onclick="app.reservationManager.cancelReservation(${reservation.id})">キャンセル</button>
                    </div>
                ` : ''}
            </article>
        `;
    }

    getStatusIcon(status: string): string {
        const iconMap: Record<string, string> = {
            [AppConstants.RESERVATION_STATUS.PENDING]: AppConstants.RESERVATION_STATUS_ICONS.PENDING,
            [AppConstants.RESERVATION_STATUS.CONFIRMED]: AppConstants.RESERVATION_STATUS_ICONS.CONFIRMED,
            [AppConstants.RESERVATION_STATUS.CANCELLED]: AppConstants.RESERVATION_STATUS_ICONS.CANCELLED,
            [AppConstants.RESERVATION_STATUS.COMPLETED]: AppConstants.RESERVATION_STATUS_ICONS.COMPLETED,
        };
        return iconMap[status] || AppConstants.RESERVATION_STATUS_ICONS.DEFAULT;
    }

    getStatusClass(status: string): string {
        const classMap: Record<string, string> = {
            [AppConstants.RESERVATION_STATUS.PENDING]: AppConstants.RESERVATION_STATUS_CLASSES.PENDING,
            [AppConstants.RESERVATION_STATUS.CONFIRMED]: AppConstants.RESERVATION_STATUS_CLASSES.CONFIRMED,
            [AppConstants.RESERVATION_STATUS.CANCELLED]: AppConstants.RESERVATION_STATUS_CLASSES.CANCELLED,
            [AppConstants.RESERVATION_STATUS.COMPLETED]: AppConstants.RESERVATION_STATUS_CLASSES.COMPLETED,
        };
        return classMap[status] || AppConstants.RESERVATION_STATUS_CLASSES.DEFAULT;
    }

    getStatusLabel(status: string): string {
        const labelMap: Record<string, string> = {
            [AppConstants.RESERVATION_STATUS.PENDING]: AppConstants.RESERVATION_STATUS_LABELS.PENDING,
            [AppConstants.RESERVATION_STATUS.CONFIRMED]: AppConstants.RESERVATION_STATUS_LABELS.CONFIRMED,
            [AppConstants.RESERVATION_STATUS.CANCELLED]: AppConstants.RESERVATION_STATUS_LABELS.CANCELLED,
            [AppConstants.RESERVATION_STATUS.COMPLETED]: AppConstants.RESERVATION_STATUS_LABELS.COMPLETED,
        };
        return labelMap[status] || status;
    }

    async cancelReservation(reservationId: number): Promise<void> {
        const confirmed = await ConfirmDialog.show(
            AppConstants.RESERVATION_MESSAGES.CANCEL_CONFIRM_TITLE,
            AppConstants.RESERVATION_MESSAGES.CANCEL_CONFIRM_MESSAGE,
        );
        if (!confirmed) return;

        try {
            const response = await this.apiClient.post(
                `${AppConstants.API_ENDPOINTS.RESERVATIONS}/${reservationId}/cancel`,
                {},
            );
            if (response.success === true) {
                ToastManager.success(AppConstants.RESERVATION_MESSAGES.CANCEL_SUCCESS);
                await this.loadReservations();
            } else {
                throw new Error(response.error);
            }
        } catch (error) {
            const message = error instanceof Error ? error.message : '';
            ToastManager.error(message || AppConstants.RESERVATION_MESSAGES.CANCEL_ERROR);
        }
    }

    async showCreateForm(): Promise<void> {
        await this.openFlowStep('new', { resetSelection: true });
    }

    async openFlowStep(
        step: 'new' | 'confirm' | 'complete',
        options: { fromHash?: boolean; resetSelection?: boolean } = {},
    ): Promise<void> {
        const router = this.app && this.app.pageRouter ? this.app.pageRouter : null;
        if (router === null) {
            return;
        }
        const keyMap = {
            new: 'reservation_new',
            confirm: 'reservation_confirm',
            complete: 'reservation_complete',
        } as const;
        if (step === 'confirm' && (this.selectedDate === null || this.selectedTime === null)) {
            await this.openFlowStep('new', { resetSelection: true });
            return;
        }
        this.pageMode = true;
        const section = router.openPage({
            key: keyMap[step],
            hash: 'reservation/' + step,
            returnHash: router.resolveReturnHash('reservations'),
            title: step === 'new' ? '予約' : (step === 'confirm' ? '予約確認' : '予約完了'),
            bodyClass: 'liff-page-reservation-flow',
        });
        if (section === null) {
            return;
        }
        if (step === 'new') {
            if (options.resetSelection === true || options.fromHash === true) {
                this.selectedDate = null;
                this.selectedTime = null;
                this.pendingPeople = AppConstants.RESERVATION_VALIDATION.MIN_PEOPLE;
                this.pendingNotes = null;
                this.pendingCourseId = null;
            }
            await this.loadAvailableDates();
            section.innerHTML = `<div class="liff-flow-page reservation-flow-new">${this.getCreateFormHtml()}</div>`;
            this.currentModal = section;
            this.attachFormListeners(section);
            this.renderCalendar(section);
            this.restoreNewFormSelection();
            return;
        }
        if (step === 'confirm') {
            const dateLabel = this.selectedDate || '';
            const timeLabel = this.selectedTime || '';
            section.innerHTML = `
                <div class="liff-flow-page reservation-flow-confirm">
                    <h2>予約内容の確認</h2>
                    <p class="liff-entity-detail__meta">日時: ${DomHelper.escapeHtml(dateLabel)} ${DomHelper.escapeHtml(timeLabel)}</p>
                    <p class="liff-entity-detail__meta">人数: ${this.pendingPeople}名</p>
                    ${this.pendingCourseId ? `<p class="liff-entity-detail__meta">コース: ${DomHelper.escapeHtml(this.courses.find((course) => course.id === this.pendingCourseId)?.name || '')}</p>` : ''}
                    ${this.pendingNotes ? `<p class="liff-entity-detail__body">${DomHelper.escapeHtml(this.pendingNotes)}</p>` : ''}
                    <div class="liff-flow-actions">
                        <button type="button" class="btn btn-primary" id="reservationConfirmSubmit">
                            <i class="fas fa-check"></i> この内容で予約する
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="app.reservationManager.openFlowStep('new')">内容を修正</button>
                    </div>
                </div>
            `;
            const submitBtn = section.querySelector('#reservationConfirmSubmit');
            if (submitBtn !== null) {
                submitBtn.addEventListener('click', () => {
                    void this.submitReservationFromDraft();
                });
            }
            return;
        }
        section.innerHTML = `
            <div class="liff-flow-page reservation-flow-complete">
                <div class="reservation-flow-complete__icon"><i class="fas fa-check-circle"></i></div>
                <h2>予約が完了しました</h2>
                <p>${DomHelper.escapeHtml(this.lastCompletedLabel || 'ご予約ありがとうございます')}</p>
                <div class="liff-flow-actions">
                    <button type="button" class="btn btn-primary" onclick="app.switchTab('reservations')">予約一覧へ</button>
                    <button type="button" class="btn btn-secondary" onclick="app.switchTab('home')">ホームへ</button>
                </div>
            </div>
        `;
    }

    async submitReservationFromDraft(): Promise<void> {
        if (!this.selectedDate || !this.selectedTime) {
            ToastManager.error('日付と時間を選択してください');
            return;
        }
        const data = {
            reserved_at: `${this.selectedDate} ${this.selectedTime}:00`,
            number_of_people: this.pendingPeople,
            notes: this.pendingNotes,
            reservation_course_id: this.pendingCourseId,
        };
        try {
            const response = await this.apiClient.post(AppConstants.API_ENDPOINTS.RESERVATIONS, data);
            if (response.success === true) {
                ToastManager.success(AppConstants.RESERVATION_MESSAGES.CREATE_SUCCESS);
                this.lastCompletedLabel = `${this.selectedDate} ${this.selectedTime}（${this.pendingPeople}名）`;
                this.selectedDate = null;
                this.selectedTime = null;
                this.pendingNotes = null;
                this.pendingCourseId = null;
                await this.loadReservations();
                await this.openFlowStep('complete');
            } else {
                throw new Error(response.error);
            }
        } catch (error) {
            const message = error instanceof Error ? error.message : '';
            if (message && message.includes('会員登録が必要')) {
                ToastManager.error('会員登録が必要です');
                if (this.app && this.app.switchTab) this.app.switchTab(AppConstants.TABS.MEMBER);
            } else {
                ToastManager.error(message || AppConstants.RESERVATION_MESSAGES.CREATE_ERROR);
            }
        }
    }

    async showEditForm(reservation: Reservation | null): Promise<void> {
        if (!reservation) return;
        this.editingReservationId = reservation.id;
        const reservedAt = new Date(reservation.reserved_at);
        this.selectedDate = this.formatDateKey(reservedAt);
        this.selectedTime = this.formatTimeForSlot(reservedAt);
        await this.loadAvailableDates(reservation.id);

        const modal = document.createElement('div');
        modal.className = 'modal-overlay';
        modal.dataset.editId = String(reservation.id);
        modal.innerHTML = this.getEditFormHtml(reservation);
        document.body.appendChild(modal);
        this.currentModal = modal;
        this.attachFormListeners(modal);
        this.setInitialCalendarMonth();
        this.renderCalendar(modal);
        const dateInfo = this.selectedDate ? this.availableDateMap.get(this.selectedDate) : null;
        if (dateInfo && Array.isArray(dateInfo.time_slots) && dateInfo.time_slots.length > 0) {
            this.renderTimeSelector(dateInfo.time_slots, '空きがありません');
            this.setTimeSectionDisabled(false);
        } else {
            this.renderTimeSelector([], '空きがありません');
            this.setTimeSectionDisabled(true);
        }
        this.updateSelectedDateLabel();
        this.updateSubmitButton();
        this.editingReservationId = null;
    }

    formatTimeForSlot(dateObj: Date): string {
        const hours = dateObj.getHours();
        const minutes = dateObj.getMinutes();
        return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
    }

    getPeopleStepperHtml(value: number): string {
        const min = AppConstants.RESERVATION_VALIDATION.MIN_PEOPLE;
        const max = AppConstants.RESERVATION_VALIDATION.MAX_PEOPLE;
        const v = Math.min(Math.max(Number(value) || min, min), max);
        return `
            <div class="people-stepper" data-people-stepper data-min="${min}" data-max="${max}">
                <button type="button" class="people-stepper-btn" data-people="-1" aria-label="減らす"><i class="fas fa-minus"></i></button>
                <div class="people-stepper-display">
                    <span class="people-stepper-value" data-people-value>${v}</span>
                    <span class="people-stepper-unit">名</span>
                </div>
                <button type="button" class="people-stepper-btn" data-people="1" aria-label="増やす"><i class="fas fa-plus"></i></button>
                <input type="hidden" name="number_of_people" value="${v}">
            </div>
        `;
    }

    adjustPeople(modal: HTMLElement, delta: number): void {
        const stepper = modal ? modal.querySelector('[data-people-stepper]') as HTMLElement | null : null;
        if (!stepper) return;
        const min = parseInt(stepper.dataset.min || '1', 10) || 1;
        const max = parseInt(stepper.dataset.max || '99', 10) || 99;
        const input = stepper.querySelector('input[name="number_of_people"]') as HTMLInputElement | null;
        const valueEl = stepper.querySelector('[data-people-value]');
        if (!input) return;
        const current = parseInt(input.value, 10) || min;
        const next = Math.min(Math.max(current + delta, min), max);
        input.value = String(next);
        if (valueEl) valueEl.textContent = String(next);
        stepper.querySelectorAll('.people-stepper-btn').forEach((btnEl) => {
            const btn = btnEl as HTMLButtonElement;
            const d = parseInt(btn.dataset.people || '0', 10);
            btn.disabled = (d < 0 && next <= min) || (d > 0 && next >= max);
        });
    }

    getEditFormHtml(reservation: Reservation): string {
        const numPeople = Math.min(
            Math.max(Number(reservation.number_of_people) || 1, AppConstants.RESERVATION_VALIDATION.MIN_PEOPLE),
            AppConstants.RESERVATION_VALIDATION.MAX_PEOPLE,
        );
        const notes = (reservation.notes !== null && reservation.notes !== undefined && reservation.notes !== '')
            ? DomHelper.escapeHtml(reservation.notes)
            : '';
        return `
            <div class="modal-content reservation-modal-content">
                <div class="modal-header">
                    <h3><i class="fas fa-edit"></i> ${AppConstants.RESERVATION_LABELS.EDIT_TITLE}</h3>
                    <button type="button" class="close-btn" data-action="close-modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form id="reservationEditForm" class="reservation-form" data-edit-id="${reservation.id}">
                    <div class="form-section">
                        <div class="form-section-header">
                            <div class="step-label">
                                <span class="step-number">1</span>
                                <span class="step-title">日付を選択</span>
                            </div>
                            <div class="step-hint"><i class="fas fa-check"></i> 予約可 / <i class="fas fa-times"></i> 予約不可</div>
                        </div>
                        <div id="dateSelector" class="date-selector">
                            <div class="date-selector-loading">
                                <i class="fas fa-spinner fa-spin"></i> 読み込み中...
                            </div>
                        </div>
                    </div>
                    <div class="form-section" id="timeSection">
                        <div class="form-section-header">
                            <div class="step-label">
                                <span class="step-number">2</span>
                                <span class="step-title">時間を選択</span>
                            </div>
                            <div class="selected-date-label" id="selectedDateLabel">日付を選択してください</div>
                        </div>
                        <div id="timeSelector" class="time-selector"></div>
                    </div>
                    <div class="form-section">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-users"></i> ${AppConstants.RESERVATION_LABELS.NUMBER_OF_PEOPLE}
                            </label>
                            ${this.getPeopleStepperHtml(numPeople)}
                        </div>
                        <div class="form-group">
                            ${this.getCourseSelectHtml(reservation.reservation_course_id || null)}
                        </div>
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-comment"></i> ${AppConstants.RESERVATION_LABELS.NOTES_OPTIONAL}
                            </label>
                            <textarea name="notes" class="form-control"
                                rows="${AppConstants.RESERVATION_VALIDATION.TEXTAREA_ROWS}"
                                maxlength="${AppConstants.RESERVATION_VALIDATION.MAX_NOTES_LENGTH}">${notes}</textarea>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" id="submitReservationBtn">
                            <i class="fas fa-check"></i> ${AppConstants.RESERVATION_LABELS.UPDATE_BUTTON}
                        </button>
                        <button type="button" class="btn btn-secondary" data-action="close-modal">
                            <i class="fas fa-times"></i> ${AppConstants.RESERVATION_LABELS.CANCEL_BUTTON}
                        </button>
                    </div>
                </form>
            </div>
        `;
    }

    async loadAvailableDates(excludeReservationId: number | null = null): Promise<void> {
        const url = excludeReservationId
            ? `${AppConstants.API_ENDPOINTS.RESERVATIONS_AVAILABLE_DATES}?exclude_reservation_id=${excludeReservationId}`
            : AppConstants.API_ENDPOINTS.RESERVATIONS_AVAILABLE_DATES;
        try {
            const response = await this.apiClient.get(url);
            if (response.success === true) {
                const data = response.data as { available_dates?: AvailableDateInfo[]; start_date?: string; end_date?: string; courses?: ReservationCourse[] } | undefined;
                this.availableDates = (data && data.available_dates) || [];
                this.courses = (data && data.courses) || [];
                this.availableDateMap = new Map(this.availableDates.map((dateInfo) => [dateInfo.date, dateInfo]));
                this.calendarStartDate = this.parseDate(data && data.start_date) || this.getDefaultCalendarStart();
                this.calendarEndDate = this.parseDate(data && data.end_date) || this.getDefaultCalendarEnd();
                this.setInitialCalendarMonth();
                if (this.selectedDate && !this.availableDateMap.has(this.selectedDate)) {
                    this.selectedDate = null;
                    this.selectedTime = null;
                }
            } else {
                this.availableDates = [];
                this.availableDateMap = new Map();
                this.calendarStartDate = this.getDefaultCalendarStart();
                this.calendarEndDate = this.getDefaultCalendarEnd();
                this.setInitialCalendarMonth();
            }
        } catch (error) {

            console.error('Failed to load available dates:', error);
            this.availableDates = [];
            this.courses = [];
            this.availableDateMap = new Map();
            this.calendarStartDate = this.getDefaultCalendarStart();
            this.calendarEndDate = this.getDefaultCalendarEnd();
            this.setInitialCalendarMonth();
        }
    }

    getCreateFormHtml(): string {
        const notes = this.pendingNotes !== null ? DomHelper.escapeHtml(this.pendingNotes) : '';
        return `
            <div class="modal-content reservation-modal-content">
                <div class="modal-header">
                    <h3><i class="fas fa-calendar-plus"></i> ${AppConstants.RESERVATION_LABELS.CREATE_TITLE}</h3>
                    <button type="button" class="close-btn" data-action="close-modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form id="reservationForm" class="reservation-form">
                    <div class="form-section">
                        <div class="form-section-header">
                            <div class="step-label">
                                <span class="step-number">1</span>
                                <span class="step-title">日付を選択</span>
                            </div>
                            <div class="step-hint"><i class="fas fa-check"></i> 予約可 / <i class="fas fa-times"></i> 予約不可</div>
                        </div>
                        <div id="dateSelector" class="date-selector">
                            <div class="date-selector-loading">
                                <i class="fas fa-spinner fa-spin"></i> 読み込み中...
                            </div>
                        </div>
                    </div>
                    <div class="form-section" id="timeSection">
                        <div class="form-section-header">
                            <div class="step-label">
                                <span class="step-number">2</span>
                                <span class="step-title">時間を選択</span>
                            </div>
                            <div class="selected-date-label" id="selectedDateLabel">日付を選択してください</div>
                        </div>
                        <div id="timeSelector" class="time-selector"></div>
                    </div>
                    <div class="form-section">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-users"></i> ${AppConstants.RESERVATION_LABELS.NUMBER_OF_PEOPLE}
                            </label>
                            ${this.getPeopleStepperHtml(this.pendingPeople)}
                        </div>
                        <div class="form-group">
                            ${this.getCourseSelectHtml(this.pendingCourseId)}
                        </div>
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-comment"></i> ${AppConstants.RESERVATION_LABELS.NOTES_OPTIONAL}
                            </label>
                            <textarea name="notes" class="form-control"
                                rows="${AppConstants.RESERVATION_VALIDATION.TEXTAREA_ROWS}"
                                maxlength="${AppConstants.RESERVATION_VALIDATION.MAX_NOTES_LENGTH}">${notes}</textarea>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" id="submitReservationBtn" disabled>
                            <i class="fas fa-arrow-right"></i> 内容を確認
                        </button>
                        <button type="button" class="btn btn-secondary" data-action="close-modal">
                            <i class="fas fa-times"></i> ${AppConstants.RESERVATION_LABELS.CANCEL_BUTTON}
                        </button>
                    </div>
                </form>
            </div>
        `;
    }

    getCourseSelectHtml(selectedId: number | null): string {
        if (this.courses.length === 0) return '';
        const options = this.courses.map((course) => `<option value="${course.id}" ${selectedId === course.id ? 'selected' : ''}>${DomHelper.escapeHtml(course.name)} · ${Number(course.price).toLocaleString('ja-JP')}円</option>`).join('');
        return `<label class="form-label" for="reservationCourse">コース</label><select class="form-control" id="reservationCourse" name="reservation_course_id"><option value="">指定なし</option>${options}</select>`;
    }

    private restoreNewFormSelection(): void {
        this.updateSelectedDateLabel();
        if (this.selectedDate === null) {
            this.renderTimeSelector([], '日付を選択してください');
            this.setTimeSectionDisabled(true);
            this.updateSubmitButton();
            return;
        }
        const dateInfo = this.availableDateMap.get(this.selectedDate);
        if (dateInfo !== undefined && Array.isArray(dateInfo.time_slots) && dateInfo.time_slots.length > 0) {
            this.renderTimeSelector(dateInfo.time_slots, '空きがありません');
            this.setTimeSectionDisabled(false);
        } else {
            this.renderTimeSelector([], '空きがありません');
            this.setTimeSectionDisabled(true);
        }
        this.updateSubmitButton();
    }

    renderCalendar(modal: HTMLElement): void {
        const dateSelector = modal.querySelector('#dateSelector');
        if (!dateSelector) return;
        if (!this.calendarStartDate || !this.calendarEndDate) {
            (dateSelector as HTMLElement).innerHTML = '<div class="empty-dates"><i class="fas fa-calendar-times"></i><p>利用可能な日がありません</p></div>';
            return;
        }
        if (!this.currentCalendarMonth) this.setInitialCalendarMonth();
        if (!this.currentCalendarMonth) return;

        const monthStart = new Date(this.currentCalendarMonth.getFullYear(), this.currentCalendarMonth.getMonth(), 1);
        const monthEnd = new Date(this.currentCalendarMonth.getFullYear(), this.currentCalendarMonth.getMonth() + 1, 0);
        const prevMonth = new Date(this.currentCalendarMonth.getFullYear(), this.currentCalendarMonth.getMonth() - 1, 1);
        const nextMonth = new Date(this.currentCalendarMonth.getFullYear(), this.currentCalendarMonth.getMonth() + 1, 1);
        const prevDisabled = this.isMonthBeforeRange(prevMonth);
        const nextDisabled = this.isMonthAfterRange(nextMonth);

        const weekdays = ['日', '月', '火', '水', '木', '金', '土']
            .map((day) => `<div class="calendar-weekday">${day}</div>`)
            .join('');
        const totalDays = monthEnd.getDate();
        const startDayIndex = monthStart.getDay();
        const dayCells: string[] = [];
        for (let i = 0; i < startDayIndex; i += 1) dayCells.push('<button type="button" class="calendar-day is-empty" disabled></button>');
        for (let day = 1; day <= totalDays; day += 1) {
            const dateObj = new Date(this.currentCalendarMonth.getFullYear(), this.currentCalendarMonth.getMonth(), day);
            const dateKey = this.formatDateKey(dateObj);
            const isInRange = this.isDateInRange(dateObj);
            const isAvailable = isInRange && this.availableDateMap.has(dateKey);
            const isSelected = this.selectedDate === dateKey;
            const availabilityIcon = isAvailable ? '<i class="fas fa-check" aria-hidden="true"></i>' : '<i class="fas fa-times" aria-hidden="true"></i>';
            const availabilityClass = isAvailable ? 'is-available' : 'is-unavailable';
            const outsideRangeClass = !isInRange ? 'is-outside-range' : '';
            const disabledAttr = isAvailable ? '' : 'disabled';
            dayCells.push(`
                <button type="button" class="calendar-day ${availabilityClass} ${outsideRangeClass} ${isSelected ? 'selected' : ''}" data-date="${dateKey}" ${disabledAttr}>
                    <span class="calendar-day-number">${day}</span>
                    <span class="calendar-availability">${availabilityIcon}</span>
                </button>
            `);
        }

        const emptyMessage = this.availableDateMap.size === 0 ? '<div class="calendar-empty-message">現在予約可能な日がありません</div>' : '';
        const rangeLabel = this.getCalendarRangeLabel();
        (dateSelector as HTMLElement).innerHTML = `
            <div class="calendar-header">
                <button type="button" class="calendar-nav-btn" ${prevDisabled ? 'disabled' : ''} data-calendar-nav="-1">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <div class="calendar-title">${this.getMonthLabel(this.currentCalendarMonth)}</div>
                <button type="button" class="calendar-nav-btn" ${nextDisabled ? 'disabled' : ''} data-calendar-nav="1">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            <div class="calendar-legend">
                <span class="legend-item"><span class="legend-mark available"><i class="fas fa-check" aria-hidden="true"></i></span> 予約可</span>
                <span class="legend-item"><span class="legend-mark unavailable"><i class="fas fa-times" aria-hidden="true"></i></span> 予約不可</span>
            </div>
            ${rangeLabel ? `<div class="calendar-range">${rangeLabel}</div>` : ''}
            ${emptyMessage}
            <div class="calendar-weekdays">${weekdays}</div>
            <div class="calendar-grid">${dayCells.join('')}</div>
        `;
    }

    parseDate(dateStr?: string | null): Date | null {
        if (!dateStr) return null;
        return new Date(`${dateStr}T00:00:00`);
    }
    getDefaultCalendarStart(): Date { const tomorrow = new Date(); tomorrow.setDate(tomorrow.getDate() + 1); tomorrow.setHours(0, 0, 0, 0); return tomorrow; }
    getDefaultCalendarEnd(): Date { const endDate = new Date(); endDate.setDate(endDate.getDate() + DEFAULT_ADVANCE_DAYS); endDate.setHours(23, 59, 59, 999); return endDate; }
    setInitialCalendarMonth(): void {
        const baseDate = this.selectedDate ? this.parseDate(this.selectedDate) : this.calendarStartDate;
        const normalizedBase = baseDate || new Date();
        this.currentCalendarMonth = new Date(normalizedBase.getFullYear(), normalizedBase.getMonth(), 1);
    }
    formatDateKey(dateObj: Date): string { return `${dateObj.getFullYear()}-${String(dateObj.getMonth() + 1).padStart(2, '0')}-${String(dateObj.getDate()).padStart(2, '0')}`; }
    isDateInRange(dateObj: Date): boolean { return !!(this.calendarStartDate && this.calendarEndDate && dateObj >= this.calendarStartDate && dateObj <= this.calendarEndDate); }
    isMonthBeforeRange(monthDate: Date): boolean { if (!this.calendarStartDate) return true; const monthEnd = new Date(monthDate.getFullYear(), monthDate.getMonth() + 1, 0, 23, 59, 59); return monthEnd < this.calendarStartDate; }
    isMonthAfterRange(monthDate: Date): boolean { if (!this.calendarEndDate) return true; const monthStart = new Date(monthDate.getFullYear(), monthDate.getMonth(), 1); return monthStart > this.calendarEndDate; }
    getMonthLabel(dateObj: Date): string { return `${dateObj.getFullYear()}年${dateObj.getMonth() + 1}月`; }
    getCalendarRangeLabel(): string | null { if (!this.calendarStartDate || !this.calendarEndDate) return null; return `予約可能期間: ${this.formatDateShort(this.calendarStartDate)} 〜 ${this.formatDateShort(this.calendarEndDate)}`; }
    formatDateDisplay(dateObj: Date): string { return dateObj.toLocaleDateString('ja-JP', { year: 'numeric', month: 'numeric', day: 'numeric', weekday: 'short' }); }
    formatDateShort(dateObj: Date): string { return dateObj.toLocaleDateString('ja-JP', { year: 'numeric', month: 'numeric', day: 'numeric' }); }

    changeCalendarMonth(offset: number): void {
        if (!this.currentCalendarMonth) return;
        const nextMonth = new Date(this.currentCalendarMonth.getFullYear(), this.currentCalendarMonth.getMonth() + offset, 1);
        if (offset < 0 && this.isMonthBeforeRange(nextMonth)) return;
        if (offset > 0 && this.isMonthAfterRange(nextMonth)) return;
        this.currentCalendarMonth = nextMonth;
        if (this.currentModal) this.renderCalendar(this.currentModal);
    }

    selectDate(date: string): void {
        this.selectedDate = date;
        this.selectedTime = null;
        this.updateSelectedDateLabel();
        const dateInfo = this.availableDateMap.get(date);
        if (dateInfo && Array.isArray(dateInfo.time_slots) && dateInfo.time_slots.length > 0) {
            this.renderTimeSelector(dateInfo.time_slots, '空きがありません');
            this.setTimeSectionDisabled(false);
        } else if (this.currentModal) {
            this.renderTimeSelector([], '空きがありません');
            this.setTimeSectionDisabled(true);
        }
        if (this.currentModal) this.renderCalendar(this.currentModal);
        this.updateSubmitButton();
    }

    renderTimeSelector(timeSlots: string[], emptyMessage = '日付を選択してください'): void {
        const timeSelector = this.currentModal ? this.currentModal.querySelector('#timeSelector') : null;
        if (!timeSelector) return;
        if (!Array.isArray(timeSlots) || timeSlots.length === 0) {
            (timeSelector as HTMLElement).innerHTML = `
                <div class="time-selector-empty">
                    <i class="fas fa-clock"></i>
                    <p>${emptyMessage}</p>
                </div>
            `;
            return;
        }
        const timesHtml = timeSlots.map((time) => {
            const isSelected = this.selectedTime === time;
            return `<button type="button" class="time-option ${isSelected ? 'selected' : ''}" data-time="${time}">${time}</button>`;
        }).join('');
        (timeSelector as HTMLElement).innerHTML = `<div class="time-options-grid">${timesHtml}</div>`;
    }

    selectTime(time: string): void {
        this.selectedTime = time;
        const timeButtons = this.currentModal ? this.currentModal.querySelectorAll('.time-option') : [];
        timeButtons.forEach((btnEl) => {
            const btn = btnEl as HTMLElement;
            const isTarget = (btn as any).dataset.time === time;
            btn.classList.toggle('selected', isTarget);
        });
        this.updateSubmitButton();
    }

    updateSubmitButton(): void {
        const submitBtn = this.currentModal ? this.currentModal.querySelector('#submitReservationBtn') as HTMLButtonElement | null : null;
        if (submitBtn) submitBtn.disabled = !(this.selectedDate && this.selectedTime);
    }

    attachFormListeners(modal: HTMLElement): void {
        const form = (modal.querySelector('#reservationForm') || modal.querySelector('#reservationEditForm')) as HTMLFormElement | null;
        if (!form) return;
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const editId = (form.dataset && form.dataset.editId) || (modal.dataset && modal.dataset.editId);
            if (editId) {
                await this.submitUpdate(parseInt(editId, 10), form, modal);
                return;
            }
            if (this.pageMode) {
                const formData = new FormData(form);
                this.pendingPeople = parseInt(String(formData.get('number_of_people') || '1'), 10) || 1;
                const notes = formData.get('notes');
                this.pendingNotes = notes !== null && String(notes) !== '' ? String(notes) : null;
                this.pendingCourseId = Number(formData.get('reservation_course_id')) || null;
                if (!this.selectedDate || !this.selectedTime) {
                    ToastManager.error('日付と時間を選択してください');
                    return;
                }
                await this.openFlowStep('confirm');
                return;
            }
            await this.submitReservation(form, modal);
        });

        modal.addEventListener('click', (event: Event) => {
            const target = event.target as HTMLElement;
            if (target === modal && modal.classList.contains('modal-overlay')) { this.closeModal(modal); return; }
            const closeButton = target.closest('[data-action="close-modal"]');
            if (closeButton) {
                if (this.pageMode && this.app && this.app.pageRouter) {
                    this.app.pageRouter.closePage(true);
                    this.pageMode = false;
                    return;
                }
                this.closeModal(modal);
                return;
            }
            const navButton = target.closest('[data-calendar-nav]') as HTMLElement | null;
            if (navButton) {
                const offset = parseInt(navButton.dataset.calendarNav || '', 10);
                if (!Number.isNaN(offset)) this.changeCalendarMonth(offset);
                return;
            }
            const dayButton = target.closest('.calendar-day') as HTMLButtonElement | null;
            if (dayButton && !dayButton.disabled && dayButton.dataset.date) { this.selectDate(dayButton.dataset.date); return; }
            const timeButton = target.closest('.time-option') as HTMLButtonElement | null;
            if (timeButton && !timeButton.disabled && timeButton.dataset.time) { this.selectTime(timeButton.dataset.time); return; }
            const peopleButton = target.closest('[data-people]') as HTMLButtonElement | null;
            if (peopleButton && !peopleButton.disabled) {
                const delta = parseInt(peopleButton.dataset.people || '', 10);
                if (!Number.isNaN(delta)) this.adjustPeople(modal, delta);
            }
        });
        this.adjustPeople(modal, 0);
    }

    closeModal(modal: HTMLElement): void {
        if (this.pageMode) {
            if (this.app && this.app.pageRouter) {
                this.app.pageRouter.closePage(true);
            }
            this.pageMode = false;
            this.currentModal = null;
            this.editingReservationId = null;
            return;
        }
        modal.remove();
        if (this.currentModal === modal) this.currentModal = null;
        this.editingReservationId = null;
    }
    setTimeSectionDisabled(isDisabled: boolean): void {
        const timeSection = this.currentModal ? this.currentModal.querySelector('#timeSection') : null;
        if (!timeSection) return;
        (timeSection as HTMLElement).classList.toggle('is-disabled', isDisabled);
    }
    updateSelectedDateLabel(): void {
        const label = this.currentModal ? this.currentModal.querySelector('#selectedDateLabel') : null;
        if (!label) return;
        if (!this.selectedDate) { (label as HTMLElement).textContent = '日付を選択してください'; return; }
        const dateInfo = this.availableDateMap.get(this.selectedDate);
        const parsedDate = this.parseDate(this.selectedDate);
        (label as HTMLElement).textContent = (dateInfo && dateInfo.display) || (parsedDate ? this.formatDateDisplay(parsedDate) : this.selectedDate);
    }

    async submitUpdate(reservationId: number, form: HTMLFormElement, modal: HTMLElement): Promise<void> {
        if (!this.selectedDate || !this.selectedTime) { ToastManager.error('日付と時間を選択してください'); return; }
        const formData = new FormData(form);
        const data = {
            reserved_at: `${this.selectedDate} ${this.selectedTime}:00`,
            number_of_people: parseInt(String(formData.get('number_of_people') || '0'), 10),
            notes: formData.get('notes') || null,
            reservation_course_id: Number(formData.get('reservation_course_id')) || null,
        };
        try {
            const response = await this.apiClient.put(`${AppConstants.API_ENDPOINTS.RESERVATIONS}/${reservationId}`, data);
            if (response.success === true) {
                ToastManager.success(AppConstants.RESERVATION_MESSAGES.UPDATE_SUCCESS);
                this.closeModal(modal);
                this.selectedDate = null;
                this.selectedTime = null;
                await this.loadReservations();
            } else throw new Error(response.error);
        } catch (error) {
            const message = error instanceof Error ? error.message : '';
            ToastManager.error(message || AppConstants.RESERVATION_MESSAGES.UPDATE_ERROR);
        }
    }

    async submitReservation(form: HTMLFormElement, modal: HTMLElement): Promise<void> {
        if (!this.selectedDate || !this.selectedTime) { ToastManager.error('日付と時間を選択してください'); return; }
        const formData = new FormData(form);
        const data = {
            reserved_at: `${this.selectedDate} ${this.selectedTime}:00`,
            number_of_people: parseInt(String(formData.get('number_of_people') || '0'), 10),
            notes: formData.get('notes') || null,
            reservation_course_id: Number(formData.get('reservation_course_id')) || null,
        };
        try {
            const response = await this.apiClient.post(AppConstants.API_ENDPOINTS.RESERVATIONS, data);
            if (response.success === true) {
                ToastManager.success(AppConstants.RESERVATION_MESSAGES.CREATE_SUCCESS);
                this.closeModal(modal);
                this.selectedDate = null;
                this.selectedTime = null;
                await this.loadReservations();
            } else throw new Error(response.error);
        } catch (error) {
            const message = error instanceof Error ? error.message : '';
            if (message && message.includes('会員登録が必要')) {
                ToastManager.error('会員登録が必要です');
                this.closeModal(modal);
                if (this.app && this.app.switchTab) this.app.switchTab(AppConstants.TABS.MEMBER);
            } else {
                ToastManager.error(message || AppConstants.RESERVATION_MESSAGES.CREATE_ERROR);
            }
        }
    }

    getReservationSkeleton(): string {
        const skeletonCount = (AppConstants.SKELETON_COUNT as Record<string, number>).RESERVATIONS || 3;
        return Array(skeletonCount).fill(0).map(() => `
            <div class="reservation-card ${AppConstants.CSS_CLASSES.SKELETON}">
                <div class="reservation-card-header">
                    <div class="skeleton-line skeleton-title"></div>
                    <div class="skeleton-line skeleton-badge"></div>
                </div>
                <div class="reservation-card-body">
                    <div class="skeleton-line skeleton-text"></div>
                    <div class="skeleton-line skeleton-text"></div>
                    <div class="skeleton-line skeleton-text-short"></div>
                </div>
            </div>
        `).join('');
    }
    getEmptyTemplate(): string {
        return `
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-calendar-times"></i>
                </div>
                <h3 class="empty-state-title">${AppConstants.RESERVATION_MESSAGES.EMPTY_TITLE}</h3>
                <p class="empty-state-message">${AppConstants.RESERVATION_MESSAGES.EMPTY_MESSAGE}</p>
                <button class="btn btn-primary btn-empty-action" onclick="app.reservationManager.showCreateForm()">
                    <i class="fas fa-plus"></i> ${AppConstants.RESERVATION_MESSAGES.EMPTY_BUTTON}
                </button>
            </div>
        `;
    }
    getErrorTemplate(error: unknown): string {
        const message = error instanceof Error ? error.message : '';
        return `
            <div class="error-state">
                <div class="error-state-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h3 class="error-state-title">${AppConstants.RESERVATION_MESSAGES.ERROR_TITLE}</h3>
                <p class="error-message">${DomHelper.escapeHtml(message || AppConstants.RESERVATION_MESSAGES.ERROR_DEFAULT)}</p>
                <button class="btn btn-primary btn-error-action" onclick="app.reservationManager.loadReservations()">
                    <i class="fas fa-redo"></i> ${AppConstants.RESERVATION_MESSAGES.RELOAD_BUTTON}
                </button>
            </div>
        `;
    }
    getMembershipRequiredTemplate(): string {
        return `
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-id-card"></i>
                </div>
                <h3 class="empty-state-title">会員登録が必要です</h3>
                <p class="empty-state-message">予約機能をご利用いただくには、会員登録が必要です。会員登録をすると、予約機能とポイント機能をご利用いただけます。</p>
                <button class="btn btn-primary btn-empty-action" onclick="app.switchTab('${AppConstants.TABS.MEMBER}')">
                    <i class="fas fa-user-plus"></i> 会員登録へ
                </button>
            </div>
        `;
    }

    getOptionalMembershipTemplate(): string {
        return `
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <h3 class="empty-state-title">予約をはじめる</h3>
                <p class="empty-state-message">ゲストでも予約内容を確認できます。会員登録すると履歴を引き継げます。</p>
                <button class="btn btn-secondary btn-empty-action" onclick="app.switchTab('${AppConstants.TABS.MEMBER}')">
                    <i class="fas fa-user-plus"></i> 任意で会員登録
                </button>
            </div>
        `;
    }
}

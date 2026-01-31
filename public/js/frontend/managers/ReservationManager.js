const DEFAULT_ADVANCE_DAYS = 30;

class ReservationManager {
    constructor(appInstance) {
        this.app = appInstance;
        this.reservations = [];
        this.availableDates = [];
        this.selectedDate = null;
        this.selectedTime = null;
        this.availableDateMap = new Map();
        this.calendarStartDate = null;
        this.calendarEndDate = null;
        this.currentCalendarMonth = null;
        this.currentModal = null;
        this.editingReservationId = null;
        this.apiClient = new ApiClient();
        this.reservationsSection = DomHelper.getElementById(AppConstants.ELEMENT_IDS.RESERVATIONS_SECTION);
    }

    getReservationById(id) {
        return this.reservations.find(r => r.id === id) || null;
    }

    async loadReservations() {
        if (this.reservationsSection) {
            this.reservationsSection.innerHTML = this.getReservationSkeleton();
        }

        try {
            const response = await this.apiClient.get(
                AppConstants.API_ENDPOINTS.RESERVATIONS
            );
            if (response.success) {
                this.reservations = response.data?.reservations || [];
                this.renderReservations();
            } else {
                if (response.data?.requires_membership === true) {
                    this.reservationsSection.innerHTML = this.getMembershipRequiredTemplate();
                    return;
                }
                throw new Error(response.error);
            }
        } catch (error) {
            console.error(AppConstants.RESERVATION_MESSAGES.LOAD_ERROR, error);
            if (this.reservationsSection) {
                if (error.message && error.message.includes('会員登録が必要')) {
                    this.reservationsSection.innerHTML = this.getMembershipRequiredTemplate();
                } else {
                    this.reservationsSection.innerHTML = this.getErrorTemplate(error);
                }
            }
        }
    }

    renderReservations() {
        if (!this.reservationsSection) return;

        if (this.reservations.length === 0) {
            this.reservationsSection.innerHTML = this.getEmptyTemplate();
            return;
        }

        const reservationsHtml = this.reservations.map(reservation => 
            this.getReservationCardHtml(reservation)
        ).join('');

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

    getReservationCardHtml(reservation) {
        const reservedAt = new Date(reservation.reserved_at);
        const dateStr = reservedAt.toLocaleDateString(
            AppConstants.DATE_FORMAT.LOCALE, 
            AppConstants.DATE_FORMAT.DATE_OPTIONS
        );
        const timeStr = reservedAt.toLocaleTimeString(
            AppConstants.DATE_FORMAT.LOCALE, 
            AppConstants.DATE_FORMAT.TIME_OPTIONS
        );
        const statusClass = this.getStatusClass(reservation.status);
        const statusLabel = this.getStatusLabel(reservation.status);
        const canEdit = reservation.status !== AppConstants.RESERVATION_STATUS.CANCELLED
            && reservation.status !== AppConstants.RESERVATION_STATUS.COMPLETED;
        const canCancel = canEdit;
        const dayOfWeek = ['日', '月', '火', '水', '木', '金', '土'][reservedAt.getDay()];

        return `
            <div class="reservation-card">
                <div class="reservation-card-header">
                    <div class="reservation-number">
                        <i class="fas fa-hashtag"></i>
                        ${DomHelper.escapeHtml(reservation.reservation_number)}
                    </div>
                    <span class="reservation-status-badge ${statusClass}">
                        <i class="fas ${this.getStatusIcon(reservation.status)}"></i>
                        ${statusLabel}
                    </span>
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
                                <span class="reservation-people-unit">名様</span>
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

    getStatusIcon(status) {
        const iconMap = {
            [AppConstants.RESERVATION_STATUS.PENDING]: AppConstants.RESERVATION_STATUS_ICONS.PENDING,
            [AppConstants.RESERVATION_STATUS.CONFIRMED]: AppConstants.RESERVATION_STATUS_ICONS.CONFIRMED,
            [AppConstants.RESERVATION_STATUS.CANCELLED]: AppConstants.RESERVATION_STATUS_ICONS.CANCELLED,
            [AppConstants.RESERVATION_STATUS.COMPLETED]: AppConstants.RESERVATION_STATUS_ICONS.COMPLETED,
        };
        return iconMap[status] || AppConstants.RESERVATION_STATUS_ICONS.DEFAULT;
    }

    getStatusClass(status) {
        const classMap = {
            [AppConstants.RESERVATION_STATUS.PENDING]: AppConstants.RESERVATION_STATUS_CLASSES.PENDING,
            [AppConstants.RESERVATION_STATUS.CONFIRMED]: AppConstants.RESERVATION_STATUS_CLASSES.CONFIRMED,
            [AppConstants.RESERVATION_STATUS.CANCELLED]: AppConstants.RESERVATION_STATUS_CLASSES.CANCELLED,
            [AppConstants.RESERVATION_STATUS.COMPLETED]: AppConstants.RESERVATION_STATUS_CLASSES.COMPLETED,
        };
        return classMap[status] || AppConstants.RESERVATION_STATUS_CLASSES.DEFAULT;
    }

    getStatusLabel(status) {
        const labelMap = {
            [AppConstants.RESERVATION_STATUS.PENDING]: AppConstants.RESERVATION_STATUS_LABELS.PENDING,
            [AppConstants.RESERVATION_STATUS.CONFIRMED]: AppConstants.RESERVATION_STATUS_LABELS.CONFIRMED,
            [AppConstants.RESERVATION_STATUS.CANCELLED]: AppConstants.RESERVATION_STATUS_LABELS.CANCELLED,
            [AppConstants.RESERVATION_STATUS.COMPLETED]: AppConstants.RESERVATION_STATUS_LABELS.COMPLETED,
        };
        return labelMap[status] || status;
    }

    async cancelReservation(reservationId) {
        const confirmed = await ConfirmDialog.show(
            AppConstants.RESERVATION_MESSAGES.CANCEL_CONFIRM_TITLE,
            AppConstants.RESERVATION_MESSAGES.CANCEL_CONFIRM_MESSAGE
        );

        if (!confirmed) return;

        try {
            const response = await this.apiClient.post(
                `${AppConstants.API_ENDPOINTS.RESERVATIONS}/${reservationId}/cancel`,
                {}
            );
            if (response.success) {
                ToastManager.success(AppConstants.RESERVATION_MESSAGES.CANCEL_SUCCESS);
                await this.loadReservations();
            } else {
                throw new Error(response.error);
            }
        } catch (error) {
            ToastManager.error(error.message || AppConstants.RESERVATION_MESSAGES.CANCEL_ERROR);
        }
    }

    async showCreateForm() {
        this.selectedDate = null;
        this.selectedTime = null;
        await this.loadAvailableDates();
        const modal = document.createElement('div');
        modal.className = 'modal-overlay';
        modal.innerHTML = this.getCreateFormHtml();
        document.body.appendChild(modal);
        this.currentModal = modal;
        this.attachFormListeners(modal);
        this.renderCalendar(modal);
        this.renderTimeSelector([], '日付を選択してください');
        this.updateSelectedDateLabel();
        this.setTimeSectionDisabled(true);
    }

    async showEditForm(reservation) {
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
        const dateInfo = this.availableDateMap.get(this.selectedDate);
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

    formatTimeForSlot(dateObj) {
        const hours = dateObj.getHours();
        const minutes = dateObj.getMinutes();
        return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
    }

    getEditFormHtml(reservation) {
        const numPeople = Math.min(Math.max(Number(reservation.number_of_people) || 1, AppConstants.RESERVATION_VALIDATION.MIN_PEOPLE), AppConstants.RESERVATION_VALIDATION.MAX_PEOPLE);
        const notes = (reservation.notes != null && reservation.notes !== '') ? DomHelper.escapeHtml(reservation.notes) : '';
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
                            <input type="number" name="number_of_people" class="form-control" required 
                                min="${AppConstants.RESERVATION_VALIDATION.MIN_PEOPLE}" 
                                max="${AppConstants.RESERVATION_VALIDATION.MAX_PEOPLE}" 
                                value="${numPeople}">
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

    async loadAvailableDates(excludeReservationId = null) {
        const url = excludeReservationId
            ? `${AppConstants.API_ENDPOINTS.RESERVATIONS_AVAILABLE_DATES}?exclude_reservation_id=${excludeReservationId}`
            : AppConstants.API_ENDPOINTS.RESERVATIONS_AVAILABLE_DATES;
        try {
            const response = await this.apiClient.get(url);
            if (response.success) {
                this.availableDates = response.data?.available_dates || [];
                this.availableDateMap = new Map(
                    this.availableDates.map(dateInfo => [dateInfo.date, dateInfo])
                );
                this.calendarStartDate = this.parseDate(response.data?.start_date) ?? this.getDefaultCalendarStart();
                this.calendarEndDate = this.parseDate(response.data?.end_date) ?? this.getDefaultCalendarEnd();
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
            this.availableDateMap = new Map();
            this.calendarStartDate = this.getDefaultCalendarStart();
            this.calendarEndDate = this.getDefaultCalendarEnd();
            this.setInitialCalendarMonth();
        }
    }

    getCreateFormHtml() {
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
                            <input type="number" name="number_of_people" class="form-control" required 
                                min="${AppConstants.RESERVATION_VALIDATION.MIN_PEOPLE}" 
                                max="${AppConstants.RESERVATION_VALIDATION.MAX_PEOPLE}" 
                                value="${AppConstants.RESERVATION_VALIDATION.MIN_PEOPLE}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-comment"></i> ${AppConstants.RESERVATION_LABELS.NOTES_OPTIONAL}
                            </label>
                            <textarea name="notes" class="form-control" 
                                rows="${AppConstants.RESERVATION_VALIDATION.TEXTAREA_ROWS}" 
                                maxlength="${AppConstants.RESERVATION_VALIDATION.MAX_NOTES_LENGTH}"></textarea>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" id="submitReservationBtn" disabled>
                            <i class="fas fa-check"></i> ${AppConstants.RESERVATION_LABELS.SUBMIT_BUTTON}
                        </button>
                        <button type="button" class="btn btn-secondary" data-action="close-modal">
                            <i class="fas fa-times"></i> ${AppConstants.RESERVATION_LABELS.CANCEL_BUTTON}
                        </button>
                    </div>
                </form>
            </div>
        `;
    }

    renderCalendar(modal) {
        const dateSelector = modal.querySelector('#dateSelector');
        if (!dateSelector) return;

        if (!this.calendarStartDate || !this.calendarEndDate) {
            dateSelector.innerHTML = '<div class="empty-dates"><i class="fas fa-calendar-times"></i><p>利用可能な日がありません</p></div>';
            return;
        }

        if (!this.currentCalendarMonth) {
            this.setInitialCalendarMonth();
        }

        const monthStart = new Date(this.currentCalendarMonth.getFullYear(), this.currentCalendarMonth.getMonth(), 1);
        const monthEnd = new Date(this.currentCalendarMonth.getFullYear(), this.currentCalendarMonth.getMonth() + 1, 0);
        const prevMonth = new Date(this.currentCalendarMonth.getFullYear(), this.currentCalendarMonth.getMonth() - 1, 1);
        const nextMonth = new Date(this.currentCalendarMonth.getFullYear(), this.currentCalendarMonth.getMonth() + 1, 1);
        const prevDisabled = this.isMonthBeforeRange(prevMonth);
        const nextDisabled = this.isMonthAfterRange(nextMonth);

        const weekdays = ['日', '月', '火', '水', '木', '金', '土']
            .map(day => `<div class="calendar-weekday">${day}</div>`)
            .join('');

        const totalDays = monthEnd.getDate();
        const startDayIndex = monthStart.getDay();
        const dayCells = [];

        for (let i = 0; i < startDayIndex; i += 1) {
            dayCells.push('<button type="button" class="calendar-day is-empty" disabled></button>');
        }

        for (let day = 1; day <= totalDays; day += 1) {
            const dateObj = new Date(this.currentCalendarMonth.getFullYear(), this.currentCalendarMonth.getMonth(), day);
            const dateKey = this.formatDateKey(dateObj);
            const isInRange = this.isDateInRange(dateObj);
            const isAvailable = isInRange && this.availableDateMap.has(dateKey);
            const isSelected = this.selectedDate === dateKey;
            const availabilityIcon = isAvailable
                ? '<i class="fas fa-check" aria-hidden="true"></i>'
                : '<i class="fas fa-times" aria-hidden="true"></i>';
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

        const emptyMessage = this.availableDateMap.size === 0
            ? '<div class="calendar-empty-message">現在予約可能な日がありません</div>'
            : '';
        const rangeLabel = this.getCalendarRangeLabel();

        dateSelector.innerHTML = `
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

    parseDate(dateStr) {
        if (!dateStr) return null;
        return new Date(`${dateStr}T00:00:00`);
    }

    getDefaultCalendarStart() {
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        tomorrow.setHours(0, 0, 0, 0);
        return tomorrow;
    }

    getDefaultCalendarEnd() {
        const endDate = new Date();
        endDate.setDate(endDate.getDate() + DEFAULT_ADVANCE_DAYS);
        endDate.setHours(23, 59, 59, 999);
        return endDate;
    }

    setInitialCalendarMonth() {
        const baseDate = this.selectedDate
            ? this.parseDate(this.selectedDate)
            : this.calendarStartDate;
        const normalizedBase = baseDate ?? new Date();
        this.currentCalendarMonth = new Date(
            normalizedBase.getFullYear(),
            normalizedBase.getMonth(),
            1
        );
    }

    formatDateKey(dateObj) {
        const year = dateObj.getFullYear();
        const month = String(dateObj.getMonth() + 1).padStart(2, '0');
        const day = String(dateObj.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    isDateInRange(dateObj) {
        if (!this.calendarStartDate || !this.calendarEndDate) return false;
        return dateObj >= this.calendarStartDate && dateObj <= this.calendarEndDate;
    }

    isMonthBeforeRange(monthDate) {
        if (!this.calendarStartDate) return true;
        const monthEnd = new Date(monthDate.getFullYear(), monthDate.getMonth() + 1, 0, 23, 59, 59);
        return monthEnd < this.calendarStartDate;
    }

    isMonthAfterRange(monthDate) {
        if (!this.calendarEndDate) return true;
        const monthStart = new Date(monthDate.getFullYear(), monthDate.getMonth(), 1);
        return monthStart > this.calendarEndDate;
    }

    getMonthLabel(dateObj) {
        return `${dateObj.getFullYear()}年${dateObj.getMonth() + 1}月`;
    }

    getCalendarRangeLabel() {
        if (!this.calendarStartDate || !this.calendarEndDate) {
            return null;
        }
        return `予約可能期間: ${this.formatDateShort(this.calendarStartDate)} 〜 ${this.formatDateShort(this.calendarEndDate)}`;
    }

    formatDateDisplay(dateObj) {
        return dateObj.toLocaleDateString('ja-JP', {
            year: 'numeric',
            month: 'numeric',
            day: 'numeric',
            weekday: 'short',
        });
    }

    formatDateShort(dateObj) {
        return dateObj.toLocaleDateString('ja-JP', {
            year: 'numeric',
            month: 'numeric',
            day: 'numeric',
        });
    }

    changeCalendarMonth(offset) {
        const nextMonth = new Date(
            this.currentCalendarMonth.getFullYear(),
            this.currentCalendarMonth.getMonth() + offset,
            1
        );
        if (offset < 0 && this.isMonthBeforeRange(nextMonth)) {
            return;
        }
        if (offset > 0 && this.isMonthAfterRange(nextMonth)) {
            return;
        }
        this.currentCalendarMonth = nextMonth;
        if (this.currentModal) {
            this.renderCalendar(this.currentModal);
        }
    }

    selectDate(date) {
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

        if (this.currentModal) {
            this.renderCalendar(this.currentModal);
        }
        this.updateSubmitButton();
    }

    renderTimeSelector(timeSlots, emptyMessage = '日付を選択してください') {
        const timeSelector = this.currentModal?.querySelector('#timeSelector');
        if (!timeSelector) return;

        if (!Array.isArray(timeSlots) || timeSlots.length === 0) {
            timeSelector.innerHTML = `
                <div class="time-selector-empty">
                    <i class="fas fa-clock"></i>
                    <p>${emptyMessage}</p>
                </div>
            `;
            return;
        }

        const timesHtml = timeSlots.map(time => {
            const isSelected = this.selectedTime === time;
            return `
                <button type="button" class="time-option ${isSelected ? 'selected' : ''}" 
                    data-time="${time}">
                    ${time}
                </button>
            `;
        }).join('');

        timeSelector.innerHTML = `<div class="time-options-grid">${timesHtml}</div>`;
    }

    selectTime(time) {
        this.selectedTime = time;

        this.currentModal?.querySelectorAll('.time-option').forEach(btn => {
            const isTarget = btn.dataset.time === time;
            btn.classList.toggle('selected', isTarget);
        });

        this.updateSubmitButton();
    }

    updateSubmitButton() {
        const submitBtn = this.currentModal?.querySelector('#submitReservationBtn');
        if (submitBtn) {
            submitBtn.disabled = !(this.selectedDate && this.selectedTime);
        }
    }

    attachFormListeners(modal) {
        const form = modal.querySelector('#reservationForm') || modal.querySelector('#reservationEditForm');
        if (!form) return;
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const editId = form.dataset?.editId || modal.dataset?.editId;
            if (editId) {
                await this.submitUpdate(parseInt(editId, 10), form, modal);
            } else {
                await this.submitReservation(form, modal);
            }
        });

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                this.closeModal(modal);
                return;
            }

            const closeButton = event.target.closest('[data-action="close-modal"]');
            if (closeButton) {
                this.closeModal(modal);
                return;
            }

            const navButton = event.target.closest('[data-calendar-nav]');
            if (navButton) {
                const offset = parseInt(navButton.dataset.calendarNav, 10);
                if (!Number.isNaN(offset)) {
                    this.changeCalendarMonth(offset);
                }
                return;
            }

            const dayButton = event.target.closest('.calendar-day');
            if (dayButton && !dayButton.disabled && dayButton.dataset.date) {
                this.selectDate(dayButton.dataset.date);
                return;
            }

            const timeButton = event.target.closest('.time-option');
            if (timeButton && !timeButton.disabled && timeButton.dataset.time) {
                this.selectTime(timeButton.dataset.time);
            }
        });
    }

    closeModal(modal) {
        modal.remove();
        if (this.currentModal === modal) {
            this.currentModal = null;
        }
        this.editingReservationId = null;
    }

    setTimeSectionDisabled(isDisabled) {
        const timeSection = this.currentModal?.querySelector('#timeSection');
        if (!timeSection) return;
        timeSection.classList.toggle('is-disabled', isDisabled);
    }

    updateSelectedDateLabel() {
        const label = this.currentModal?.querySelector('#selectedDateLabel');
        if (!label) return;
        if (!this.selectedDate) {
            label.textContent = '日付を選択してください';
            return;
        }
        const dateInfo = this.availableDateMap.get(this.selectedDate);
        const parsedDate = this.parseDate(this.selectedDate);
        label.textContent = dateInfo?.display || (parsedDate ? this.formatDateDisplay(parsedDate) : this.selectedDate);
    }

    async submitUpdate(reservationId, form, modal) {
        if (!this.selectedDate || !this.selectedTime) {
            ToastManager.error('日付と時間を選択してください');
            return;
        }

        const formData = new FormData(form);
        const reservedAt = `${this.selectedDate} ${this.selectedTime}:00`;
        const data = {
            reserved_at: reservedAt,
            number_of_people: parseInt(formData.get('number_of_people'), 10),
            notes: formData.get('notes') || null,
        };

        try {
            const response = await this.apiClient.put(
                `${AppConstants.API_ENDPOINTS.RESERVATIONS}/${reservationId}`,
                data
            );
            if (response.success) {
                ToastManager.success(AppConstants.RESERVATION_MESSAGES.UPDATE_SUCCESS);
                this.closeModal(modal);
                this.selectedDate = null;
                this.selectedTime = null;
                await this.loadReservations();
            } else {
                throw new Error(response.error);
            }
        } catch (error) {
            ToastManager.error(error.message || AppConstants.RESERVATION_MESSAGES.UPDATE_ERROR);
        }
    }

    async submitReservation(form, modal) {
        if (!this.selectedDate || !this.selectedTime) {
            ToastManager.error('日付と時間を選択してください');
            return;
        }

        const formData = new FormData(form);
        const reservedAt = `${this.selectedDate} ${this.selectedTime}:00`;
        
        const data = {
            reserved_at: reservedAt,
            number_of_people: parseInt(formData.get('number_of_people'), 10),
            notes: formData.get('notes') || null,
        };

        try {
            const response = await this.apiClient.post(
                AppConstants.API_ENDPOINTS.RESERVATIONS,
                data
            );
            if (response.success) {
                ToastManager.success(AppConstants.RESERVATION_MESSAGES.CREATE_SUCCESS);
                this.closeModal(modal);
                this.selectedDate = null;
                this.selectedTime = null;
                await this.loadReservations();
            } else {
                throw new Error(response.error);
            }
        } catch (error) {
            if (error.message && error.message.includes('会員登録が必要')) {
                ToastManager.error('会員登録が必要です');
                this.closeModal(modal);
                if (this.app && this.app.switchTab) {
                    this.app.switchTab(AppConstants.TABS.MEMBER);
                }
            } else {
                ToastManager.error(error.message || AppConstants.RESERVATION_MESSAGES.CREATE_ERROR);
            }
        }
    }

    getReservationSkeleton() {
        return Array(AppConstants.SKELETON_COUNT.RESERVATIONS).fill(0).map(() => `
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

    getEmptyTemplate() {
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

    getErrorTemplate(error) {
        return `
            <div class="error-state">
                <div class="error-state-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h3 class="error-state-title">${AppConstants.RESERVATION_MESSAGES.ERROR_TITLE}</h3>
                <p class="error-message">${DomHelper.escapeHtml(error.message || AppConstants.RESERVATION_MESSAGES.ERROR_DEFAULT)}</p>
                <button class="btn btn-primary btn-error-action" onclick="app.reservationManager.loadReservations()">
                    <i class="fas fa-redo"></i> ${AppConstants.RESERVATION_MESSAGES.RELOAD_BUTTON}
                </button>
            </div>
        `;
    }

    getMembershipRequiredTemplate() {
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
}

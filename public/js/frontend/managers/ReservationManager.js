class ReservationManager {
    constructor(appInstance) {
        this.app = appInstance;
        this.reservations = [];
        this.availableDates = [];
        this.selectedDate = null;
        this.selectedTime = null;
        this.apiClient = new ApiClient();
        this.reservationsSection = DomHelper.getElementById(AppConstants.ELEMENT_IDS.RESERVATIONS_SECTION);
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
        const canCancel = reservation.status !== AppConstants.RESERVATION_STATUS.CANCELLED 
            && reservation.status !== AppConstants.RESERVATION_STATUS.COMPLETED;
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
                ${canCancel ? `
                    <div class="reservation-card-footer">
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
        await this.loadAvailableDates();
        const modal = document.createElement('div');
        modal.className = 'modal-overlay';
        modal.innerHTML = this.getCreateFormHtml();
        document.body.appendChild(modal);
        this.attachFormListeners(modal);
        this.renderDateSelector(modal);
    }

    async loadAvailableDates() {
        try {
            const response = await this.apiClient.get(
                AppConstants.API_ENDPOINTS.RESERVATIONS_AVAILABLE_DATES
            );
            if (response.success) {
                this.availableDates = response.data?.available_dates || [];
            }
        } catch (error) {
            console.error('Failed to load available dates:', error);
            this.availableDates = [];
        }
    }

    getCreateFormHtml() {
        return `
            <div class="modal-content reservation-modal-content">
                <div class="modal-header">
                    <h3><i class="fas fa-calendar-plus"></i> ${AppConstants.RESERVATION_LABELS.CREATE_TITLE}</h3>
                    <button class="close-btn" onclick="this.closest('.modal-overlay').remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form id="reservationForm" class="reservation-form">
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-calendar-alt"></i> 日付を選択
                        </div>
                        <div id="dateSelector" class="date-selector">
                            <div class="date-selector-loading">
                                <i class="fas fa-spinner fa-spin"></i> 読み込み中...
                            </div>
                        </div>
                    </div>
                    <div class="form-section" id="timeSection" style="display: none;">
                        <div class="form-section-title">
                            <i class="fas fa-clock"></i> 時間を選択
                        </div>
                        <div id="timeSelector" class="time-selector">
                        </div>
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
                        <button type="button" class="btn btn-secondary" onclick="this.closest('.modal-overlay').remove()">
                            <i class="fas fa-times"></i> ${AppConstants.RESERVATION_LABELS.CANCEL_BUTTON}
                        </button>
                    </div>
                </form>
            </div>
        `;
    }

    renderDateSelector(modal) {
        const dateSelector = modal.querySelector('#dateSelector');
        if (!dateSelector) return;

        if (this.availableDates.length === 0) {
            dateSelector.innerHTML = '<div class="empty-dates"><i class="fas fa-calendar-times"></i><p>利用可能な日がありません</p></div>';
            return;
        }

        const datesHtml = this.availableDates.map(dateInfo => {
            const isSelected = this.selectedDate === dateInfo.date;
            const displayParts = dateInfo.display.split('(');
            const datePart = displayParts[0].trim();
            const dayPart = displayParts[1] ? displayParts[1].replace(')', '') : '';
            
            return `
                <button type="button" class="date-option ${isSelected ? 'selected' : ''}" 
                    data-date="${dateInfo.date}"
                    onclick="app.reservationManager.selectDate('${dateInfo.date}', this)">
                    <div class="date-option-day">${dayPart}</div>
                    <div class="date-option-date">${datePart}</div>
                </button>
            `;
        }).join('');

        dateSelector.innerHTML = `<div class="date-options-grid">${datesHtml}</div>`;
    }

    selectDate(date, buttonElement) {
        this.selectedDate = date;
        this.selectedTime = null;

        document.querySelectorAll('.date-option').forEach(btn => {
            btn.classList.remove('selected');
        });
        buttonElement.classList.add('selected');

        const dateInfo = this.availableDates.find(d => d.date === date);
        if (dateInfo) {
            this.renderTimeSelector(dateInfo.time_slots);
            const timeSection = document.querySelector('#timeSection');
            if (timeSection) {
                timeSection.style.display = 'block';
            }
        }

        this.updateSubmitButton();
    }

    renderTimeSelector(timeSlots) {
        const timeSelector = document.querySelector('#timeSelector');
        if (!timeSelector) return;

        const timesHtml = timeSlots.map(time => {
            const isSelected = this.selectedTime === time;
            return `
                <button type="button" class="time-option ${isSelected ? 'selected' : ''}" 
                    data-time="${time}"
                    onclick="app.reservationManager.selectTime('${time}', this)">
                    ${time}
                </button>
            `;
        }).join('');

        timeSelector.innerHTML = `<div class="time-options-grid">${timesHtml}</div>`;
    }

    selectTime(time, buttonElement) {
        this.selectedTime = time;

        document.querySelectorAll('.time-option').forEach(btn => {
            btn.classList.remove('selected');
        });
        buttonElement.classList.add('selected');

        this.updateSubmitButton();
    }

    updateSubmitButton() {
        const submitBtn = document.querySelector('#submitReservationBtn');
        if (submitBtn) {
            submitBtn.disabled = !(this.selectedDate && this.selectedTime);
        }
    }

    attachFormListeners(modal) {
        const form = modal.querySelector('#reservationForm');
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            await this.submitReservation(form, modal);
        });
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
                modal.remove();
                this.selectedDate = null;
                this.selectedTime = null;
                await this.loadReservations();
            } else {
                throw new Error(response.error);
            }
        } catch (error) {
            if (error.message && error.message.includes('会員登録が必要')) {
                ToastManager.error('会員登録が必要です');
                modal.remove();
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

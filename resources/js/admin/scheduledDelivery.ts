const CONDITION_TYPES = [
    'new_user_push_1',
    'new_user_push_2',
    'last_shop_push',
    'milestone_shop_push',
    'rank_up_arrival',
    'total_point_push',
    'total_stamp_push',
];

const NO_CLOCK_TYPES = ['new_user_push_1', 'rank_up_arrival'];

function initScheduledDeliveryForm(): void {
    if (document.body.dataset.pageKind !== 'scheduled-delivery-form') {
        return;
    }

    const couponLabel = document.getElementById('couponLabel');

    function setFieldsEnabled(root: Element | null, enabled: boolean): void {
        root?.querySelectorAll('input, select, textarea').forEach((field) => {
            (field as HTMLInputElement).disabled = !enabled;
        });
    }

    function selected(name: string): string {
        const el = document.querySelector(`input[name="${name}"]:checked:not(:disabled)`) as HTMLInputElement | null;
        return el ? el.value : '';
    }

    function setSelected(name: string, value: string): void {
        document.querySelectorAll(`input[name="${name}"]`).forEach((input) => {
            const radio = input as HTMLInputElement;
            radio.checked = radio.value === value;
            const label = radio.closest('.sd-choice');
            if (label) {
                label.classList.toggle('is-selected', radio.checked);
            }
        });
    }

    function syncTypeRadios(type: string): void {
        document.querySelectorAll('input[name="schedule_type"]').forEach((input) => {
            const radio = input as HTMLInputElement;
            radio.checked = radio.value === type;
        });
    }

    function currentType(): string {
        const type = selected('schedule_type');
        return type !== '' ? type : 'new_user_push_1';
    }

    function sync(): void {
        const category = selected('category') || 'condition';
        const conditionBlock = document.getElementById('scheduleTypeCondition');
        const reservationBlock = document.getElementById('scheduleTypeReservation');
        conditionBlock?.classList.toggle('is-hidden', category !== 'condition');
        reservationBlock?.classList.toggle('is-hidden', category !== 'reservation');

        let type = currentType();
        const inCondition = CONDITION_TYPES.indexOf(type) !== -1;
        if (category === 'condition' && !inCondition) {
            type = 'new_user_push_1';
        }
        if (category === 'reservation' && inCondition) {
            type = 'birthday_next_month';
        }
        setFieldsEnabled(conditionBlock, category === 'condition');
        setFieldsEnabled(reservationBlock, category === 'reservation');
        syncTypeRadios(type);

        document.querySelectorAll('.sd-param').forEach((el) => {
            const types = (el.getAttribute('data-types') || '').split(/\s+/);
            const showClock = types.indexOf('cron') !== -1;
            const visible = showClock
                ? NO_CLOCK_TYPES.indexOf(type) === -1
                : types.indexOf(type) !== -1;
            el.classList.toggle('is-hidden', !visible);
            setFieldsEnabled(el, visible);
        });

        const contentType = selected('content_type') || 'announcement';
        const announcementFields = document.getElementById('announcementFields');
        const notifyFields = document.getElementById('notifyFields');
        announcementFields?.classList.toggle('is-hidden', contentType === 'coupon_only');
        notifyFields?.classList.toggle('is-hidden', contentType !== 'coupon_only');
        setFieldsEnabled(announcementFields, contentType !== 'coupon_only');
        setFieldsEnabled(notifyFields, contentType === 'coupon_only');
        if (couponLabel) {
            couponLabel.textContent = contentType === 'coupon_only' ? '配布するクーポン' : '同時に配布するクーポン（任意）';
        }
        setSelected('content_type', contentType);
        setSelected('category', category);
    }

    document.querySelectorAll('input[name="category"], input[name="schedule_type"], input[name="content_type"]').forEach((input) => {
        input.addEventListener('change', sync);
    });
    sync();
}

export function bootstrapScheduledDeliveryForm(): void {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initScheduledDeliveryForm);
    } else {
        initScheduledDeliveryForm();
    }
}

bootstrapScheduledDeliveryForm();

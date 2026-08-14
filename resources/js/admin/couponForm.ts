function initCouponForm(): void {
    const valueUnit = document.getElementById('valueUnit');
    const valueLabel = document.getElementById('valueLabel');
    const valueExample = document.getElementById('valueExample');
    const valueInput = document.getElementById('value') as HTMLInputElement | null;
    const nameInput = document.getElementById('name') as HTMLInputElement | null;
    const descriptionInput = document.getElementById('description') as HTMLTextAreaElement | null;
    const codeInput = document.getElementById('code') as HTMLInputElement | null;
    const generateCodeButton = document.getElementById('generateCouponCode');

    function selectedValue(name: string): string {
        const el = document.querySelector(`input[name="${name}"]:checked`) as HTMLInputElement | null;
        return el ? el.value : '';
    }

    function syncChoiceCards(name: string): void {
        document.querySelectorAll(`input[name="${name}"]`).forEach((input) => {
            const label = input.closest('.cp-choice');
            if (label) {
                label.classList.toggle('is-selected', (input as HTMLInputElement).checked);
            }
        });
    }

    function syncValueUnit(): void {
        const isFixed = selectedValue('type') === 'fixed';
        if (valueUnit) valueUnit.textContent = isFixed ? '円' : '%';
        if (valueLabel) valueLabel.textContent = isFixed ? '割引額' : '割引率';
        syncValueExample();
    }

    function syncValueExample(): void {
        if (!valueExample || !valueInput) return;
        const rawValue = Number(valueInput.value);
        const displayValue = Number.isFinite(rawValue) && rawValue > 0
            ? rawValue.toLocaleString('ja-JP', { maximumFractionDigits: 2 })
            : '—';
        const suffix = selectedValue('type') === 'fixed' ? '円' : '%';
        valueExample.textContent = `会計金額から ${displayValue}${suffix} 割引します`;
    }

    function syncCounter(input: HTMLInputElement | HTMLTextAreaElement | null, counterId: string, max: number): void {
        if (!input) return;
        const counter = document.getElementById(counterId);
        if (counter) counter.textContent = `${input.value.length.toLocaleString('ja-JP')} / ${max.toLocaleString('ja-JP')}`;
    }

    function generateCouponCode(): void {
        if (!codeInput) return;
        const now = new Date();
        const date = [now.getFullYear(), now.getMonth() + 1, now.getDate()]
            .map((part, index) => index === 0 ? String(part) : String(part).padStart(2, '0'))
            .join('');
        const suffix = Math.random().toString(36).slice(2, 6).toUpperCase();
        codeInput.value = `CPN-${date}-${suffix}`;
        codeInput.dispatchEvent(new Event('input', { bubbles: true }));
        codeInput.focus();
    }

    document.querySelectorAll('input[name="type"]').forEach((input) => {
        input.addEventListener('change', () => {
            syncChoiceCards('type');
            syncValueUnit();
        });
    });
    document.querySelectorAll('input[name="image_fit"]').forEach((input) => {
        input.addEventListener('change', () => syncChoiceCards('image_fit'));
    });
    document.querySelectorAll('input[name="apply_condition_division"]').forEach((input) => {
        input.addEventListener('change', () => syncChoiceCards('apply_condition_division'));
    });
    valueInput?.addEventListener('input', syncValueExample);
    nameInput?.addEventListener('input', () => syncCounter(nameInput, 'nameCounter', 255));
    descriptionInput?.addEventListener('input', () => syncCounter(descriptionInput, 'descriptionCounter', 2000));
    generateCodeButton?.addEventListener('click', generateCouponCode);

    syncValueUnit();
    syncChoiceCards('image_fit');
    syncChoiceCards('apply_condition_division');
    syncCounter(nameInput, 'nameCounter', 255);
    syncCounter(descriptionInput, 'descriptionCounter', 2000);
}

export function bootstrapCouponForm(): void {
    const kind = document.body.dataset.pageKind;
    if (kind !== 'coupon-create' && kind !== 'coupon-edit') {
        return;
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCouponForm);
    } else {
        initCouponForm();
    }
}

bootstrapCouponForm();

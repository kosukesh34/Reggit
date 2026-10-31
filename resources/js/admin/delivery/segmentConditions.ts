import {
    checkedOptionLabels,
    checkedValues,
    field,
    fieldValue,
    formattedNumber,
    selectedOptionText,
} from './dom';
import type { SegmentCombineMode, SegmentConditionChip, SegmentConditionOperator } from './types';

/** セグメント条件: 条件カードの追加メニュー、条件の文（チップ）、送信・人数確認に使うパラメータ。 */

export const SEGMENT_COMBINE_ANY: SegmentCombineMode = 'any';
export const SEGMENT_COMBINE_ALL: SegmentCombineMode = 'all';

export function readCombineMode(form: HTMLFormElement, modeName: string): SegmentCombineMode {
    const selected = Array.from(form.querySelectorAll<HTMLInputElement>('input[type="radio"]'))
        .find((input) => input.name === modeName && input.checked && !input.disabled);
    return selected?.value === SEGMENT_COMBINE_ALL ? SEGMENT_COMBINE_ALL : SEGMENT_COMBINE_ANY;
}

export function listOperator(selectedCount: number, mode: SegmentCombineMode = SEGMENT_COMBINE_ANY): SegmentConditionOperator {
    if (selectedCount < 2) return '=';
    return mode === SEGMENT_COMBINE_ALL ? 'すべて' : 'いずれか';
}

export function conditionSentence(label: string, operator: SegmentConditionOperator, value: string): string {
    if (operator === 'すべて') return `${label}は ${value} のすべてを選択`;
    if (operator === 'いずれか') return `${label}は ${value} のいずれかを選択`;
    if (operator === '≥') return `${label}は ${value}以上`;
    if (operator === '≤') return `${label}は ${value}以下`;
    return `${label}は ${value}`;
}

function conditionItemHasValue(item: HTMLElement): boolean {
    // 初期値だけのフィールドで「条件あり」と誤判定しない
    if (item.dataset.condition === 'sales') {
        const selected = item.querySelector<HTMLInputElement>('input[name="segment_condition"]:checked');
        return selected !== null && selected.value !== '';
    }
    if (item.dataset.condition === 'last_visit') {
        return (item.querySelector<HTMLSelectElement>('#segment_last_visit_mode')?.value || '').trim() !== '';
    }
    const controls = item.querySelectorAll<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>('input, select, textarea');
    for (const control of controls) {
        if (control.dataset.ignoreEmpty === '1') continue;
        const hiddenAncestor = control.closest('[hidden]');
        if (hiddenAncestor !== null && hiddenAncestor !== item) continue;
        if (control instanceof HTMLInputElement && (control.type === 'radio' || control.type === 'checkbox')) {
            if (control.checked && control.value !== '') return true;
            continue;
        }
        if (control.value.trim() !== '') return true;
    }
    return false;
}

function clearConditionItem(item: HTMLElement): void {
    item.querySelectorAll<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>('input, select, textarea').forEach((control) => {
        if (control.dataset.ignoreEmpty === '1') return;
        if (control instanceof HTMLInputElement && (control.type === 'checkbox' || control.type === 'radio')) {
            control.checked = control.type === 'radio' && (control.value === '' || control.value === SEGMENT_COMBINE_ANY);
            return;
        }
        control.value = '';
    });
}

export function syncConditionPicker(form: HTMLFormElement): void {
    form.querySelectorAll<HTMLElement>('#segmentConditionPool [data-condition]').forEach((item) => {
        item.hidden = true;
    });
}

export function bindConditionPicker(form: HTMLFormElement): void {
    const list = form.querySelector<HTMLElement>('#segmentConditionRows');
    const template = form.querySelector<HTMLTemplateElement>('#segmentConditionRowTemplate');
    const pool = form.querySelector<HTMLElement>('#segmentConditionPool');
    const addButton = form.querySelector<HTMLButtonElement>('#segmentAddCondition');
    const menu = form.querySelector<HTMLElement>('#segmentConditionMenu');
    if (list === null || template === null || pool === null || addButton === null || menu === null || addButton.dataset.bound === '1') return;
    addButton.dataset.bound = '1';

    const groupButtons = Array.from(menu.querySelectorAll<HTMLButtonElement>('[data-menu-group]'));
    const fieldButtons = Array.from(menu.querySelectorAll<HTMLButtonElement>('[data-menu-field]'));
    const visibleFields = (): HTMLButtonElement[] => fieldButtons.filter((button) => button.parentElement?.hidden !== true && !button.disabled);
    const activeGroup = (): HTMLButtonElement | undefined => groupButtons.find((button) => button.getAttribute('aria-pressed') === 'true');

    const showGroup = (group: string): void => {
        const used = new Set(Array.from(list.querySelectorAll<HTMLElement>('[data-condition]')).map((item) => item.dataset.condition));
        groupButtons.forEach((button) => button.setAttribute('aria-pressed', String(button.dataset.menuGroup === group)));
        fieldButtons.forEach((button) => {
            if (button.parentElement) button.parentElement.hidden = button.dataset.group !== group;
            button.disabled = used.has(button.dataset.menuField);
        });
    };

    const closeMenu = (restoreFocus: boolean): void => {
        menu.hidden = true;
        addButton.setAttribute('aria-expanded', 'false');
        if (restoreFocus) addButton.focus();
    };

    const openMenu = (): void => {
        menu.hidden = false;
        addButton.setAttribute('aria-expanded', 'true');
        showGroup(groupButtons[0]?.dataset.menuGroup ?? '');
        groupButtons[0]?.focus();
    };

    const addCard = (key: string, focus: boolean): void => {
        const item = pool.querySelector<HTMLElement>(`[data-condition="${CSS.escape(key)}"]`);
        const fieldButton = fieldButtons.find((button) => button.dataset.menuField === key);
        const card = template.content.firstElementChild?.cloneNode(true);
        if (item === null || fieldButton === undefined || !(card instanceof HTMLElement)) return;
        const title = fieldButton.textContent?.trim() ?? '';
        const groupLabel = groupButtons.find((button) => button.dataset.menuGroup === fieldButton.dataset.group)?.textContent?.trim() ?? '';
        const groupElement = card.querySelector<HTMLElement>('.ad-cond-card-group');
        const titleElement = card.querySelector<HTMLElement>('.ad-cond-card-title');
        const remove = card.querySelector<HTMLButtonElement>('.ad-cond-card-remove');
        const body = card.querySelector<HTMLElement>('.ad-cond-card-body');
        if (groupElement) groupElement.textContent = groupLabel;
        if (titleElement) titleElement.textContent = title;
        remove?.setAttribute('aria-label', `${title}の条件を外す`);
        remove?.addEventListener('click', () => {
            clearConditionItem(item);
            item.hidden = true;
            pool.appendChild(item);
            card.remove();
            item.querySelector<HTMLElement>('.segment-filter')?.dispatchEvent(new Event('change', { bubbles: true }));
            addButton.focus();
        });
        item.hidden = false;
        body?.appendChild(item);
        list.appendChild(card);
        if (focus) item.querySelector<HTMLElement>('input, select, textarea')?.focus();
    };

    addButton.addEventListener('click', () => {
        if (menu.hidden) openMenu();
        else closeMenu(false);
    });
    groupButtons.forEach((button) => {
        const select = (): void => showGroup(button.dataset.menuGroup ?? '');
        button.addEventListener('click', select);
        button.addEventListener('mouseenter', select);
        button.addEventListener('focus', select);
        button.addEventListener('keydown', (event) => {
            if (event.key !== 'ArrowRight') return;
            event.preventDefault();
            visibleFields()[0]?.focus();
        });
    });
    fieldButtons.forEach((button) => {
        button.addEventListener('click', () => {
            closeMenu(false);
            addCard(button.dataset.menuField ?? '', true);
        });
        button.addEventListener('keydown', (event) => {
            if (event.key !== 'ArrowLeft') return;
            event.preventDefault();
            activeGroup()?.focus();
        });
    });
    menu.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            event.preventDefault();
            closeMenu(true);
            return;
        }
        if (event.key !== 'ArrowDown' && event.key !== 'ArrowUp') return;
        const current = document.activeElement;
        const column = current instanceof HTMLButtonElement && current.dataset.menuGroup !== undefined ? groupButtons : visibleFields();
        const index = column.findIndex((button) => button === current);
        if (index === -1) return;
        event.preventDefault();
        column[(index + (event.key === 'ArrowDown' ? 1 : -1) + column.length) % column.length]?.focus();
    });
    document.addEventListener('click', (event) => {
        if (menu.hidden || !(event.target instanceof Node)) return;
        if (!menu.contains(event.target) && !addButton.contains(event.target)) closeMenu(false);
    });

    Array.from(pool.querySelectorAll<HTMLElement>('[data-condition]'))
        .filter((item) => conditionItemHasValue(item))
        .forEach((item) => addCard(item.dataset.condition ?? '', false));

    // 未使用条件・見た目だけのカードは送信しない（初期値のままだと絞り込みが汚れる）
    if (form.dataset.segmentSubmitBound !== '1') {
        form.dataset.segmentSubmitBound = '1';
        form.addEventListener('submit', () => {
            const disable = (root: ParentNode): void => {
                root.querySelectorAll<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>('input, select, textarea').forEach((control) => {
                    control.disabled = true;
                });
            };
            disable(pool);
            list.querySelectorAll<HTMLElement>('[data-condition]').forEach((item) => {
                if (!conditionItemHasValue(item)) disable(item);
            });
            const match = form.querySelector<HTMLElement>('#segmentMatchMode');
            if (match !== null && match.hidden) disable(match);
        });
    }
}

function notifyCleared(control: HTMLElement | undefined): void {
    control?.dispatchEvent(new Event('change', { bubbles: true }));
}

function clearNamed(form: HTMLFormElement, name: string): void {
    const inputs = Array.from(form.querySelectorAll<HTMLInputElement>(`input[name="${name}"]`));
    inputs.forEach((input) => { input.checked = false; });
    notifyCleared(inputs[0]);
}

function clearIds(ids: string[]): void {
    const controls = ids
        .map((id) => field<HTMLInputElement | HTMLSelectElement>(id))
        .filter((control): control is HTMLInputElement | HTMLSelectElement => control !== null);
    controls.forEach((control) => {
        if (control instanceof HTMLInputElement && control.type === 'checkbox') {
            control.checked = false;
        } else {
            control.value = '';
        }
    });
    notifyCleared(controls[0]);
}

function clearSalesCondition(form: HTMLFormElement): void {
    const empty = form.querySelector<HTMLInputElement>('input[name="segment_condition"][value=""]');
    if (empty === null) return;
    empty.checked = true;
    notifyCleared(empty);
}

function checksByName(form: HTMLFormElement, name: string): HTMLInputElement[] {
    return Array.from(form.querySelectorAll<HTMLInputElement>('input[type="checkbox"]'))
        .filter((input) => input.name === name);
}

export function syncValueModes(form: HTMLFormElement): void {
    form.querySelectorAll<HTMLElement>('[data-value-mode]').forEach((block) => {
        const checkedCount = checksByName(form, block.dataset.valueMode ?? '').filter((input) => input.checked).length;
        const visible = checkedCount >= 2;
        block.hidden = !visible;
        block.querySelectorAll<HTMLInputElement>('input').forEach((input) => {
            input.disabled = !visible;
        });
    });
}

function segmentConditionChips(form: HTMLFormElement): SegmentConditionChip[] {
    const chips: SegmentConditionChip[] = [];
    const add = (label: string, operator: SegmentConditionChip['operator'], value: string, clear: () => void): void => {
        if (value !== '') chips.push({ label, operator, value, clear });
    };

    const groupLabels = checkedOptionLabels('segment_audience_group_ids[]');
    add('保存したグループ', listOperator(groupLabels.length), groupLabels.join('、'), () => clearNamed(form, 'segment_audience_group_ids[]'));
    add('登録チャネル', '=', selectedOptionText('segment_channel'), () => clearIds(['segment_channel']));
    const birthdayMonths = checkedValues('segment_birthday_months[]').map((month) => `${month}月`);
    add('誕生月', listOperator(birthdayMonths.length), birthdayMonths.join('、'), () => clearNamed(form, 'segment_birthday_months[]'));
    add('性別', '=', selectedOptionText('segment_sex'), () => clearIds(['segment_sex']));
    const rfmLabels = checkedOptionLabels('segment_rfm_segments[]');
    add('RFMセグメント', listOperator(rfmLabels.length), rfmLabels.join('、'), () => clearNamed(form, 'segment_rfm_segments[]'));
    const tagLabels = checkedOptionLabels('segment_tag_ids[]');
    add('会員タグ', listOperator(tagLabels.length, readCombineMode(form, 'segment_value_mode[tags]')), tagLabels.join('、'), () => clearNamed(form, 'segment_tag_ids[]'));

    const visitMin = fieldValue('segment_visit_count_min');
    const visitMax = fieldValue('segment_visit_count_max');
    add('来店回数', '=', visitMin !== '' && visitMax !== ''
        ? `${formattedNumber(visitMin)}〜${formattedNumber(visitMax)}回`
        : visitMin !== '' ? `${formattedNumber(visitMin)}回以上` : visitMax !== '' ? `${formattedNumber(visitMax)}回以下` : '', () => clearIds(['segment_visit_count_min', 'segment_visit_count_max']));

    add('会員ランク', '=', fieldValue('segment_rank'), () => clearIds(['segment_rank']));
    const pointsMin = fieldValue('segment_lifetime_points_min');
    const pointsMax = fieldValue('segment_lifetime_points_max');
    add('累計付与ポイント', '=', pointsMin !== '' && pointsMax !== ''
        ? `${formattedNumber(pointsMin)}〜${formattedNumber(pointsMax)}pt`
        : pointsMin !== '' ? `${formattedNumber(pointsMin)}pt以上` : pointsMax !== '' ? `${formattedNumber(pointsMax)}pt以下` : '', () => clearIds(['segment_lifetime_points_min', 'segment_lifetime_points_max']));

    const lastVisitMode = fieldValue('segment_last_visit_mode');
    const lastVisitDays = fieldValue('segment_last_visit_days');
    const clearLastVisit = (): void => clearIds(['segment_last_visit_mode']);
    if (lastVisitMode === 'inactive_days' && lastVisitDays !== '') {
        add('最終来店', '=', `${formattedNumber(lastVisitDays)}日以上来店なし`, clearLastVisit);
    } else if (lastVisitMode === 'visited_within_days' && lastVisitDays !== '') {
        add('最終来店', '=', `${formattedNumber(lastVisitDays)}日以内に来店あり`, clearLastVisit);
    } else if (lastVisitMode === 'days_after' && lastVisitDays !== '') {
        add('最終来店', '=', `${formattedNumber(lastVisitDays)}日後`, clearLastVisit);
    }

    add('購入商品', '=', selectedOptionText('segment_product_id'), () => clearIds(['segment_product_id']));
    const heldCouponName = selectedOptionText('segment_coupon_id');
    const heldCouponStatus = fieldValue('segment_coupon_status');
    const heldCouponStatusLabel = heldCouponStatus === 'unused' ? '未使用' : heldCouponStatus === 'used' ? '使用済み' : '';
    add(
        '保有クーポン',
        '=',
        heldCouponName !== '' && heldCouponStatusLabel !== '' ? `${heldCouponName}（${heldCouponStatusLabel}）` : '',
        () => clearIds(['segment_coupon_id', 'segment_coupon_status']),
    );
    if (field<HTMLInputElement>('event_rank_up')?.checked === true) {
        add('ランクアップ達成', '=', fieldValue('segment_rank') || '対象ランクを指定', () => clearIds(['event_rank_up', 'segment_rank']));
    }
    add('スタンプカード', '=', selectedOptionText('stamp_card_id'), () => clearIds(['stamp_card_id']));
    const stampMin = fieldValue('stamp_count_min');
    add('スタンプ累計', '≥', stampMin === '' ? '' : `${formattedNumber(stampMin)}個`, () => clearIds(['stamp_count_min']));
    if (field<HTMLInputElement>('stamp_completed')?.checked === true) {
        add('スタンプ', '=', '達成済み', () => clearIds(['stamp_completed']));
    }

    const salesCondition = document.querySelector<HTMLInputElement>('input[name="segment_condition"]:checked')?.value || '';
    const period = selectedOptionText('segment_period_days');
    if (salesCondition === 'min_amount') {
        const minAmount = fieldValue('segment_min_amount');
        add(`購入額（${period}）`, '≥', minAmount === '' ? '' : `${formattedNumber(minAmount)}円`, () => clearSalesCondition(form));
    } else if (salesCondition === 'no_orders') {
        add(`購入履歴（${period}）`, '=', 'なし', () => clearSalesCondition(form));
    }

    form.querySelectorAll<HTMLElement>('.ad-profile-filter').forEach((group) => {
        const inputs = Array.from(group.querySelectorAll<HTMLInputElement>('[name^="segment_profile["]'));
        const values = inputs
            .filter((input) => input.type !== 'checkbox' || input.checked)
            .map((input) => input.value.trim()).filter(Boolean);
        const modeName = `segment_value_mode[profile][${group.dataset.profileKey || ''}]`;
        const selectedCount = inputs.filter((input) => input.type === 'checkbox' && input.checked).length;
        const profileOperator = listOperator(selectedCount, readCombineMode(form, modeName));
        add(group.dataset.profileLabel || 'プロフィール', profileOperator, values.join('、'), () => {
            inputs.forEach((input) => {
                if (input.type === 'checkbox') input.checked = false;
                else input.value = '';
            });
            notifyCleared(inputs[0]);
        });
    });

    return chips;
}

export function renderSegmentConditionSummary(form: HTMLFormElement, isSegment: boolean): void {
    const block = field<HTMLElement>('deliverySummaryConditionBlock');
    const container = field<HTMLElement>('deliverySummaryConditions');
    if (!block || !container) return;

    const chips = isSegment ? segmentConditionChips(form) : [];
    const matchMode = field<HTMLElement>('segmentMatchMode');
    const groupSave = field<HTMLElement>('segmentGroupSave');
    block.hidden = !isSegment || chips.length === 0;
    if (matchMode) matchMode.hidden = chips.length < 2;
    syncValueModes(form);
    syncConditionPicker(form);
    if (groupSave && groupSave.dataset.keepOpen !== '1') groupSave.hidden = chips.length === 0;
    container.replaceChildren();
    if (!isSegment || chips.length === 0) return;

    chips.forEach((condition) => {
        const chip = document.createElement('div');
        chip.className = 'ad-condition-chip';
        const text = document.createElement('span');
        text.className = 'ad-condition-text';
        text.textContent = conditionSentence(condition.label, condition.operator, condition.value);
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'ad-condition-remove';
        remove.textContent = '×';
        remove.setAttribute('aria-label', `${condition.label}の条件を削除`);
        remove.addEventListener('click', () => condition.clear());
        chip.append(text, remove);
        container.appendChild(chip);
    });
}

export function segmentParams(form: HTMLFormElement): URLSearchParams {
    const params = new URLSearchParams();
    [
        'segment_channel', 'segment_visit_count_min', 'segment_visit_count_max',
        'segment_lifetime_points_min', 'segment_lifetime_points_max', 'segment_last_visit_mode',
        'segment_last_visit_days', 'segment_sex', 'segment_rank', 'segment_product_id',
        'segment_coupon_id', 'segment_coupon_status',
        'segment_period_days', 'segment_min_amount',
        'stamp_card_id', 'stamp_count_min',
    ].forEach((id) => {
        const element = field<HTMLInputElement | HTMLSelectElement>(id);
        if (element && !element.disabled && element.value !== '') params.set(id, element.value);
    });
    checkedValues('segment_birthday_months[]').forEach((value) => params.append('segment_birthday_months[]', value));
    checkedValues('segment_tag_ids[]').forEach((value) => params.append('segment_tag_ids[]', value));
    checkedValues('segment_rfm_segments[]').forEach((value) => params.append('segment_rfm_segments[]', value));
    checkedValues('segment_audience_group_ids[]').forEach((value) => params.append('segment_audience_group_ids[]', value));
    form.querySelectorAll<HTMLInputElement>('input[name^="segment_value_mode["]').forEach((input) => {
        if (input.checked && !input.disabled) params.append(input.name, input.value);
    });
    const matchMode = form.querySelector<HTMLInputElement>('input[name="segment_match_mode"]:checked');
    if (matchMode && !matchMode.disabled) params.set('segment_match_mode', matchMode.value);
    const condition = document.querySelector<HTMLInputElement>('input[name="segment_condition"]:checked')?.value || '';
    if (condition !== '') params.set('segment_condition', condition);
    if (field<HTMLInputElement>('stamp_completed')?.checked === true) params.set('stamp_completed', '1');
    form.querySelectorAll<HTMLInputElement | HTMLSelectElement>('[name^="segment_profile["]')
        .forEach((control) => {
            if (control.disabled) return;
            if (control instanceof HTMLInputElement && control.type === 'checkbox' && !control.checked) return;
            if (control instanceof HTMLSelectElement && control.multiple) {
                Array.from(control.selectedOptions).forEach((option) => params.append(control.name, option.value));
            } else if (control.value.trim() !== '') {
                params.append(control.name, control.value);
            }
        });
    return params;
}

export function hasSegmentCriteria(form: HTMLFormElement): boolean {
    return [
        'segment_channel', 'segment_visit_count_min', 'segment_visit_count_max',
        'segment_lifetime_points_min', 'segment_lifetime_points_max', 'segment_last_visit_mode',
        'segment_sex', 'segment_rank', 'segment_product_id',
        'segment_coupon_id', 'segment_coupon_status',
    ].some((id) => (field<HTMLInputElement | HTMLSelectElement>(id)?.value || '').trim() !== '')
        || checkedValues('segment_birthday_months[]').length > 0
        || checkedValues('segment_tag_ids[]').length > 0
        || checkedValues('segment_rfm_segments[]').length > 0
        || checkedValues('segment_audience_group_ids[]').length > 0
        || (document.querySelector<HTMLInputElement>('input[name="segment_condition"]:checked')?.value || '') !== ''
        || field<HTMLInputElement>('event_rank_up')?.checked === true
        || (field<HTMLInputElement | HTMLSelectElement>('stamp_card_id')?.value || '').trim() !== ''
        || (field<HTMLInputElement>('stamp_count_min')?.value || '').trim() !== ''
        || field<HTMLInputElement>('stamp_completed')?.checked === true
        || Array.from(form.querySelectorAll<HTMLInputElement | HTMLSelectElement>('[name^="segment_profile["]'))
            .some((control) => control instanceof HTMLSelectElement && control.multiple
                ? control.selectedOptions.length > 0
                : control instanceof HTMLInputElement && control.type === 'checkbox'
                    ? control.checked
                    : control.value.trim() !== '');
}

/** 人数プレビューを持たない画面（スケジュール配信・会員一覧の検索）で条件ビルダーだけを動かす */
export function initScheduledSegmentUi(): void {
    const form = document.querySelector('form[data-segment-form]');
    if (!(form instanceof HTMLFormElement)) return;

    const checksByName = (name: string): HTMLInputElement[] => Array.from(form.querySelectorAll<HTMLInputElement>('input[type="checkbox"]')).filter((input) => input.name === name);
    const checkedLabels = (name: string): string[] => checksByName(name).filter((input) => input.checked).map((input) => input.dataset.label?.trim() || input.value);

    const selectedOptionText = (id: string): string => {
        const element = form.querySelector<HTMLSelectElement>(`#${CSS.escape(id)}`);
        if (element === null || element.value === '') return '';
        return element.selectedOptions[0]?.textContent?.trim() || '';
    };
    const fieldValue = (id: string): string => (form.querySelector<HTMLInputElement | HTMLSelectElement>(`#${CSS.escape(id)}`)?.value || '').trim();

    const render = (): void => {
        form.querySelectorAll<HTMLElement>('[data-value-mode]').forEach((block) => {
            const visible = checksByName(block.dataset.valueMode ?? '').filter((input) => input.checked).length >= 2;
            block.hidden = !visible;
            block.querySelectorAll<HTMLInputElement>('input').forEach((input) => { input.disabled = !visible; });
        });
        const minAmount = document.getElementById('segmentMinAmountBlock');
        const sales = form.querySelector<HTMLInputElement>('input[name="segment_condition"]:checked')?.value || '';
        if (minAmount) minAmount.hidden = sales !== 'min_amount';

        const container = document.getElementById('deliverySummaryConditions');
        const block = document.getElementById('deliverySummaryConditionBlock');
        const match = document.getElementById('segmentMatchMode');
        const save = document.getElementById('segmentGroupSave');
        if (!container || !block) return;
        const chips: string[] = [];
        const push = (label: string, operator: SegmentConditionOperator, value: string): void => {
            if (value !== '') chips.push(conditionSentence(label, operator, value));
        };
        push('保存したグループ', listOperator(checkedLabels('segment_audience_group_ids[]').length), checkedLabels('segment_audience_group_ids[]').join('、'));
        push('登録チャネル', '=', selectedOptionText('segment_channel'));
        const months = checksByName('segment_birthday_months[]').filter((input) => input.checked).map((input) => `${input.value}月`);
        push('誕生月', listOperator(months.length), months.join('、'));
        push('性別', '=', selectedOptionText('segment_sex'));
        const rfm = checkedLabels('segment_rfm_segments[]');
        push('RFMセグメント', listOperator(rfm.length), rfm.join('、'));
        const tags = checkedLabels('segment_tag_ids[]');
        push('会員タグ', listOperator(tags.length, readCombineMode(form, 'segment_value_mode[tags]')), tags.join('、'));
        const visitMin = fieldValue('segment_visit_count_min');
        const visitMax = fieldValue('segment_visit_count_max');
        push('来店回数', '=', visitMin !== '' && visitMax !== ''
            ? `${visitMin}〜${visitMax}回`
            : visitMin !== '' ? `${visitMin}回以上` : visitMax !== '' ? `${visitMax}回以下` : '');
        push('会員ランク', '=', fieldValue('segment_rank'));
        const pointsMin = fieldValue('segment_lifetime_points_min');
        const pointsMax = fieldValue('segment_lifetime_points_max');
        push('累計付与ポイント', '=', pointsMin !== '' && pointsMax !== ''
            ? `${pointsMin}〜${pointsMax}pt`
            : pointsMin !== '' ? `${pointsMin}pt以上` : pointsMax !== '' ? `${pointsMax}pt以下` : '');
        const lastVisitMode = fieldValue('segment_last_visit_mode');
        const lastVisitDays = fieldValue('segment_last_visit_days');
        if (lastVisitMode === 'inactive_days' && lastVisitDays !== '') push('最終来店', '=', `${lastVisitDays}日以上来店なし`);
        if (lastVisitMode === 'visited_within_days' && lastVisitDays !== '') push('最終来店', '=', `${lastVisitDays}日以内に来店あり`);
        if (lastVisitMode === 'days_after' && lastVisitDays !== '') push('最終来店', '=', `${lastVisitDays}日後`);
        push('購入商品', '=', selectedOptionText('segment_product_id'));
        const heldCouponName = selectedOptionText('segment_coupon_id');
        const heldCouponStatus = fieldValue('segment_coupon_status');
        const heldCouponStatusLabel = heldCouponStatus === 'unused' ? '未使用' : heldCouponStatus === 'used' ? '使用済み' : '';
        push('保有クーポン', '=', heldCouponName !== '' && heldCouponStatusLabel !== '' ? `${heldCouponName}（${heldCouponStatusLabel}）` : '');
        const period = selectedOptionText('segment_period_days');
        if (sales === 'min_amount') {
            const amount = fieldValue('segment_min_amount');
            push(`購入額（${period}）`, '≥', amount === '' ? '' : `${amount}円`);
        } else if (sales === 'no_orders') {
            push(`購入履歴（${period}）`, '=', 'なし');
        }
        form.querySelectorAll<HTMLElement>('.ad-profile-filter').forEach((group) => {
            const inputs = Array.from(group.querySelectorAll<HTMLInputElement>('[name^="segment_profile["]'));
            const values = inputs.filter((input) => input.type !== 'checkbox' || input.checked).map((input) => input.value.trim()).filter(Boolean);
            const selectedCount = inputs.filter((input) => input.type === 'checkbox' && input.checked).length;
            const modeName = `segment_value_mode[profile][${group.dataset.profileKey || ''}]`;
            const operator = listOperator(selectedCount, readCombineMode(form, modeName));
            push(group.dataset.profileLabel || 'プロフィール', operator, values.join('、'));
        });
        block.hidden = chips.length === 0;
        if (match) match.hidden = chips.length < 2;
        if (save && save.dataset.keepOpen !== '1') save.hidden = chips.length === 0;
        syncConditionPicker(form);
        container.replaceChildren();
        chips.forEach((text) => {
            const row = document.createElement('div');
            row.className = 'ad-condition-chip';
            const label = document.createElement('span');
            label.className = 'ad-condition-text';
            label.textContent = text;
            row.append(label);
            container.appendChild(row);
        });
    };

    form.querySelectorAll<HTMLInputElement | HTMLSelectElement>('.segment-filter').forEach((control) => {
        control.addEventListener('change', render);
        control.addEventListener('input', render);
    });
    bindConditionPicker(form);
    render();
}

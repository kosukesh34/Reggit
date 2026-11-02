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
        const selected = item.querySelector<HTMLInputElement>('input[type="radio"]:checked');
        return selected !== null && selected.value !== '';
    }
    if (item.dataset.condition === 'last_visit') {
        return (item.querySelector<HTMLSelectElement>('select')?.value || '').trim() !== '';
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

/**
 * まとまり1の1つ目の条件はそのままの name/id。2つ目以降の同じ種類の条件は segment_more[n]、
 * まとまり2以降の条件は segment_blocks[k][segment_more][n] を name の先頭に付けて区別する（data-more に保持）。
 */
type SegmentScope = { id: (id: string) => string; name: (name: string) => string; block: string };

const BASE_SCOPE: SegmentScope = { id: (id) => id, name: (name) => name, block: '' };

function extraScope(prefix: string, block = ''): SegmentScope {
    const suffix = prefix.replace(/[^a-z0-9]+/gi, '-').replace(/-+$/, '');
    return {
        id: (id) => `${id}--${suffix}`,
        name: (name) => name.replace(/^([^[]+)/, `${prefix}[$1]`),
        block,
    };
}

function extraItems(root: ParentNode): HTMLElement[] {
    return Array.from(root.querySelectorAll<HTMLElement>('.ad-cond-list [data-more]'));
}

function segmentScopes(form: HTMLFormElement): SegmentScope[] {
    return [BASE_SCOPE, ...extraItems(form).map((item) => extraScope(
        item.dataset.more ?? '',
        item.closest<HTMLElement>('[data-block]')?.querySelector('.ad-cond-block-title')?.textContent?.trim() ?? '',
    ))];
}

/** まとまり1（既存の「条件の組み合わせ」が対象にする範囲）の条件数 */
function firstBlockConditionCount(form: HTMLFormElement): number {
    const list = form.querySelector<HTMLElement>('#segmentConditionRows');
    const scopes = [BASE_SCOPE, ...(list === null ? [] : extraItems(list).map((item) => extraScope(item.dataset.more ?? '')))];
    return scopes.flatMap((scope) => scopedConditionChips(form, scope)).length;
}

/** 再描画のきっかけだけを伝える（外した条件の入力はもう DOM にないため） */
function notifySegmentChanged(form: HTMLFormElement): void {
    form.querySelector<HTMLElement>('.segment-filter')?.dispatchEvent(new Event('change', { bubbles: true }));
}

export function syncConditionPicker(form: HTMLFormElement): void {
    form.querySelectorAll<HTMLElement>('#segmentConditionPool [data-condition]').forEach((item) => {
        item.hidden = true;
    });
}

type ConditionTarget = { list: HTMLElement; button: HTMLButtonElement; prefix: string };

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
    const firstTarget: ConditionTarget = { list, button: addButton, prefix: '' };
    let target = firstTarget;

    const showGroup = (group: string): void => {
        groupButtons.forEach((button) => button.setAttribute('aria-pressed', String(button.dataset.menuGroup === group)));
        fieldButtons.forEach((button) => {
            if (button.parentElement) button.parentElement.hidden = button.dataset.group !== group;
        });
    };

    // 同じ種類の条件・まとまり2以降の条件の複製元（値を入れる前の状態）
    const templates = new Map(Array.from(pool.querySelectorAll<HTMLElement>('[data-condition]'))
        .map((item) => [item.dataset.condition ?? '', item.cloneNode(true) as HTMLElement]));
    let extraCount = 0;
    const createExtra = (key: string, blockPrefix: string): HTMLElement | null => {
        const source = templates.get(key);
        if (source === undefined) return null;
        const item = source.cloneNode(true) as HTMLElement;
        const prefix = blockPrefix === '' ? `segment_more[${extraCount++}]` : `${blockPrefix}[segment_more][${extraCount++}]`;
        const scope = extraScope(prefix);
        item.dataset.more = prefix;
        item.querySelector('[name="event_rank_up"]')?.closest('label')?.remove();
        item.querySelectorAll<HTMLElement>('[id]').forEach((element) => { element.id = scope.id(element.id); });
        item.querySelectorAll<HTMLLabelElement>('label[for]').forEach((label) => { label.htmlFor = scope.id(label.htmlFor); });
        item.querySelectorAll<HTMLElement>('[name]').forEach((element) => element.setAttribute('name', scope.name(element.getAttribute('name') ?? '')));
        item.querySelectorAll<HTMLElement>('[data-value-mode]').forEach((element) => { element.dataset.valueMode = scope.name(element.dataset.valueMode ?? ''); });
        clearConditionItem(item);
        const minAmount = item.querySelector<HTMLElement>(`#${CSS.escape(scope.id('segmentMinAmountBlock'))}`);
        if (minAmount !== null) {
            const syncMinAmount = (): void => {
                const active = item.querySelector<HTMLInputElement>('input[type="radio"]:checked')?.value === 'min_amount';
                minAmount.hidden = !active;
                minAmount.querySelectorAll<HTMLInputElement>('input').forEach((input) => { input.disabled = !active; });
            };
            item.addEventListener('change', syncMinAmount);
            syncMinAmount();
        }
        return item;
    };

    const closeMenu = (restoreFocus: boolean): void => {
        menu.hidden = true;
        target.button.setAttribute('aria-expanded', 'false');
        if (restoreFocus) target.button.focus();
    };

    const openMenu = (next: ConditionTarget): void => {
        if (!menu.hidden) closeMenu(false);
        target = next;
        next.button.parentElement?.appendChild(menu);
        menu.hidden = false;
        next.button.setAttribute('aria-expanded', 'true');
        showGroup(groupButtons[0]?.dataset.menuGroup ?? '');
        groupButtons[0]?.focus();
    };

    const addCard = (key: string, focus: boolean, into: ConditionTarget, extra = false): HTMLElement | null => {
        const original = extra || into.prefix !== '' ? null : pool.querySelector<HTMLElement>(`[data-condition="${CSS.escape(key)}"]`);
        const item = original ?? createExtra(key, into.prefix);
        const fieldButton = fieldButtons.find((button) => button.dataset.menuField === key);
        const card = template.content.firstElementChild?.cloneNode(true);
        if (item === null || fieldButton === undefined || !(card instanceof HTMLElement)) return null;
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
            card.remove();
            if (item.dataset.more === undefined) {
                item.hidden = true;
                pool.appendChild(item);
            }
            notifySegmentChanged(form);
            into.button.focus();
        });
        item.hidden = false;
        body?.appendChild(item);
        into.list.appendChild(card);
        if (focus) item.querySelector<HTMLElement>('input, select, textarea')?.focus();
        return item;
    };

    // まとまり2以降。まとまり同士は常に「すべて一致」で、まとまりの中の組み合わせだけを選ぶ
    const blocksRoot = document.createElement('div');
    blocksRoot.className = 'ad-cond-blocks';
    const addBlockButton = document.createElement('button');
    addBlockButton.type = 'button';
    addBlockButton.className = 'ad-cond-add-block';
    addBlockButton.innerHTML = '<i class="fas fa-layer-group" aria-hidden="true"></i>まとまりを追加';
    addButton.parentElement?.after(blocksRoot, addBlockButton);
    const matchLegend = form.querySelector<HTMLElement>('#segmentMatchMode legend');
    const matchLegendText = matchLegend?.textContent ?? '';
    let blockCount = 0;
    const renumberBlocks = (): void => {
        const blocks = Array.from(blocksRoot.querySelectorAll<HTMLElement>('[data-block]'));
        blocks.forEach((block, index) => {
            const title = block.querySelector<HTMLElement>('.ad-cond-block-title');
            if (title) title.textContent = `まとまり${index + 2}`;
        });
        if (matchLegend) matchLegend.textContent = blocks.length > 0 ? `まとまり1の${matchLegendText}` : matchLegendText;
    };
    const createBlock = (mode: string): ConditionTarget => {
        const prefix = `segment_blocks[${blockCount++}]`;
        const block = document.createElement('section');
        block.className = 'ad-cond-block';
        block.dataset.block = prefix;
        block.innerHTML = `<div class="ad-cond-block-head">
                <span class="ad-cond-block-join">かつ</span>
                <strong class="ad-cond-block-title"></strong>
                <label class="ad-cond-block-mode">条件の組み合わせ
                    <select class="form-control segment-filter" name="${prefix}[segment_match_mode]">
                        <option value="${SEGMENT_COMBINE_ANY}">いずれか一致</option>
                        <option value="${SEGMENT_COMBINE_ALL}">すべて一致</option>
                    </select>
                </label>
                <button type="button" class="ad-cond-card-remove"><i class="fas fa-times" aria-hidden="true"></i></button>
            </div>
            <div class="ad-cond-list"></div>
            <div class="ad-cond-add"><button type="button" class="ad-cond-add-btn" aria-expanded="false"><i class="fas fa-plus" aria-hidden="true"></i>条件を追加</button></div>`;
        const select = block.querySelector<HTMLSelectElement>('select');
        if (select) select.value = mode === SEGMENT_COMBINE_ALL ? SEGMENT_COMBINE_ALL : SEGMENT_COMBINE_ANY;
        const blockList = block.querySelector<HTMLElement>('.ad-cond-list');
        const blockButton = block.querySelector<HTMLButtonElement>('.ad-cond-add-btn');
        if (blockList === null || blockButton === null) throw new Error('condition block markup is incomplete');
        const blockTarget: ConditionTarget = { list: blockList, button: blockButton, prefix };
        blockButton.setAttribute('aria-controls', 'segmentConditionMenu');
        blockButton.addEventListener('click', () => {
            if (menu.hidden || target !== blockTarget) openMenu(blockTarget);
            else closeMenu(false);
        });
        const removeBlock = block.querySelector<HTMLButtonElement>('.ad-cond-block-head .ad-cond-card-remove');
        removeBlock?.addEventListener('click', () => {
            if (target === blockTarget) {
                closeMenu(false);
                target = firstTarget;
                addButton.parentElement?.appendChild(menu);
            }
            block.remove();
            renumberBlocks();
            notifySegmentChanged(form);
            addBlockButton.focus();
        });
        blocksRoot.appendChild(block);
        renumberBlocks();
        removeBlock?.setAttribute('aria-label', `${block.querySelector('.ad-cond-block-title')?.textContent ?? 'まとまり'}を削除`);
        return blockTarget;
    };
    addBlockButton.addEventListener('click', () => {
        const blockTarget = createBlock(SEGMENT_COMBINE_ANY);
        notifySegmentChanged(form);
        openMenu(blockTarget);
    });

    // 保存済み・送信し直しの segment_more / segment_blocks を条件カードとして戻す
    const conditionKeyOf = (entry: Record<string, unknown>): string | undefined => {
        for (const [name, value] of Object.entries(entry)) {
            if (name === 'segment_value_mode') continue;
            if (name === 'segment_profile') {
                const profileKey = value !== null && typeof value === 'object' ? Object.keys(value)[0] : undefined;
                if (profileKey !== undefined && templates.has(`profile-${profileKey}`)) return `profile-${profileKey}`;
                continue;
            }
            for (const [key, item] of templates) {
                if (item.querySelector(`[name="${name}"], [name="${name}[]"]`) !== null) return key;
            }
        }
        return undefined;
    };
    const fillExtra = (item: HTMLElement, entry: Record<string, unknown>): void => {
        const prefix = item.dataset.more ?? '';
        item.querySelectorAll<HTMLInputElement | HTMLSelectElement>('input[name], select[name]').forEach((control) => {
            const path = (control.name.slice(prefix.length).match(/\[[^\]]*\]/g) ?? []).map((part) => part.slice(1, -1));
            if (path[path.length - 1] === '') path.pop();
            const value = path.reduce<unknown>((current, key) => (current !== null && typeof current === 'object' ? (current as Record<string, unknown>)[key] : undefined), entry);
            if (value === undefined || value === null) return;
            const values = (typeof value === 'object' ? Object.values(value) : [value]).map(String);
            if (control instanceof HTMLInputElement && (control.type === 'checkbox' || control.type === 'radio')) {
                control.checked = values.includes(control.value);
            } else if (control instanceof HTMLSelectElement && control.multiple) {
                Array.from(control.options).forEach((option) => { option.selected = values.includes(option.value); });
            } else {
                control.value = values[0] ?? '';
            }
        });
        item.dispatchEvent(new Event('change'));
    };
    const savedData = (key: 'segmentMore' | 'segmentBlocks'): unknown[] => {
        try {
            const parsed: unknown = JSON.parse(list.closest<HTMLElement>('.ad-cond-builder')?.dataset[key] || '[]');
            return parsed !== null && typeof parsed === 'object' ? Object.values(parsed) : [];
        } catch {
            return [];
        }
    };
    const restoreExtras = (entries: unknown, into: ConditionTarget): void => {
        Object.values(entries !== null && typeof entries === 'object' ? entries : {}).forEach((entry) => {
            if (entry === null || typeof entry !== 'object') return;
            const key = conditionKeyOf(entry as Record<string, unknown>);
            const item = key === undefined ? null : addCard(key, false, into, true);
            if (item !== null) fillExtra(item, entry as Record<string, unknown>);
        });
    };

    addButton.addEventListener('click', () => {
        if (menu.hidden || target !== firstTarget) openMenu(firstTarget);
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
            addCard(button.dataset.menuField ?? '', true, target);
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
        if (!menu.contains(event.target) && !target.button.contains(event.target) && !addBlockButton.contains(event.target)) closeMenu(false);
    });

    Array.from(pool.querySelectorAll<HTMLElement>('[data-condition]'))
        .filter((item) => conditionItemHasValue(item))
        .forEach((item) => addCard(item.dataset.condition ?? '', false, firstTarget));
    restoreExtras(savedData('segmentMore'), firstTarget);
    savedData('segmentBlocks').forEach((blockEntry) => {
        if (blockEntry === null || typeof blockEntry !== 'object') return;
        const entry = blockEntry as Record<string, unknown>;
        restoreExtras(entry.segment_more, createBlock(String(entry.segment_match_mode ?? '')));
    });

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
            form.querySelectorAll<HTMLElement>('.ad-cond-list [data-condition]').forEach((item) => {
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

function clearSalesCondition(form: HTMLFormElement, name = 'segment_condition'): void {
    const empty = form.querySelector<HTMLInputElement>(`input[name="${name}"][value=""]`);
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
    return segmentScopes(form).flatMap((scope) => scopedConditionChips(form, scope)
        .map((chip) => (scope.block === '' ? chip : { ...chip, label: `${scope.block}：${chip.label}` })));
}

function scopedConditionChips(form: HTMLFormElement, { id, name }: SegmentScope): SegmentConditionChip[] {
    const chips: SegmentConditionChip[] = [];
    const add = (label: string, operator: SegmentConditionChip['operator'], value: string, clear: () => void): void => {
        if (value !== '') chips.push({ label, operator, value, clear });
    };

    const groupLabels = checkedOptionLabels(name('segment_audience_group_ids[]'));
    add('保存したグループ', listOperator(groupLabels.length), groupLabels.join('、'), () => clearNamed(form, name('segment_audience_group_ids[]')));
    add('登録チャネル', '=', selectedOptionText(id('segment_channel')), () => clearIds([id('segment_channel')]));
    const birthdayMonths = checkedValues(name('segment_birthday_months[]')).map((month) => `${month}月`);
    add('誕生月', listOperator(birthdayMonths.length), birthdayMonths.join('、'), () => clearNamed(form, name('segment_birthday_months[]')));
    add('性別', '=', selectedOptionText(id('segment_sex')), () => clearIds([id('segment_sex')]));
    const rfmLabels = checkedOptionLabels(name('segment_rfm_segments[]'));
    add('RFMセグメント', listOperator(rfmLabels.length), rfmLabels.join('、'), () => clearNamed(form, name('segment_rfm_segments[]')));
    const tagLabels = checkedOptionLabels(name('segment_tag_ids[]'));
    add('会員タグ', listOperator(tagLabels.length, readCombineMode(form, name('segment_value_mode[tags]'))), tagLabels.join('、'), () => clearNamed(form, name('segment_tag_ids[]')));

    const visitMin = fieldValue(id('segment_visit_count_min'));
    const visitMax = fieldValue(id('segment_visit_count_max'));
    add('来店回数', '=', visitMin !== '' && visitMax !== ''
        ? `${formattedNumber(visitMin)}〜${formattedNumber(visitMax)}回`
        : visitMin !== '' ? `${formattedNumber(visitMin)}回以上` : visitMax !== '' ? `${formattedNumber(visitMax)}回以下` : '', () => clearIds([id('segment_visit_count_min'), id('segment_visit_count_max')]));

    add('会員ランク', '=', selectedOptionText(id('segment_rank')), () => clearIds([id('segment_rank')]));
    const pointsMin = fieldValue(id('segment_lifetime_points_min'));
    const pointsMax = fieldValue(id('segment_lifetime_points_max'));
    add('累計付与ポイント', '=', pointsMin !== '' && pointsMax !== ''
        ? `${formattedNumber(pointsMin)}〜${formattedNumber(pointsMax)}pt`
        : pointsMin !== '' ? `${formattedNumber(pointsMin)}pt以上` : pointsMax !== '' ? `${formattedNumber(pointsMax)}pt以下` : '', () => clearIds([id('segment_lifetime_points_min'), id('segment_lifetime_points_max')]));

    const lastVisitMode = fieldValue(id('segment_last_visit_mode'));
    const lastVisitDays = fieldValue(id('segment_last_visit_days'));
    const clearLastVisit = (): void => clearIds([id('segment_last_visit_mode')]);
    if (lastVisitMode === 'inactive_days' && lastVisitDays !== '') {
        add('最終来店', '=', `${formattedNumber(lastVisitDays)}日以上来店なし`, clearLastVisit);
    } else if (lastVisitMode === 'visited_within_days' && lastVisitDays !== '') {
        add('最終来店', '=', `${formattedNumber(lastVisitDays)}日以内に来店あり`, clearLastVisit);
    } else if (lastVisitMode === 'days_after' && lastVisitDays !== '') {
        add('最終来店', '=', `${formattedNumber(lastVisitDays)}日後`, clearLastVisit);
    }

    add('購入商品', '=', selectedOptionText(id('segment_product_id')), () => clearIds([id('segment_product_id')]));
    const heldCouponName = selectedOptionText(id('segment_coupon_id'));
    const heldCouponStatusLabel = selectedOptionText(id('segment_coupon_status'));
    add(
        '保有クーポン',
        '=',
        heldCouponName !== '' && heldCouponStatusLabel !== '' ? `${heldCouponName}（${heldCouponStatusLabel}）` : '',
        () => clearIds([id('segment_coupon_id'), id('segment_coupon_status')]),
    );
    if (field<HTMLInputElement>(id('event_rank_up'))?.checked === true) {
        add('ランクアップ達成', '=', selectedOptionText(id('segment_rank')) || '対象ランクを指定', () => clearIds([id('event_rank_up'), id('segment_rank')]));
    }
    add('スタンプカード', '=', selectedOptionText(id('stamp_card_id')), () => clearIds([id('stamp_card_id')]));
    const stampMin = fieldValue(id('stamp_count_min'));
    add('スタンプ累計', '≥', stampMin === '' ? '' : `${formattedNumber(stampMin)}個`, () => clearIds([id('stamp_count_min')]));
    if (field<HTMLInputElement>(id('stamp_completed'))?.checked === true) {
        add('スタンプ', '=', '達成済み', () => clearIds([id('stamp_completed')]));
    }

    const salesCondition = form.querySelector<HTMLInputElement>(`input[name="${name('segment_condition')}"]:checked`)?.value || '';
    const period = selectedOptionText(id('segment_period_days'));
    if (salesCondition === 'min_amount') {
        const minAmount = fieldValue(id('segment_min_amount'));
        add(`購入額（${period}）`, '≥', minAmount === '' ? '' : `${formattedNumber(minAmount)}円`, () => clearSalesCondition(form, name('segment_condition')));
    } else if (salesCondition === 'no_orders') {
        add(`購入履歴（${period}）`, '=', 'なし', () => clearSalesCondition(form, name('segment_condition')));
    }

    form.querySelectorAll<HTMLElement>('.ad-profile-filter').forEach((group) => {
        const inputs = Array.from(group.querySelectorAll<HTMLInputElement>(`[name^="${name('segment_profile')}["]`));
        const values = inputs
            .filter((input) => input.type !== 'checkbox' || input.checked)
            .map((input) => input.value.trim()).filter(Boolean);
        const modeName = name(`segment_value_mode[profile][${group.dataset.profileKey || ''}]`);
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
    if (matchMode) matchMode.hidden = firstBlockConditionCount(form) < 2;
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
    form.querySelectorAll<HTMLSelectElement>('[data-block] select[name$="[segment_match_mode]"]').forEach((select) => {
        if (!select.disabled) params.set(select.name, select.value);
    });
    extraItems(form).filter((item) => conditionItemHasValue(item)).forEach((item) => {
        item.querySelectorAll<HTMLInputElement | HTMLSelectElement>('input[name], select[name]').forEach((control) => {
            if (control.disabled) return;
            if (control instanceof HTMLInputElement && (control.type === 'checkbox' || control.type === 'radio') && !control.checked) return;
            if (control instanceof HTMLSelectElement && control.multiple) {
                Array.from(control.selectedOptions).forEach((option) => params.append(control.name, option.value));
            } else if (control.value.trim() !== '') {
                params.append(control.name, control.value);
            }
        });
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
        || extraItems(form).some((item) => conditionItemHasValue(item))
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

    const render = (): void => {
        syncValueModes(form);
        const minAmount = document.getElementById('segmentMinAmountBlock');
        const sales = form.querySelector<HTMLInputElement>('input[name="segment_condition"]:checked')?.value || '';
        if (minAmount) minAmount.hidden = sales !== 'min_amount';

        const container = document.getElementById('deliverySummaryConditions');
        const block = document.getElementById('deliverySummaryConditionBlock');
        const match = document.getElementById('segmentMatchMode');
        const save = document.getElementById('segmentGroupSave');
        if (!container || !block) return;
        const chips = segmentConditionChips(form).map((chip) => conditionSentence(chip.label, chip.operator, chip.value));
        block.hidden = chips.length === 0;
        if (match) match.hidden = firstBlockConditionCount(form) < 2;
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

    // 後から追加した同じ種類の条件カードの入力も拾えるよう、フォームでまとめて受ける
    const onSegmentEvent = (event: Event): void => {
        if (event.target instanceof Element && event.target.matches('.segment-filter')) render();
    };
    form.addEventListener('change', onSegmentEvent);
    form.addEventListener('input', onSegmentEvent);
    bindConditionPicker(form);
    render();
}

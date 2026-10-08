type AudienceType = 'immediate_bulk' | 'personal' | 'segment';
type DeliveryTiming = 'immediate' | 'scheduled';
type AudienceResolveMode = 'snapshot' | 'at_send';

type DeliveryConfig = {
    membersUrl: string;
    segmentPreviewUrl: string;
    audiencePreviewUrl: string;
    memberCount: number;
    selectedMemberIds: number[];
    mode?: 'announcement' | 'coupon';
};

type MemberSearchResult = {
    id: number;
    display_identifier?: string;
    member_number?: string | null;
    name?: string | null;
};

type AudiencePreviewMember = {
    id: number;
    display_identifier?: string;
    member_number: string;
    name: string;
    rank: string;
    channel: string;
    kana: string;
    email: string;
    phone: string;
    birthday: string;
    sex: string;
    points: number;
    last_visited_at: string;
    tags: Array<{ id: number; name: string; color: string }>;
    notification_preference: string;
    notification_preference_label: string;
    notification_sendable: boolean;
    notification_reason: string;
};

type AudiencePreviewResponse = {
    count: number;
    members: AudiencePreviewMember[];
    notification_channels: NotificationChannelCounts;
    page: number;
    last_page: number;
    per_page: number;
};

type NotificationChannelCounts = {
    line: number;
    push: number;
    unreachable: number;
    opted_out: number;
};

type SegmentConditionOperator = '=' | '≥' | '≤' | 'いずれか' | 'すべて';

type SegmentConditionChip = {
    label: string;
    operator: SegmentConditionOperator;
    value: string;
    clear: () => void;
};

/** App\Enums\SegmentCombineMode と同じ値 */
type SegmentCombineMode = 'any' | 'all';
const SEGMENT_COMBINE_ANY: SegmentCombineMode = 'any';
const SEGMENT_COMBINE_ALL: SegmentCombineMode = 'all';

function readCombineMode(form: HTMLFormElement, modeName: string): SegmentCombineMode {
    const selected = Array.from(form.querySelectorAll<HTMLInputElement>('input[type="radio"]'))
        .find((input) => input.name === modeName && input.checked && !input.disabled);
    return selected?.value === SEGMENT_COMBINE_ALL ? SEGMENT_COMBINE_ALL : SEGMENT_COMBINE_ANY;
}

function listOperator(selectedCount: number, mode: SegmentCombineMode = SEGMENT_COMBINE_ANY): SegmentConditionOperator {
    if (selectedCount < 2) return '=';
    return mode === SEGMENT_COMBINE_ALL ? 'すべて' : 'いずれか';
}

function conditionSentence(label: string, operator: SegmentConditionOperator, value: string): string {
    if (operator === 'すべて') return `${label}は ${value} のすべてを選択`;
    if (operator === 'いずれか') return `${label}は ${value} のいずれかを選択`;
    if (operator === '≥') return `${label}は ${value}以上`;
    if (operator === '≤') return `${label}は ${value}以下`;
    return `${label}は ${value}`;
}

function conditionItemHasValue(item: HTMLElement): boolean {
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

function syncConditionPicker(form: HTMLFormElement): void {
    form.querySelectorAll<HTMLElement>('#segmentConditionPool [data-condition]').forEach((item) => {
        item.hidden = true;
    });
}

function bindConditionPicker(form: HTMLFormElement): void {
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
}

function initAnnouncementDelivery(): void {
    const pageKind = document.body.dataset.pageKind;
    if (pageKind !== 'announcement-delivery' && pageKind !== 'coupon-delivery') return;
    const isCouponDelivery = pageKind === 'coupon-delivery';

    // Chrome のスクロール復元でフォームが画面外になり、右パネルだけ見えるのを防ぐ
    if ('scrollRestoration' in history) {
        history.scrollRestoration = 'manual';
    }
    if (window.location.hash === '') {
        window.scrollTo(0, 0);
    }

    const form = document.getElementById('deliveryForm') as HTMLFormElement | null;
    const configElement = document.getElementById('announcementDeliveryConfig');
    if (!form || !configElement?.textContent) return;

    let config: DeliveryConfig;
    try {
        config = JSON.parse(configElement.textContent) as DeliveryConfig;
    } catch {
        return;
    }

    const field = <T extends HTMLElement>(id: string): T | null => document.getElementById(id) as T | null;
    const audienceRadios = Array.from(document.querySelectorAll<HTMLInputElement>('input[name="audience_type"]'));
    const timingRadios = Array.from(document.querySelectorAll<HTMLInputElement>('input[name="delivery_timing"]'));
    const resolveRadios = Array.from(document.querySelectorAll<HTMLInputElement>('input[name="audience_resolve_mode"]'));
    const personalBlock = field<HTMLElement>('personalTargetBlock');
    const scheduledBlock = field<HTMLElement>('scheduledBlock');
    const audienceResolveBlock = field<HTMLElement>('audienceResolveBlock');
    const segmentBlock = field<HTMLElement>('segmentBlock');
    const scheduledAt = field<HTMLInputElement>('scheduled_at');
    const segmentMinAmountBlock = field<HTMLElement>('segmentMinAmountBlock');
    const titleInput = field<HTMLInputElement>('title');
    const bodyInput = field<HTMLTextAreaElement>('body');
    const imageUrlInput = field<HTMLInputElement>('image_url');
    const imageFileInput = field<HTMLInputElement>('ann_image');
    const pinInput = field<HTMLInputElement>('is_pinned');
    const sendNotificationInput = field<HTMLInputElement>('send_notification');
    const couponToggle = field<HTMLInputElement>('attachCoupon');
    const couponPanel = field<HTMLElement>('couponPanel');
    const couponSelect = field<HTMLSelectElement>('delivery_coupon_id');
    const selectedCouponPreview = field<HTMLElement>('selectedCouponPreview');
    const couponChecks = Array.from(form.querySelectorAll<HTMLInputElement>('input[name="delivery_coupon_ids[]"], input[name="coupon_id"]'));
    const searchInput = field<HTMLInputElement>('memberSearch');
    const searchResults = field<HTMLElement>('memberSearchResults');
    const selectedMembersElement = field<HTMLElement>('selectedMembers');
    const selectedMembersHidden = field<HTMLElement>('targetMemberIdsHidden');
    const formError = field<HTMLElement>('deliveryFormError');
    const audienceRows = field<HTMLTableSectionElement>('audiencePreviewRows');
    const audiencePrevPage = field<HTMLButtonElement>('audiencePrevPage');
    const audienceNextPage = field<HTMLButtonElement>('audienceNextPage');
    const reviewModal = field<HTMLElement>('deliveryReviewModal');
    const reviewBody = field<HTMLElement>('deliveryReviewBody');
    const reviewConfirm = field<HTMLButtonElement>('deliveryReviewConfirm');
    const reviewAudienceButton = field<HTMLButtonElement>('deliveryReviewAudienceButton');
    const reviewAudiencePanel = field<HTMLElement>('deliveryReviewAudiencePanel');
    const reviewAudienceClose = field<HTMLButtonElement>('deliveryReviewAudienceClose');
    const reviewAudienceRows = field<HTMLTableSectionElement>('deliveryReviewAudienceRows');
    const reviewAudiencePrev = field<HTMLButtonElement>('deliveryReviewAudiencePrev');
    const reviewAudienceNext = field<HTMLButtonElement>('deliveryReviewAudienceNext');
    const livePreviewFrame = field<HTMLIFrameElement>('announcementLivePreview');
    const memberDetailModal = field<HTMLElement>('audienceMemberDetailModal');
    const selectedMembers = new Map<number, string>();
    let segmentCount: number | null = null;
    let segmentTimer: number | undefined;
    let audienceTimer: number | undefined;
    let audiencePage = 1;
    let searchTimer: number | undefined;
    let uploadedImageUrl = '';
    let uploadedImageUrls: string[] = [];
    let notificationChannels: NotificationChannelCounts | null = null;
    let resolvedAudienceCount: number | null = null;
    let lastAudiencePreview: AudiencePreviewResponse | null = null;
    let finalSubmitApproved = false;

    (config.selectedMemberIds || []).forEach((id) => selectedMembers.set(Number(id), `会員ID: ${id}`));

    function selectedAudienceType(): AudienceType {
        const checked = audienceRadios.find((radio) => radio.checked);
        return (checked?.value || 'immediate_bulk') as AudienceType;
    }

    function selectedDeliveryTiming(): DeliveryTiming {
        const checked = timingRadios.find((radio) => radio.checked);
        return (checked?.value || 'immediate') as DeliveryTiming;
    }

    function selectedResolveMode(): AudienceResolveMode {
        const checked = resolveRadios.find((radio) => radio.checked && !radio.disabled);
        return (checked?.value || 'snapshot') as AudienceResolveMode;
    }

    function canChooseResolveMode(): boolean {
        return selectedDeliveryTiming() === 'scheduled' && selectedAudienceType() !== 'personal';
    }

    function resolveModeText(): string {
        return selectedResolveMode() === 'at_send' ? '配信時に条件を再評価' : '今の一覧で固定';
    }

    function setBlockEnabled(block: HTMLElement | null, enabled: boolean): void {
        if (!block) return;
        block.hidden = !enabled;
        block.querySelectorAll<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>('input[name], select[name], textarea[name]')
            .forEach((control) => {
                control.disabled = !enabled;
            });
    }

    function formatDateTime(value: string): string {
        if (!value) return '日時を選択';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return '日時を選択';
        return date.toLocaleString('ja-JP', {
            year: 'numeric', month: 'numeric', day: 'numeric', hour: '2-digit', minute: '2-digit',
        });
    }

    function setText(id: string, value: string): void {
        const element = field<HTMLElement>(id);
        if (element) element.textContent = value;
    }

    function fieldValue(id: string): string {
        return (field<HTMLInputElement | HTMLSelectElement>(id)?.value || '').trim();
    }

    function selectedOptionText(id: string): string {
        const select = field<HTMLSelectElement>(id);
        if (!select || select.value === '') return '';
        return select.selectedOptions[0]?.textContent?.trim() || select.value;
    }

    function formattedNumber(value: string): string {
        const number = Number(value);
        return Number.isFinite(number) ? number.toLocaleString('ja-JP') : value;
    }

    function checkedValues(name: string): string[] {
        return Array.from(document.querySelectorAll<HTMLInputElement>(`input[name="${name}"]:checked`))
            .map((input) => input.value.trim())
            .filter((value) => value !== '');
    }

    function checkedOptionLabels(name: string): string[] {
        return Array.from(document.querySelectorAll<HTMLInputElement>(`input[name="${name}"]:checked`))
            .map((input) => input.dataset.label?.trim() || input.value)
            .filter((value) => value !== '');
    }

    function notifyCleared(control: HTMLElement | undefined): void {
        control?.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function clearNamed(name: string): void {
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

    function clearSalesCondition(): void {
        const empty = form.querySelector<HTMLInputElement>('input[name="segment_condition"][value=""]');
        if (empty === null) return;
        empty.checked = true;
        notifyCleared(empty);
    }

    function checksByName(name: string): HTMLInputElement[] {
        return Array.from(form.querySelectorAll<HTMLInputElement>('input[type="checkbox"]'))
            .filter((input) => input.name === name);
    }

    function syncValueModes(): void {
        form.querySelectorAll<HTMLElement>('[data-value-mode]').forEach((block) => {
            const checkedCount = checksByName(block.dataset.valueMode ?? '').filter((input) => input.checked).length;
            const visible = checkedCount >= 2;
            block.hidden = !visible;
            block.querySelectorAll<HTMLInputElement>('input').forEach((input) => {
                input.disabled = !visible;
            });
        });
    }

    function segmentConditionChips(): SegmentConditionChip[] {
        const chips: SegmentConditionChip[] = [];
        const add = (label: string, operator: SegmentConditionChip['operator'], value: string, clear: () => void): void => {
            if (value !== '') chips.push({ label, operator, value, clear });
        };

        const groupLabels = checkedOptionLabels('segment_audience_group_ids[]');
        add('保存したグループ', listOperator(groupLabels.length), groupLabels.join('、'), () => clearNamed('segment_audience_group_ids[]'));
        add('登録チャネル', '=', selectedOptionText('segment_channel'), () => clearIds(['segment_channel']));
        const birthdayMonths = checkedValues('segment_birthday_months[]').map((month) => `${month}月`);
        add('誕生月', listOperator(birthdayMonths.length), birthdayMonths.join('、'), () => clearNamed('segment_birthday_months[]'));
        add('性別', '=', selectedOptionText('segment_sex'), () => clearIds(['segment_sex']));
        const rfmLabels = checkedOptionLabels('segment_rfm_segments[]');
        add('RFMセグメント', listOperator(rfmLabels.length), rfmLabels.join('、'), () => clearNamed('segment_rfm_segments[]'));
        const tagLabels = checkedOptionLabels('segment_tag_ids[]');
        add('会員タグ', listOperator(tagLabels.length, readCombineMode(form, 'segment_value_mode[tags]')), tagLabels.join('、'), () => clearNamed('segment_tag_ids[]'));

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
            add(`購入額（${period}）`, '≥', minAmount === '' ? '' : `${formattedNumber(minAmount)}円`, clearSalesCondition);
        } else if (salesCondition === 'no_orders') {
            add(`購入履歴（${period}）`, '=', 'なし', clearSalesCondition);
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

    function renderSegmentConditionSummary(): void {
        const block = field<HTMLElement>('deliverySummaryConditionBlock');
        const container = field<HTMLElement>('deliverySummaryConditions');
        if (!block || !container) return;

        const isSegment = selectedAudienceType() === 'segment';
        const chips = isSegment ? segmentConditionChips() : [];
        const matchMode = field<HTMLElement>('segmentMatchMode');
        const groupSave = field<HTMLElement>('segmentGroupSave');
        block.hidden = !isSegment || chips.length === 0;
        if (matchMode) matchMode.hidden = chips.length < 2;
        syncValueModes();
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

    function shouldSendNotification(): boolean {
        return sendNotificationInput?.checked !== false;
    }

    function notificationSummaryText(): string {
        if (!shouldSendNotification()) return '送らない';
        if (notificationChannels === null) return '通知先を確認中…';
        const parts: string[] = [];
        if (notificationChannels.line > 0) parts.push(`LINE ${notificationChannels.line.toLocaleString('ja-JP')}人`);
        if (notificationChannels.push > 0) parts.push(`Push ${notificationChannels.push.toLocaleString('ja-JP')}人`);
        if (notificationChannels.unreachable > 0) {
            parts.push(`通知先なし ${notificationChannels.unreachable.toLocaleString('ja-JP')}人`);
        }
        if (notificationChannels.opted_out > 0) {
            parts.push(`通知オフ ${notificationChannels.opted_out.toLocaleString('ja-JP')}人`);
        }
        if (parts.length === 0) return '通知可能な会員なし';
        return parts.join('・');
    }

    function renderNotificationSummary(): void {
        setText('deliverySummaryNotifications', notificationSummaryText());
    }

    function updateCounter(input: HTMLInputElement | HTMLTextAreaElement | null, counterId: string, max: number): void {
        if (!input) return;
        setText(counterId, `${input.value.length.toLocaleString('ja-JP')} / ${max.toLocaleString('ja-JP')}`);
    }

    function couponOptions(): HTMLOptionElement[] {
        const attached = isCouponDelivery || couponToggle?.checked === true;
        if (!attached) return [];
        if (couponChecks.length > 0) {
            return couponChecks.filter((input) => input.checked).map((input) => {
                const option = document.createElement('option');
                option.value = input.value;
                option.textContent = input.dataset.name || input.value;
                option.dataset.benefit = input.dataset.benefit || '';
                option.dataset.period = input.dataset.period || '';
                return option;
            });
        }
        if (!couponSelect) return [];
        return Array.from(couponSelect.selectedOptions).filter((option) => option.value !== '');
    }

    function syncCoupon(): void {
        const enabled = isCouponDelivery || couponToggle?.checked === true;
        if (couponPanel) couponPanel.hidden = !enabled;
        couponChecks.forEach((input) => {
            input.disabled = input.dataset.expired === '1' || !enabled;
        });
        if (couponSelect) {
            couponSelect.disabled = !enabled;
            couponSelect.required = enabled && couponChecks.length === 0;
        }

        const options = couponOptions();
        if (selectedCouponPreview) selectedCouponPreview.hidden = options.length === 0;

        if (options.length > 0) {
            const names = options.map((option) => option.textContent?.trim() || '選択したクーポン');
            setText('selectedCouponName', names.length > 1 ? `${names.length}件のクーポン` : names[0]);
            setText('selectedCouponBenefit', names.length > 1 ? names.join('、') : (options[0].dataset.benefit || ''));
            setText('selectedCouponPeriod', options[0].dataset.period || '');
            setText('deliverySummaryCoupon', names.join('、'));
        } else {
            setText('deliverySummaryCoupon', enabled ? 'クーポンを選択' : '配布しない');
        }
        updateAudiencePreview(1);
        updateSubmitLabel();
    }

    function currentImageUrl(): string {
        return uploadedImageUrl || imageUrlInput?.value.trim() || '';
    }

    function sendAnnouncementLivePreview(): void {
        if (!livePreviewFrame?.contentWindow || livePreviewFrame.getAttribute('src') === 'about:blank') return;
        livePreviewFrame.contentWindow.postMessage({
            type: 'reggit:announcement-form-preview:update',
            announcement: {
                id: '__reggit_announcement_form_preview__',
                title: titleInput?.value.trim() || 'お知らせのタイトル',
                body: bodyInput?.value.trim() || '',
                image_url: currentImageUrl() || null,
                image_urls: uploadedImageUrls,
                published_at: new Date().toISOString(),
                is_pinned: pinInput?.checked === true,
                is_read: false,
            },
        }, window.location.origin);
    }

    function ensureAnnouncementLivePreview(): void {
        const previewDetails = reviewModal?.querySelector<HTMLDetailsElement>('.ad-channel-preview-details');
        if (previewDetails !== null && previewDetails !== undefined && !previewDetails.open) return;
        if (!livePreviewFrame) return;
        const currentSrc = livePreviewFrame.getAttribute('src') || '';
        if (currentSrc === '' || currentSrc === 'about:blank') {
            const source = livePreviewFrame.dataset.src || '';
            if (source !== '') livePreviewFrame.src = source;
            return;
        }
        sendAnnouncementLivePreview();
    }

    function syncPreview(): void {
        const title = titleInput?.value.trim() || 'お知らせのタイトル';
        const body = bodyInput?.value.trim() || '';
        setText('deliverySummaryPinned', pinInput?.checked ? 'する' : 'しない');

        setText('deliveryLineTitle', title);
        const lineBody = field<HTMLElement>('deliveryLineBody');
        if (lineBody) {
            lineBody.hidden = body === '';
            lineBody.textContent = body;
        }
        const lineImage = field<HTMLImageElement>('deliveryLineImage');
        const lineImageWrap = field<HTMLElement>('deliveryLineImageWrap');
        const imageUrl = currentImageUrl();
        if (lineImage && lineImageWrap) {
            lineImageWrap.hidden = imageUrl === '';
            if (imageUrl !== '' && lineImage.getAttribute('src') !== imageUrl) lineImage.src = imageUrl;
        }
        sendAnnouncementLivePreview();
    }

    function audienceText(): string {
        const type = selectedAudienceType();
        if (type === 'personal') {
            return selectedMembers.size > 0 ? `${selectedMembers.size.toLocaleString('ja-JP')}人（選択した会員）` : '会員を選択';
        }
        if (type === 'segment') {
            return segmentCount === null ? '条件を指定' : `${segmentCount.toLocaleString('ja-JP')}人（条件一致）`;
        }
        return `${Number(config.memberCount).toLocaleString('ja-JP')}人（全会員）`;
    }

    function updateSubmitLabel(): void {
        const audienceType = selectedAudienceType();
        const timing = selectedDeliveryTiming();
        let count = Number(config.memberCount);
        if (audienceType === 'personal') count = selectedMembers.size;
        if (audienceType === 'segment') count = segmentCount ?? 0;
        let label = timing === 'scheduled' ? '予約内容を確認する' : '配信内容を確認する';
        if (count > 0) {
            label = timing === 'scheduled'
                ? `${count.toLocaleString('ja-JP')}人への予約内容を確認`
                : `${count.toLocaleString('ja-JP')}人への配信内容を確認`;
        }
        setText('deliverySubmitLabel', label);
    }

    function syncAudienceType(): void {
        const type = selectedAudienceType();
        audienceRadios.forEach((radio) => {
            radio.closest('.ad-delivery-choice')?.classList.toggle('is-selected', radio.checked);
        });
        setBlockEnabled(personalBlock, type === 'personal');
        setBlockEnabled(segmentBlock, type === 'segment');
        toggleSegmentMinAmount();
        setText('deliverySummaryAudience', audienceText());
        renderSegmentConditionSummary();
        if (type === 'segment') updateSegmentPreview();
        updateAudiencePreview(1);
        updateSubmitLabel();
        syncAudienceResolve();
    }

    function syncDeliveryTiming(): void {
        const timing = selectedDeliveryTiming();
        timingRadios.forEach((radio) => {
            radio.closest('.ad-timing-choice')?.classList.toggle('is-selected', radio.checked);
        });
        setBlockEnabled(scheduledBlock, timing === 'scheduled');
        if (scheduledAt) scheduledAt.required = timing === 'scheduled';
        setText('deliverySummaryTiming', timing === 'scheduled' ? formatDateTime(scheduledAt?.value || '') : '今すぐ');
        updateSubmitLabel();
        syncAudienceResolve();
    }

    function syncAudienceResolve(): void {
        const enabled = canChooseResolveMode();
        setBlockEnabled(audienceResolveBlock, enabled);
        resolveRadios.forEach((radio) => {
            radio.closest('.ad-timing-choice')?.classList.toggle('is-selected', radio.checked);
        });
        const resolveRow = field<HTMLElement>('deliverySummaryResolveRow');
        if (resolveRow) resolveRow.hidden = !enabled;
        setText('deliverySummaryResolve', resolveModeText());
        setText('audiencePreviewHint', !enabled
            ? 'この一覧の対象者を配信時点で保存します'
            : (selectedResolveMode() === 'at_send'
                ? 'いまの一致者です。配信時刻に条件をやり直して対象を決めます'
                : 'この一覧の対象者を予約時点で固定します'));
    }

    function renderSelectedMembers(): void {
        if (!selectedMembersElement || !selectedMembersHidden) return;
        selectedMembersElement.replaceChildren();
        selectedMembersHidden.replaceChildren();

        if (selectedMembers.size === 0) {
            const empty = document.createElement('p');
            empty.className = 'ad-selected-members-empty';
            empty.textContent = '配信する会員はまだ選択されていません';
            selectedMembersElement.appendChild(empty);
        }

        selectedMembers.forEach((label, id) => {
            const tag = document.createElement('span');
            tag.className = 'ad-member-tag';
            tag.append(document.createTextNode(label));
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.setAttribute('aria-label', `${label}を選択解除`);
            remove.textContent = '×';
            remove.addEventListener('click', () => {
                selectedMembers.delete(id);
                renderSelectedMembers();
                updateAudiencePreview(1);
            });
            tag.appendChild(remove);
            selectedMembersElement.appendChild(tag);

            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'target_member_ids[]';
            hidden.value = String(id);
            hidden.disabled = selectedAudienceType() !== 'personal';
            selectedMembersHidden.appendChild(hidden);
        });

        setText('deliverySummaryAudience', audienceText());
        updateSubmitLabel();
    }

    function renderMemberResults(results: MemberSearchResult[]): void {
        if (!searchResults) return;
        searchResults.replaceChildren();
        const available = results.filter((member) => !selectedMembers.has(Number(member.id)));
        if (available.length === 0) {
            const empty = document.createElement('p');
            empty.className = 'ad-member-results-empty';
            empty.textContent = '該当する会員がいません';
            searchResults.appendChild(empty);
        } else {
            available.forEach((member) => {
                const name = member.name?.trim() || '名前未登録';
                const number = (member.display_identifier ?? member.member_number)?.trim() || `会員ID: ${member.id}`;
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'ad-member-result';
                const copy = document.createElement('span');
                const strong = document.createElement('strong');
                strong.textContent = name;
                const small = document.createElement('small');
                small.textContent = number;
                copy.append(strong, small);
                const icon = document.createElement('i');
                icon.className = 'fas fa-plus-circle';
                icon.setAttribute('aria-hidden', 'true');
                button.append(copy, icon);
                button.addEventListener('click', () => {
                    selectedMembers.set(Number(member.id), `${name}（${number}）`);
                    renderSelectedMembers();
                    updateAudiencePreview(1);
                    searchResults.hidden = true;
                    if (searchInput) {
                        searchInput.value = '';
                        searchInput.focus();
                    }
                });
                searchResults.appendChild(button);
            });
        }
        searchResults.hidden = false;
    }

    function toggleSegmentMinAmount(): void {
        const condition = document.querySelector<HTMLInputElement>('input[name="segment_condition"]:checked')?.value || '';
        const active = selectedAudienceType() === 'segment' && condition === 'min_amount';
        if (segmentMinAmountBlock) {
            segmentMinAmountBlock.hidden = !active;
            segmentMinAmountBlock.querySelectorAll<HTMLInputElement>('input[name]').forEach((input) => {
                input.disabled = !active;
                input.required = active;
            });
        }
    }

    function segmentParams(): URLSearchParams {
        const params = new URLSearchParams();
        [
            'segment_channel', 'segment_visit_count_min', 'segment_visit_count_max',
            'segment_lifetime_points_min', 'segment_lifetime_points_max', 'segment_last_visit_mode',
            'segment_last_visit_days', 'segment_sex', 'segment_rank', 'segment_product_id',
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

    function audienceParams(page: number): URLSearchParams {
        const params = segmentParams();
        params.set('audience_type', selectedAudienceType());
        params.set('page', String(page));
        couponOptions().forEach((option) => params.append('delivery_coupon_ids[]', option.value));
        if (selectedAudienceType() === 'personal') {
            selectedMembers.forEach((_label, id) => params.append('target_member_ids[]', String(id)));
        }
        return params;
    }

    function renderAudienceRows(target: HTMLTableSectionElement | null, members: AudiencePreviewMember[]): void {
        if (!target) return;
        target.replaceChildren();
        if (members.length === 0) {
            const row = document.createElement('tr');
            const cell = document.createElement('td');
            cell.colSpan = 6;
            cell.className = 'ad-audience-empty';
            cell.textContent = '現在の条件に該当する会員はいません';
            row.appendChild(cell);
            target.appendChild(row);
            return;
        }

        members.forEach((member) => {
            const row = document.createElement('tr');
            row.className = 'ad-audience-member-row';
            row.tabIndex = 0;
            row.setAttribute('role', 'button');
            row.setAttribute('aria-label', `${member.name}の会員情報を表示`);

            const nameCell = document.createElement('td');
            const name = document.createElement('strong');
            name.textContent = member.name;
            const hint = document.createElement('small');
            hint.textContent = 'タップして詳細';
            nameCell.append(name, hint);

            const tagsCell = document.createElement('td');
            const tags = document.createElement('div');
            tags.className = 'ad-audience-tags';
            if (member.tags.length === 0) {
                tags.textContent = '—';
            } else {
                member.tags.forEach((memberTag) => {
                    const tag = document.createElement('span');
                    tag.style.setProperty('--ad-member-tag-color', memberTag.color);
                    tag.textContent = memberTag.name;
                    tags.appendChild(tag);
                });
            }
            tagsCell.appendChild(tags);

            const numberCell = document.createElement('td');
            numberCell.textContent = (member.display_identifier ?? member.member_number);
            const sendableCell = document.createElement('td');
            const sendable = document.createElement('span');
            sendable.className = `ad-sendability ${member.notification_sendable ? 'is-sendable' : 'is-blocked'}`;
            sendable.innerHTML = member.notification_sendable
                ? '<i class="fas fa-check" aria-hidden="true"></i> 通知可'
                : '<i class="fas fa-minus" aria-hidden="true"></i> 通知なし';
            sendableCell.appendChild(sendable);
            const channelCell = document.createElement('td');
            channelCell.textContent = member.channel;
            const rankCell = document.createElement('td');
            rankCell.textContent = member.rank;
            row.append(nameCell, tagsCell, numberCell, sendableCell, channelCell, rankCell);
            row.addEventListener('click', () => openMemberDetail(member));
            row.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    openMemberDetail(member);
                }
            });
            target.appendChild(row);
        });
    }

    function renderMemberDetailTags(member: AudiencePreviewMember): void {
        const container = field<HTMLElement>('audienceMemberDetailTags');
        if (!container) return;
        container.replaceChildren();
        if (member.tags.length === 0) {
            const empty = document.createElement('span');
            empty.className = 'is-empty';
            empty.textContent = 'タグなし';
            container.appendChild(empty);
            return;
        }
        member.tags.forEach((memberTag) => {
            const tag = document.createElement('span');
            tag.style.setProperty('--ad-member-tag-color', memberTag.color);
            tag.textContent = memberTag.name;
            container.appendChild(tag);
        });
    }

    function openMemberDetail(member: AudiencePreviewMember): void {
        if (!memberDetailModal) return;
        setText('audienceMemberDetailAvatar', member.name.slice(0, 1) || '—');
        setText('audienceMemberDetailTitle', member.name);
        setText('audienceMemberDetailNumber', (member.display_identifier ?? member.member_number));
        setText('audienceMemberDetailSendable', member.notification_sendable ? '今回の通知を送信できます' : '今回は通知を送信しません');
        setText('audienceMemberDetailReason', member.notification_reason);
        setText('audienceMemberDetailPreference', member.notification_preference_label);
        setText('audienceMemberDetailChannel', member.channel);
        setText('audienceMemberDetailRank', member.rank);
        setText('audienceMemberDetailPoints', `${Number(member.points).toLocaleString('ja-JP')}pt`);
        setText('audienceMemberDetailBirthday', member.birthday);
        setText('audienceMemberDetailSex', member.sex);
        setText('audienceMemberDetailLastVisit', member.last_visited_at);
        setText('audienceMemberDetailPhone', member.phone || '—');
        setText('audienceMemberDetailEmail', member.email || '—');
        renderMemberDetailTags(member);
        field<HTMLElement>('audienceMemberDetailSendableIcon')?.classList.toggle('is-blocked', !member.notification_sendable);
        memberDetailModal.hidden = false;
        memberDetailModal.querySelector<HTMLButtonElement>('[data-close-audience-member-detail]')?.focus();
    }

    function closeMemberDetail(): void {
        if (memberDetailModal) memberDetailModal.hidden = true;
    }

    function renderAudiencePreview(data: AudiencePreviewResponse): void {
        audiencePage = data.page;
        lastAudiencePreview = data;
        resolvedAudienceCount = Number(data.count);
        notificationChannels = data.notification_channels;
        renderNotificationSummary();
        setText('audiencePreviewCount', `対象 ${Number(data.count).toLocaleString('ja-JP')}人`);
        setText('audiencePageLabel', `${data.page.toLocaleString('ja-JP')} / ${data.last_page.toLocaleString('ja-JP')}ページ`);
        setText('deliveryReviewAudiencePageLabel', `${data.page.toLocaleString('ja-JP')} / ${data.last_page.toLocaleString('ja-JP')}ページ`);
        renderAudienceRows(audienceRows, Array.isArray(data.members) ? data.members : []);
        renderAudienceRows(reviewAudienceRows, Array.isArray(data.members) ? data.members : []);

        if (audiencePrevPage) audiencePrevPage.disabled = data.page <= 1;
        if (audienceNextPage) audienceNextPage.disabled = data.page >= data.last_page;
        if (reviewAudiencePrev) reviewAudiencePrev.disabled = data.page <= 1;
        if (reviewAudienceNext) reviewAudienceNext.disabled = data.page >= data.last_page;
    }

    function updateAudiencePreview(page = 1): void {
        window.clearTimeout(audienceTimer);
        setText('audiencePreviewCount', '確認中…');
        if (page === 1) {
            notificationChannels = null;
            resolvedAudienceCount = null;
            lastAudiencePreview = null;
            renderNotificationSummary();
        }
        audienceTimer = window.setTimeout(async () => {
            try {
                const response = await fetch(`${config.audiencePreviewUrl}?${audienceParams(page).toString()}`, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!response.ok) throw new Error('audience preview failed');
                renderAudiencePreview(await response.json() as AudiencePreviewResponse);
            } catch {
                setText('audiencePreviewCount', '対象者を取得できません');
                notificationChannels = null;
                resolvedAudienceCount = null;
                renderNotificationSummary();
                if (audienceRows) {
                    audienceRows.innerHTML = '<tr><td colspan="6" class="ad-audience-empty">対象者一覧を取得できませんでした</td></tr>';
                }
                if (reviewAudienceRows) {
                    reviewAudienceRows.innerHTML = '<tr><td colspan="6" class="ad-audience-empty">対象者一覧を取得できませんでした</td></tr>';
                }
            }
        }, 250);
    }

    function updateSegmentPreview(): void {
        if (selectedAudienceType() !== 'segment') return;
        window.clearTimeout(segmentTimer);
        setText('segmentPreview', '人数を確認中…');
        segmentTimer = window.setTimeout(async () => {
            try {
                const response = await fetch(`${config.segmentPreviewUrl}?${segmentParams().toString()}`, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!response.ok) throw new Error('preview failed');
                const data = await response.json() as { count?: number };
                segmentCount = Number(data.count || 0);
                setText('segmentPreview', `対象 ${segmentCount.toLocaleString('ja-JP')}人`);
                setText('segmentChoiceCount', `${segmentCount.toLocaleString('ja-JP')}人`);
                setText('deliverySummaryAudience', audienceText());
                updateSubmitLabel();
            } catch {
                segmentCount = null;
                setText('segmentPreview', '人数を取得できません');
                setText('segmentChoiceCount', '人数を確認');
                setText('deliverySummaryAudience', audienceText());
                updateSubmitLabel();
            }
        }, 350);
    }

    function hasSegmentCriteria(): boolean {
        return [
            'segment_channel', 'segment_visit_count_min', 'segment_visit_count_max',
            'segment_lifetime_points_min', 'segment_lifetime_points_max', 'segment_last_visit_mode',
            'segment_sex', 'segment_rank', 'segment_product_id',
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

    function showFormError(message: string, target?: HTMLElement | null): void {
        if (formError) {
            formError.textContent = message;
            formError.hidden = false;
        }
        let ancestor = target?.parentElement;
        while (ancestor) {
            if (ancestor instanceof HTMLDetailsElement) ancestor.open = true;
            ancestor = ancestor.parentElement;
        }
        target?.focus();
    }

    function closeDeliveryReview(): void {
        if (!reviewModal) return;
        reviewModal.hidden = true;
        if (reviewAudiencePanel) reviewAudiencePanel.hidden = true;
        reviewAudienceButton?.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('ad-review-open');
    }

    function setReviewAudienceExpanded(expanded: boolean): void {
        if (!reviewAudiencePanel) return;
        reviewAudiencePanel.hidden = !expanded;
        reviewAudienceButton?.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        if (expanded && lastAudiencePreview !== null) {
            renderAudiencePreview(lastAudiencePreview);
        }
    }

    function copyReviewConditions(): void {
        const reviewBlock = field<HTMLElement>('deliveryReviewConditionBlock');
        const reviewConditions = field<HTMLElement>('deliveryReviewConditions');
        const summaryConditions = field<HTMLElement>('deliverySummaryConditions');
        if (!reviewBlock || !reviewConditions) return;
        const show = selectedAudienceType() === 'segment' && summaryConditions !== null;
        reviewBlock.hidden = !show;
        reviewConditions.replaceChildren();
        if (!show || !summaryConditions) return;
        Array.from(summaryConditions.children).forEach((chip) => {
            const copy = chip.cloneNode(true) as HTMLElement;
            copy.querySelector('.ad-condition-remove')?.remove();
            reviewConditions.appendChild(copy);
        });
    }

    function openDeliveryReview(): void {
        if (!reviewModal || notificationChannels === null) return;
        syncPreview();
        setReviewAudienceExpanded(false);
        setText('deliveryReviewAudienceLabel', audienceText());
        setText('deliveryReviewTiming', selectedDeliveryTiming() === 'scheduled' ? formatDateTime(scheduledAt?.value || '') : '今すぐ');
        const reviewResolveRow = field<HTMLElement>('deliveryReviewResolveRow');
        if (reviewResolveRow) reviewResolveRow.hidden = !canChooseResolveMode();
        setText('deliveryReviewResolve', resolveModeText());
        const couponNames = couponOptions().map((option) => option.textContent?.trim() || '選択したクーポン');
        setText('deliveryReviewCoupon', couponNames.join('・') || (isCouponDelivery ? '未選択' : '配布しない'));
        if (pinInput) setText('deliveryReviewPinned', pinInput.checked ? 'する' : 'しない');
        const sending = shouldSendNotification();
        setText('deliveryReviewLineCount', `${notificationChannels.line.toLocaleString('ja-JP')}人`);
        setText('deliveryReviewPushCount', `${notificationChannels.push.toLocaleString('ja-JP')}人`);

        const unreachable = field<HTMLElement>('deliveryReviewUnreachable');
        const optedOut = field<HTMLElement>('deliveryReviewOptedOut');
        const notifyOff = field<HTMLElement>('deliveryReviewNotifyOff');
        const channelList = field<HTMLElement>('deliveryReviewChannelList');
        const notifyNote = field<HTMLElement>('deliveryReviewNotifyNote');
        if (notifyOff) notifyOff.hidden = sending;
        if (channelList) channelList.hidden = !sending;
        if (notifyNote) notifyNote.hidden = !sending;
        if (unreachable) {
            unreachable.hidden = !sending || notificationChannels.unreachable === 0;
            unreachable.textContent = sending && notificationChannels.unreachable > 0
                ? `${notificationChannels.unreachable.toLocaleString('ja-JP')}人はLINE・Push通知の送信先がありません。アプリ内のお知らせには掲載されます。`
                : '';
        }
        if (optedOut) {
            optedOut.hidden = !sending || notificationChannels.opted_out === 0;
            optedOut.textContent = sending && notificationChannels.opted_out > 0
                ? `${notificationChannels.opted_out.toLocaleString('ja-JP')}人は本人の通知設定によりLINE・Pushを送りません。`
                : '';
        }

        copyReviewConditions();
        const scheduled = selectedDeliveryTiming() === 'scheduled';
        const resolveAtSend = scheduled && canChooseResolveMode() && selectedResolveMode() === 'at_send';
        setText('deliveryReviewWarningTitle', scheduled ? '指定した日時に配信します' : '確認後すぐに配信します');
        const couponVerb = isCouponDelivery
            ? 'クーポンを配布'
            : (sending ? 'お知らせと通知を送信' : 'お知らせを掲載');
        setText('deliveryReviewWarningText', resolveAtSend
            ? `${formatDateTime(scheduledAt?.value || '')}に、その時点で条件へ一致する会員へ${couponVerb}します。`
            : (scheduled
                ? `${formatDateTime(scheduledAt?.value || '')}に、いま確認した対象会員へ${couponVerb}します。`
                : `確定すると対象会員へ${couponVerb}します。`));
        setText('deliveryReviewConfirmLabel', scheduled ? 'この内容で予約する' : 'この内容で今すぐ配信する');

        const previewDetails = reviewModal.querySelector<HTMLDetailsElement>('.ad-channel-preview-details');
        if (previewDetails) previewDetails.open = false;
        reviewModal.hidden = false;
        document.body.classList.add('ad-review-open');
        reviewModal.querySelector<HTMLButtonElement>('.ad-review-close')?.focus();
        window.requestAnimationFrame(() => {
            if (reviewBody) reviewBody.scrollTop = 0;
        });
    }

    audienceRadios.forEach((radio) => radio.addEventListener('change', () => {
        renderSelectedMembers();
        syncAudienceType();
    }));
    timingRadios.forEach((radio) => radio.addEventListener('change', syncDeliveryTiming));
    resolveRadios.forEach((radio) => radio.addEventListener('change', syncAudienceResolve));

    document.querySelectorAll<HTMLInputElement | HTMLSelectElement>('.segment-filter').forEach((control) => {
        const sync = (): void => {
            toggleSegmentMinAmount();
            renderSegmentConditionSummary();
            updateSegmentPreview();
            updateAudiencePreview(1);
        };
        control.addEventListener('change', sync);
        control.addEventListener('input', sync);
    });

    const memberCsv = field<HTMLInputElement>('memberCsv');
    const memberCsvMessage = field<HTMLElement>('memberCsvMessage');
    memberCsv?.addEventListener('change', () => {
        const file = memberCsv.files?.[0];
        memberCsv.value = '';
        if (!file) return;
        const reader = new FileReader();
        reader.onload = async () => {
            const lines = String(reader.result || '').split(/\r?\n/).map((line) => line.trim()).filter((line) => line !== '');
            const numbers = Array.from(new Set(lines.map((line, index) => {
                const cell = (line.split(',')[0] || '').trim().replace(/^"|"$/g, '');
                if (index === 0 && (cell === '会員番号' || cell === '会員ID' || cell.toLowerCase() === 'member_number' || cell.toLowerCase() === 'id')) return '';
                return cell;
            }).filter((number) => number !== '')));
            if (numbers.length === 0) {
                if (memberCsvMessage) {
                    memberCsvMessage.hidden = false;
                    memberCsvMessage.textContent = `${document.body.dataset.memberIdentifierLabel || '会員番号'}が見つかりませんでした。`;
                }
                return;
            }
            try {
                const response = await fetch(`${config.membersUrl}?numbers=${encodeURIComponent(numbers.join(','))}`, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!response.ok) throw new Error('csv import failed');
                const payload = await response.json() as { data?: MemberSearchResult[]; missing?: string[] };
                (payload.data || []).forEach((member) => {
                    const name = member.name?.trim() || '名前未登録';
                    const number = (member.display_identifier ?? member.member_number)?.trim() || `会員ID: ${member.id}`;
                    selectedMembers.set(Number(member.id), `${name}（${number}）`);
                });
                renderSelectedMembers();
                updateAudiencePreview(1);
                if (memberCsvMessage) {
                    const missing = payload.missing || [];
                    memberCsvMessage.hidden = false;
                    memberCsvMessage.textContent = missing.length === 0
                        ? `${(payload.data || []).length}人を追加しました。`
                        : `${(payload.data || []).length}人を追加しました。見つからない${document.body.dataset.memberIdentifierLabel || '会員番号'}: ${missing.join('、')}`;
                }
            } catch {
                if (memberCsvMessage) {
                    memberCsvMessage.hidden = false;
                    memberCsvMessage.textContent = 'CSVの読み込みに失敗しました。';
                }
            }
        };
        reader.readAsText(file);
    });

    searchInput?.addEventListener('input', () => {
        window.clearTimeout(searchTimer);
        const query = searchInput.value.trim();
        if (!searchResults) return;
        if (query === '') {
            searchResults.hidden = true;
            return;
        }
        searchTimer = window.setTimeout(async () => {
            try {
                const response = await fetch(`${config.membersUrl}?q=${encodeURIComponent(query)}`, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!response.ok) throw new Error('search failed');
                const data = await response.json() as { data?: MemberSearchResult[] };
                renderMemberResults(Array.isArray(data.data) ? data.data : []);
            } catch {
                renderMemberResults([]);
            }
        }, 280);
    });

    syncValueModes();

    couponToggle?.addEventListener('change', syncCoupon);
    couponSelect?.addEventListener('change', syncCoupon);
    couponChecks.forEach((input) => input.addEventListener('change', syncCoupon));
    titleInput?.addEventListener('input', () => {
        updateCounter(titleInput, 'announcementTitleCounter', 200);
        syncPreview();
    });
    bodyInput?.addEventListener('input', () => {
        updateCounter(bodyInput, 'announcementBodyCounter', 5000);
        syncPreview();
    });
    imageUrlInput?.addEventListener('input', () => {
        uploadedImageUrl = '';
        syncPreview();
    });
    imageFileInput?.addEventListener('change', () => {
        uploadedImageUrls.forEach(url => URL.revokeObjectURL(url));
        uploadedImageUrls = Array.from(imageFileInput.files || []).slice(0, 10).map(file => URL.createObjectURL(file));
        uploadedImageUrl = uploadedImageUrls[0] || '';
        syncPreview();
    });
    imageFileInput?.addEventListener('admin-image-preview', syncPreview);
    livePreviewFrame?.addEventListener('load', sendAnnouncementLivePreview);
    window.addEventListener('message', (event) => {
        if (event.origin === window.location.origin && event.data?.type === 'reggit:announcement-form-preview:ready') {
            sendAnnouncementLivePreview();
        }
    });
    pinInput?.addEventListener('change', syncPreview);
    sendNotificationInput?.addEventListener('change', renderNotificationSummary);
    document.querySelectorAll<HTMLAnchorElement>('.ad-summary-audience-link, .ad-confirm-conditions-head a').forEach((link) => {
        link.addEventListener('click', () => {
            const details = field<HTMLDetailsElement>('audiencePreviewDetails');
            if (details) details.open = true;
        });
    });
    reviewModal?.querySelector('.ad-channel-preview-details')?.addEventListener('toggle', () => {
        ensureAnnouncementLivePreview();
    });
    scheduledAt?.addEventListener('change', () => {
        if (selectedDeliveryTiming() === 'scheduled') setText('deliverySummaryTiming', formatDateTime(scheduledAt.value));
    });
    audiencePrevPage?.addEventListener('click', () => updateAudiencePreview(Math.max(1, audiencePage - 1)));
    audienceNextPage?.addEventListener('click', () => updateAudiencePreview(audiencePage + 1));
    reviewAudiencePrev?.addEventListener('click', () => updateAudiencePreview(Math.max(1, audiencePage - 1)));
    reviewAudienceNext?.addEventListener('click', () => updateAudiencePreview(audiencePage + 1));
    reviewAudienceButton?.addEventListener('click', () => {
        setReviewAudienceExpanded(reviewAudiencePanel?.hidden !== false);
    });
    reviewAudienceClose?.addEventListener('click', () => setReviewAudienceExpanded(false));
    document.querySelectorAll<HTMLElement>('[data-close-delivery-review]').forEach((button) => {
        button.addEventListener('click', closeDeliveryReview);
    });
    document.querySelectorAll<HTMLElement>('[data-close-audience-member-detail]').forEach((button) => {
        button.addEventListener('click', closeMemberDetail);
    });
    reviewConfirm?.addEventListener('click', () => {
        applyCouponDeliveryType();
        if (!form.reportValidity()) {
            closeDeliveryReview();
            return;
        }
        reviewConfirm.disabled = true;
        setText('deliveryReviewConfirmLabel', selectedDeliveryTiming() === 'scheduled' ? '予約しています…' : '配信しています…');
        finalSubmitApproved = true;
        applyCouponDeliveryType();
        closeDeliveryReview();
        form.requestSubmit();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        if (memberDetailModal?.hidden === false) {
            closeMemberDetail();
            return;
        }
        if (reviewModal?.hidden === false) closeDeliveryReview();
    });

    function applyCouponDeliveryType(): void {
        if (!isCouponDelivery) return;
        const hidden = field<HTMLInputElement>('delivery_type');
        if (hidden === null) return;
        const scheduled = selectedDeliveryTiming() === 'scheduled';
        const audience = selectedAudienceType();
        if (field<HTMLInputElement>('event_rank_up')?.checked === true) {
            hidden.value = 'event_rank_up_arrival';
            return;
        }
        if (audience === 'personal') {
            hidden.value = scheduled ? 'scheduled_personal' : 'immediate_personal';
            return;
        }
        if (audience === 'segment') {
            hidden.value = scheduled ? 'scheduled_segment_attributes' : 'immediate_segment_attributes';
            return;
        }
        hidden.value = scheduled ? 'scheduled_all' : 'immediate_all';
    }

    form.addEventListener('submit', (event) => {
        if (finalSubmitApproved || event.submitter?.hasAttribute('formaction')) return;
        if (formError) formError.hidden = true;
        const type = selectedAudienceType();
        if (type === 'personal' && selectedMembers.size === 0) {
            event.preventDefault();
            showFormError('配信する会員を1人以上選択してください。', searchInput);
            return;
        }
        if (type === 'segment' && !hasSegmentCriteria()) {
            event.preventDefault();
            showFormError('セグメント配信では、絞り込み条件を1つ以上指定してください。', field('segment_channel'));
            return;
        }
        if ((isCouponDelivery || couponToggle?.checked) && couponOptions().length === 0) {
            event.preventDefault();
            showFormError('配布するクーポンを選択してください。', couponChecks[0] ?? couponSelect);
            return;
        }
        applyCouponDeliveryType();
        if (notificationChannels === null || resolvedAudienceCount === null) {
            event.preventDefault();
            showFormError('配信対象と通知先を確認しています。少し待ってからもう一度お試しください。', field('delivery-audience'));
            updateAudiencePreview(1);
            return;
        }

        event.preventDefault();
        openDeliveryReview();
    });

    bindConditionPicker(form);
    renderSelectedMembers();
    syncAudienceType();
    syncDeliveryTiming();
    syncAudienceResolve();
    syncCoupon();
    syncPreview();
    renderNotificationSummary();
    updateCounter(titleInput, 'announcementTitleCounter', 200);
    updateCounter(bodyInput, 'announcementBodyCounter', 5000);
}

function initScheduledSegmentUi(): void {
    if (document.body.dataset.pageKind !== 'scheduled-delivery-form') return;
    const form = document.getElementById('scheduledDeliveryForm');
    if (!(form instanceof HTMLFormElement)) return;

    const checksByName = (name: string): HTMLInputElement[] => Array.from(form.querySelectorAll<HTMLInputElement>('input[type="checkbox"]')).filter((input) => input.name === name);
    const checkedLabels = (name: string): string[] => checksByName(name).filter((input) => input.checked).map((input) => input.dataset.label?.trim() || input.value);

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
        const months = checksByName('segment_birthday_months[]').filter((input) => input.checked).map((input) => `${input.value}月`);
        push('誕生月', listOperator(months.length), months.join('、'));
        const sex = form.querySelector<HTMLSelectElement>('#segment_sex');
        push('性別', '=', sex?.selectedOptions[0]?.textContent?.trim() && sex.value !== '' ? sex.selectedOptions[0].textContent.trim() : '');
        const rfm = checkedLabels('segment_rfm_segments[]');
        push('RFMセグメント', listOperator(rfm.length), rfm.join('、'));
        const tags = checkedLabels('segment_tag_ids[]');
        push('会員タグ', listOperator(tags.length, readCombineMode(form, 'segment_value_mode[tags]')), tags.join('、'));
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

export function bootstrapAnnouncementDelivery(): void {
    const boot = (): void => {
        initAnnouncementDelivery();
        initScheduledSegmentUi();
    };
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
}

bootstrapAnnouncementDelivery();

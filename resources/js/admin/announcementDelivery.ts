import { createMemberDetail, renderAudienceRows } from './delivery/audienceTable';
import { createDeliveryReview } from './delivery/deliveryReview';
import { fetchJson, field, setBlockEnabled, setText } from './delivery/dom';
import { createMemberPicker } from './delivery/memberPicker';
import {
    bindConditionPicker,
    hasSegmentCriteria,
    initScheduledSegmentUi,
    renderSegmentConditionSummary,
    segmentParams,
    syncValueModes,
} from './delivery/segmentConditions';
import type {
    AudiencePreviewResponse,
    AudienceResolveMode,
    AudienceType,
    DeliveryConfig,
    DeliveryTiming,
    NotificationChannelCounts,
    SelectedCoupon,
} from './delivery/types';

/** お知らせ・クーポン配信画面。配信対象・タイミング・クーポンの選択を同期し、対象者を確認してから送信する。 */
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
    const pinInput = field<HTMLInputElement>('is_pinned');
    const sendNotificationInput = field<HTMLInputElement>('send_notification');
    const couponToggle = field<HTMLInputElement>('attachCoupon');
    const couponPanel = field<HTMLElement>('couponPanel');
    const couponChecks = Array.from(form.querySelectorAll<HTMLInputElement>('input[name="delivery_coupon_ids[]"], input[name="coupon_id"]'));
    const formError = field<HTMLElement>('deliveryFormError');
    const audienceRows = field<HTMLTableSectionElement>('audiencePreviewRows');
    const audiencePrevPage = field<HTMLButtonElement>('audiencePrevPage');
    const audienceNextPage = field<HTMLButtonElement>('audienceNextPage');
    const reviewConfirm = field<HTMLButtonElement>('deliveryReviewConfirm');
    const selectedMembers = new Map<number, string>();
    let segmentCount: number | null = null;
    let segmentTimer: number | undefined;
    let audienceTimer: number | undefined;
    let audiencePage = 1;
    let notificationChannels: NotificationChannelCounts | null = null;
    let resolvedAudienceCount: number | null = null;
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

    function isCouponAttached(): boolean {
        return isCouponDelivery || couponToggle?.checked === true;
    }

    function selectedCoupons(): SelectedCoupon[] {
        if (!isCouponAttached()) return [];
        return couponChecks.filter((input) => input.checked).map((input) => ({
            id: input.value,
            name: (input.dataset.name || input.value).trim() || '選択したクーポン',
        }));
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

    function updateCounter(input: HTMLInputElement | HTMLTextAreaElement | null, counterId: string, max: number): void {
        if (!input) return;
        setText(counterId, `${input.value.length.toLocaleString('ja-JP')} / ${max.toLocaleString('ja-JP')}`);
    }

    const memberDetail = createMemberDetail(field<HTMLElement>('audienceMemberDetailModal'));
    const memberPicker = createMemberPicker({
        membersUrl: config.membersUrl,
        selectedMembers,
        isPersonal: () => selectedAudienceType() === 'personal',
        onSelectionChange: () => updateAudiencePreview(1),
        onRendered: updateSubmitLabel,
    });
    const review = createDeliveryReview({
        config,
        isCouponDelivery,
        notificationChannels: () => notificationChannels,
        audienceText,
        isScheduled: () => selectedDeliveryTiming() === 'scheduled',
        canChooseResolveMode,
        resolvesAtSend: () => selectedResolveMode() === 'at_send',
        resolveModeText,
        selectedCoupons,
        shouldSendNotification: () => sendNotificationInput?.checked !== false,
        scheduledAt,
        pinInput,
    });

    function syncCoupon(): void {
        const enabled = isCouponAttached();
        if (couponPanel) couponPanel.hidden = !enabled;
        couponChecks.forEach((input) => {
            input.disabled = input.dataset.expired === '1' || !enabled;
        });
        updateAudiencePreview(1);
        updateSubmitLabel();
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

    function syncAudienceType(): void {
        const type = selectedAudienceType();
        audienceRadios.forEach((radio) => {
            radio.closest('.ad-delivery-choice')?.classList.toggle('is-selected', radio.checked);
        });
        setBlockEnabled(personalBlock, type === 'personal');
        setBlockEnabled(segmentBlock, type === 'segment');
        toggleSegmentMinAmount();
        renderSegmentConditionSummary(form, type === 'segment');
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
        updateSubmitLabel();
        syncAudienceResolve();
    }

    function syncAudienceResolve(): void {
        const enabled = canChooseResolveMode();
        setBlockEnabled(audienceResolveBlock, enabled);
        resolveRadios.forEach((radio) => {
            radio.closest('.ad-timing-choice')?.classList.toggle('is-selected', radio.checked);
        });
        setText('audiencePreviewHint', !enabled
            ? 'この一覧の対象者を配信時点で保存します'
            : (selectedResolveMode() === 'at_send'
                ? 'いまの一致者です。配信時刻に条件をやり直して対象を決めます'
                : 'この一覧の対象者を予約時点で固定します'));
    }

    function audienceParams(page: number): URLSearchParams {
        const params = segmentParams(form);
        params.set('audience_type', selectedAudienceType());
        params.set('page', String(page));
        selectedCoupons().forEach((coupon) => params.append('delivery_coupon_ids[]', coupon.id));
        if (selectedAudienceType() === 'personal') {
            selectedMembers.forEach((_label, id) => params.append('target_member_ids[]', String(id)));
        }
        return params;
    }

    function renderAudiencePreview(data: AudiencePreviewResponse): void {
        audiencePage = data.page;
        resolvedAudienceCount = Number(data.count);
        notificationChannels = data.notification_channels;
        setText('audiencePreviewCount', `対象 ${Number(data.count).toLocaleString('ja-JP')}人`);
        setText('audiencePageLabel', `${data.page.toLocaleString('ja-JP')} / ${data.last_page.toLocaleString('ja-JP')}ページ`);
        renderAudienceRows(audienceRows, Array.isArray(data.members) ? data.members : [], memberDetail.open);
        if (audiencePrevPage) audiencePrevPage.disabled = data.page <= 1;
        if (audienceNextPage) audienceNextPage.disabled = data.page >= data.last_page;
    }

    function updateAudiencePreview(page = 1): void {
        window.clearTimeout(audienceTimer);
        setText('audiencePreviewCount', '確認中…');
        if (page === 1) {
            notificationChannels = null;
            resolvedAudienceCount = null;
        }
        audienceTimer = window.setTimeout(async () => {
            try {
                renderAudiencePreview(await fetchJson<AudiencePreviewResponse>(`${config.audiencePreviewUrl}?${audienceParams(page).toString()}`));
            } catch {
                setText('audiencePreviewCount', '対象者を取得できません');
                notificationChannels = null;
                resolvedAudienceCount = null;
                if (audienceRows) {
                    audienceRows.innerHTML = '<tr><td colspan="6" class="ad-audience-empty">対象者一覧を取得できませんでした</td></tr>';
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
                const data = await fetchJson<{ count?: number }>(`${config.segmentPreviewUrl}?${segmentParams(form).toString()}`);
                segmentCount = Number(data.count || 0);
                setText('segmentPreview', `対象 ${segmentCount.toLocaleString('ja-JP')}人`);
                setText('segmentChoiceCount', `${segmentCount.toLocaleString('ja-JP')}人`);
                updateSubmitLabel();
            } catch {
                segmentCount = null;
                setText('segmentPreview', '人数を取得できません');
                setText('segmentChoiceCount', '人数を確認');
                updateSubmitLabel();
            }
        }, 350);
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

    audienceRadios.forEach((radio) => radio.addEventListener('change', () => {
        memberPicker.render();
        syncAudienceType();
    }));
    timingRadios.forEach((radio) => radio.addEventListener('change', syncDeliveryTiming));
    resolveRadios.forEach((radio) => radio.addEventListener('change', syncAudienceResolve));

    document.querySelectorAll<HTMLInputElement | HTMLSelectElement>('.segment-filter').forEach((control) => {
        const sync = (): void => {
            toggleSegmentMinAmount();
            renderSegmentConditionSummary(form, selectedAudienceType() === 'segment');
            updateSegmentPreview();
            updateAudiencePreview(1);
        };
        control.addEventListener('change', sync);
        control.addEventListener('input', sync);
    });

    syncValueModes(form);

    couponToggle?.addEventListener('change', syncCoupon);
    couponChecks.forEach((input) => input.addEventListener('change', syncCoupon));
    titleInput?.addEventListener('input', () => updateCounter(titleInput, 'announcementTitleCounter', 200));
    bodyInput?.addEventListener('input', () => updateCounter(bodyInput, 'announcementBodyCounter', 5000));
    document.querySelectorAll<HTMLAnchorElement>('.ad-summary-audience-link, .ad-confirm-conditions-head a').forEach((link) => {
        link.addEventListener('click', () => {
            const details = field<HTMLDetailsElement>('audiencePreviewDetails');
            if (details) details.open = true;
        });
    });
    audiencePrevPage?.addEventListener('click', () => updateAudiencePreview(Math.max(1, audiencePage - 1)));
    audienceNextPage?.addEventListener('click', () => updateAudiencePreview(audiencePage + 1));
    reviewConfirm?.addEventListener('click', () => {
        applyCouponDeliveryType();
        if (!form.reportValidity()) {
            review.close();
            return;
        }
        reviewConfirm.disabled = true;
        setText('deliveryReviewConfirmLabel', selectedDeliveryTiming() === 'scheduled' ? '予約しています…' : '配信しています…');
        finalSubmitApproved = true;
        applyCouponDeliveryType();
        review.close();
        form.requestSubmit();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        if (memberDetail.isOpen()) {
            memberDetail.close();
            return;
        }
        if (review.isOpen()) review.close();
    });

    form.addEventListener('submit', (event) => {
        if (finalSubmitApproved || event.submitter?.hasAttribute('formaction')) return;
        if (formError) formError.hidden = true;
        const type = selectedAudienceType();
        if (type === 'personal' && selectedMembers.size === 0) {
            event.preventDefault();
            showFormError('配信する会員を1人以上選択してください。', field('memberSearch'));
            return;
        }
        if (type === 'segment' && !hasSegmentCriteria(form)) {
            event.preventDefault();
            showFormError('セグメント配信では、絞り込み条件を1つ以上指定してください。', field('segment_channel'));
            return;
        }
        if (isCouponAttached() && selectedCoupons().length === 0) {
            event.preventDefault();
            showFormError('配布するクーポンを選択してください。', couponChecks[0] ?? null);
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
        review.open(type === 'segment');
    });

    bindConditionPicker(form);
    memberPicker.render();
    syncAudienceType();
    syncDeliveryTiming();
    syncAudienceResolve();
    syncCoupon();
    updateCounter(titleInput, 'announcementTitleCounter', 200);
    updateCounter(bodyInput, 'announcementBodyCounter', 5000);
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

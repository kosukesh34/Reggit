import { lineQuotaCheck } from '../lineQuota';
import { field, formatDateTime, setText } from './dom';
import type { DeliveryConfig, NotificationChannelCounts, SelectedCoupon } from './types';

/** 送信前の確認モーダル。配信設定・通知先の内訳・LINE 通数・注意文を表示する。 */

type DeliveryReviewOptions = {
    config: DeliveryConfig;
    isCouponDelivery: boolean;
    notificationChannels: () => NotificationChannelCounts | null;
    audienceText: () => string;
    isScheduled: () => boolean;
    canChooseResolveMode: () => boolean;
    resolvesAtSend: () => boolean;
    resolveModeText: () => string;
    selectedCoupons: () => SelectedCoupon[];
    shouldSendNotification: () => boolean;
    scheduledAt: HTMLInputElement | null;
    pinInput: HTMLInputElement | null;
};

function copyReviewConditions(isSegment: boolean): void {
    const reviewBlock = field<HTMLElement>('deliveryReviewConditionBlock');
    const reviewConditions = field<HTMLElement>('deliveryReviewConditions');
    const summaryConditions = field<HTMLElement>('deliverySummaryConditions');
    if (!reviewBlock || !reviewConditions) return;
    const show = isSegment && summaryConditions !== null;
    reviewBlock.hidden = !show;
    reviewConditions.replaceChildren();
    if (!show || !summaryConditions) return;
    Array.from(summaryConditions.children).forEach((chip) => {
        const copy = chip.cloneNode(true) as HTMLElement;
        copy.querySelector('.ad-condition-remove')?.remove();
        reviewConditions.appendChild(copy);
    });
}

export function createDeliveryReview(options: DeliveryReviewOptions): {
    open: (isSegment: boolean) => void;
    close: () => void;
    isOpen: () => boolean;
} {
    const modal = field<HTMLElement>('deliveryReviewModal');
    const body = field<HTMLElement>('deliveryReviewBody');

    function close(): void {
        if (!modal) return;
        modal.hidden = true;
        document.body.classList.remove('ad-review-open');
    }

    function open(isSegment: boolean): void {
        const channels = options.notificationChannels();
        if (!modal || channels === null) return;
        const scheduledLabel = formatDateTime(options.scheduledAt?.value || '');
        setText('deliveryReviewAudienceLabel', options.audienceText());
        setText('deliveryReviewTiming', options.isScheduled() ? scheduledLabel : '今すぐ');
        const reviewResolveRow = field<HTMLElement>('deliveryReviewResolveRow');
        if (reviewResolveRow) reviewResolveRow.hidden = !options.canChooseResolveMode();
        setText('deliveryReviewResolve', options.resolveModeText());
        const coupons = options.selectedCoupons();
        setText('deliveryReviewCoupon', coupons.map((coupon) => coupon.name).join('・') || (options.isCouponDelivery ? '未選択' : '配布しない'));
        if (options.pinInput) setText('deliveryReviewPinned', options.pinInput.checked ? 'する' : 'しない');
        const sending = options.shouldSendNotification();
        setText('deliveryReviewLineCount', `${channels.line.toLocaleString('ja-JP')}人`);
        setText('deliveryReviewPushCount', `${channels.push.toLocaleString('ja-JP')}人`);

        const unreachable = field<HTMLElement>('deliveryReviewUnreachable');
        const optedOut = field<HTMLElement>('deliveryReviewOptedOut');
        const notifyOff = field<HTMLElement>('deliveryReviewNotifyOff');
        const channelList = field<HTMLElement>('deliveryReviewChannelList');
        if (notifyOff) notifyOff.hidden = sending;
        if (channelList) channelList.hidden = !sending;
        if (unreachable) {
            unreachable.hidden = !sending || channels.unreachable === 0;
            unreachable.textContent = sending && channels.unreachable > 0
                ? `${channels.unreachable.toLocaleString('ja-JP')}人はLINE・Push通知の送信先がありません。アプリ内のお知らせには掲載されます。`
                : '';
        }
        const quota = field<HTMLElement>('deliveryReviewQuota');
        if (quota) {
            const check = sending && options.config.lineQuota
                ? lineQuotaCheck(channels.line, coupons.length > 0 ? 2 : 1, options.config.lineQuota)
                : null;
            quota.hidden = check === null;
            quota.textContent = check?.message ?? '';
            quota.classList.toggle('is-over', check?.over === true);
            quota.setAttribute('role', check?.over === true ? 'alert' : 'status');
        }
        if (optedOut) {
            optedOut.hidden = !sending || channels.opted_out === 0;
            optedOut.textContent = sending && channels.opted_out > 0
                ? `${channels.opted_out.toLocaleString('ja-JP')}人は本人の通知設定によりLINE・Pushを送りません。`
                : '';
        }

        copyReviewConditions(isSegment);
        const scheduled = options.isScheduled();
        const resolveAtSend = scheduled && options.canChooseResolveMode() && options.resolvesAtSend();
        setText('deliveryReviewWarningTitle', scheduled ? '指定した日時に配信します' : '確認後すぐに配信します');
        const couponVerb = options.isCouponDelivery
            ? 'クーポンを配布'
            : (sending ? 'お知らせと通知を送信' : 'お知らせを掲載');
        setText('deliveryReviewWarningText', resolveAtSend
            ? `${scheduledLabel}に、その時点で条件へ一致する会員へ${couponVerb}します。`
            : (scheduled
                ? `${scheduledLabel}に、いま確認した対象会員へ${couponVerb}します。`
                : `確定すると対象会員へ${couponVerb}します。`));
        setText('deliveryReviewConfirmLabel', scheduled ? 'この内容で予約する' : 'この内容で今すぐ配信する');

        modal.hidden = false;
        document.body.classList.add('ad-review-open');
        modal.querySelector<HTMLButtonElement>('.ad-review-close')?.focus();
        window.requestAnimationFrame(() => {
            if (body) body.scrollTop = 0;
        });
    }

    document.querySelectorAll<HTMLElement>('[data-close-delivery-review]').forEach((button) => {
        button.addEventListener('click', close);
    });

    return { open, close, isOpen: () => modal?.hidden === false };
}

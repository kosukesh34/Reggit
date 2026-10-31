import type { LineQuota } from '../lineQuota';

export type AudienceType = 'immediate_bulk' | 'personal' | 'segment';
export type DeliveryTiming = 'immediate' | 'scheduled';
export type AudienceResolveMode = 'snapshot' | 'at_send';

export type DeliveryConfig = {
    membersUrl: string;
    segmentPreviewUrl: string;
    audiencePreviewUrl: string;
    memberCount: number;
    selectedMemberIds: number[];
    mode?: 'announcement' | 'coupon';
    /** LINEプランがONで、今月の通数を取得できたときだけ渡る */
    lineQuota?: LineQuota | null;
};

export type MemberSearchResult = {
    id: number;
    display_identifier?: string;
    member_number?: string | null;
    name?: string | null;
};

export type AudiencePreviewMember = {
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

export type NotificationChannelCounts = {
    line: number;
    push: number;
    unreachable: number;
    opted_out: number;
};

export type AudiencePreviewResponse = {
    count: number;
    members: AudiencePreviewMember[];
    notification_channels: NotificationChannelCounts;
    page: number;
    last_page: number;
    per_page: number;
};

export type SegmentConditionOperator = '=' | '≥' | '≤' | 'いずれか' | 'すべて';

export type SegmentConditionChip = {
    label: string;
    operator: SegmentConditionOperator;
    value: string;
    clear: () => void;
};

/** App\Enums\SegmentCombineMode と同じ値 */
export type SegmentCombineMode = 'any' | 'all';

/** 確認モーダルに出すクーポン */
export type SelectedCoupon = {
    id: string;
    name: string;
};

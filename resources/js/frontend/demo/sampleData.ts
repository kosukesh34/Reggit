/** デザイナー／デモ用のサンプルデータ（単一の定義元） */

import { DEMO_COUPON_PRIZES } from './demoCoupons';

const SAMPLE_COUPON_CODES = ['DRINK-FREE-2401', 'DESSERT-20-2401'];

export function buildBuiltinSampleCoupons(): Array<{
    id: string;
    coupon_name: string;
    description: string;
    status: string;
    valid_until: string;
    issued_at: string;
    image_url: string;
    code: string;
    use_display: string;
    is_sample_preview: true;
}> {
    const until = new Date();
    until.setDate(until.getDate() + 14);
    const validUntil = until.toISOString();
    const issuedAt = new Date().toISOString();
    return DEMO_COUPON_PRIZES.slice(0, 2).map((prize, index) => ({
        id: `sample-coupon-${index + 1}`,
        coupon_name: prize.coupon_name,
        description: prize.description,
        status: 'available',
        valid_until: validUntil,
        issued_at: issuedAt,
        image_url: prize.image_url,
        code: SAMPLE_COUPON_CODES[index] ?? `SAMPLE-${index + 1}`,
        use_display: 'barcode',
        is_sample_preview: true as const,
    }));
}

export function buildBuiltinSampleStampCards(): Array<{
    id: string;
    name: string;
    current_stamps: number;
    required_stamps: number;
    layout_style: string;
    image_url: string;
    reward_description: string;
    valid_until: string;
    is_sample_preview: true;
    reward_milestones: Array<{ stamp_count: number; description: string }>;
}> {
    return [
        {
            id: 'sample-stamp-1',
            name: '来店スタンプラリー',
            current_stamps: 6,
            required_stamps: 10,
            layout_style: 'grid',
            image_url: '/images/liff/stamp-card-bg.jpg',
            reward_description: '10個集めるとドリンク1杯無料',
            valid_until: '2026-12-31',
            is_sample_preview: true,
            reward_milestones: [
                { stamp_count: 5, description: 'お菓子プレゼント' },
                { stamp_count: 10, description: 'ドリンク無料' },
            ],
        },
    ];
}

export function buildBuiltinSampleGallery(): {
    categories: Array<{ id: number; name: string }>;
    items: Array<{
        id: number;
        category_id: number;
        title: string;
        description: string;
        image_url: string;
        size: string;
        is_sample_preview: true;
    }>;
} {
    return {
        categories: [
            { id: 1, name: 'メニュー' },
            { id: 2, name: '店内' },
        ],
        items: [
            {
                id: 1,
                category_id: 1,
                title: 'アイスラテ',
                description: 'なめらかなエスプレッソとミルク',
                image_url: '/images/liff/menu-iced-latte.jpg',
                size: 'large',
                is_sample_preview: true,
            },
            {
                id: 2,
                category_id: 1,
                title: 'カプチーノ',
                description: 'ふわふわフォームの定番',
                image_url: '/images/liff/menu-cappuccino.jpg',
                size: 'medium',
                is_sample_preview: true,
            },
            {
                id: 3,
                category_id: 1,
                title: '抹茶ラテ',
                description: 'ほろ苦い抹茶とミルク',
                image_url: '/images/liff/menu-matcha.jpg',
                size: 'medium',
                is_sample_preview: true,
            },
            {
                id: 4,
                category_id: 1,
                title: 'アボカドトースト',
                description: '朝の軽食にぴったり',
                image_url: '/images/liff/menu-avocado-toast.jpg',
                size: 'medium',
                is_sample_preview: true,
            },
            {
                id: 5,
                category_id: 2,
                title: '店内の雰囲気',
                description: '落ち着いて過ごせる空間',
                image_url: '/images/liff/home-hero.jpg',
                size: 'large',
                is_sample_preview: true,
            },
            {
                id: 6,
                category_id: 1,
                title: 'チーズケーキ',
                description: '本日のデザート',
                image_url: '/images/liff/menu-cheesecake.jpg',
                size: 'small',
                is_sample_preview: true,
            },
        ],
    };
}

export function buildBuiltinSampleQueueEntry(): {
    id: string;
    formatted_queue_number: string;
    status: string;
    party_size: number;
    waiting_count: number;
    ahead_count: number;
    estimated_wait_minutes: number;
    line_linked: boolean;
    is_sample_preview: true;
} {
    return {
        id: 'sample',
        formatted_queue_number: String(new Date().getDate()) + '12',
        status: 'waiting',
        party_size: 2,
        waiting_count: 5,
        ahead_count: 3,
        estimated_wait_minutes: 15,
        line_linked: true,
        is_sample_preview: true,
    };
}

export function buildBuiltinSampleReservations(): Array<{
    id: number;
    reserved_at: string;
    status: string;
    reservation_number: string;
    number_of_people: number;
    notes: string;
    is_sample_preview: true;
}> {
    const reservedAt = new Date();
    reservedAt.setDate(reservedAt.getDate() + 3);
    reservedAt.setHours(18, 0, 0, 0);
    return [
        {
            id: -1,
            reserved_at: reservedAt.toISOString(),
            status: 'confirmed',
            reservation_number: String(new Date().getDate()) + '1',
            number_of_people: 2,
            notes: '窓側の席希望',
            is_sample_preview: true,
        },
    ];
}

export function buildBuiltinSampleMemberData(): {
    is_member: true;
    points: number;
    current_rank: string;
    current_rank_label: string;
    current_rank_color: string;
    next_rank_label: string;
    points_to_next_rank: number;
    days_to_next_rank: number;
    days_required_for_next_rank: number;
    show_points_display: boolean;
    show_rank_progress: boolean;
    member_card_design: string;
    member_card_display_type: string;
    member_card_code_type: string;
    points_expiry: { expires_at_label: string; points_expiring: number };
    rank_up_history: Array<{ from_rank_label: string; to_rank_label: string; achieved_at: string }>;
    user: { name: string };
    member: { member_number: string; points: number };
    is_sample_preview: true;
} {
    return {
        is_member: true,
        points: 2450,
        current_rank: 'green',
        current_rank_label: 'GREEN',
        current_rank_color: '#0E7C66',
        next_rank_label: 'GOLD',
        points_to_next_rank: 550,
        days_to_next_rank: 18,
        days_required_for_next_rank: 30,
        show_points_display: true,
        show_rank_progress: true,
        member_card_design: 'emblem',
        member_card_display_type: 'card',
        member_card_code_type: 'barcode',
        points_expiry: {
            expires_at_label: '無期限',
            points_expiring: 0,
        },
        rank_up_history: [
            { from_rank_label: 'ブロンズ', to_rank_label: 'GREEN', achieved_at: '2025/11/03' },
        ],
        user: {
            name: 'Alex',
        },
        member: {
            member_number: '1234567890123456',
            points: 2450,
        },
        is_sample_preview: true,
    };
}

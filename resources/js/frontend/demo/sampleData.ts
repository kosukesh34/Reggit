/** デザイナー／デモ用のサンプルデータ（単一の定義元） */

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

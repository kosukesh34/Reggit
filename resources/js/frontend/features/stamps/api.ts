export type StampRewardMilestone = {
    stamp_count?: number | string;
    description?: string;
};

export type StampCard = {
    id?: number | string;
    current_stamps?: number;
    required_stamps?: number;
    completed_at?: string | null;
    layout_style?: string;
    design?: string;
    image_url?: string | null;
    stamp_images?: Record<string, string> | null;
    stamp_empty_images?: Record<string, string> | null;
    member_stamp_valid_until?: string | null;
    valid_until?: string | null;
    reward_milestones?: StampRewardMilestone[];
    name?: string;
    reward_description?: string | null;
    is_sample_preview?: boolean;
    cycle_mode?: string;
    cycle_count?: number;
    is_archived?: boolean;
    next_stamp_card_name?: string | null;
};

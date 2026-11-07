export type CouponRecord = {
    id: number | string;
    coupon_name?: string;
    description?: string;
    status?: string;
    design_template?: string;
    theme_color?: string;
    valid_from?: string;
    valid_until?: string;
    issued_at?: string;
    used_at?: string;
    image_url?: string;
    image_fit?: string;
    code?: string;
    use_display?: string;
    combine_division?: string;
    min_order_amount?: number | null;
    max_order_amount?: number | null;
    target_products?: string[];
    /** 使える曜日・時間帯（例「平日（祝日を除く） 10:00〜12:00」）。制限なしは null */
    usage_time_label?: string | null;
    [key: string]: unknown;
};

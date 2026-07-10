export const TAB_FEATURE_MAP_SAFE: Record<string, string> = {
    home: 'feature_home_enabled',
    products: 'feature_orders_enabled',
    orders: 'feature_orders_enabled',
    stamps: 'feature_stamps_enabled',
    coupons: 'feature_coupons_enabled',
    queue: 'feature_queue_enabled',
    reservations: 'feature_reservations_enabled',
    member: 'feature_members_enabled',
    gallery: 'feature_gallery_enabled',
    announcements: 'feature_announcements_enabled',
};

export function useTabFeatureMap(): Record<string, string> {
    return TAB_FEATURE_MAP_SAFE;
}

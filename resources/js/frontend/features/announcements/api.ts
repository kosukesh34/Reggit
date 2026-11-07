export type Announcement = {
    id?: number | string;
    title?: string;
    body?: string;
    action_path?: string | null;
    action_label?: string | null;
    image_urls?: string[];
    image_url?: string | null;
    published_at?: string;
    is_pinned?: boolean;
    is_read?: boolean;
};

import { ApiClient } from '../../../shared/utils/apiClient';
import { AppConstants } from '../../../shared/constants';

export type Announcement = {
    id?: number | string;
    title?: string;
    body?: string;
    image_url?: string | null;
    published_at?: string;
    is_pinned?: boolean;
    is_read?: boolean;
};

function client(): ApiClient {
    const app = window.app as { apiClient?: ApiClient } | undefined;
    return app?.apiClient ?? new ApiClient();
}

export async function fetchAnnouncements(): Promise<Announcement[]> {
    const result = await client().get(AppConstants.API_ENDPOINTS.ANNOUNCEMENTS);
    const data = result.success ? (result.data as { announcements?: Announcement[] }) : null;
    return data && Array.isArray(data.announcements) ? data.announcements : [];
}

export async function markAnnouncementRead(id: number | string): Promise<void> {
    try {
        await client().post(AppConstants.API_ENDPOINTS.ANNOUNCEMENT_READ(id), {});
    } catch {
        /* ignore */
    }
}

export function openAnnouncementDetail(id: number | string): void {
    const mgr = (window.app as { announcementManager?: { openDetail?: (id: number | string) => void } } | undefined)
        ?.announcementManager;
    if (typeof mgr?.openDetail === 'function') {
        void mgr.openDetail(id);
    }
}

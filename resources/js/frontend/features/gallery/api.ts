import { ApiClient } from '../../../shared/utils/apiClient';
import { AppConstants } from '../../../shared/constants';
import { isDemoDataMode } from '../../demo/demoMode';
import { buildBuiltinSampleGallery } from '../../demo/sampleData';

export type GalleryCategory = {
    id: string | number;
    name: string;
};

export type GalleryItem = {
    id?: string | number;
    category_id?: string | number;
    size?: string;
    image_url?: string;
    title?: string;
    description?: string;
};

export type GalleryPayload = {
    categories: GalleryCategory[];
    items: GalleryItem[];
};

function client(): ApiClient {
    const app = window.app as { apiClient?: ApiClient } | undefined;
    return app?.apiClient ?? new ApiClient();
}

function samplePayload(categoryId: string | number | null): GalleryPayload {
    const sample = buildBuiltinSampleGallery();
    const items = categoryId === null || categoryId === ''
        ? sample.items
        : sample.items.filter((item) => String(item.category_id) === String(categoryId));
    return {
        categories: sample.categories,
        items: items.map((item) => ({ ...item })),
    };
}

export async function fetchGallery(categoryId: string | number | null = null): Promise<GalleryPayload> {
    const url = categoryId !== null && categoryId !== ''
        ? `${AppConstants.API_ENDPOINTS.GALLERY}?category_id=${encodeURIComponent(String(categoryId))}`
        : AppConstants.API_ENDPOINTS.GALLERY;
    try {
        const result = await client().get(url);
        const data = (result.success ? result.data : null) as {
            categories?: GalleryCategory[];
            items?: GalleryItem[];
        } | null;
        let categories = data?.categories || [];
        let items = (data?.items || []).map((item, index) => ({
            ...item,
            id: item.id ?? index,
        }));
        if (items.length === 0 && isDemoDataMode()) {
            return samplePayload(categoryId);
        }
        if (categories.length === 0 && isDemoDataMode()) {
            categories = buildBuiltinSampleGallery().categories;
        }
        return { categories, items };
    } catch (error) {
        if (isDemoDataMode()) {
            return samplePayload(categoryId);
        }
        throw error;
    }
}

export function openGalleryDetail(id: number | string): void {
    const mgr = (window.app as { galleryManager?: { openDetail?: (id: number | string) => void } } | undefined)
        ?.galleryManager;
    if (typeof mgr?.openDetail === 'function') {
        void mgr.openDetail(id);
    }
}

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

export type BuilderBlockData = {
    id?: string | number;
    type?: string;
    hidden?: boolean;
    border_radius?: number | string | null;
    margin_bottom?: number | string | null;
    shadow?: boolean;
    padding?: number | string | null;
    background_color?: string;
    text_color?: string;
    design?: string;
    source?: string;
    [key: string]: unknown;
};

export type BuilderNavItem = {
    action?: string;
    link?: string;
    title?: string;
    label?: string;
    icon?: string;
    image_url?: string;
    description?: string;
    badge?: string;
    text?: string;
    caption?: string;
    variant?: string;
    color?: string;
    field?: string;
    value?: string;
    hint?: string;
    day?: string;
    time?: string;
    url?: string;
    type?: string;
    q?: string;
    a?: string;
};

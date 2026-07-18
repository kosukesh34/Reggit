export type TemplateItem = {
    id?: string;
    name?: string;
    label?: string;
    description?: string;
    category?: string;
    palette?: string;
    scope?: 'tab' | 'all' | 'all_tabs' | string;
    tab?: string;
    target_tabs?: string[];
    menu?: Array<{ tab: string; label?: string; icon?: string; enabled?: boolean }>;
    blocks?: unknown[];
    layouts?: Record<string, unknown[]>;
    theme?: Record<string, unknown>;
    tab_settings?: Record<string, unknown>;
    custom_css?: string;
    device_support?: string[];
};

export type GalleryAccent = {
    color: string;
    soft: string;
    icon: string;
};

export const CATEGORY_LABELS: Record<string, string> = {
    all: 'すべて',
    suite: '全画面アプリ',
    loyalty: '会員・リワード',
    order: '注文',
    booking: '予約',
    cafe: 'カフェ',
    salon: 'サロン',
    retail: '小売',
    clinic: 'クリニック',
    delivery: 'デリバリー',
    screen: '単画面',
    home: 'ホーム',
    member: '会員',
    reservation: '予約',
    campaign: '販促',
    story: 'ブランド',
    support: 'サポート',
    industry: '業種別',
    native: 'ネイティブ風',
    apple: '定番',
    luxury: 'ラグジュアリー',
    super: '全画面',
    other: 'その他',
};

/** 空キャンバスやギャラリー推奨枠で優先表示するテンプレ ID */
export const FEATURED_TEMPLATE_IDS = [
    'rg-suite-loyalty',
    'rg-suite-cafe',
    'rg-suite-salon',
    'rg-suite-order',
    'rg-suite-retail',
    'rg-screen-rewards',
    'rg-screen-pass',
    'rg-screen-flash',
    'rg-screen-next-visit',
    'rg-screen-vip',
] as const;

export const CATEGORY_ACCENTS: Record<string, GalleryAccent> = {
    suite: { color: '#007aff', soft: '#e8f1ff', icon: 'fa-mobile-alt' },
    loyalty: { color: '#5856d6', soft: '#eeedfe', icon: 'fa-crown' },
    order: { color: '#ff9500', soft: '#fff4e5', icon: 'fa-bag-shopping' },
    booking: { color: '#af52de', soft: '#f6e9fc', icon: 'fa-calendar-check' },
    cafe: { color: '#8B5E3C', soft: '#f5ebe3', icon: 'fa-coffee' },
    salon: { color: '#ff2d55', soft: '#ffe5ec', icon: 'fa-spa' },
    retail: { color: '#1c1c1e', soft: '#f2f2f7', icon: 'fa-shopping-bag' },
    clinic: { color: '#32ade6', soft: '#e5f6fc', icon: 'fa-stethoscope' },
    delivery: { color: '#ff3b30', soft: '#ffe8e6', icon: 'fa-motorcycle' },
    screen: { color: '#34c759', soft: '#e8f9ee', icon: 'fa-layer-group' },
    home: { color: '#2563eb', soft: '#dbeafe', icon: 'fa-home' },
    member: { color: '#7c3aed', soft: '#ede9fe', icon: 'fa-id-card' },
    reservation: { color: '#0891b2', soft: '#cffafe', icon: 'fa-calendar-alt' },
    campaign: { color: '#e11d48', soft: '#ffe4e6', icon: 'fa-bullhorn' },
    story: { color: '#c2410c', soft: '#ffedd5', icon: 'fa-book-open' },
    support: { color: '#0f766e', soft: '#ccfbf1', icon: 'fa-headset' },
    industry: { color: '#4f46e5', soft: '#e0e7ff', icon: 'fa-store' },
    native: { color: '#0f172a', soft: '#e2e8f0', icon: 'fa-mobile-alt' },
    apple: { color: '#007aff', soft: '#e8f1ff', icon: 'fa-apple' },
    luxury: { color: '#92400e', soft: '#fef3c7', icon: 'fa-gem' },
    super: { color: '#007aff', soft: '#e8f1ff', icon: 'fa-layer-group' },
    other: { color: '#57534e', soft: '#f5f5f4', icon: 'fa-th-large' },
};

const PRESET_PALETTES: Record<string, { label: string; accent: string }> = {
    apple_system: { label: 'Apple System', accent: '#007aff' },
    noir: { label: 'Noir / Stone', accent: '#1c1917' },
    ocean: { label: 'Blue / Cyan', accent: '#0284c7' },
    bordeaux: { label: 'Rose / Wine', accent: '#9f1239' },
    citrus: { label: 'Orange / Amber', accent: '#ea580c' },
    terracotta: { label: 'Clay / Warm', accent: '#c2410c' },
    lavender: { label: 'Violet / Soft', accent: '#7c3aed' },
    sand: { label: 'Sand / Sage', accent: '#78716c' },
    royal: { label: 'Indigo / Royal', accent: '#4f46e5' },
};

const TAB_LABELS: Record<string, string> = {
    home: 'ホーム',
    products: '商品',
    orders: '注文',
    stamps: 'スタンプ',
    coupons: 'クーポン',
    queue: '順番待ち',
    reservations: '予約',
    member: '会員',
    gallery: 'ギャラリー',
    announcements: 'お知らせ',
};

export function isAllScreensTemplate(tpl: TemplateItem): boolean {
    if (tpl.scope === 'all' || tpl.scope === 'all_tabs') return true;
    if (tpl.scope === 'tab') return false;
    return (
        tpl.layouts !== undefined &&
        tpl.layouts !== null &&
        typeof tpl.layouts === 'object' &&
        !Array.isArray(tpl.layouts) &&
        !Array.isArray(tpl.blocks)
    );
}

export function templateDisplayName(tpl: TemplateItem, fallbackIndex = 0): string {
    return tpl.label || tpl.name || `テンプレート ${fallbackIndex + 1}`;
}

export function templateCategoryKey(tpl: TemplateItem): string {
    if (isAllScreensTemplate(tpl)) {
        const rawSuite = String(tpl.category || 'suite');
        if (rawSuite !== '' && rawSuite !== 'other') return rawSuite === 'super' ? 'suite' : rawSuite;
        return 'suite';
    }
    const raw = tpl.category || 'other';
    if (CATEGORY_LABELS[raw] !== undefined) return raw;
    return raw || 'other';
}

export function categoryLabel(key: string): string {
    return CATEGORY_LABELS[key] || key || 'その他';
}

export function galleryAccentFor(tpl: TemplateItem): GalleryAccent {
    const themeAccent = String(tpl.theme?.accent_color || tpl.theme?.accent || '').trim();
    const preset = String(tpl.theme?.theme_preset || '');
    const presetMeta = PRESET_PALETTES[preset];
    const base = CATEGORY_ACCENTS[templateCategoryKey(tpl)] || CATEGORY_ACCENTS.other;
    if (themeAccent !== '') {
        return { ...base, color: themeAccent };
    }
    if (presetMeta !== undefined) {
        return { ...base, color: presetMeta.accent };
    }
    return base;
}

export function paletteLabelFor(tpl: TemplateItem): string {
    if (typeof tpl.palette === 'string' && tpl.palette.trim() !== '') {
        return tpl.palette;
    }
    const preset = String(tpl.theme?.theme_preset || '');
    if (PRESET_PALETTES[preset] !== undefined) {
        return PRESET_PALETTES[preset].label;
    }
    if (isAllScreensTemplate(tpl)) return 'Full App';
    return categoryLabel(templateCategoryKey(tpl));
}

export function previewBlocksFor(tpl: TemplateItem, tab?: string): Record<string, unknown>[] {
    if (isAllScreensTemplate(tpl) && tpl.layouts !== undefined) {
        const preferred =
            tab !== undefined && tab !== '' && Array.isArray(tpl.layouts[tab])
                ? tab
                : Array.isArray(tpl.layouts.home)
                  ? 'home'
                  : Object.keys(tpl.layouts)[0];
        if (preferred === undefined) return [];
        const rows = Array.isArray(tpl.layouts[preferred]) ? tpl.layouts[preferred] : [];
        return rows as Record<string, unknown>[];
    }
    if (tab !== undefined && tab !== '' && tpl.layouts !== undefined && Array.isArray(tpl.layouts[tab])) {
        return tpl.layouts[tab] as Record<string, unknown>[];
    }
    return Array.isArray(tpl.blocks) ? (tpl.blocks as Record<string, unknown>[]) : [];
}

export function templateTags(tpl: TemplateItem): string[] {
    const tags: string[] = [];
    const theme = tpl.theme || {};
    const bg = String(theme.background_style || 'solid');
    if (bg === 'gradient') tags.push('Gradient');
    else if (bg === 'image') tags.push('Photo');
    else tags.push('Light');

    if (isAllScreensTemplate(tpl)) {
        tags.push('全画面');
    } else if (Array.isArray(tpl.target_tabs) && tpl.target_tabs.length > 0) {
        const first = tpl.target_tabs[0];
        tags.push(TAB_LABELS[first] || first);
    } else {
        tags.push(categoryLabel(templateCategoryKey(tpl)));
    }

    const blocks = previewBlocksFor(tpl);
    const types = new Set(blocks.map((b) => String(b.type || '')));
    if (types.has('member_summary')) tags.push('会員');
    else if (types.has('hero')) tags.push('Hero');
    else if (types.has('collection')) tags.push('棚');
    else if (types.has('data')) tags.push('データ');
    else if (types.has('quick_actions')) tags.push('導線');

    return tags.slice(0, 3);
}

export function collectCategoryKeys(templates: TemplateItem[]): string[] {
    const keys = new Set<string>();
    templates.forEach((tpl) => {
        keys.add(templateCategoryKey(tpl));
    });
    const order = Object.keys(CATEGORY_LABELS).filter((k) => k !== 'all' && keys.has(k));
    const extras = [...keys].filter((k) => !CATEGORY_LABELS[k]).sort();
    return [...order, ...extras];
}

export function featuredTemplates(templates: TemplateItem[], limit = 6): TemplateItem[] {
    const byId = new Map(templates.filter((t) => t.id).map((t) => [String(t.id), t]));
    const featuredSet = new Set<string>(FEATURED_TEMPLATE_IDS);
    const picked: TemplateItem[] = [];
    FEATURED_TEMPLATE_IDS.forEach((id) => {
        const row = byId.get(id);
        if (row !== undefined) picked.push(row);
    });
    if (picked.length >= limit) return picked.slice(0, limit);
    templates.forEach((tpl) => {
        if (picked.length >= limit) return;
        if (tpl.id !== undefined && featuredSet.has(String(tpl.id))) return;
        picked.push(tpl);
    });
    return picked.slice(0, limit);
}

export function screenBgFor(tpl: TemplateItem): string {
    const theme = tpl.theme || {};
    const style = String(theme.background_style || 'solid');
    if (style === 'gradient') {
        return `linear-gradient(165deg, ${String(theme.bg_gradient_start || '#f8fafc')}, ${String(theme.bg_gradient_end || '#e2e8f0')})`;
    }
    if (style === 'image' && theme.bg_image_url) {
        const opacity = Math.min(80, Math.max(0, Number(theme.bg_overlay_opacity ?? 60))) / 100;
        return `linear-gradient(rgba(15,23,42,${opacity}), rgba(15,23,42,${opacity})), url(${String(theme.bg_image_url)}) center/cover`;
    }
    return String(theme.bg_color || '#f8fafc');
}

const FAVORITES_KEY = 'ld-template-favorites';

export function loadFavoriteIds(): Set<string> {
    try {
        const raw = localStorage.getItem(FAVORITES_KEY);
        if (raw === null || raw === '') return new Set();
        const parsed = JSON.parse(raw);
        return new Set(Array.isArray(parsed) ? parsed.map(String) : []);
    } catch {
        return new Set();
    }
}

export function saveFavoriteIds(ids: Set<string>): void {
    try {
        localStorage.setItem(FAVORITES_KEY, JSON.stringify([...ids]));
    } catch {
        // ignore quota / private mode
    }
}

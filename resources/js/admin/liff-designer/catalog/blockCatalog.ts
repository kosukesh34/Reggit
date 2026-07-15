import type { GlobalSettings, LiffBlock, MenuItem } from './types';

export const TAB_LABELS: Record<string, string> = {
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

export const TAB_ICONS: Record<string, string> = {
    home: 'fa-home',
    products: 'fa-utensils',
    orders: 'fa-receipt',
    stamps: 'fa-stamp',
    coupons: 'fa-ticket-alt',
    queue: 'fa-users',
    reservations: 'fa-calendar-alt',
    member: 'fa-user',
    gallery: 'fa-images',
    announcements: 'fa-bullhorn',
};

export const MENU_TABS = Object.keys(TAB_LABELS).filter((k) => k !== 'announcements');

export type BlockTypeMeta = {
    type: string;
    label: string;
    icon: string;
    desc: string;
    category: 'layout' | 'basic' | 'data' | 'store';
};

export const BLOCK_TYPES: BlockTypeMeta[] = [
    { type: 'hero', label: 'ヒーロー', icon: 'fa-star', desc: '大きな画像+テキスト', category: 'layout' },
    { type: 'heading', label: '見出し', icon: 'fa-heading', desc: 'セクション見出し', category: 'basic' },
    { type: 'text', label: 'テキスト', icon: 'fa-paragraph', desc: '説明文・本文', category: 'basic' },
    { type: 'image', label: '画像', icon: 'fa-image', desc: '画像を表示', category: 'basic' },
    { type: 'button', label: 'ボタン', icon: 'fa-mouse-pointer', desc: 'アクションボタン', category: 'basic' },
    { type: 'card', label: 'カード', icon: 'fa-id-card', desc: '画像+テキスト', category: 'layout' },
    { type: 'collection', label: 'コレクション', icon: 'fa-th-large', desc: '横スクロール一覧', category: 'layout' },
    { type: 'slider', label: 'スライダー', icon: 'fa-images', desc: '画像カルーセル', category: 'layout' },
    { type: 'icon_text', label: 'アイコン+文字', icon: 'fa-list-ul', desc: 'アイコン付きテキスト行', category: 'layout' },
    { type: 'list', label: 'リスト', icon: 'fa-list-ol', desc: '箇条書きリスト', category: 'basic' },
    { type: 'banner', label: 'バナー', icon: 'fa-bullhorn', desc: 'お知らせバナー', category: 'layout' },
    { type: 'spacer', label: '余白', icon: 'fa-arrows-alt-v', desc: 'スペースを追加', category: 'basic' },
    { type: 'divider', label: '区切り線', icon: 'fa-minus', desc: '区切り線を追加', category: 'basic' },
    { type: 'member_summary', label: '会員サマリー', icon: 'fa-id-badge', desc: 'ポイント・ランク表示', category: 'data' },
    { type: 'quick_actions', label: 'クイックアクション', icon: 'fa-grip', desc: 'タブへのショートカット', category: 'data' },
    { type: 'data', label: 'データ', icon: 'fa-database', desc: '動的データ表示', category: 'data' },
    { type: 'friend_add', label: '友だち追加', icon: 'fa-user-plus', desc: 'LINE友だち追加ボタン', category: 'data' },
    { type: 'button_group', label: 'ボタン並び', icon: 'fa-ellipsis-h', desc: 'ボタンを2〜3個横並び', category: 'basic' },
    { type: 'video', label: '動画', icon: 'fa-play-circle', desc: 'YouTube動画を埋め込み', category: 'layout' },
    { type: 'hours', label: '営業時間', icon: 'fa-clock', desc: '営業時間の一覧表', category: 'store' },
    { type: 'access', label: '店舗アクセス', icon: 'fa-map-marker-alt', desc: '住所・電話・地図リンク', category: 'store' },
    { type: 'social_links', label: 'SNSリンク', icon: 'fa-share-alt', desc: 'Instagram等のリンク', category: 'store' },
    { type: 'faq', label: 'よくある質問', icon: 'fa-question-circle', desc: '開閉式のQ&A', category: 'store' },
];

export const BLOCK_CATEGORIES = [
    { id: 'all', label: 'すべて' },
    { id: 'layout', label: 'レイアウト' },
    { id: 'basic', label: '基本' },
    { id: 'data', label: 'データ' },
    { id: 'store', label: '店舗' },
];

export const ACTION_TYPES = [
    { value: '', label: 'なし' },
    { value: 'tab', label: 'タブへ移動' },
    { value: 'url', label: '外部リンクを開く' },
    { value: 'tel', label: '電話をかける' },
    { value: 'mailto', label: 'メールを送る' },
];

export const DATA_SOURCES = [
    { value: 'products', label: '商品一覧' },
    { value: 'orders', label: '注文履歴' },
    { value: 'coupons', label: 'クーポン一覧' },
    { value: 'stamps', label: 'スタンプカード' },
    { value: 'announcements', label: 'お知らせ' },
    { value: 'queue', label: '順番待ち' },
    { value: 'reservations', label: '予約一覧' },
    { value: 'gallery', label: 'ギャラリー' },
    { value: 'member_card', label: '会員カード' },
];

export const ALIGN_OPTIONS = [
    { value: 'left', label: '左' },
    { value: 'center', label: '中央' },
    { value: 'right', label: '右' },
];

export const BLOCK_DESIGNS: Record<string, Array<{ value: string; label: string }>> = {
    member_summary: [
        { value: 'rewards', label: 'リワード（標準）' },
        { value: 'wallet', label: 'ウォレット' },
        { value: 'compact', label: 'コンパクト' },
        { value: 'emblem', label: 'エンブレム' },
        { value: 'minimal', label: 'ミニマル' },
        { value: 'split', label: 'スプリット' },
        { value: 'glass', label: 'グラス' },
    ],
    hero: [
        { value: 'editorial', label: 'エディトリアル（標準）' },
        { value: 'minimal', label: 'ミニマル' },
        { value: 'split', label: 'スプリット' },
        { value: 'centered', label: 'センター' },
    ],
    heading: [
        { value: 'plain', label: '標準' },
        { value: 'section', label: 'セクション見出し' },
        { value: 'eyebrow', label: '英字ラベル風' },
        { value: 'underline', label: '下線アクセント' },
    ],
    text: [
        { value: 'body', label: '本文' },
        { value: 'lead', label: 'リード文' },
        { value: 'note', label: '注釈カード' },
        { value: 'quote', label: '引用風' },
    ],
    image: [
        { value: 'rounded', label: '角丸' },
        { value: 'full_bleed', label: 'フルブリード' },
        { value: 'polaroid', label: 'ポラロイド' },
        { value: 'arch', label: 'アーチ' },
    ],
    banner: [
        { value: 'promo', label: 'プロモ（標準）' },
        { value: 'outline', label: 'アウトライン' },
        { value: 'soft', label: 'ソフト' },
        { value: 'pill', label: 'ピル' },
    ],
    card: [
        { value: 'editorial', label: '縦型（標準）' },
        { value: 'horizontal', label: '横型' },
        { value: 'overlay', label: 'オーバーレイ' },
    ],
    button: [
        { value: 'cta', label: '塗りつぶし（標準）' },
        { value: 'outline', label: 'アウトライン' },
        { value: 'soft', label: 'ソフト' },
    ],
    quick_actions: [
        { value: 'grid', label: 'グリッド（標準）' },
        { value: 'pill', label: 'ピル' },
        { value: 'compact', label: 'コンパクト' },
    ],
    collection: [
        { value: 'product_shelf', label: '商品棚' },
        { value: 'editorial', label: 'エディトリアル' },
        { value: 'compact', label: 'コンパクト' },
        { value: 'magazine', label: 'マガジン' },
    ],
    slider: [
        { value: 'hero', label: 'ヒーロー' },
        { value: 'cards', label: 'カード' },
        { value: 'minimal', label: 'ミニマル' },
    ],
    hours: [
        { value: 'table', label: '表形式' },
        { value: 'card', label: 'カード' },
        { value: 'timeline', label: 'タイムライン' },
    ],
    access: [
        { value: 'card', label: '店舗カード' },
        { value: 'map', label: '地図CTA' },
        { value: 'minimal', label: 'ミニマル' },
    ],
    faq: [
        { value: 'accordion', label: 'アコーディオン' },
        { value: 'cards', label: 'カード' },
        { value: 'minimal', label: 'ミニマル' },
    ],
    data: [
        { value: '', label: '自動' },
        { value: 'cards', label: 'カード' },
        { value: 'timeline', label: 'タイムライン' },
        { value: 'compact', label: 'コンパクト' },
        { value: 'premium', label: 'プレミアム' },
    ],
    friend_add: [
        { value: 'solid', label: '塗りつぶし（標準）' },
        { value: 'outline', label: 'アウトライン' },
        { value: 'soft', label: 'ソフト' },
    ],
};

export const MEMBER_CARD_DESIGNS = [
    { value: '', label: '店舗設定に従う' },
    { value: 'default', label: '標準' },
    { value: 'logo_top', label: 'ロゴ上' },
    { value: 'logo_center', label: 'ロゴ中央' },
    { value: 'logo_header', label: 'ロゴヘッダー' },
    { value: 'compact', label: 'コンパクト' },
    { value: 'split_banner', label: 'スプリット' },
    { value: 'minimal_line', label: 'ミニマル' },
    { value: 'emblem', label: 'エンブレム' },
];

export const SOCIAL_LINK_TYPES = [
    { value: 'instagram', label: 'Instagram' },
    { value: 'x', label: 'X' },
    { value: 'facebook', label: 'Facebook' },
    { value: 'youtube', label: 'YouTube' },
    { value: 'tiktok', label: 'TikTok' },
    { value: 'line', label: 'LINE' },
    { value: 'website', label: 'Webサイト' },
];

export function defaultGlobalSettings(): GlobalSettings {
    return {
        content_padding: 18,
        section_gap: 16,
        radius_scale: 'soft',
        tab_style: 'default',
        background_style: 'solid',
        bg_color: '#f9fafb',
        content_width: 'comfy',
        bg_overlay_opacity: 60,
        system_ui_style: 'refined',
        system_card_style: 'soft',
        system_media_shape: 'rounded',
        system_header_style: 'minimal',
        member_card_design: '',
        member_screen_design: 'wallet',
        coupon_screen_design: 'ticket',
        reservation_screen_design: 'cards',
        activity_screen_design: 'cards',
        accent_color: '#1c1917',
        theme_preset: '',
    };
}

export function defaultTabSettings(): Record<string, unknown> {
    return { background_color: '#fafaf9', background_image: '', background_size: 'cover' };
}

export function createBlockId(counter: { n: number }): string {
    counter.n += 1;
    return `b${counter.n}`;
}

function pickDesign(type: string, design: unknown): string {
    const options = BLOCK_DESIGNS[type];
    if (options === undefined) {
        return typeof design === 'string' ? design : '';
    }
    const value = typeof design === 'string' ? design : '';
    if (options.some((o) => o.value === value)) {
        return value;
    }
    return options[0]?.value ?? '';
}

export function normalizeBlock(item: Record<string, unknown>, idCounter: { n: number }): LiffBlock {
    const rawType = String(item.type || 'text');
    let type = rawType;
    if (type === 'label') type = 'heading';
    if (type === 'gallery') type = 'data';
    if (type === 'form') type = 'text';

    const id = typeof item.id === 'string' && item.id !== '' ? item.id : createBlockId(idCounter);
    const block: LiffBlock = {
        id,
        type,
        border_radius: item.border_radius ?? 10,
        padding: item.padding ?? null,
        margin_bottom: item.margin_bottom ?? 0,
        text_align: item.text_align || 'left',
        background_color: item.background_color || '',
        text_color: item.text_color || '',
        opacity: item.opacity ?? 100,
        shadow: item.shadow ?? false,
    };

    switch (type) {
        case 'hero':
            block.title = item.title || 'ようこそ！';
            block.subtitle = item.subtitle || item.text || 'Reggitへ';
            block.image_url = item.image_url || '';
            block.gradient_start = item.gradient_start || '#667eea';
            block.gradient_end = item.gradient_end || '#764ba2';
            block.text_align = item.text_align || 'center';
            block.design = pickDesign('hero', item.design);
            break;
        case 'heading':
            block.text = item.text || item.title || '見出し';
            block.size = item.size || 'medium';
            block.text_color = item.text_color || '#1f2937';
            block.design = pickDesign('heading', item.design);
            break;
        case 'text':
            block.text = item.text || item.body || 'テキスト';
            block.text_color = item.text_color || '#4b5563';
            block.font_size = item.font_size || 13;
            block.design = pickDesign('text', item.design);
            break;
        case 'image':
            block.image_url = item.image_url || 'https://picsum.photos/seed/img1/750/400';
            block.alt = item.alt || 'サンプル画像';
            block.fit = item.fit || 'cover';
            block.design = pickDesign('image', item.design);
            break;
        case 'button':
            block.text = item.text || item.button_label || 'ボタン';
            block.color = item.color || '#06C755';
            block.text_color = item.text_color || '#ffffff';
            block.action = item.action || '';
            block.full_width = item.full_width !== false;
            block.text_align = 'center';
            block.design = pickDesign('button', item.design);
            break;
        case 'card':
            block.title = item.title || '本日のおすすめ';
            block.description = item.description || 'シェフが厳選した季節の素材を使った一品です。';
            block.image_url = item.image_url || 'https://picsum.photos/seed/card1/600/300';
            block.image_height = item.image_height || 140;
            block.action = item.action || item.link || '';
            block.design = pickDesign('card', item.design);
            break;
        case 'collection':
            block.items = item.items || [
                { title: 'おすすめ商品', image_url: 'https://picsum.photos/seed/col1/300/300', description: '人気No.1', action: '' },
                { title: '季節限定', image_url: 'https://picsum.photos/seed/col2/300/300', description: '期間限定メニュー', action: '' },
                { title: '新メニュー', image_url: 'https://picsum.photos/seed/col3/300/300', description: 'NEW', action: '' },
                { title: 'セット割', image_url: 'https://picsum.photos/seed/col4/300/300', description: 'お得なセット', action: '' },
            ];
            block.layout = item.layout || 'scroll';
            block.columns = item.columns ?? 2;
            block.max_rows = item.max_rows ?? 0;
            block.item_width = item.item_width || 140;
            block.item_image_height = item.item_image_height || 100;
            block.gap = item.gap ?? 8;
            block.show_description = item.show_description !== false;
            block.item_action = item.item_action || '';
            block.section_label = item.section_label || item.title || '';
            block.design = pickDesign('collection', item.design);
            break;
        case 'slider':
            block.slides = item.slides || [
                { image_url: 'https://picsum.photos/seed/slide1/750/400', caption: 'キャンペーン実施中' },
                { image_url: 'https://picsum.photos/seed/slide2/750/400', caption: '新メニュー登場' },
                { image_url: 'https://picsum.photos/seed/slide3/750/400', caption: 'ポイント2倍デー' },
            ];
            block.slide_height = item.slide_height || 160;
            block.auto_play = item.auto_play !== false;
            block.show_dots = item.show_dots !== false;
            block.section_label = item.section_label || item.title || '';
            block.design = pickDesign('slider', item.design);
            break;
        case 'icon_text':
            block.rows = item.rows || [
                { icon: 'fa-check-circle', text: '特徴1', color: '#06C755' },
                { icon: 'fa-check-circle', text: '特徴2', color: '#06C755' },
                { icon: 'fa-check-circle', text: '特徴3', color: '#06C755' },
            ];
            break;
        case 'list':
            block.items = item.items || item.list_items || ['項目1', '項目2', '項目3'];
            block.style = item.style || 'bullet';
            block.text_color = item.text_color || '#374151';
            break;
        case 'banner':
            block.text = item.text || 'お知らせテキスト';
            block.color = item.color || '#3b82f6';
            block.text_color = item.text_color || '#ffffff';
            block.icon = item.icon || 'fa-info-circle';
            block.action = item.action || item.link || '';
            block.text_align = item.text_align || 'center';
            block.design = pickDesign('banner', item.design);
            break;
        case 'spacer':
            block.height = item.height || 16;
            break;
        case 'divider':
            block.divider_color = item.divider_color || '#e5e7eb';
            block.divider_style = item.divider_style || 'solid';
            break;
        case 'data':
            block.source = item.source === 'member' ? 'member_card' : (item.source || (rawType === 'gallery' ? 'gallery' : 'products'));
            block.card_design = typeof item.card_design === 'string' ? item.card_design : '';
            block.design = pickDesign('data', item.design);
            break;
        case 'member_summary':
            block.greeting = item.greeting || '';
            block.design = pickDesign('member_summary', item.design);
            break;
        case 'quick_actions':
            block.design = pickDesign('quick_actions', item.design);
            block.actions = Array.isArray(item.actions) && item.actions.length > 0 ? item.actions : [
                { icon: 'fa-utensils', label: '注文', action: '#products' },
                { icon: 'fa-ticket-alt', label: 'クーポン', action: '#coupons' },
                { icon: 'fa-stamp', label: 'スタンプ', action: '#stamps' },
                { icon: 'fa-calendar-check', label: '予約', action: '#reservations' },
            ];
            break;
        case 'friend_add':
            block.text = item.text || '友だち追加';
            block.color = item.color || '#06C755';
            block.text_color = item.text_color || '#ffffff';
            block.url = item.url || '';
            block.design = pickDesign('friend_add', item.design);
            break;
        case 'hours':
            block.title = item.title || '営業時間';
            block.note = item.note || '';
            block.rows = Array.isArray(item.rows) && item.rows.length > 0 ? item.rows : [
                { day: '月〜金', time: '11:00 - 21:00' },
                { day: '土・日・祝', time: '10:00 - 22:00' },
                { day: '定休日', time: '水曜日' },
            ];
            block.design = pickDesign('hours', item.design);
            break;
        case 'access':
            block.title = item.title || '店舗情報';
            block.address = item.address || '東京都渋谷区○○ 1-2-3';
            block.phone = item.phone || '03-1234-5678';
            block.map_url = item.map_url || '';
            block.note = item.note || '';
            block.design = pickDesign('access', item.design);
            break;
        case 'social_links':
            block.links = Array.isArray(item.links) && item.links.length > 0 ? item.links : [
                { type: 'instagram', url: 'https://instagram.com/' },
                { type: 'x', url: 'https://x.com/' },
                { type: 'line', url: 'https://line.me/' },
            ];
            break;
        case 'faq':
            block.title = item.title || 'よくある質問';
            block.items = Array.isArray(item.items) && item.items.length > 0 ? item.items : [
                { q: '予約は必要ですか？', a: '当日のご来店も歓迎ですが、ご予約いただくとスムーズです。' },
                { q: '支払い方法は？', a: '現金・クレジットカード・QR決済がご利用いただけます。' },
            ];
            block.design = pickDesign('faq', item.design);
            break;
        case 'video':
            block.youtube_id = item.youtube_id || '';
            block.caption = item.caption || '';
            break;
        case 'button_group':
            block.buttons = Array.isArray(item.buttons) && item.buttons.length > 0 ? item.buttons : [
                { text: '予約する', action: '#reservations', variant: 'solid', color: '' },
                { text: 'メニュー', action: '#products', variant: 'outline', color: '' },
            ];
            break;
        default:
            block.text = item.text || item.body || '';
            block._unsupported = true;
    }

    return block;
}

export function normalizeMenuItems(raw: unknown): MenuItem[] {
    const byTab: Record<string, Record<string, unknown>> = {};
    if (Array.isArray(raw)) {
        raw.forEach((item) => {
            if (item !== null && typeof item === 'object' && 'tab' in item) {
                const tab = String((item as { tab: unknown }).tab);
                byTab[tab] = item as Record<string, unknown>;
            }
        });
    }
    const items = MENU_TABS.map((tab, i) => {
        const saved = byTab[tab] || {};
        return {
            tab,
            label: typeof saved.label === 'string' && saved.label !== '' ? saved.label : TAB_LABELS[tab],
            icon: typeof saved.icon === 'string' && saved.icon !== '' ? saved.icon : TAB_ICONS[tab],
            order: typeof saved.order === 'number' ? saved.order : i,
            enabled: saved.enabled !== false,
        };
    });
    items.sort((a, b) => a.order - b.order);
    items.forEach((item, i) => {
        item.order = i;
    });
    return items;
}

export function collectLegacyNotes(rawLayouts: Record<string, unknown[]>): string[] {
    const notes: string[] = [];
    Object.values(rawLayouts).forEach((rows) => {
        if (!Array.isArray(rows)) return;
        rows.forEach((item) => {
            if (item === null || typeof item !== 'object') return;
            const type = String((item as { type?: unknown }).type || '');
            if (type === 'gallery' && !notes.includes('gallery')) {
                notes.push('旧「ギャラリー」ブロックは「データ（ギャラリー）」に変換しました');
            }
            if (type === 'form' && !notes.includes('form')) {
                notes.push('旧「フォーム」ブロックは「テキスト」に変換しました（専用編集は非対応）');
            }
            if (type === 'label' && !notes.includes('label')) {
                notes.push('旧「ラベル」ブロックは「見出し」に変換しました');
            }
        });
    });
    return notes;
}

export function createEmptyLayouts(): Record<string, LiffBlock[]> {
    const layouts: Record<string, LiffBlock[]> = {};
    Object.keys(TAB_LABELS).forEach((key) => {
        layouts[key] = [];
    });
    return layouts;
}

export function findBlockMeta(type: string): BlockTypeMeta | undefined {
    return BLOCK_TYPES.find((b) => b.type === type);
}

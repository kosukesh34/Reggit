import { ensureLiffDesignerModules } from '../ensureModules';

/**
 * LIFFデザイナー — ブロック種別ごとのデザインバリエーション定義
 */
    const MEMBER_CARD_DESIGNS = [
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

    const OPTIONS = {
        member_summary: [
            { value: 'rewards', label: 'リワード（標準）' },
            { value: 'wallet', label: 'ウォレット' },
            { value: 'compact', label: 'コンパクト' },
            { value: 'emblem', label: 'エンブレム' },
            { value: 'minimal', label: 'ミニマル' },
            { value: 'split', label: 'スプリット' },
            { value: 'glass', label: 'グラス' },
            { value: 'tier', label: 'メンバーシップ階層' },
        ],
        hero: [
            { value: 'editorial', label: 'エディトリアル（標準）' },
            { value: 'minimal', label: 'ミニマル' },
            { value: 'split', label: 'スプリット' },
            { value: 'centered', label: 'センター' },
            { value: 'apple', label: 'Apple' },
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
            { value: 'countdown', label: 'フラッシュセール' },
        ],
        card: [
            { value: 'editorial', label: '縦型（標準）' },
            { value: 'horizontal', label: '横型' },
            { value: 'overlay', label: 'オーバーレイ' },
            { value: 'featured', label: 'フィーチャー' },
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
            { value: 'chip_scroll', label: 'カテゴリチップ' },
            { value: 'picks', label: 'スタッフおすすめ' },
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
    };

    const DEFAULTS = {
        member_summary: 'rewards',
        hero: 'editorial',
        heading: 'plain',
        text: 'body',
        image: 'rounded',
        banner: 'promo',
        card: 'editorial',
        button: 'cta',
        quick_actions: 'grid',
        collection: 'product_shelf',
        slider: 'hero',
        hours: 'table',
        access: 'card',
        faq: 'accordion',
        data: '',
    };

    function allowedValues(blockType) {
        const opts = OPTIONS[blockType];
        return opts ? opts.map((o) => o.value) : [];
    }

    function normalize(blockType, value) {
        const allowed = allowedValues(blockType);
        const def = DEFAULTS[blockType] || '';
        if (!value || allowed.indexOf(value) < 0) return def;
        return value;
    }

    function normalizeCardDesign(value) {
        if (!value) return '';
        const allowed = MEMBER_CARD_DESIGNS.map((o) => o.value).filter(Boolean);
        return allowed.indexOf(value) >= 0 ? value : '';
    }

    function addDesignPicker(ld, panel, block, blockType, field) {
        const key = field || 'design';
        const opts = OPTIONS[blockType];
        if (!opts || !ld || typeof ld.addSel !== 'function') return;
        if (block[key] === undefined || block[key] === null || block[key] === '') {
            block[key] = DEFAULTS[blockType] || opts[0].value;
        }
        ld.addSel(panel, 'デザイン', block[key], opts, (v) => {
            block[key] = v;
        });
        const note = document.createElement('div');
        note.className = 'ld-settings__hint ld-settings__hint--design';
        note.textContent = '選択したデザインは中央プレビューとユーザー画面の両方に反映されます。';
        panel.appendChild(note);
    }

    function addCardDesignPicker(ld, panel, block) {
        if (!ld || typeof ld.addSel !== 'function') return;
        block.card_design = normalizeCardDesign(block.card_design || '');
        ld.addSel(panel, '会員証デザイン', block.card_design, MEMBER_CARD_DESIGNS, (v) => {
            block.card_design = v;
        });
        const note = document.createElement('div');
        note.className = 'ld-settings__hint';
        note.textContent = '空欄の場合はランク設定の会員証デザインが使われます。';
        panel.appendChild(note);
    }

export const blockDesigns = {
    OPTIONS,
    MEMBER_CARD_DESIGNS,
    DEFAULTS,
    allowedValues,
    normalize,
    normalizeCardDesign,
    addDesignPicker,
    addCardDesignPicker,
};


ensureLiffDesignerModules().blockDesigns = blockDesigns;

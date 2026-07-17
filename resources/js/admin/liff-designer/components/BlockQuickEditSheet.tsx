import React from 'react';
import { BLOCK_DESIGNS, DATA_SOURCES, findBlockMeta, MEMBER_CARD_DESIGNS } from '../catalog/blockCatalog';
import type { LiffBlock } from '../catalog/types';
import { ActionField, ColorField, DesignPicker, ImageUrlField, RangeField, SelectField, TextArea, TextInput } from './fields/FormFields';

type RecordItem = Record<string, unknown>;

function SheetGroup({ label, children }: { label: string; children: React.ReactNode }) {
    return (
        <div className="ld-sheet__group">
            <p className="ld-sheet__group-label">{label}</p>
            {children}
        </div>
    );
}

function updateArrayItem<T extends RecordItem>(items: T[], index: number, patch: Partial<T>): T[] {
    return items.map((item, i) => (i === index ? { ...item, ...patch } : item));
}

function CollectionQuickEdit({ block, onChange }: { block: LiffBlock; onChange: (patch: Partial<LiffBlock>) => void }) {
    const items = (Array.isArray(block.items) ? block.items : []) as RecordItem[];
    return (
        <>
            <TextInput
                label="セクション見出し"
                value={String(block.section_label || '')}
                onChange={(v) => onChange({ section_label: v })}
            />
            <RangeField
                label="画像の高さ"
                value={Number(block.item_image_height || 100)}
                min={40}
                max={200}
                onChange={(v) => onChange({ item_image_height: v })}
            />
            {items.slice(0, 6).map((item, i) => (
                <SheetGroup key={i} label={`項目 ${i + 1}`}>
                    <TextInput
                        label="タイトル"
                        value={String(item.title || '')}
                        onChange={(v) => onChange({ items: updateArrayItem(items, i, { title: v }) })}
                    />
                    <ImageUrlField
                        label="画像"
                        compact
                        value={String(item.image_url || '')}
                        onChange={(v) => onChange({ items: updateArrayItem(items, i, { image_url: v }) })}
                    />
                    <TextInput
                        label="説明"
                        value={String(item.description || '')}
                        onChange={(v) => onChange({ items: updateArrayItem(items, i, { description: v }) })}
                    />
                    <ActionField
                        label="タップしたとき"
                        value={String(item.action || '')}
                        onChange={(v) => onChange({ items: updateArrayItem(items, i, { action: v }) })}
                    />
                </SheetGroup>
            ))}
            {items.length > 6 ? (
                <p className="ld-form-hint">残り {items.length - 6} 件は詳細編集モードで変更できます。</p>
            ) : null}
        </>
    );
}

function SliderQuickEdit({ block, onChange }: { block: LiffBlock; onChange: (patch: Partial<LiffBlock>) => void }) {
    const slides = (Array.isArray(block.slides) ? block.slides : []) as RecordItem[];
    return (
        <>
            <TextInput
                label="セクション見出し"
                value={String(block.section_label || '')}
                onChange={(v) => onChange({ section_label: v })}
            />
            <RangeField
                label="スライドの高さ"
                value={Number(block.slide_height || 160)}
                min={80}
                max={300}
                onChange={(v) => onChange({ slide_height: v })}
            />
            {slides.map((slide, i) => (
                <SheetGroup key={i} label={`スライド ${i + 1}`}>
                    <ImageUrlField
                        label="画像"
                        compact
                        value={String(slide.image_url || '')}
                        onChange={(v) => onChange({ slides: updateArrayItem(slides, i, { image_url: v }) })}
                    />
                    <TextInput
                        label="キャプション"
                        value={String(slide.caption || '')}
                        onChange={(v) => onChange({ slides: updateArrayItem(slides, i, { caption: v }) })}
                    />
                </SheetGroup>
            ))}
        </>
    );
}

function QuickActionsEdit({ block, onChange }: { block: LiffBlock; onChange: (patch: Partial<LiffBlock>) => void }) {
    const actions = (Array.isArray(block.actions) ? block.actions : []) as RecordItem[];
    return (
        <>
            {actions.map((action, i) => (
                <SheetGroup key={i} label={`アクション ${i + 1}`}>
                    <TextInput
                        label="ラベル"
                        value={String(action.label || '')}
                        onChange={(v) => onChange({ actions: updateArrayItem(actions, i, { label: v }) })}
                    />
                    <ActionField
                        label="タップしたとき"
                        value={String(action.action || '')}
                        onChange={(v) => onChange({ actions: updateArrayItem(actions, i, { action: v }) })}
                    />
                </SheetGroup>
            ))}
        </>
    );
}

function IconTextQuickEdit({ block, onChange }: { block: LiffBlock; onChange: (patch: Partial<LiffBlock>) => void }) {
    const rows = (Array.isArray(block.rows) ? block.rows : []) as RecordItem[];
    return (
        <>
            {rows.map((row, i) => (
                <SheetGroup key={i} label={`行 ${i + 1}`}>
                    <TextInput
                        label="テキスト"
                        value={String(row.text || '')}
                        onChange={(v) => onChange({ rows: updateArrayItem(rows, i, { text: v }) })}
                    />
                    <ColorField
                        label="アイコン色"
                        value={String(row.color || '#06C755')}
                        onChange={(v) => onChange({ rows: updateArrayItem(rows, i, { color: v }) })}
                    />
                </SheetGroup>
            ))}
        </>
    );
}

function SocialLinksQuickEdit({ block, onChange }: { block: LiffBlock; onChange: (patch: Partial<LiffBlock>) => void }) {
    const links = (Array.isArray(block.links) ? block.links : []) as RecordItem[];
    return (
        <>
            {links.map((link, i) => (
                <SheetGroup key={i} label={`リンク ${i + 1}`}>
                    <TextInput
                        label="URL"
                        value={String(link.url || '')}
                        onChange={(v) => onChange({ links: updateArrayItem(links, i, { url: v }) })}
                    />
                </SheetGroup>
            ))}
        </>
    );
}

function ButtonGroupQuickEdit({ block, onChange }: { block: LiffBlock; onChange: (patch: Partial<LiffBlock>) => void }) {
    const buttons = (Array.isArray(block.buttons) ? block.buttons : []) as RecordItem[];
    return (
        <>
            {buttons.map((btn, i) => (
                <SheetGroup key={i} label={`ボタン ${i + 1}`}>
                    <TextInput
                        label="文言"
                        value={String(btn.text || '')}
                        onChange={(v) => onChange({ buttons: updateArrayItem(buttons, i, { text: v }) })}
                    />
                    <ActionField
                        label="タップしたとき"
                        value={String(btn.action || '')}
                        onChange={(v) => onChange({ buttons: updateArrayItem(buttons, i, { action: v }) })}
                    />
                </SheetGroup>
            ))}
        </>
    );
}

function FaqQuickEdit({ block, onChange }: { block: LiffBlock; onChange: (patch: Partial<LiffBlock>) => void }) {
    const items = (Array.isArray(block.items) ? block.items : []) as RecordItem[];
    return (
        <>
            <TextInput label="タイトル" value={String(block.title || '')} onChange={(v) => onChange({ title: v })} />
            {items.slice(0, 3).map((item, i) => (
                <SheetGroup key={i} label={`質問 ${i + 1}`}>
                    <TextInput
                        label="質問"
                        value={String(item.q || '')}
                        onChange={(v) => onChange({ items: updateArrayItem(items, i, { q: v }) })}
                    />
                    <TextArea
                        label="回答"
                        value={String(item.a || '')}
                        onChange={(v) => onChange({ items: updateArrayItem(items, i, { a: v }) })}
                    />
                </SheetGroup>
            ))}
            {items.length > 3 ? (
                <p className="ld-form-hint">残り {items.length - 3} 件は詳細編集モードで変更できます。</p>
            ) : null}
        </>
    );
}

function QuickFields({ block, friendAddUrl, onChange }: Omit<SheetProps, 'onClose'>) {
    switch (block.type) {
        case 'hero':
            return (
                <>
                    <TextInput label="タイトル" value={String(block.title || '')} onChange={(v) => onChange({ title: v })} />
                    <TextArea label="サブタイトル" value={String(block.subtitle || '')} onChange={(v) => onChange({ subtitle: v })} />
                    <ImageUrlField label="背景画像" value={String(block.image_url || '')} onChange={(v) => onChange({ image_url: v })} />
                    <ColorField label="グラデーション開始" value={String(block.gradient_start || '#667eea')} onChange={(v) => onChange({ gradient_start: v })} />
                    <ColorField label="グラデーション終了" value={String(block.gradient_end || '#764ba2')} onChange={(v) => onChange({ gradient_end: v })} />
                </>
            );
        case 'heading':
            return (
                <>
                    <TextInput label="見出し" value={String(block.text || '')} onChange={(v) => onChange({ text: v })} />
                    <ColorField label="文字色" value={String(block.text_color || '#1f2937')} onChange={(v) => onChange({ text_color: v })} />
                </>
            );
        case 'text':
            return (
                <>
                    <TextArea label="本文" value={String(block.text || '')} onChange={(v) => onChange({ text: v })} />
                    <ColorField label="文字色" value={String(block.text_color || '#4b5563')} onChange={(v) => onChange({ text_color: v })} />
                </>
            );
        case 'image':
            return (
                <>
                    <ImageUrlField label="画像" value={String(block.image_url || '')} onChange={(v) => onChange({ image_url: v })} />
                    <TextInput label="代替テキスト" value={String(block.alt || '')} onChange={(v) => onChange({ alt: v })} />
                    <RangeField
                        label="画像の高さ"
                        value={Number(block.image_height || 180)}
                        min={80}
                        max={400}
                        onChange={(v) => onChange({ image_height: v })}
                    />
                </>
            );
        case 'button':
            return (
                <>
                    <TextInput label="ボタン文言" value={String(block.text || '')} onChange={(v) => onChange({ text: v })} />
                    <ColorField label="ボタン色" value={String(block.color || '#06C755')} onChange={(v) => onChange({ color: v })} />
                    <ColorField label="文字色" value={String(block.text_color || '#ffffff')} onChange={(v) => onChange({ text_color: v })} />
                    <ActionField label="タップしたとき" value={String(block.action || '')} onChange={(v) => onChange({ action: v })} />
                </>
            );
        case 'card':
            return (
                <>
                    <TextInput label="タイトル" value={String(block.title || '')} onChange={(v) => onChange({ title: v })} />
                    <TextArea label="説明" value={String(block.description || '')} onChange={(v) => onChange({ description: v })} />
                    <ImageUrlField label="カード画像" value={String(block.image_url || '')} onChange={(v) => onChange({ image_url: v })} />
                    <RangeField
                        label="画像の高さ"
                        value={Number(block.image_height || 140)}
                        min={60}
                        max={300}
                        onChange={(v) => onChange({ image_height: v })}
                    />
                    <ActionField label="タップしたとき" value={String(block.action || '')} onChange={(v) => onChange({ action: v })} />
                </>
            );
        case 'banner':
            return (
                <>
                    <TextInput label="テキスト" value={String(block.text || '')} onChange={(v) => onChange({ text: v })} />
                    <ColorField label="背景色" value={String(block.color || '#3b82f6')} onChange={(v) => onChange({ color: v })} />
                    <ColorField label="文字色" value={String(block.text_color || '#ffffff')} onChange={(v) => onChange({ text_color: v })} />
                    <ActionField label="タップしたとき" value={String(block.action || '')} onChange={(v) => onChange({ action: v })} />
                </>
            );
        case 'friend_add':
            return (
                <>
                    <TextInput label="ボタン文言" value={String(block.text || '')} onChange={(v) => onChange({ text: v })} />
                    <ColorField label="ボタン色" value={String(block.color || '#06C755')} onChange={(v) => onChange({ color: v })} />
                    <ColorField label="文字色" value={String(block.text_color || '#ffffff')} onChange={(v) => onChange({ text_color: v })} />
                    <TextInput
                        label="友だち追加URL（空なら店舗設定を使用）"
                        value={String(block.url || '')}
                        onChange={(v) => onChange({ url: v })}
                        placeholder={friendAddUrl || 'https://line.me/R/ti/p/@ID'}
                    />
                </>
            );
        case 'member_summary':
            return <TextInput label="挨拶文" value={String(block.greeting || '')} onChange={(v) => onChange({ greeting: v })} />;
        case 'stats_row':
            return <TextInput label="セクション見出し" value={String(block.section_label || '')} onChange={(v) => onChange({ section_label: v })} />;
        case 'hours':
            return <TextInput label="タイトル" value={String(block.title || '')} onChange={(v) => onChange({ title: v })} />;
        case 'access':
            return (
                <>
                    <TextInput label="タイトル" value={String(block.title || '')} onChange={(v) => onChange({ title: v })} />
                    <TextArea label="住所" value={String(block.address || '')} onChange={(v) => onChange({ address: v })} />
                    <TextInput label="電話番号" value={String(block.phone || '')} onChange={(v) => onChange({ phone: v })} />
                </>
            );
        case 'faq':
            return <FaqQuickEdit block={block} onChange={onChange} />;
        case 'video':
            return (
                <>
                    <TextInput label="YouTube URL / ID" value={String(block.youtube_id || '')} onChange={(v) => onChange({ youtube_id: v })} />
                    <TextInput label="キャプション" value={String(block.caption || '')} onChange={(v) => onChange({ caption: v })} />
                </>
            );
        case 'list':
            return (
                <TextArea
                    label="項目（1行1項目）"
                    value={(Array.isArray(block.items) ? block.items : []).map(String).join('\n')}
                    onChange={(v) => onChange({ items: v.split('\n').filter((s) => s.trim() !== '') })}
                />
            );
        case 'collection':
            return <CollectionQuickEdit block={block} onChange={onChange} />;
        case 'slider':
            return <SliderQuickEdit block={block} onChange={onChange} />;
        case 'quick_actions':
            return <QuickActionsEdit block={block} onChange={onChange} />;
        case 'icon_text':
            return <IconTextQuickEdit block={block} onChange={onChange} />;
        case 'social_links':
            return <SocialLinksQuickEdit block={block} onChange={onChange} />;
        case 'button_group':
            return <ButtonGroupQuickEdit block={block} onChange={onChange} />;
        case 'data':
            return (
                <>
                    <SelectField
                        label="表示データ"
                        value={String(block.source || 'products')}
                        options={DATA_SOURCES}
                        onChange={(v) => onChange({ source: v })}
                    />
                    {block.source === 'member_card' || block.source === 'member' ? (
                        <DesignPicker
                            value={String(block.card_design || '')}
                            options={MEMBER_CARD_DESIGNS}
                            onChange={(v) => onChange({ card_design: v })}
                        />
                    ) : null}
                </>
            );
        case 'spacer':
            return (
                <RangeField
                    label="余白の高さ"
                    value={Number(block.height || 16)}
                    min={8}
                    max={80}
                    onChange={(v) => onChange({ height: v })}
                />
            );
        case 'divider':
            return (
                <ColorField
                    label="線の色"
                    value={String(block.divider_color || '#e5e7eb')}
                    onChange={(v) => onChange({ divider_color: v })}
                />
            );
        default:
            return (
                <>
                    <p className="ld-form-hint">このブロックは簡易編集に対応していません。詳細編集モードをご利用ください。</p>
                    {block.text !== undefined ? (
                        <TextArea label="テキスト" value={String(block.text || '')} onChange={(v) => onChange({ text: v })} />
                    ) : null}
                </>
            );
    }
}

const SKIP_BACKGROUND_TYPES = new Set(['spacer', 'divider', 'data']);

type PanelProps = {
    block: LiffBlock;
    friendAddUrl?: string;
    onChange: (patch: Partial<LiffBlock>) => void;
    onClose: () => void;
};

export function BlockQuickEditPanel({ block, friendAddUrl, onChange, onClose }: PanelProps) {
    const meta = findBlockMeta(block.type);
    const designOptions = BLOCK_DESIGNS[block.type] || [];

    return (
        <div className="ld-sidebar__section ld-sidebar__section--quick-edit">
            <div className="ld-quick-edit__head">
                <div className="ld-sidebar__title">
                    <i className={`fas ${meta?.icon || 'fa-cube'}`} />
                    {meta?.label || block.type}
                </div>
                <button type="button" className="ld-icon-btn ld-icon-btn--sm" onClick={onClose} aria-label="編集を閉じる">
                    <i className="fas fa-times" />
                </button>
            </div>
            <p className="ld-sidebar__lead">選択中のブロックを編集しています</p>
            {designOptions.length > 0 ? (
                <DesignPicker
                    value={String(block.design || '')}
                    options={designOptions}
                    onChange={(v) => onChange({ design: v })}
                />
            ) : null}
            <QuickFields block={block} friendAddUrl={friendAddUrl} onChange={onChange} />
            {SKIP_BACKGROUND_TYPES.has(block.type) ? null : (
                <ColorField
                    label="ブロック背景色"
                    value={String(block.background_color || '#ffffff')}
                    onChange={(v) => onChange({ background_color: v })}
                />
            )}
            <button type="button" className="ld-btn ld-btn--primary ld-btn--block ld-quick-edit__done" onClick={onClose}>
                完了
            </button>
        </div>
    );
}

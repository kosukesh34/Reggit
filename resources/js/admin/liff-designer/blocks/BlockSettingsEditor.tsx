import React from 'react';
import {
    BLOCK_DESIGNS,
    DATA_SOURCES,
    MEMBER_CARD_DESIGNS,
    SOCIAL_LINK_TYPES,
    STAMP_FILLED_STYLES,
    STAMP_SCREEN_DESIGNS,
    STAMP_SLOT_STYLES,
    findBlockMeta,
} from '../catalog/blockCatalog';
import type { LiffBlock } from '../catalog/types';
import {
    ActionField,
    AlignField,
    CheckboxField,
    ColorField,
    DesignPicker,
    FieldGroup,
    ImageUrlField,
    RangeField,
    SelectField,
    TextArea,
    TextInput,
} from '../components/fields/FormFields';

type EditorProps = {
    block: LiffBlock;
    onChange: (patch: Partial<LiffBlock>) => void;
    onReplace: (block: LiffBlock) => void;
    onDelete: () => void;
    friendAddUrl?: string;
};

function CommonSettings({ block, onChange }: { block: LiffBlock; onChange: (patch: Partial<LiffBlock>) => void }) {
    return (
        <div className="ld-settings__common">
            <div className="ld-settings__section-title">スタイル</div>
            <AlignField value={String(block.text_align || 'left')} onChange={(v) => onChange({ text_align: v })} />
            <RangeField label="角丸" value={Number(block.border_radius ?? 10)} min={0} max={24} onChange={(v) => onChange({ border_radius: v })} />
            <RangeField label="下マージン" value={Number(block.margin_bottom ?? 0)} min={0} max={48} onChange={(v) => onChange({ margin_bottom: v })} />
            <ColorField label="背景色" value={String(block.background_color || '#ffffff')} onChange={(v) => onChange({ background_color: v })} />
            <CheckboxField label="影をつける" checked={block.shadow === true} onChange={(v) => onChange({ shadow: v })} />
        </div>
    );
}

function ArrayEditor<T extends Record<string, unknown>>({
    label,
    items,
    createItem,
    fields,
    onChange,
}: {
    label: string;
    items: T[];
    createItem: () => T;
    fields: Array<{ key: keyof T & string; label: string; kind?: 'text' | 'action' | 'color' | 'select' | 'image'; options?: Array<{ value: string; label: string }> }>;
    onChange: (items: T[]) => void;
}) {
    return (
        <FieldGroup label={label}>
            <div className="ld-array-list">
                {items.map((item, index) => (
                    <div key={index} className="ld-array-item">
                        <div className="ld-array-item__head">
                            <span>#{index + 1}</span>
                            <button
                                type="button"
                                className="ld-btn ld-btn--ghost ld-btn--xs"
                                onClick={() => onChange(items.filter((_, i) => i !== index))}
                            >
                                削除
                            </button>
                        </div>
                        {fields.map((field) => {
                            const val = String(item[field.key] ?? '');
                            if (field.kind === 'action') {
                                return (
                                    <ActionField
                                        key={field.key}
                                        label={field.label}
                                        value={val}
                                        onChange={(v) => {
                                            const next = [...items];
                                            next[index] = { ...item, [field.key]: v };
                                            onChange(next);
                                        }}
                                    />
                                );
                            }
                            if (field.kind === 'color') {
                                return (
                                    <ColorField
                                        key={field.key}
                                        label={field.label}
                                        value={val || '#000000'}
                                        onChange={(v) => {
                                            const next = [...items];
                                            next[index] = { ...item, [field.key]: v };
                                            onChange(next);
                                        }}
                                    />
                                );
                            }
                            if (field.kind === 'select' && field.options) {
                                return (
                                    <SelectField
                                        key={field.key}
                                        label={field.label}
                                        value={val}
                                        options={field.options}
                                        onChange={(v) => {
                                            const next = [...items];
                                            next[index] = { ...item, [field.key]: v };
                                            onChange(next);
                                        }}
                                    />
                                );
                            }
                            if (field.kind === 'image') {
                                return (
                                    <ImageUrlField
                                        key={field.key}
                                        label={field.label}
                                        value={val}
                                        compact
                                        onChange={(v) => {
                                            const next = [...items];
                                            next[index] = { ...item, [field.key]: v };
                                            onChange(next);
                                        }}
                                    />
                                );
                            }
                            return (
                                <TextInput
                                    key={field.key}
                                    label={field.label}
                                    value={val}
                                    onChange={(v) => {
                                        const next = [...items];
                                        next[index] = { ...item, [field.key]: v };
                                        onChange(next);
                                    }}
                                />
                            );
                        })}
                    </div>
                ))}
                <button
                    type="button"
                    className="ld-btn ld-btn--outline ld-btn--block"
                    onClick={() => onChange([...items, createItem()])}
                >
                    <i className="fas fa-plus" /> 追加
                </button>
            </div>
        </FieldGroup>
    );
}

function TypeEditor({ block, onChange, friendAddUrl }: EditorProps) {
    const designOptions = BLOCK_DESIGNS[block.type] || [];
    const setDesign = (v: string) => onChange({ design: v });

    switch (block.type) {
        case 'hero':
            return (
                <>
                    <DesignPicker type="hero" value={String(block.design || '')} options={designOptions} onChange={setDesign} />
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
                    <DesignPicker type="heading" value={String(block.design || '')} options={designOptions} onChange={setDesign} />
                    <TextInput label="見出し" value={String(block.text || '')} onChange={(v) => onChange({ text: v })} />
                    <SelectField
                        label="サイズ"
                        value={String(block.size || 'medium')}
                        options={[
                            { value: 'small', label: '小' },
                            { value: 'medium', label: '中' },
                            { value: 'large', label: '大' },
                        ]}
                        onChange={(v) => onChange({ size: v })}
                    />
                    <ColorField label="文字色" value={String(block.text_color || '#1f2937')} onChange={(v) => onChange({ text_color: v })} />
                </>
            );
        case 'text':
            return (
                <>
                    <DesignPicker type="text" value={String(block.design || '')} options={designOptions} onChange={setDesign} />
                    <TextArea label="本文" value={String(block.text || '')} onChange={(v) => onChange({ text: v })} />
                    <RangeField label="文字サイズ" value={Number(block.font_size || 13)} min={10} max={28} onChange={(v) => onChange({ font_size: v })} />
                    <ColorField label="文字色" value={String(block.text_color || '#4b5563')} onChange={(v) => onChange({ text_color: v })} />
                </>
            );
        case 'image':
            return (
                <>
                    <DesignPicker type="image" value={String(block.design || '')} options={designOptions} onChange={setDesign} />
                    <ImageUrlField label="画像" value={String(block.image_url || '')} onChange={(v) => onChange({ image_url: v })} />
                    <TextInput label="代替テキスト" value={String(block.alt || '')} onChange={(v) => onChange({ alt: v })} />
                    <SelectField
                        label="表示方法"
                        value={String(block.fit || 'cover')}
                        options={[
                            { value: 'cover', label: 'カバー' },
                            { value: 'contain', label: '全体表示' },
                            { value: 'fill', label: '引き伸ばし' },
                        ]}
                        onChange={(v) => onChange({ fit: v })}
                    />
                </>
            );
        case 'button':
            return (
                <>
                    <DesignPicker type="button" value={String(block.design || '')} options={designOptions} onChange={setDesign} />
                    <TextInput label="ラベル" value={String(block.text || '')} onChange={(v) => onChange({ text: v })} />
                    <ColorField label="ボタン色" value={String(block.color || '#06C755')} onChange={(v) => onChange({ color: v })} />
                    <ColorField label="文字色" value={String(block.text_color || '#ffffff')} onChange={(v) => onChange({ text_color: v })} />
                    <ActionField label="タップしたときの動作" value={String(block.action || '')} onChange={(v) => onChange({ action: v })} />
                    <CheckboxField label="幅いっぱい" checked={block.full_width !== false} onChange={(v) => onChange({ full_width: v })} />
                </>
            );
        case 'card':
            return (
                <>
                    <DesignPicker type="card" value={String(block.design || '')} options={designOptions} onChange={setDesign} />
                    <TextInput label="タイトル" value={String(block.title || '')} onChange={(v) => onChange({ title: v })} />
                    <TextArea label="説明" value={String(block.description || '')} onChange={(v) => onChange({ description: v })} />
                    <ImageUrlField label="カード画像" value={String(block.image_url || '')} onChange={(v) => onChange({ image_url: v })} />
                    <RangeField label="画像の高さ" value={Number(block.image_height || 140)} min={60} max={300} onChange={(v) => onChange({ image_height: v })} />
                    <ActionField label="タップしたときの動作" value={String(block.action || '')} onChange={(v) => onChange({ action: v })} />
                </>
            );
        case 'collection':
            return (
                <>
                    <DesignPicker type="collection" value={String(block.design || '')} options={designOptions} onChange={setDesign} />
                    <TextInput label="セクションラベル" value={String(block.section_label || '')} onChange={(v) => onChange({ section_label: v })} />
                    <SelectField
                        label="レイアウト"
                        value={String(block.layout || 'scroll')}
                        options={[
                            { value: 'scroll', label: '横スクロール' },
                            { value: 'grid', label: 'グリッド' },
                        ]}
                        onChange={(v) => onChange({ layout: v })}
                    />
                    {block.layout === 'grid' ? (
                        <>
                            <SelectField
                                label="列数"
                                value={String(block.columns || 2)}
                                options={[
                                    { value: '2', label: '2列' },
                                    { value: '3', label: '3列' },
                                    { value: '4', label: '4列' },
                                ]}
                                onChange={(v) => onChange({ columns: Number(v) })}
                            />
                            <RangeField label="表示行数 (0=全て)" value={Number(block.max_rows || 0)} min={0} max={10} onChange={(v) => onChange({ max_rows: v })} />
                        </>
                    ) : (
                        <RangeField label="アイテム幅" value={Number(block.item_width || 140)} min={80} max={240} onChange={(v) => onChange({ item_width: v })} />
                    )}
                    <RangeField label="画像の高さ" value={Number(block.item_image_height || 100)} min={40} max={200} onChange={(v) => onChange({ item_image_height: v })} />
                    <RangeField label="間隔" value={Number(block.gap ?? 8)} min={0} max={24} onChange={(v) => onChange({ gap: v })} />
                    <CheckboxField label="説明を表示" checked={block.show_description !== false} onChange={(v) => onChange({ show_description: v })} />
                    <ActionField label="デフォルトのタップ先" value={String(block.item_action || '')} onChange={(v) => onChange({ item_action: v })} />
                    <ArrayEditor
                        label="アイテム"
                        items={(Array.isArray(block.items) ? block.items : []) as Array<Record<string, unknown>>}
                        createItem={() => ({ title: '新規', image_url: `https://picsum.photos/seed/${Date.now()}/300/300`, description: '', action: '' })}
                        fields={[
                            { key: 'title', label: 'タイトル' },
                            { key: 'image_url', label: '画像', kind: 'image' },
                            { key: 'description', label: '説明' },
                            { key: 'action', label: 'タップ先', kind: 'action' },
                        ]}
                        onChange={(items) => onChange({ items })}
                    />
                </>
            );
        case 'slider':
            return (
                <>
                    <DesignPicker type="slider" value={String(block.design || '')} options={designOptions} onChange={setDesign} />
                    <SelectField
                        label="データソース"
                        value={String(block.source || '')}
                        options={[
                            { value: '', label: '手動（画像を設定）' },
                            { value: 'gallery', label: 'ギャラリー（実データ）' },
                        ]}
                        onChange={(v) => onChange(v === 'gallery'
                            ? { source: 'gallery', slides: [] }
                            : { source: '', slides: Array.isArray(block.slides) && (block.slides as unknown[]).length > 0 ? block.slides : [
                                { image_url: '', caption: '' },
                            ] })}
                    />
                    <RangeField label="スライドの高さ" value={Number(block.slide_height || 160)} min={80} max={300} onChange={(v) => onChange({ slide_height: v })} />
                    <CheckboxField label="自動再生" checked={block.auto_play !== false} onChange={(v) => onChange({ auto_play: v })} />
                    <CheckboxField label="ドット表示" checked={block.show_dots !== false} onChange={(v) => onChange({ show_dots: v })} />
                    {String(block.source || '') === 'gallery' ? (
                        <p className="ld-settings__hint">店舗に登録したギャラリー画像が自動で表示されます。</p>
                    ) : (
                        <ArrayEditor
                            label="スライド"
                            items={(Array.isArray(block.slides) ? block.slides : []) as Array<Record<string, unknown>>}
                            createItem={() => ({ image_url: '', caption: '' })}
                            fields={[
                                { key: 'image_url', label: '画像', kind: 'image' },
                                { key: 'caption', label: 'キャプション' },
                            ]}
                            onChange={(slides) => onChange({ slides })}
                        />
                    )}
                </>
            );
        case 'icon_text':
            return (
                <ArrayEditor
                    label="行"
                    items={(Array.isArray(block.rows) ? block.rows : []) as Array<Record<string, unknown>>}
                    createItem={() => ({ icon: 'fa-check-circle', text: '項目', color: '#06C755' })}
                    fields={[
                        { key: 'icon', label: 'アイコン (fa-*)' },
                        { key: 'text', label: 'テキスト' },
                        { key: 'color', label: '色', kind: 'color' },
                    ]}
                    onChange={(rows) => onChange({ rows })}
                />
            );
        case 'list':
            return (
                <>
                    <SelectField
                        label="スタイル"
                        value={String(block.style || 'bullet')}
                        options={[
                            { value: 'bullet', label: '箇条書き' },
                            { value: 'number', label: '番号付き' },
                        ]}
                        onChange={(v) => onChange({ style: v })}
                    />
                    <ColorField label="文字色" value={String(block.text_color || '#374151')} onChange={(v) => onChange({ text_color: v })} />
                    <FieldGroup label="項目（1行1項目）">
                        <textarea
                            className="ld-form-textarea"
                            rows={5}
                            value={(Array.isArray(block.items) ? block.items : []).map(String).join('\n')}
                            onChange={(e) => onChange({ items: e.target.value.split('\n').filter((s) => s.trim() !== '') })}
                        />
                    </FieldGroup>
                </>
            );
        case 'banner':
            return (
                <>
                    <DesignPicker type="banner" value={String(block.design || '')} options={designOptions} onChange={setDesign} />
                    <TextInput label="テキスト" value={String(block.text || '')} onChange={(v) => onChange({ text: v })} />
                    <SelectField
                        label="データ紐づけ"
                        value={String(block.field || 'static')}
                        options={[
                            { value: 'static', label: '固定テキスト' },
                            { value: 'points_to_next', label: '次ランクまで（会員実データ）' },
                        ]}
                        onChange={(v) => onChange({ field: v === 'static' ? '' : v })}
                    />
                    <TextInput label="アイコン (fa-*)" value={String(block.icon || '')} onChange={(v) => onChange({ icon: v })} />
                    <ColorField label="背景色" value={String(block.color || '#3b82f6')} onChange={(v) => onChange({ color: v })} />
                    <ColorField label="文字色" value={String(block.text_color || '#ffffff')} onChange={(v) => onChange({ text_color: v })} />
                    <ActionField label="タップしたときの動作" value={String(block.action || '')} onChange={(v) => onChange({ action: v })} />
                </>
            );
        case 'spacer':
            return <RangeField label="高さ (px)" value={Number(block.height || 16)} min={4} max={80} onChange={(v) => onChange({ height: v })} />;
        case 'divider':
            return (
                <>
                    <ColorField label="線の色" value={String(block.divider_color || '#e5e7eb')} onChange={(v) => onChange({ divider_color: v })} />
                    <SelectField
                        label="線のスタイル"
                        value={String(block.divider_style || 'solid')}
                        options={[
                            { value: 'solid', label: '直線' },
                            { value: 'dashed', label: '破線' },
                            { value: 'dotted', label: '点線' },
                        ]}
                        onChange={(v) => onChange({ divider_style: v })}
                    />
                </>
            );
        case 'data':
            return (
                <>
                    <DesignPicker type="data" value={String(block.design || '')} options={designOptions} onChange={setDesign} />
                    <SelectField
                        label="データソース"
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
                    {block.source === 'stamps' ? (
                        <>
                            <DesignPicker
                                value={String(block.stamp_design || '')}
                                options={STAMP_SCREEN_DESIGNS}
                                onChange={(v) => onChange({ stamp_design: v })}
                            />
                            <SelectField
                                label="スロット形状"
                                value={String(block.stamp_slot_style || 'circle')}
                                options={STAMP_SLOT_STYLES}
                                onChange={(v) => onChange({ stamp_slot_style: v })}
                            />
                            <SelectField
                                label="押印スタイル"
                                value={String(block.stamp_filled_style || 'image')}
                                options={STAMP_FILLED_STYLES}
                                onChange={(v) => onChange({ stamp_filled_style: v })}
                            />
                            <ColorField
                                label="アクセント色（空欄=ブランド色）"
                                value={String(block.stamp_accent_color || '')}
                                onChange={(v) => onChange({ stamp_accent_color: v })}
                            />
                        </>
                    ) : null}
                </>
            );
        case 'member_summary':
            return (
                <>
                    <DesignPicker type="member_summary" value={String(block.design || '')} options={designOptions} onChange={setDesign} />
                    <TextInput label="挨拶文（空欄可）" value={String(block.greeting || '')} onChange={(v) => onChange({ greeting: v })} />
                </>
            );
        case 'stats_row':
            return (
                <>
                    <TextInput label="セクション見出し" value={String(block.section_label || '')} onChange={(v) => onChange({ section_label: v })} />
                    <ArrayEditor
                        label="統計項目"
                        items={(Array.isArray(block.items) ? block.items : []).map((it) => ({
                            ...(it as Record<string, unknown>),
                            field: String((it as Record<string, unknown>).field || 'static'),
                        })) as Array<Record<string, unknown>>}
                        createItem={() => ({ label: '項目', value: '0', hint: '', field: 'static' })}
                        fields={[
                            { key: 'label', label: 'ラベル' },
                            {
                                key: 'field',
                                label: 'データ紐づけ',
                                kind: 'select',
                                options: [
                                    { value: 'static', label: '固定値（下の値を表示）' },
                                    { value: 'points', label: 'ポイント（会員実データ）' },
                                    { value: 'rank', label: 'ランク（会員実データ）' },
                                    { value: 'points_to_next', label: '次ランクまで（会員実データ）' },
                                ],
                            },
                            { key: 'value', label: 'プレビュー用の値' },
                            { key: 'hint', label: '単位' },
                        ]}
                        onChange={(items) => onChange({ items })}
                    />
                </>
            );
        case 'quick_actions':
            return (
                <>
                    <DesignPicker type="quick_actions" value={String(block.design || '')} options={designOptions} onChange={setDesign} />
                    <ArrayEditor
                        label="アクション"
                        items={(Array.isArray(block.actions) ? block.actions : []) as Array<Record<string, unknown>>}
                        createItem={() => ({ icon: 'fa-circle', label: '新規', action: '' })}
                        fields={[
                            { key: 'icon', label: 'アイコン' },
                            { key: 'label', label: 'ラベル' },
                            { key: 'action', label: '動作', kind: 'action' },
                        ]}
                        onChange={(actions) => onChange({ actions })}
                    />
                </>
            );
        case 'friend_add':
            return (
                <>
                    <DesignPicker type="friend_add" value={String(block.design || '')} options={designOptions} onChange={setDesign} />
                    <TextInput label="ボタン文言" value={String(block.text || '')} onChange={(v) => onChange({ text: v })} />
                    <ColorField label="ボタン色" value={String(block.color || '#06C755')} onChange={(v) => onChange({ color: v })} />
                    <ColorField label="文字色" value={String(block.text_color || '#ffffff')} onChange={(v) => onChange({ text_color: v })} />
                    <TextInput
                        label="上書きURL（空なら接続設定を使用）"
                        value={String(block.url || '')}
                        onChange={(v) => onChange({ url: v })}
                        placeholder={friendAddUrl || 'https://line.me/R/ti/p/@ID'}
                    />
                    {friendAddUrl !== undefined && friendAddUrl !== '' ? (
                        <p className="ld-form-hint">店舗設定の友だち追加URL: {friendAddUrl}</p>
                    ) : (
                        <p className="ld-form-hint">店舗設定に友だち追加URLを入れるか、上書きURLを指定してください。</p>
                    )}
                </>
            );
        case 'hours':
            return (
                <>
                    <DesignPicker type="hours" value={String(block.design || '')} options={designOptions} onChange={setDesign} />
                    <TextInput label="タイトル" value={String(block.title || '')} onChange={(v) => onChange({ title: v })} />
                    <TextInput label="備考" value={String(block.note || '')} onChange={(v) => onChange({ note: v })} />
                    <ArrayEditor
                        label="営業時間"
                        items={(Array.isArray(block.rows) ? block.rows : []) as Array<Record<string, unknown>>}
                        createItem={() => ({ day: '曜日', time: '00:00 - 00:00' })}
                        fields={[
                            { key: 'day', label: '曜日' },
                            { key: 'time', label: '時間' },
                        ]}
                        onChange={(rows) => onChange({ rows })}
                    />
                </>
            );
        case 'access':
            return (
                <>
                    <DesignPicker type="access" value={String(block.design || '')} options={designOptions} onChange={setDesign} />
                    <TextInput label="タイトル" value={String(block.title || '')} onChange={(v) => onChange({ title: v })} />
                    <TextArea label="住所" value={String(block.address || '')} onChange={(v) => onChange({ address: v })} />
                    <TextInput label="電話番号" value={String(block.phone || '')} onChange={(v) => onChange({ phone: v })} />
                    <TextInput label="地図URL" value={String(block.map_url || '')} onChange={(v) => onChange({ map_url: v })} />
                    <TextInput label="備考" value={String(block.note || '')} onChange={(v) => onChange({ note: v })} />
                </>
            );
        case 'social_links':
            return (
                <ArrayEditor
                    label="SNSリンク"
                    items={(Array.isArray(block.links) ? block.links : []) as Array<Record<string, unknown>>}
                    createItem={() => ({ type: 'instagram', url: '' })}
                    fields={[
                        { key: 'type', label: '種類', kind: 'select', options: SOCIAL_LINK_TYPES },
                        { key: 'url', label: 'URL' },
                    ]}
                    onChange={(links) => onChange({ links })}
                />
            );
        case 'faq':
            return (
                <>
                    <DesignPicker type="faq" value={String(block.design || '')} options={designOptions} onChange={setDesign} />
                    <TextInput label="タイトル" value={String(block.title || '')} onChange={(v) => onChange({ title: v })} />
                    <ArrayEditor
                        label="Q&A"
                        items={(Array.isArray(block.items) ? block.items : []) as Array<Record<string, unknown>>}
                        createItem={() => ({ q: '質問', a: '回答' })}
                        fields={[
                            { key: 'q', label: '質問' },
                            { key: 'a', label: '回答' },
                        ]}
                        onChange={(items) => onChange({ items })}
                    />
                </>
            );
        case 'video':
            return (
                <>
                    <TextInput label="YouTube URL / ID" value={String(block.youtube_id || '')} onChange={(v) => onChange({ youtube_id: v })} />
                    <TextInput label="キャプション" value={String(block.caption || '')} onChange={(v) => onChange({ caption: v })} />
                </>
            );
        case 'button_group':
            return (
                <ArrayEditor
                    label="ボタン（最大3）"
                    items={(Array.isArray(block.buttons) ? block.buttons : []).slice(0, 3) as Array<Record<string, unknown>>}
                    createItem={() => ({ text: 'ボタン', action: '', variant: 'solid', color: '' })}
                    fields={[
                        { key: 'text', label: 'ラベル' },
                        { key: 'action', label: '動作', kind: 'action' },
                        {
                            key: 'variant',
                            label: 'スタイル',
                            kind: 'select',
                            options: [
                                { value: 'solid', label: '塗りつぶし' },
                                { value: 'outline', label: '枠線のみ' },
                            ],
                        },
                        { key: 'color', label: '色（任意）', kind: 'color' },
                    ]}
                    onChange={(buttons) => onChange({ buttons: buttons.slice(0, 3) })}
                />
            );
        default:
            return (
                <div className="ld-unsupported">
                    <p>このブロックタイプ（{block.type}）は専用エディタがありません。</p>
                    <p className="ld-form-hint">削除するか、保存前に内容を確認してください。</p>
                    <TextArea label="テキスト（汎用）" value={String(block.text || '')} onChange={(v) => onChange({ text: v })} />
                </div>
            );
    }
}

export function BlockSettingsEditor(props: EditorProps) {
    const meta = findBlockMeta(props.block.type);
    return (
        <div className="ld-block-settings">
            <div className="ld-settings__type-tag">
                <i className={`fas ${meta?.icon || 'fa-cube'}`} /> {meta?.label || props.block.type}ブロック
            </div>
            <TypeEditor {...props} />
            <CommonSettings block={props.block} onChange={props.onChange} />
            <div className="ld-settings__delete">
                <button type="button" className="ld-btn ld-btn--danger ld-btn--block" onClick={props.onDelete}>
                    <i className="fas fa-trash-alt" /> 削除
                </button>
            </div>
        </div>
    );
}

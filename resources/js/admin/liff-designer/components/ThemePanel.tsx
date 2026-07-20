import React, { useState } from 'react';
import { useDesigner } from '../state/DesignerContext';
import { expandThemeIntoGlobal } from '../utils/globalDesign';
import { ColorField, FieldGroup, ImageUrlField, RangeField, SelectField, TextInput } from './fields/FormFields';
import {
    MEMBER_CARD_BG_STYLES,
    MEMBER_CARD_CODE_TYPES,
    MEMBER_CARD_DESIGN_CHOICES,
    MEMBER_CARD_DISPLAY_TYPES,
    STAMP_FILLED_STYLES,
    STAMP_SCREEN_DESIGN_CHOICES,
    STAMP_SLOT_STYLES,
} from '../catalog/blockCatalog';

function Accordion({ title, open, onToggle, children }: { title: string; open: boolean; onToggle: () => void; children: React.ReactNode }) {
    return (
        <div className={`ld-accordion${open ? ' is-open' : ''}`}>
            <button type="button" className="ld-accordion__head" onClick={onToggle}>
                <span>{title}</span>
                <i className={`fas fa-chevron-${open ? 'up' : 'down'}`} />
            </button>
            {open ? <div className="ld-accordion__body">{children}</div> : null}
        </div>
    );
}

export function ThemePanel() {
    const { state, dispatch, config } = useDesigner();
    const g = state.globalSettings;
    const [open, setOpen] = useState({ policy: true, preset: true, basic: true, look: false, system: false, css: false });
    const requireMembership = g.require_membership !== false;

    const contentWidths = Object.entries(config.contentWidths || {
        edge: { label: 'フル幅' },
        comfy: { label: 'ゆったり（推奨）' },
        narrow: { label: 'ナロー' },
    }).map(([value, v]) => ({ value, label: v.label }));

    const bgStyles = Object.entries(config.backgroundStyles || {
        solid: '単色',
        gradient: 'グラデーション',
        image: '背景画像',
    }).map(([value, label]) => ({ value, label }));

    const fontOptions = [
        { value: '', label: '（プリセット標準）' },
        ...Object.entries(config.fontStacks || {}).map(([value, v]) => ({ value, label: v.label })),
    ];

    const toneOptions = [
        { value: '', label: '（プリセット標準）' },
        ...Object.entries(config.surfaceTones || {}).map(([value, v]) => ({ value, label: v.label })),
    ];

    const applyPreset = (presetId: string) => {
        const preset = (config.themePresets || []).find((p) => p.id === presetId);
        if (preset === undefined) return;
        const next = expandThemeIntoGlobal(g, { theme_preset: presetId }, config.themePresets);
        dispatch({ type: 'UPDATE_GLOBAL', patch: next });
        dispatch({ type: 'APPLY_GLOBAL_BACKGROUND' });
        dispatch({
            type: 'SHOW_TOAST',
            message: `テーマ「${preset.label || preset.name || presetId}」を適用（各画面の背景上書きも解除）`,
        });
    };

    return (
        <div className="ld-sidebar__section">
            <div className="ld-sidebar__title"><i className="fas fa-swatchbook" /> テーマ設定</div>

            <Accordion title="アプリの動作" open={open.policy} onToggle={() => setOpen((s) => ({ ...s, policy: !s.policy }))}>
                <FieldGroup label="会員登録ポリシー（本番の動き）">
                    <div className="ld-bg-style-tabs" role="group" aria-label="会員登録ポリシー">
                        <button
                            type="button"
                            className={`ld-bg-style-tab${requireMembership ? ' is-active' : ''}`}
                            onClick={() => dispatch({ type: 'UPDATE_GLOBAL', patch: { require_membership: true } })}
                        >
                            会員必須
                        </button>
                        <button
                            type="button"
                            className={`ld-bg-style-tab${!requireMembership ? ' is-active' : ''}`}
                            onClick={() => dispatch({ type: 'UPDATE_GLOBAL', patch: { require_membership: false } })}
                        >
                            会員任意
                        </button>
                    </div>
                </FieldGroup>
                <p className="ld-form-hint">
                    {requireMembership
                        ? '本番ではクーポン・スタンプ・予約・会員証は登録後のみ。キャンバス上部の「ゲスト表示」は未登録時の見え方確認用です。'
                        : '本番ではゲストでも閲覧できます。キャンバス上部の「会員表示 / ゲスト表示」はプレビュー切替です。'}
                </p>
            </Accordion>

            <Accordion title="プリセット" open={open.preset} onToggle={() => setOpen((s) => ({ ...s, preset: !s.preset }))}>
                <div className="ld-theme-gallery">
                    {(config.themePresets || []).map((preset) => (
                        <button
                            key={preset.id}
                            type="button"
                            className={`ld-theme-card${g.theme_preset === preset.id ? ' is-active' : ''}`}
                            onClick={() => applyPreset(preset.id)}
                        >
                            <span
                                className="ld-theme-card__swatch"
                                style={{
                                    background: `linear-gradient(135deg, ${String(preset.bg_color || '#fafaf9')} 55%, ${String(preset.accent_color || preset.accent || '#1c1917')} 55%)`,
                                }}
                            />
                            <span className="ld-theme-card__label">{preset.label || preset.name || preset.id}</span>
                        </button>
                    ))}
                </div>
            </Accordion>

            <Accordion title="基本（色・幅・フォント）" open={open.basic} onToggle={() => setOpen((s) => ({ ...s, basic: !s.basic }))}>
                <TextInput
                    label="店舗名・アプリ名"
                    value={String(g.app_display_name || '')}
                    placeholder="例: ブルーバードカフェ"
                    onChange={(v) => dispatch({ type: 'UPDATE_GLOBAL', patch: { app_display_name: v } })}
                />
                <SelectField label="コンテンツ幅" value={String(g.content_width || 'comfy')} options={contentWidths} onChange={(v) => dispatch({ type: 'UPDATE_GLOBAL', patch: { content_width: v } })} />
                <FieldGroup label="ページ背景">
                    <div className="ld-bg-style-tabs">
                        {bgStyles.map((s) => (
                            <button
                                key={s.value}
                                type="button"
                                className={`ld-bg-style-tab${g.background_style === s.value ? ' is-active' : ''}`}
                                onClick={() => dispatch({ type: 'UPDATE_GLOBAL', patch: { background_style: s.value } })}
                            >
                                {s.label}
                            </button>
                        ))}
                    </div>
                </FieldGroup>
                {g.background_style === 'gradient' ? (
                    <>
                        <ColorField
                            label="グラデーション開始"
                            value={String(g.bg_gradient_start || '#eef2ff')}
                            onChange={(v) => {
                                dispatch({ type: 'UPDATE_GLOBAL', patch: { bg_gradient_start: v } });
                                dispatch({ type: 'APPLY_GLOBAL_BACKGROUND' });
                            }}
                        />
                        <ColorField
                            label="グラデーション終了"
                            value={String(g.bg_gradient_end || '#ffffff')}
                            onChange={(v) => {
                                dispatch({ type: 'UPDATE_GLOBAL', patch: { bg_gradient_end: v } });
                                dispatch({ type: 'APPLY_GLOBAL_BACKGROUND' });
                            }}
                        />
                    </>
                ) : null}
                {g.background_style === 'image' ? (
                    <>
                        <ImageUrlField label="背景画像" value={String(g.bg_image_url || '')} onChange={(v) => dispatch({ type: 'UPDATE_GLOBAL', patch: { bg_image_url: v } })} />
                        <RangeField label="オーバーレイ不透明度" value={Number(g.bg_overlay_opacity ?? 60)} min={0} max={80} onChange={(v) => dispatch({ type: 'UPDATE_GLOBAL', patch: { bg_overlay_opacity: v } })} />
                    </>
                ) : (
                    <ColorField
                        label="背景色"
                        value={String(g.bg_color || '#f9fafb')}
                        onChange={(v) => {
                            dispatch({ type: 'UPDATE_GLOBAL', patch: { bg_color: v } });
                            dispatch({ type: 'APPLY_GLOBAL_BACKGROUND' });
                        }}
                    />
                )}
                <button
                    type="button"
                    className="ld-btn ld-btn--soft ld-btn--block"
                    onClick={() => {
                        dispatch({ type: 'APPLY_GLOBAL_BACKGROUND' });
                        dispatch({ type: 'SHOW_TOAST', message: '背景をアプリ全体に適用しました（各画面の個別背景をクリア）' });
                    }}
                >
                    <i className="fas fa-fill-drip" /> 各画面の個別背景をクリア
                </button>
                <p className="ld-form-hint">背景色を変えると各画面の個別上書きも外し、全体に反映します。</p>
                <ColorField label="アクセントカラー" value={String(g.accent_color || '#1c1917')} onChange={(v) => dispatch({ type: 'UPDATE_GLOBAL', patch: { accent_color: v, theme_preset: 'custom' } })} />
                <SelectField label="フォント" value={String(g.font_family || '')} options={fontOptions} onChange={(v) => dispatch({ type: 'UPDATE_GLOBAL', patch: { font_family: v } })} />
                <SelectField label="背景トーン" value={String(g.surface_tone || '')} options={toneOptions} onChange={(v) => dispatch({ type: 'UPDATE_GLOBAL', patch: { surface_tone: v } })} />
            </Accordion>

            <Accordion title="見た目（角丸・密度・カード）" open={open.look} onToggle={() => setOpen((s) => ({ ...s, look: !s.look }))}>
                <RangeField label="セクション間隔" value={Number(g.section_gap || 16)} min={4} max={32} onChange={(v) => dispatch({ type: 'UPDATE_GLOBAL', patch: { section_gap: v } })} />
                <SelectField
                    label="角丸スタイル"
                    value={String(g.radius_scale || 'soft')}
                    options={[
                        { value: 'compact', label: 'コンパクト' },
                        { value: 'default', label: '標準' },
                        { value: 'soft', label: 'ソフト' },
                        { value: 'round', label: 'ラウンド' },
                    ]}
                    onChange={(v) => dispatch({ type: 'UPDATE_GLOBAL', patch: { radius_scale: v } })}
                />
                <SelectField
                    label="タブバースタイル"
                    value={String(g.tab_style || 'default')}
                    options={[
                        { value: 'default', label: '標準' },
                        { value: 'pill', label: 'ピル型' },
                        { value: 'floating', label: 'フローティング' },
                    ]}
                    onChange={(v) => dispatch({ type: 'UPDATE_GLOBAL', patch: { tab_style: v } })}
                />
                <SelectField
                    label="カードの質感"
                    value={String(g.card_style || '')}
                    options={[
                        { value: '', label: '（プリセット標準）' },
                        { value: 'flat', label: 'フラット' },
                        { value: 'elevated', label: 'エレベート' },
                        { value: 'outline', label: 'アウトライン' },
                    ]}
                    onChange={(v) => dispatch({ type: 'UPDATE_GLOBAL', patch: { card_style: v } })}
                />
                <SelectField
                    label="ボタン形状"
                    value={String(g.button_shape || '')}
                    options={[
                        { value: '', label: '（プリセット標準）' },
                        { value: 'rounded', label: '角丸' },
                        { value: 'pill', label: 'ピル型' },
                        { value: 'square', label: 'スクエア' },
                    ]}
                    onChange={(v) => dispatch({ type: 'UPDATE_GLOBAL', patch: { button_shape: v } })}
                />
                <SelectField
                    label="余白の密度"
                    value={String(g.density || 'default')}
                    options={[
                        { value: 'compact', label: 'コンパクト' },
                        { value: 'default', label: '標準' },
                        { value: 'relaxed', label: 'ゆったり' },
                    ]}
                    onChange={(v) => dispatch({ type: 'UPDATE_GLOBAL', patch: { density: v } })}
                />
            </Accordion>

            <Accordion title="機能画面UI" open={open.system} onToggle={() => setOpen((s) => ({ ...s, system: !s.system }))}>
                <SelectField
                    label="自動表示UIの雰囲気"
                    value={String(g.system_ui_style || 'refined')}
                    options={[
                        { value: 'refined', label: '上質ミニマル' },
                        { value: 'editorial', label: '写真映えリッチ' },
                        { value: 'compact', label: '情報密度高め' },
                        { value: 'luxe', label: 'ラグジュアリー' },
                    ]}
                    onChange={(v) => dispatch({ type: 'UPDATE_GLOBAL', patch: { system_ui_style: v } })}
                />
                <SelectField
                    label="自動表示カード"
                    value={String(g.system_card_style || 'soft')}
                    options={[
                        { value: 'soft', label: 'ソフトカード' },
                        { value: 'elevated', label: '浮かせる' },
                        { value: 'outline', label: '細線アウトライン' },
                        { value: 'flat', label: 'フラット' },
                    ]}
                    onChange={(v) => dispatch({ type: 'UPDATE_GLOBAL', patch: { system_card_style: v } })}
                />
                <SelectField
                    label="会員証レイアウト"
                    value={String(g.member_card_design || 'default')}
                    options={MEMBER_CARD_DESIGN_CHOICES}
                    onChange={(v) => dispatch({ type: 'UPDATE_GLOBAL', patch: { member_card_design: v } })}
                />
                <SelectField
                    label="会員証の表示形式"
                    value={String(g.member_card_display_type || 'card')}
                    options={MEMBER_CARD_DISPLAY_TYPES}
                    onChange={(v) => dispatch({ type: 'UPDATE_GLOBAL', patch: { member_card_display_type: v } })}
                />
                <SelectField
                    label="会員番号の表示"
                    value={String(g.member_card_code_type || 'barcode')}
                    options={MEMBER_CARD_CODE_TYPES}
                    onChange={(v) => dispatch({ type: 'UPDATE_GLOBAL', patch: { member_card_code_type: v } })}
                />
                <ColorField
                    label="会員証アクセント色（空欄=ブランド色）"
                    value={String(g.member_card_accent_color || '')}
                    onChange={(v) => dispatch({ type: 'UPDATE_GLOBAL', patch: { member_card_accent_color: v } })}
                />
                <SelectField
                    label="会員証背景スタイル"
                    value={String(g.member_card_bg_style || 'gradient')}
                    options={MEMBER_CARD_BG_STYLES}
                    onChange={(v) => dispatch({ type: 'UPDATE_GLOBAL', patch: { member_card_bg_style: v } })}
                />
                <div className="ld-member-card-preview">
                    <div className={`ld-crm-wallet ld-crm-wallet--${String(g.member_card_design || 'default')}`}>
                        <div className="ld-crm-wallet__top">
                            <div>
                                <span className="ld-crm-wallet__shop">{String(g.app_display_name || 'Members')}</span>
                                <strong>デモ 太郎</strong>
                            </div>
                            <em className="ld-crm-wallet__rank">Gold</em>
                        </div>
                        <div className="ld-crm-wallet__barcode">
                            {Array.from({ length: 22 }).map((_, i) => (
                                <i key={i} style={{ width: `${(i % 4) + 1}px` }} />
                            ))}
                        </div>
                        <div className="ld-crm-wallet__no">
                            {String(g.member_card_code_type || 'barcode') === 'qrcode' ? 'QRコード表示' : 'バーコード表示'}
                            {' · '}
                            {String(g.member_card_display_type || 'card') === 'sheet' ? 'シート型' : 'カード型'}
                        </div>
                    </div>
                </div>
                <SelectField
                    label="会員画面UI"
                    value={String(g.member_screen_design || 'wallet')}
                    options={[
                        { value: 'wallet', label: 'ウォレット' },
                        { value: 'apple', label: 'Apple' },
                        { value: 'premium', label: 'プレミアム' },
                        { value: 'minimal', label: 'ミニマル' },
                        { value: 'compact', label: 'コンパクト' },
                    ]}
                    onChange={(v) => dispatch({ type: 'UPDATE_GLOBAL', patch: { member_screen_design: v } })}
                />
                <SelectField
                    label="スタンプ画面デザイン"
                    value={String(g.stamp_screen_design || 'grid_card')}
                    options={STAMP_SCREEN_DESIGN_CHOICES}
                    onChange={(v) => dispatch({ type: 'UPDATE_GLOBAL', patch: { stamp_screen_design: v } })}
                />
                <SelectField
                    label="スタンプスロット形状"
                    value={String(g.stamp_slot_style || 'circle')}
                    options={STAMP_SLOT_STYLES}
                    onChange={(v) => dispatch({ type: 'UPDATE_GLOBAL', patch: { stamp_slot_style: v } })}
                />
                <SelectField
                    label="スタンプ押印スタイル"
                    value={String(g.stamp_filled_style || 'image')}
                    options={STAMP_FILLED_STYLES}
                    onChange={(v) => dispatch({ type: 'UPDATE_GLOBAL', patch: { stamp_filled_style: v } })}
                />
                <ColorField
                    label="スタンプアクセント色（空欄=ブランド色）"
                    value={String(g.stamp_accent_color || '')}
                    onChange={(v) => dispatch({ type: 'UPDATE_GLOBAL', patch: { stamp_accent_color: v } })}
                />
                <SelectField
                    label="クーポン画面UI"
                    value={String(g.coupon_screen_design || 'ticket')}
                    options={[
                        { value: 'ticket', label: 'チケット' },
                        { value: 'apple', label: 'Apple' },
                        { value: 'glass', label: 'グラス' },
                        { value: 'gallery', label: 'ギャラリー' },
                        { value: 'compact', label: 'コンパクト' },
                        { value: 'luxury', label: 'ラグジュアリー' },
                    ]}
                    onChange={(v) => dispatch({ type: 'UPDATE_GLOBAL', patch: { coupon_screen_design: v } })}
                />
                <SelectField
                    label="予約画面UI"
                    value={String(g.reservation_screen_design || 'cards')}
                    options={[
                        { value: 'cards', label: 'カード' },
                        { value: 'apple', label: 'Apple' },
                        { value: 'timeline', label: 'タイムライン' },
                        { value: 'compact', label: 'コンパクト' },                        { value: 'concierge', label: 'コンシェルジュ' },
                    ]}
                    onChange={(v) => dispatch({ type: 'UPDATE_GLOBAL', patch: { reservation_screen_design: v } })}
                />
                <SelectField
                    label="注文・順番待ちUI"
                    value={String(g.activity_screen_design || 'cards')}
                    options={[
                        { value: 'cards', label: 'カード' },
                        { value: 'timeline', label: 'タイムライン' },
                        { value: 'compact', label: 'コンパクト' },
                        { value: 'dashboard', label: 'ダッシュボード' },
                    ]}
                    onChange={(v) => dispatch({ type: 'UPDATE_GLOBAL', patch: { activity_screen_design: v } })}
                />
            </Accordion>

            <Accordion title="カスタムCSS" open={open.css} onToggle={() => setOpen((s) => ({ ...s, css: !s.css }))}>
                <textarea
                    className="ld-form-textarea"
                    rows={6}
                    value={state.customCss}
                    placeholder=".my-class { color: red; }"
                    onChange={(e) => dispatch({ type: 'SET_CUSTOM_CSS', value: e.target.value })}
                />
            </Accordion>
        </div>
    );
}

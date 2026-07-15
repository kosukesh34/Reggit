import React, { useState } from 'react';
import { useDesigner } from '../state/DesignerContext';
import { expandThemeIntoGlobal } from '../utils/globalDesign';
import { ColorField, FieldGroup, ImageUrlField, RangeField, SelectField, TextInput } from './fields/FormFields';

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
    const [open, setOpen] = useState({ preset: true, basic: true, look: false, system: false, css: false });

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
        dispatch({ type: 'SHOW_TOAST', message: `テーマ「${preset.label || preset.name || presetId}」を適用` });
    };

    return (
        <div className="ld-sidebar__section">
            <div className="ld-sidebar__title"><i className="fas fa-swatchbook" /> テーマ設定</div>

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
                        <ColorField label="グラデーション開始" value={String(g.bg_gradient_start || '#eef2ff')} onChange={(v) => dispatch({ type: 'UPDATE_GLOBAL', patch: { bg_gradient_start: v } })} />
                        <ColorField label="グラデーション終了" value={String(g.bg_gradient_end || '#ffffff')} onChange={(v) => dispatch({ type: 'UPDATE_GLOBAL', patch: { bg_gradient_end: v } })} />
                    </>
                ) : null}
                {g.background_style === 'image' ? (
                    <>
                        <ImageUrlField label="背景画像" value={String(g.bg_image_url || '')} onChange={(v) => dispatch({ type: 'UPDATE_GLOBAL', patch: { bg_image_url: v } })} />
                        <RangeField label="オーバーレイ不透明度" value={Number(g.bg_overlay_opacity ?? 60)} min={0} max={80} onChange={(v) => dispatch({ type: 'UPDATE_GLOBAL', patch: { bg_overlay_opacity: v } })} />
                    </>
                ) : (
                    <ColorField label="背景色" value={String(g.bg_color || '#f9fafb')} onChange={(v) => dispatch({ type: 'UPDATE_GLOBAL', patch: { bg_color: v } })} />
                )}
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
                    value={String(g.member_card_design || '')}
                    options={[
                        { value: '', label: '既存の会員設定を使う' },
                        { value: 'default', label: '標準カード' },
                        { value: 'logo_top', label: 'ロゴ上部' },
                        { value: 'logo_center', label: 'ロゴ中央' },
                        { value: 'compact', label: 'コンパクト' },
                        { value: 'emblem', label: 'エンブレム' },
                    ]}
                    onChange={(v) => dispatch({ type: 'UPDATE_GLOBAL', patch: { member_card_design: v } })}
                />
                <SelectField
                    label="会員画面UI"
                    value={String(g.member_screen_design || 'wallet')}
                    options={[
                        { value: 'wallet', label: 'ウォレット' },
                        { value: 'premium', label: 'プレミアム' },
                        { value: 'minimal', label: 'ミニマル' },
                        { value: 'compact', label: 'コンパクト' },
                    ]}
                    onChange={(v) => dispatch({ type: 'UPDATE_GLOBAL', patch: { member_screen_design: v } })}
                />
                <SelectField
                    label="クーポン画面UI"
                    value={String(g.coupon_screen_design || 'ticket')}
                    options={[
                        { value: 'ticket', label: 'チケット' },
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
                        { value: 'timeline', label: 'タイムライン' },
                        { value: 'compact', label: 'コンパクト' },
                        { value: 'concierge', label: 'コンシェルジュ' },
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

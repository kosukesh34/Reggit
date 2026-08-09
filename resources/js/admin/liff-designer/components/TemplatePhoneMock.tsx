import React, { useEffect, useMemo, useState } from 'react';
import { normalizeBlock, TAB_ICONS, TAB_LABELS } from '../catalog/blockCatalog';
import { BlockPreview } from '../blocks/BlockPreview';
import {
    DEFAULT_PREVIEW_TABS,
    PhoneAppHeader,
    PhoneAppMain,
    PhoneAppTabs,
    PhoneDeviceFrame,
} from './PhoneAppShell';
import {
    galleryAccentFor,
    previewBlocksFor,
    screenBgFor,
    signaturePreviewTab,
    templateDisplayName,
    type TemplateItem,
} from './templateGalleryMeta';
import { frameSystemClasses, screenTabStyleClass } from '../utils/globalDesign';
import type { GlobalSettings } from '../catalog/types';

type FontStackMeta = { label: string; base?: string; display?: string };

type Props = {
    template: TemplateItem;
    size?: 'card' | 'large';
    interactive?: boolean;
    fontStacks?: Record<string, FontStackMeta>;
    /** ウィザード等から「この画面を見せたい」ときだけ指定。設定変更では渡さないこと */
    preferredTab?: string;
};

function useFitPhoneMaxHeight(enabled: boolean): number | undefined {
    const [maxHeight, setMaxHeight] = useState<number | undefined>(enabled ? 520 : undefined);

    useEffect(() => {
        if (!enabled) {
            setMaxHeight(undefined);
            return;
        }
        const update = () => {
            // ヘッダー・余白・ヒントを除いた可視領域に端末全体を収める
            const next = Math.max(360, Math.min(520, window.innerHeight - 230));
            setMaxHeight(next);
        };
        update();
        window.addEventListener('resize', update);
        return () => window.removeEventListener('resize', update);
    }, [enabled]);

    return maxHeight;
}

const FONT_FALLBACKS: Record<string, { base: string; display: string }> = {
    sans: {
        base: '"Hiragino Sans", "Hiragino Kaku Gothic ProN", "Yu Gothic", Meiryo, sans-serif',
        display: '"Hiragino Sans", "Hiragino Kaku Gothic ProN", "Yu Gothic", Meiryo, sans-serif',
    },
    rounded: {
        base: '"Hiragino Maru Gothic ProN", "Hiragino Sans", "Yu Gothic", Meiryo, sans-serif',
        display: '"Hiragino Maru Gothic ProN", "Hiragino Sans", "Yu Gothic", Meiryo, sans-serif',
    },
    serif: {
        base: '"Hiragino Mincho ProN", "Yu Mincho", "MS PMincho", serif',
        display: '"Hiragino Mincho ProN", "Yu Mincho", "MS PMincho", serif',
    },
    'serif-display': {
        base: '"Hiragino Sans", "Hiragino Kaku Gothic ProN", "Yu Gothic", Meiryo, sans-serif',
        display: '"Hiragino Mincho ProN", "Yu Mincho", "MS PMincho", serif',
    },
};

function resolveFontStack(
    key: string,
    stacks?: Record<string, FontStackMeta>,
): { base: string; display: string } {
    const fromConfig = stacks?.[key];
    if (fromConfig !== undefined && typeof fromConfig.base === 'string' && fromConfig.base !== '') {
        return {
            base: fromConfig.base,
            display: typeof fromConfig.display === 'string' && fromConfig.display !== ''
                ? fromConfig.display
                : fromConfig.base,
        };
    }
    return FONT_FALLBACKS[key] || FONT_FALLBACKS.sans;
}

function previewTabsFor(template: TemplateItem) {
    // テンプレに定義された menu をそのまま使う（業種ごとのラベル・順・アイコン）
    if (Array.isArray(template.menu) && template.menu.length > 0) {
        const fromMenu = template.menu
            .filter((row) => row !== null && typeof row === 'object' && typeof row.tab === 'string' && row.enabled !== false)
            .map((row) => ({
                tab: String(row.tab),
                label: String(row.label || TAB_LABELS[row.tab] || row.tab),
                icon: String(row.icon || TAB_ICONS[row.tab] || 'fa-circle'),
            }))
            .filter((row) => TAB_LABELS[row.tab] !== undefined || row.tab !== '');
        if (fromMenu.length > 0) return fromMenu.slice(0, 6);
    }

    const keys: string[] = [];
    const push = (tab: string) => {
        if (TAB_LABELS[tab] === undefined || keys.includes(tab)) return;
        keys.push(tab);
    };

    if (isLayoutTemplate(template)) {
        Object.keys(template.layouts || {}).forEach((tab) => {
            const rows = template.layouts?.[tab];
            if (Array.isArray(rows) && rows.length > 0) push(tab);
        });
    }
    (Array.isArray(template.target_tabs) ? template.target_tabs : []).forEach((tab) => {
        if (typeof tab === 'string') push(tab);
    });
    if (keys.length === 0) push('home');

    const mapped = keys.map((tab) => {
        const fallback = DEFAULT_PREVIEW_TABS.find((t) => t.tab === tab);
        return {
            tab,
            label: fallback?.label || TAB_LABELS[tab] || tab,
            icon: fallback?.icon || TAB_ICONS[tab] || 'fa-circle',
        };
    });
    return mapped.length > 0 ? mapped.slice(0, 6) : DEFAULT_PREVIEW_TABS;
}

function isLayoutTemplate(template: TemplateItem): boolean {
    return (
        template.layouts !== undefined &&
        template.layouts !== null &&
        typeof template.layouts === 'object' &&
        !Array.isArray(template.layouts)
    );
}

function softAccent(hex: string): string {
    const rgb = hexToRgbTuple(hex);
    return `rgba(${rgb[0]}, ${rgb[1]}, ${rgb[2]}, 0.12)`;
}

function darkenAccent(hex: string): string {
    const [r, g, b] = hexToRgbTuple(hex);
    return `rgb(${Math.max(0, r - 28)}, ${Math.max(0, g - 28)}, ${Math.max(0, b - 28)})`;
}

export function TemplatePhoneMock({
    template,
    size = 'card',
    interactive = false,
    fontStacks,
    preferredTab,
}: Props) {
    const accent = galleryAccentFor(template).color;
    const bg = screenBgFor(template);
    const isDark = bg.includes('rgba(15,23,42') || String(template.theme?.bg_color || '').toLowerCase() === '#0f172a';
    const shopName = String(template.theme?.app_display_name || '').trim();
    const title = shopName !== '' ? shopName : templateDisplayName(template);
    const layoutKeysSig = useMemo(() => {
        if (template.layouts === undefined || typeof template.layouts !== 'object') return '';
        return Object.keys(template.layouts).join(',');
    }, [template.layouts]);
    const targetsSig = useMemo(
        () => (Array.isArray(template.target_tabs) ? template.target_tabs.join(',') : ''),
        [template.target_tabs],
    );
    const menuSignature = useMemo(
        () => (Array.isArray(template.menu) ? template.menu.map((m) => `${m.tab}:${m.label}:${m.icon}`).join('|') : ''),
        [template.menu],
    );
    const structureSignature = `${String(template.id || '')}|${targetsSig}|${layoutKeysSig}|${menuSignature}`;

    const tabs = useMemo(() => previewTabsFor(template), [structureSignature]);
    const initialTab = size === 'card' ? signaturePreviewTab(template) : (tabs[0]?.tab || 'home');
    const [activeTab, setActiveTab] = useState(initialTab);
    const pad = Number(template.theme?.content_padding ?? 18);
    const gap = Number(template.theme?.section_gap ?? 16);
    const cardDesign = String(template.theme?.member_card_design || '');
    const cardBgStyle = String(template.theme?.member_card_bg_style || 'gradient');
    const cardGloss = String(template.theme?.member_card_gloss || 'soft');
    const cardSurface = String(template.theme?.member_card_surface || 'plastic');
    const couponScreenDesign = String(template.theme?.coupon_screen_design || 'ticket');
    const couponMediaStyle = String(template.theme?.coupon_media_style || 'image');
    const couponShowAvailable = template.theme?.coupon_show_available !== false;
    const couponShowHistory = template.theme?.coupon_show_history !== false;
    const stampDesign = String(template.theme?.stamp_screen_design || '');
    const stampSlot = String(template.theme?.stamp_slot_style || '');
    const stampFilled = String(template.theme?.stamp_filled_style || '');
    const stampAccent = String(template.theme?.stamp_accent_color || template.theme?.accent_color || '');
    const fontKey = String(template.theme?.font_family || 'sans');
    const fonts = resolveFontStack(fontKey, fontStacks);
    const buttonShape = String(template.theme?.button_shape || '');
    const cardStyle = String(template.theme?.card_style || '');
    const contentWidth = String(template.theme?.content_width || 'comfy');
    const themeAsGlobal = (template.theme || {}) as GlobalSettings;
    const tabStyleClass = screenTabStyleClass(themeAsGlobal);
    const systemClass = frameSystemClasses(themeAsGlobal);
    const showCart = tabs.some((t) => t.tab === 'products' || t.tab === 'orders');
    const fitMaxHeight = useFitPhoneMaxHeight(size === 'large');

    useEffect(() => {
        // 構造（タブ一覧）が変わったときだけフォールバック。テーマ/setup の再生成では飛ばさない
        const nextTabs = previewTabsFor(template);
        setActiveTab((prev) => {
            if (nextTabs.some((t) => t.tab === prev)) {
                return prev;
            }
            if (size === 'card') {
                return signaturePreviewTab(template);
            }
            return nextTabs[0]?.tab || 'home';
        });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [structureSignature, size]);

    useEffect(() => {
        if (preferredTab === undefined || preferredTab === '') return;
        const nextTabs = previewTabsFor(template);
        if (!nextTabs.some((t) => t.tab === preferredTab)) return;
        setActiveTab(preferredTab);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [preferredTab]);

    const blocks = useMemo(() => {
        const idCounter = { n: 1 };
        // card サムネは先頭のみ、large はテンプレ実データをそのまま全部表示
        const limit = size === 'large' ? Number.POSITIVE_INFINITY : 6;
        const sourceRows = previewBlocksFor(template, activeTab);
        return sourceRows.slice(0, limit).map((item) => {
            const block = normalizeBlock((item || {}) as Record<string, unknown>, idCounter);
            if (
                cardDesign !== ''
                && block.type === 'data'
                && (block.source === 'member_card' || block.source === 'member')
                && (block.card_design === undefined || block.card_design === '' || block.card_design === 'default')
            ) {
                return { ...block, card_design: cardDesign };
            }
            if (block.type === 'data' && block.source === 'stamps') {
                return {
                    ...block,
                    stamp_design: block.stamp_design || stampDesign || 'grid_card',
                    stamp_slot_style: block.stamp_slot_style || stampSlot || 'circle',
                    stamp_filled_style: block.stamp_filled_style || stampFilled || 'image',
                    stamp_accent_color: block.stamp_accent_color || stampAccent,
                };
            }
            return block;
        });
    }, [template, size, cardDesign, stampDesign, stampSlot, stampFilled, stampAccent, activeTab]);

    const contentMaxWidth =
        contentWidth === 'edge' ? '100%' : contentWidth === 'narrow' ? 'min(100%, 320px)' : 'min(100%, 360px)';

    const screenVars = {
        background: bg,
        fontFamily: fonts.base,
        ['--font-base' as string]: fonts.base,
        ['--font-display' as string]: fonts.display,
        ['--font-sans' as string]: FONT_FALLBACKS.sans.base,
        ['--font-serif' as string]: FONT_FALLBACKS.serif.base,
        ['--font-rounded' as string]: FONT_FALLBACKS.rounded.base,
        ['--primary-color' as string]: accent,
        ['--primary-dark' as string]: darkenAccent(accent),
        ['--primary-soft' as string]: softAccent(accent),
        ['--primary-rgb' as string]: hexToRgb(accent),
        ['--ld-accent' as string]: accent,
        ['--ld-accent-dark' as string]: darkenAccent(accent),
        ['--ld-accent-soft' as string]: softAccent(accent),
        ['--tpm-content-max' as string]: contentMaxWidth,
    };

    const selectTab = (tab: string) => {
        if (!interactive) return;
        setActiveTab(tab);
    };

    const onMainClick = (e: React.MouseEvent) => {
        if (!interactive) return;
        const navEl = (e.target as HTMLElement).closest('[data-preview-nav]');
        if (navEl === null) return;
        const tab = navEl.getAttribute('data-preview-nav');
        if (tab !== null && tab !== '') {
            e.stopPropagation();
            setActiveTab(tab);
        }
    };

    const themePreset = String(template.theme?.theme_preset || '');
    const density = String(template.theme?.density || '');
    const surfaceTone = String(template.theme?.surface_tone || '');
    const styleClass = [
        `ld-tpm ld-tpm--${size}`,
        isDark ? 'ld-tpm--dark' : '',
        'ld-tpm--themed',
        interactive ? 'ld-tpm--interactive' : '',
        buttonShape !== '' ? `ld-tpm-btn--${buttonShape}` : '',
        cardStyle !== '' ? `ld-tpm-card--${cardStyle}` : '',
        `ld-tpm-width--${contentWidth || 'comfy'}`,
        themePreset !== '' ? `theme-${themePreset}` : '',
        density !== '' ? `density-${density}` : '',
        density !== '' ? `ld-tpm-density--${density}` : '',
        surfaceTone !== '' ? `surface-${surfaceTone}` : '',
        systemClass,
    ].filter(Boolean).join(' ');

    const screenClassName = [
        tabStyleClass,
        systemClass,
        themePreset !== '' ? `theme-${themePreset}` : '',
        density !== '' ? `density-${density}` : '',
        surfaceTone !== '' ? `surface-${surfaceTone}` : '',
    ].filter(Boolean).join(' ');

    return (
        <div
            className={styleClass}
            style={{ ['--tpm-accent' as string]: accent, ['--tpm-accent-soft' as string]: softAccent(accent) }}
            onClick={(e) => {
                if (interactive) e.stopPropagation();
            }}
        >
            <PhoneDeviceFrame
                className={screenClassName || undefined}
                size={size === 'large' ? 'large' : 'card'}
                maxHeight={fitMaxHeight}
                screenStyle={screenVars}
                screenClassName={screenClassName}
            >
                <PhoneAppHeader
                    title={title}
                    showCart={showCart}
                    onAccountClick={interactive ? () => selectTab('member') : undefined}
                    onCartClick={interactive ? () => selectTab('products') : undefined}
                />
                <PhoneAppMain
                    key={activeTab}
                    className={interactive ? 'ld-phone-app-main--tab-enter' : undefined}
                    padding={pad}
                    gap={gap}
                    onClick={onMainClick}
                >
                    {blocks.length === 0 ? (
                        <div className="ld-tpm__empty">
                            <p>この画面のレイアウトは空です</p>
                            {interactive ? <span>別のタブを選んで確認できます</span> : null}
                        </div>
                    ) : (
                        blocks.map((block) => (
                            <div key={block.id} className={`ld-block ld-block--${block.type}`}>
                                <BlockPreview
                                    block={block}
                                    shopName={shopName !== '' ? shopName : undefined}
                                    memberCardDesign={cardDesign}
                                    memberCardBgStyle={cardBgStyle}
                                    memberCardGloss={cardGloss}
                                    memberCardSurface={cardSurface}
                                    couponScreenDesign={couponScreenDesign}
                                    couponMediaStyle={couponMediaStyle}
                                    couponShowAvailable={couponShowAvailable}
                                    couponShowHistory={couponShowHistory}
                                    stampScreenDesign={stampDesign}
                                    stampSlotStyle={stampSlot}
                                    stampFilledStyle={stampFilled}
                                    stampAccentColor={stampAccent}
                                />
                            </div>
                        ))
                    )}
                </PhoneAppMain>
                <PhoneAppTabs
                    items={tabs}
                    activeTab={activeTab}
                    onSelect={interactive ? selectTab : undefined}
                />
            </PhoneDeviceFrame>
        </div>
    );
}

function hexToRgbTuple(hex: string): [number, number, number] {
    const raw = hex.replace('#', '').trim();
    if (raw.length === 3) {
        return [
            parseInt(raw[0] + raw[0], 16),
            parseInt(raw[1] + raw[1], 16),
            parseInt(raw[2] + raw[2], 16),
        ];
    }
    if (raw.length === 6) {
        const r = parseInt(raw.slice(0, 2), 16);
        const g = parseInt(raw.slice(2, 4), 16);
        const b = parseInt(raw.slice(4, 6), 16);
        if ([r, g, b].every((n) => !Number.isNaN(n))) return [r, g, b];
    }
    return [28, 25, 23];
}

function hexToRgb(hex: string): string {
    return hexToRgbTuple(hex).join(', ');
}

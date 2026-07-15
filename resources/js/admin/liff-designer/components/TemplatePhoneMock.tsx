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
    templateDisplayName,
    type TemplateItem,
} from './templateGalleryMeta';

type FontStackMeta = { label: string; base?: string; display?: string };

type Props = {
    template: TemplateItem;
    size?: 'card' | 'large';
    interactive?: boolean;
    fontStacks?: Record<string, FontStackMeta>;
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
    if (!keys.includes('member')) push('member');

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
}: Props) {
    const accent = galleryAccentFor(template).color;
    const bg = screenBgFor(template);
    const isDark = bg.includes('rgba(15,23,42') || String(template.theme?.bg_color || '').toLowerCase() === '#0f172a';
    const shopName = String(template.theme?.app_display_name || '').trim();
    const title = shopName !== '' ? shopName : templateDisplayName(template);
    const tabSignature = useMemo(() => {
        const targets = Array.isArray(template.target_tabs) ? template.target_tabs.join(',') : '';
        const layoutKeys =
            template.layouts !== undefined && typeof template.layouts === 'object'
                ? Object.keys(template.layouts).join(',')
                : '';
        return `${String(template.id || '')}|${targets}|${layoutKeys}`;
    }, [template.id, template.target_tabs, template.layouts]);

    const tabs = useMemo(() => previewTabsFor(template), [tabSignature, template]);
    const [activeTab, setActiveTab] = useState(tabs[0]?.tab || 'home');
    const pad = Number(template.theme?.content_padding ?? 18);
    const gap = Number(template.theme?.section_gap ?? 16);
    const cardDesign = String(template.theme?.member_card_design || '');
    const fontKey = String(template.theme?.font_family || 'sans');
    const fonts = resolveFontStack(fontKey, fontStacks);
    const buttonShape = String(template.theme?.button_shape || '');
    const cardStyle = String(template.theme?.card_style || '');
    const contentWidth = String(template.theme?.content_width || 'comfy');
    const showCart = tabs.some((t) => t.tab === 'products' || t.tab === 'orders');
    const fitMaxHeight = useFitPhoneMaxHeight(size === 'large');

    useEffect(() => {
        setActiveTab(tabs[0]?.tab || 'home');
    }, [tabSignature]);

    useEffect(() => {
        if (tabs.some((t) => t.tab === activeTab)) return;
        setActiveTab(tabs[0]?.tab || 'home');
    }, [tabs, activeTab]);

    const blocks = useMemo(() => {
        const idCounter = { n: 1 };
        const limit = size === 'large' ? 14 : 7;
        const sourceRows = previewBlocksFor(template, activeTab);
        const rows = sourceRows.length > 0 ? sourceRows : previewBlocksFor(template);
        return rows.slice(0, limit).map((item) => {
            const block = normalizeBlock((item || {}) as Record<string, unknown>, idCounter);
            if (
                cardDesign !== ''
                && block.type === 'data'
                && (block.source === 'member_card' || block.source === 'member')
            ) {
                return { ...block, card_design: cardDesign };
            }
            return block;
        });
    }, [template, size, cardDesign, activeTab]);

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

    const styleClass = [
        `ld-tpm ld-tpm--${size}`,
        isDark ? 'ld-tpm--dark' : '',
        'ld-tpm--themed',
        interactive ? 'ld-tpm--interactive' : '',
        buttonShape !== '' ? `ld-tpm-btn--${buttonShape}` : '',
        cardStyle !== '' ? `ld-tpm-card--${cardStyle}` : '',
        `ld-tpm-width--${contentWidth || 'comfy'}`,
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
                size={size === 'large' ? 'large' : 'card'}
                maxHeight={fitMaxHeight}
                screenStyle={screenVars}
            >
                <PhoneAppHeader
                    title={title}
                    showCart={showCart}
                    onAccountClick={interactive ? () => selectTab('member') : undefined}
                    onCartClick={interactive ? () => selectTab('products') : undefined}
                />
                <PhoneAppMain padding={pad} gap={gap} onClick={onMainClick}>
                    {blocks.length === 0 ? (
                        <div className="ld-tpm__empty">
                            <p>この画面のレイアウトは空です</p>
                            {interactive ? <span>別のタブを選んで確認できます</span> : null}
                        </div>
                    ) : (
                        blocks.map((block) => (
                            <div key={block.id} className={`ld-block ld-block--${block.type}`}>
                                <BlockPreview block={block} shopName={shopName !== '' ? shopName : undefined} />
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

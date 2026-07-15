import React, { useMemo } from 'react';
import { normalizeBlock } from '../catalog/blockCatalog';
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

type Props = {
    template: TemplateItem;
    size?: 'card' | 'large';
};

function previewTabsFor(template: TemplateItem) {
    const keys =
        isLayoutTemplate(template)
            ? Object.keys(template.layouts || {})
            : Array.isArray(template.target_tabs)
              ? template.target_tabs
              : [];
    if (keys.length === 0) return DEFAULT_PREVIEW_TABS;
    const mapped = keys
        .map((tab) => DEFAULT_PREVIEW_TABS.find((t) => t.tab === tab) || { tab, label: tab, icon: 'fa-circle' })
        .slice(0, 5);
    return mapped.length > 0 ? mapped : DEFAULT_PREVIEW_TABS;
}

function isLayoutTemplate(template: TemplateItem): boolean {
    return (
        template.layouts !== undefined &&
        template.layouts !== null &&
        typeof template.layouts === 'object' &&
        !Array.isArray(template.layouts)
    );
}

export function TemplatePhoneMock({ template, size = 'card' }: Props) {
    const accent = galleryAccentFor(template).color;
    const bg = screenBgFor(template);
    const isDark = bg.includes('rgba(15,23,42') || String(template.theme?.bg_color || '').toLowerCase() === '#0f172a';
    const title = templateDisplayName(template);
    const tabs = previewTabsFor(template);
    const activeTab = tabs[0]?.tab || 'home';
    const pad = Number(template.theme?.content_padding ?? 18);
    const gap = Number(template.theme?.section_gap ?? 16);

    const blocks = useMemo(() => {
        const idCounter = { n: 1 };
        const limit = size === 'large' ? 8 : 6;
        return previewBlocksFor(template)
            .slice(0, limit)
            .map((item) => normalizeBlock((item || {}) as Record<string, unknown>, idCounter));
    }, [template, size]);

    const screenVars = {
        background: bg,
        ['--primary-color' as string]: accent,
        ['--primary-rgb' as string]: hexToRgb(accent),
        ['--ld-accent' as string]: accent,
    };

    return (
        <div className={`ld-tpm ld-tpm--${size}${isDark ? ' ld-tpm--dark' : ''}`}>
            <PhoneDeviceFrame size={size === 'large' ? 'large' : 'card'} screenStyle={screenVars}>
                <PhoneAppHeader title={title} />
                <PhoneAppMain padding={pad} gap={gap}>
                    {blocks.length === 0 ? (
                        <div className="ld-tpm__empty">プレビューなし</div>
                    ) : (
                        blocks.map((block) => (
                            <div key={block.id} className={`ld-block ld-block--${block.type}`}>
                                <BlockPreview block={block} />
                            </div>
                        ))
                    )}
                </PhoneAppMain>
                <PhoneAppTabs items={tabs} activeTab={activeTab} />
            </PhoneDeviceFrame>
        </div>
    );
}

function hexToRgb(hex: string): string {
    const raw = hex.replace('#', '').trim();
    if (raw.length === 3) {
        const r = parseInt(raw[0] + raw[0], 16);
        const g = parseInt(raw[1] + raw[1], 16);
        const b = parseInt(raw[2] + raw[2], 16);
        return `${r}, ${g}, ${b}`;
    }
    if (raw.length === 6) {
        const r = parseInt(raw.slice(0, 2), 16);
        const g = parseInt(raw.slice(2, 4), 16);
        const b = parseInt(raw.slice(4, 6), 16);
        if ([r, g, b].every((n) => !Number.isNaN(n))) return `${r}, ${g}, ${b}`;
    }
    return '28, 25, 23';
}

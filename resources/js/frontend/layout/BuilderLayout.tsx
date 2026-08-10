import React, { useEffect, useMemo, useRef } from 'react';
import { BuilderBlock } from './blocks/BuilderBlock';
import { handleBuilderNavClick } from './blocks/builderNav';
import { resetMemberMeCache } from './blocks/memberBind';
import type { BuilderBlockData } from './blocks/types';

type BuilderLayoutProps = {
    tab: string;
    blocks: unknown[];
};

const DEFAULT_TAB_SOURCES: Record<string, string> = {
    home: 'announcements',
    products: 'products',
    orders: 'orders',
    stamps: 'stamps',
    coupons: 'coupons',
    queue: 'queue',
    member: 'member',
    reservations: 'reservations',
    gallery: 'gallery',
    announcements: 'announcements',
};

function hasDataBlock(blocks: BuilderBlockData[], source: string): boolean {
    return blocks.some((b) => {
        if (b == null || b.type !== 'data') {
            return false;
        }
        if (b.source === source) {
            return true;
        }
        if (source === 'member' && b.source === 'member_card') {
            return true;
        }
        if (source === 'member_card' && b.source === 'member') {
            return true;
        }
        return false;
    });
}

function withDefaultDataBlock(tab: string, blocks: BuilderBlockData[]): BuilderBlockData[] {
    const defaultSource = DEFAULT_TAB_SOURCES[tab];
    if (defaultSource === undefined || hasDataBlock(blocks, defaultSource)) {
        return blocks;
    }
    return [...blocks, { type: 'data', source: defaultSource }];
}

function applyTabSettings(section: HTMLElement, tab: string): void {
    const all = window.__LIFF_TAB_SETTINGS__ as Record<string, Record<string, unknown>> | undefined;
    if (all === undefined || all === null) {
        return;
    }
    const settings = all[tab] !== undefined ? all[tab] : {};
    const global = all._global !== undefined ? all._global : {};
    if (typeof settings.background_color === 'string') {
        section.style.background = settings.background_color;
    }
    if (typeof settings.background_image === 'string') {
        section.style.backgroundImage = `url('${settings.background_image}')`;
        section.style.backgroundSize = typeof settings.background_size === 'string' ? settings.background_size : 'cover';
        section.style.backgroundPosition = typeof settings.background_position === 'string'
            ? settings.background_position
            : 'center';
        section.style.backgroundRepeat = typeof settings.background_repeat === 'string'
            ? settings.background_repeat
            : 'no-repeat';
    }
    const contentPadding = settings.content_padding ?? global.content_padding;
    const sectionGap = global.section_gap;
    if (contentPadding !== undefined && contentPadding !== null) {
        section.style.setProperty('--content-padding', `${contentPadding}px`);
    }
    if (sectionGap !== undefined && sectionGap !== null) {
        section.style.setProperty('--section-gap', `${sectionGap}px`);
    }
}

/**
 * Designer ブロックを React JSX で描画する（premium マークアップ = LiffLayoutManager と同一クラス）。
 */
function BuilderLayoutInner({ tab, blocks }: BuilderLayoutProps): JSX.Element {
    const sectionRef = useRef<HTMLDivElement>(null);
    const resolvedBlocks = useMemo(() => {
        const list = (Array.isArray(blocks) ? blocks : []) as BuilderBlockData[];
        return withDefaultDataBlock(tab, list);
    }, [tab, blocks]);

    useEffect(() => {
        const section = sectionRef.current;
        if (section === null) {
            return;
        }
        section.dataset.reactOwned = '1';
        section.dataset.layoutRendered = '1';
        applyTabSettings(section, tab);
        resetMemberMeCache();

        const onClick = (e: MouseEvent): void => {
            handleBuilderNavClick(e);
        };
        section.addEventListener('click', onClick);
        return () => {
            section.removeEventListener('click', onClick);
        };
    }, [tab]);

    if (resolvedBlocks.length === 0) {
        return (
            <div
                ref={sectionRef}
                className="tab-section"
                id={`${tab}Section`}
                data-tab={tab}
                data-react-owned="1"
                data-layout-rendered="1"
            />
        );
    }

    return (
        <div
            ref={sectionRef}
            className="tab-section"
            id={`${tab}Section`}
            data-tab={tab}
            data-react-owned="1"
            data-layout-rendered="1"
        >
            <div className="builder-container">
                {resolvedBlocks.map((b, i) => (
                    <BuilderBlock key={b.id != null ? String(b.id) : `${String(b.type || 'block')}-${i}`} block={b} />
                ))}
            </div>
        </div>
    );
}

export const BuilderLayout = React.memo(BuilderLayoutInner);

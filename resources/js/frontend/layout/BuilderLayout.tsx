import React, { useEffect, useRef } from 'react';
import { paintBuilderLayout } from './builderHtml';

type BuilderLayoutProps = {
    tab: string;
    blocks: unknown[];
};

function readActiveTab(): string {
    if (typeof window.app?.currentTab === 'string' && window.app.currentTab !== '') {
        return window.app.currentTab;
    }
    const hash = (window.location.hash || '').replace(/^#/, '');
    if (hash !== '' && !hash.includes('/')) {
        return hash.split('?')[0] ?? 'home';
    }
    return 'home';
}

/**
 * Designer ブロックを React が所有して描画する。
 * HTML は layout/builderHtml（= LiffLayoutManager.renderCustomLayout）と同一。
 * アクティブタブのときだけ paint（全タブ同時 data-load で mount を潰さない）。
 */
function BuilderLayoutInner({ tab, blocks }: BuilderLayoutProps): JSX.Element {
    const sectionRef = useRef<HTMLDivElement>(null);
    const blocksRef = useRef(blocks);
    blocksRef.current = blocks;

    useEffect(() => {
        const section = sectionRef.current;
        if (section === null) {
            return;
        }
        section.dataset.reactOwned = '1';

        const needsPaint = (): boolean => {
            if ((section.innerHTML || '').trim() === '') {
                return true;
            }
            return section.querySelector('.builder-container') === null;
        };

        const paint = (): boolean => {
            const nextBlocks = Array.isArray(blocksRef.current) ? blocksRef.current : [];
            if (nextBlocks.length === 0) {
                return false;
            }
            const ok = paintBuilderLayout(section, tab, nextBlocks);
            if (ok) {
                section.dataset.layoutRendered = '1';
            }
            return ok;
        };

        const runIfActive = (): boolean => {
            const app = window.app;
            if (app === undefined || app === null) {
                return false;
            }
            if (readActiveTab() !== tab) {
                return true;
            }
            if (!needsPaint()) {
                section.dataset.layoutRendered = '1';
                return true;
            }
            return paint();
        };

        const onTabChanged = (): void => {
            runIfActive();
        };

        if (!runIfActive()) {
            document.addEventListener('liff-ready', onTabChanged);
        }
        window.addEventListener('hashchange', onTabChanged);
        window.addEventListener('ios-tab-changed', onTabChanged);
        const timer = window.setInterval(() => {
            if (runIfActive()) {
                window.clearInterval(timer);
            }
        }, 50);
        const stop = window.setTimeout(() => window.clearInterval(timer), 5000);

        return () => {
            document.removeEventListener('liff-ready', onTabChanged);
            window.removeEventListener('hashchange', onTabChanged);
            window.removeEventListener('ios-tab-changed', onTabChanged);
            window.clearInterval(timer);
            window.clearTimeout(stop);
        };
    }, [tab]);

    return (
        <div
            ref={sectionRef}
            className="tab-section"
            id={`${tab}Section`}
            data-tab={tab}
            data-react-owned="1"
        />
    );
}

export const BuilderLayout = React.memo(BuilderLayoutInner);

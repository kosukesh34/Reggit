import React, { useEffect, useRef } from 'react';

type ManagerTabHostProps = {
    tab: string;
    className?: string;
    linkUrl?: string;
};

/**
 * Manager 描画用の安定マウント点。
 * 再レンダーで innerHTML が消えないよう memo 化し、描画は ReggitApp / LiffLayoutManager に任せる。
 */
function ManagerTabHostInner({ tab, className, linkUrl }: ManagerTabHostProps): JSX.Element {
    const sectionRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        const section = sectionRef.current;
        if (section === null) {
            return;
        }
        // app 生成後に未描画なら一度だけ layoutManager に任せる（初回レース対策）
        const tryPaint = (): boolean => {
            const app = window.app;
            if (app === undefined || app === null || typeof app.layoutManager?.render !== 'function') {
                return false;
            }
            if (section.dataset.layoutRendered === '1') {
                return true;
            }
            app.layoutManager.render(tab);
            return section.dataset.layoutRendered === '1' || (section.innerHTML || '').trim() !== '';
        };

        if (tryPaint()) {
            return;
        }

        const onReady = (): void => {
            tryPaint();
        };
        document.addEventListener('liff-ready', onReady);
        const timer = window.setInterval(() => {
            if (tryPaint()) {
                window.clearInterval(timer);
            }
        }, 50);
        const stop = window.setTimeout(() => window.clearInterval(timer), 5000);

        return () => {
            document.removeEventListener('liff-ready', onReady);
            window.clearInterval(timer);
            window.clearTimeout(stop);
        };
    }, [tab]);

    const sectionClass = className ?? 'tab-section';

    return (
        <div
            ref={sectionRef}
            className={sectionClass}
            id={`${tab}Section`}
            data-tab={tab}
            data-link-url={linkUrl}
        />
    );
}

export const ManagerTabHost = React.memo(ManagerTabHostInner);

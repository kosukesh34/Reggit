import React, { useEffect, useRef } from 'react';

type AppPaintTarget = NonNullable<Window['app']>;

type ManagerPaintHostProps = {
    tab: string;
    paint: (app: AppPaintTarget) => void;
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
 * React がセクションを所有し、中身の描画だけ既存 Manager に任せる。
 * アクティブタブになったときだけ paint する（全タブ同時ロードを避ける）。
 */
function ManagerPaintHostInner({ tab, paint }: ManagerPaintHostProps): JSX.Element {
    const sectionRef = useRef<HTMLDivElement>(null);
    const paintRef = useRef(paint);
    paintRef.current = paint;

    useEffect(() => {
        const section = sectionRef.current;
        if (section === null) {
            return;
        }
        section.dataset.reactOwned = '1';

        const runIfActive = (): boolean => {
            const app = window.app;
            if (app === undefined || app === null) {
                return false;
            }
            if (readActiveTab() !== tab) {
                return true;
            }
            paintRef.current(app);
            section.dataset.layoutRendered = '1';
            return true;
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

export const ManagerPaintHost = React.memo(ManagerPaintHostInner);

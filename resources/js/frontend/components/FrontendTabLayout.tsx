import React, { useEffect, useMemo, useState } from 'react';
import { iconForPrimary } from '../ios/icons';
import {
    buildBottomTabs,
    buildCardSegments,
    buildVisitSegments,
    isFeatureEnabled,
    primaryForTab,
    type PrimaryTab,
} from '../ios/navigation';

type FrontendMenuItem = {
    tab: string;
    label: string;
    icon?: string;
    url?: string;
    open?: string;
};

export function isLinkTabKey(tab: string): boolean {
    return /^link_[0-9]+$/.test(tab);
}

/** タブバー外遷移用も含め、既知タブのセクションは常にマウントする */
const ALL_TAB_SECTIONS: FrontendMenuItem[] = [
    { tab: 'home', label: 'ホーム' },
    { tab: 'products', label: 'オーダー' },
    { tab: 'orders', label: '注文履歴' },
    { tab: 'stamps', label: 'スタンプ' },
    { tab: 'coupons', label: 'クーポン' },
    { tab: 'queue', label: '順番待ち' },
    { tab: 'member', label: '会員証' },
    { tab: 'reservations', label: '予約' },
    { tab: 'gallery', label: 'ギャラリー' },
    { tab: 'announcements', label: 'お知らせ' },
    { tab: 'designlab', label: 'デザイン変更' },
];

function getFrontendMenu(): FrontendMenuItem[] {
    const menu = window.__FRONTEND_MENU__;
    if (!Array.isArray(menu) || menu.length === 0) {
        return ALL_TAB_SECTIONS;
    }

    return menu
        .filter((item): item is FrontendMenuItem => typeof item?.tab === 'string' && item.tab.length > 0)
        .map((item) => ({
            tab: item.tab,
            label: typeof item.label === 'string' && item.label.length > 0 ? item.label : item.tab,
            icon: typeof item.icon === 'string' && item.icon.length > 0 ? item.icon : undefined,
            url: typeof item.url === 'string' ? item.url : undefined,
            open: typeof item.open === 'string' ? item.open : undefined,
        }));
}

function getLinkTabsFromMenu(): FrontendMenuItem[] {
    return getFrontendMenu().filter((m) => isLinkTabKey(m.tab) && typeof m.url === 'string' && m.url !== '');
}

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

function useActiveTab(): string {
    const [activeTab, setActiveTab] = useState(readActiveTab);

    useEffect(() => {
        const sync = () => setActiveTab(readActiveTab());
        window.addEventListener('hashchange', sync);
        window.addEventListener('ios-tab-changed', sync);
        return () => {
            window.removeEventListener('hashchange', sync);
            window.removeEventListener('ios-tab-changed', sync);
        };
    }, []);

    return activeTab;
}

function IosSegmentBar({
    items,
    activeTab,
}: {
    items: Array<{ tab: string; label: string }>;
    activeTab: string;
}): JSX.Element | null {
    if (items.length < 2) {
        return null;
    }

    return (
        <div className="ios-segment-bar" role="tablist">
            <div className="ios-segment">
                {items.map((item) => (
                    <button
                        key={item.tab}
                        type="button"
                        role="tab"
                        className={`ios-segment__btn${activeTab === item.tab ? ' is-active' : ''}`}
                        aria-selected={activeTab === item.tab}
                        data-ios-segment-tab={item.tab}
                        onClick={() => {
                            if (typeof window.app?.switchTab === 'function') {
                                void window.app.switchTab(item.tab);
                            }
                        }}
                    >
                        {item.label}
                    </button>
                ))}
            </div>
        </div>
    );
}

/** セグメント・Large Title のみ。再レンダーしてもタブ中身を消さない */
function IosDynamicChrome(): JSX.Element | null {
    const activeTab = useActiveTab();
    const primary = primaryForTab(activeTab);
    const cardSegments = useMemo(() => buildCardSegments(), []);
    const visitSegments = useMemo(() => buildVisitSegments(), []);

    if (primary === 'member') {
        return (
            <>
                <h1 className="ios-large-title">カード</h1>
                <IosSegmentBar items={cardSegments} activeTab={activeTab} />
            </>
        );
    }
    if (primary === 'queue') {
        return (
            <>
                <h1 className="ios-large-title">来店</h1>
                <IosSegmentBar items={visitSegments} activeTab={activeTab} />
            </>
        );
    }
    if (activeTab === 'designlab') {
        return null;
    }
    return null;
}

/**
 * タブ DOM は初回のみ描画する。
 * 状態付き親の再レンダーで innerHTML（Manager 描画）が消えないよう分離する。
 */
const StaticTabMounts = React.memo(function StaticTabMounts(): JSX.Element {
    const menu = getFrontendMenu();
    const linkTabs = getLinkTabsFromMenu();
    const mountedTabs = ALL_TAB_SECTIONS.map((item) => {
        const fromMenu = menu.find((m) => m.tab === item.tab);
        return fromMenu ?? item;
    });
    // active クラスは ReggitApp.switchTab が管理する（React は触らない）
    const initialActive = readActiveTab();

    return (
        <>
            {mountedTabs.map((item) => (
                <div
                    key={item.tab}
                    className={`tab-content${initialActive === item.tab ? ' active' : ''}`}
                    id={`${item.tab}Tab`}
                >
                    <div className="tab-section" id={`${item.tab}Section`} data-tab={item.tab} />
                </div>
            ))}
            {linkTabs.map((item) => (
                <div
                    key={item.tab}
                    className={`tab-content${initialActive === item.tab ? ' active' : ''}`}
                    id={`${item.tab}Tab`}
                >
                    <div
                        className="tab-section tab-section--link-embed"
                        id={`${item.tab}Section`}
                        data-tab={item.tab}
                        data-link-url={item.url}
                    />
                </div>
            ))}
        </>
    );
});

function LiffDetailShell(): JSX.Element {
    return (
        <div
            id="liffDetailPage"
            className="page-content page-content--detail page-content--announcement-detail"
            hidden
            aria-label="詳細"
        >
            <div className="page-content__bar">
                <a href="#home" className="page-content__back" data-liff-page-back="1" data-announcement-back="1" aria-label="戻る">‹</a>
                <span className="page-content__title" id="liffDetailPageTitle" />
            </div>
            <div className="page-content__body tab-section" id="liffDetailSection" data-page="" />
        </div>
    );
}

export function FrontendTabLayout(): JSX.Element {
    return (
        <>
            <div id="announcementsTopBar" className="announcements-section announcements-top-bar" aria-label="お知らせバー" hidden />
            <IosDynamicChrome />
            <StaticTabMounts />
            <LiffDetailShell />
        </>
    );
}

export function FrontendTabFooter(): JSX.Element | null {
    const isDesignerEmbedPreview = window.__IS_DESIGNER_EMBED_PREVIEW__ === true;
    if (isDesignerEmbedPreview) {
        return null;
    }

    const isNativeEmbed = window.__MOBILE_APP_MODE__ === true || document.body.classList.contains('native-app-embed');
    const tabs = buildBottomTabs();
    const activeTab = useActiveTab();
    const activePrimary: PrimaryTab | null = primaryForTab(activeTab);

    return (
        <footer
            className="main-tabs ios-tab-bar"
            style={isNativeEmbed ? { display: 'none', visibility: 'hidden', height: '0', overflow: 'hidden' } : undefined}
            aria-hidden={isNativeEmbed ? true : undefined}
        >
            {tabs.map((item, index) => (
                <button
                    key={item.primary}
                    type="button"
                    className={`main-tab-btn${activePrimary === item.primary || (activePrimary === null && index === 0) ? ' active' : ''}`}
                    data-tab={item.tab}
                    data-primary-tab={item.primary}
                >
                    <span className="ios-tab-icon" aria-hidden="true">
                        {iconForPrimary(item.primary)}
                    </span>
                    <span>{item.label}</span>
                </button>
            ))}
        </footer>
    );
}

export function isHomeMoreEnabled(): {
    orders: boolean;
    gallery: boolean;
    announcements: boolean;
    links: FrontendMenuItem[];
} {
    return {
        orders: isFeatureEnabled('orders'),
        gallery: isFeatureEnabled('gallery'),
        announcements: isFeatureEnabled('announcements'),
        links: getLinkTabsFromMenu(),
    };
}

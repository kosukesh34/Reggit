import React, { useMemo } from 'react';
import { useLiffApp } from '../context/LiffAppContext';
import { isFixedUiShell } from '../demo/demoMode';
import { iconForPrimary } from '../demo/icons';
import {
    buildBottomTabs,
    buildCardSegments,
    buildVisitSegments,
    isFeatureEnabled,
    primaryForTab,
    type PrimaryTab,
} from '../demo/navigation';
import { HomePage } from '../features/home/HomePage';
import { ProductsPage } from '../features/products/ProductsPage';
import { OrdersPage } from '../features/orders/OrdersPage';
import { StampsPage } from '../features/stamps/StampsPage';
import { CouponsPage } from '../features/coupons/CouponsPage';
import { DesignLabPage } from '../features/designlab/DesignLabPage';
import { QueuePage } from '../features/queue/QueuePage';
import { MemberPage } from '../features/member/MemberPage';
import { ReservationsPage } from '../features/reservations/ReservationsPage';
import { GalleryPage } from '../features/gallery/GalleryPage';
import { AnnouncementPage } from '../features/announcements/AnnouncementPage';
import { LinkTabEmbed } from '../features/LinkTabEmbed';
import { LiffDetailHost } from './LiffDetailHost';

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
    { tab: 'designlab', label: 'あそび' },
    { tab: 'queue', label: '順番待ち' },
    { tab: 'member', label: '会員証' },
    { tab: 'reservations', label: '予約' },
    { tab: 'gallery', label: 'ギャラリー' },
    { tab: 'announcements', label: 'お知らせ' },
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
            icon: typeof item.icon === 'string' && item.icon.length > 0 ? item.icon : 'fa-circle',
            url: typeof item.url === 'string' ? item.url : undefined,
            open: typeof item.open === 'string' ? item.open : undefined,
        }));
}

function getLinkTabsFromMenu(): FrontendMenuItem[] {
    return getFrontendMenu().filter((m) => isLinkTabKey(m.tab) && typeof m.url === 'string' && m.url !== '');
}

function useActiveTab(): string {
    return useLiffApp().activeTab;
}

function IosSegmentBar({
    items,
    activeTab,
}: {
    items: Array<{ tab: string; label: string }>;
    activeTab: string;
}): JSX.Element | null {
    const { switchTab } = useLiffApp();
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
                        onClick={() => switchTab(item.tab)}
                    >
                        {item.label}
                    </button>
                ))}
            </div>
        </div>
    );
}

/** セグメント・Large Title のみ。再レンダーしてもタブ中身を消さない */
function FixedShellChrome(): JSX.Element | null {
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
    return null;
}

function TabPage({ tab }: { tab: string }): JSX.Element {
    switch (tab) {
        case 'home':
            return <HomePage />;
        case 'products':
            return <ProductsPage />;
        case 'orders':
            return <OrdersPage />;
        case 'stamps':
            return <StampsPage />;
        case 'coupons':
            return <CouponsPage />;
        case 'designlab':
            return <DesignLabPage />;
        case 'queue':
            return <QueuePage />;
        case 'member':
            return <MemberPage />;
        case 'reservations':
            return <ReservationsPage />;
        case 'gallery':
            return <GalleryPage />;
        case 'announcements':
            return <AnnouncementPage />;
        default:
            return <div className="tab-section" id={`${tab}Section`} data-tab={tab} data-react-owned="1" />;
    }
}

/**
 * タブは React Page 経由でマウントする。
 * active クラスは ReggitApp.switchTab が管理する（React は触らない）。
 */
function ReactTabMounts(): JSX.Element {
    const { activeTab } = useLiffApp();
    const menu = getFrontendMenu();
    const linkTabs = getLinkTabsFromMenu();
    const mountedTabs = ALL_TAB_SECTIONS.map((item) => {
        const fromMenu = menu.find((m) => m.tab === item.tab);
        return fromMenu ?? item;
    });

    return (
        <>
            {mountedTabs.map((item) => (
                <div
                    key={item.tab}
                    className={`tab-content${activeTab === item.tab ? ' active' : ''}`}
                    id={`${item.tab}Tab`}
                >
                    <TabPage tab={item.tab} />
                </div>
            ))}
            {linkTabs.map((item) => (
                <div
                    key={item.tab}
                    className={`tab-content${activeTab === item.tab ? ' active' : ''}`}
                    id={`${item.tab}Tab`}
                >
                    <LinkTabEmbed tab={item.tab} linkUrl={item.url} />
                </div>
            ))}
        </>
    );
}

/**
 * 詳細シェル。中身は LiffDetailHost（product/order）または Manager innerHTML。
 * props 無し memo でカート更新時の再レンダーによる section クリアを防ぐ。
 */
const LiffDetailShell = React.memo(function LiffDetailShell(): JSX.Element {
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
            <LiffDetailHost />
        </div>
    );
});

export function FrontendTabLayout(): JSX.Element {
    return (
        <>
            <div id="announcementsTopBar" className="announcements-section announcements-top-bar" aria-label="お知らせバー" hidden />
            {isFixedUiShell() ? <FixedShellChrome /> : null}
            <ReactTabMounts />
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
    const activeTab = useActiveTab();
    const embedStyle = isNativeEmbed
        ? { display: 'none', visibility: 'hidden' as const, height: '0', overflow: 'hidden' as const }
        : undefined;

    // /liff デモ: 下部タブ数・IAは固定（ホーム / オーダー / カード / 来店）
    if (isFixedUiShell()) {
        const tabs = buildBottomTabs();
        const activePrimary: PrimaryTab | null = primaryForTab(activeTab);
        return (
            <footer
                className="main-tabs liff-tab-bar ios-tab-bar"
                style={embedStyle}
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

    // 店舗LIFF: 管理画面で設定したタブ数・ラベル・アイコンをそのまま表示
    const menu = getFrontendMenu();
    return (
        <footer
            className="main-tabs"
            style={embedStyle}
            aria-hidden={isNativeEmbed ? true : undefined}
        >
            {menu.map((item, index) => (
                <button
                    key={item.tab}
                    type="button"
                    className={`main-tab-btn${activeTab === item.tab || (activeTab === '' && index === 0) ? ' active' : ''}`}
                    data-tab={item.tab}
                    data-link-url={isLinkTabKey(item.tab) ? item.url : undefined}
                    data-link-open="embed"
                >
                    <i className={`fas ${item.icon ?? 'fa-circle'}`} aria-hidden="true" />
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

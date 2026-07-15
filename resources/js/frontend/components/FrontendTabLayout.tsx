import React from 'react';

type FrontendMenuItem = {
    tab: string;
    label: string;
    icon?: string;
};

/** タブバー外遷移用も含め、既知タブのセクションは常にマウントする */
const ALL_TAB_SECTIONS: FrontendMenuItem[] = [
    { tab: 'home', label: 'ホーム', icon: 'fa-home' },
    { tab: 'products', label: '商品', icon: 'fa-utensils' },
    { tab: 'orders', label: '注文', icon: 'fa-receipt' },
    { tab: 'stamps', label: 'スタンプ', icon: 'fa-stamp' },
    { tab: 'coupons', label: 'クーポン', icon: 'fa-ticket-alt' },
    { tab: 'queue', label: '順番待ち', icon: 'fa-list-ol' },
    { tab: 'member', label: '会員証', icon: 'fa-id-card' },
    { tab: 'reservations', label: '予約', icon: 'fa-calendar-check' },
    { tab: 'gallery', label: 'ギャラリー', icon: 'fa-images' },
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
        }));
}

export function FrontendTabLayout(): JSX.Element {
    const menu = getFrontendMenu();
    const firstTab = menu[0]?.tab ?? 'home';
    const mountedTabs = ALL_TAB_SECTIONS.map((item) => {
        const fromMenu = menu.find((m) => m.tab === item.tab);
        return fromMenu ?? item;
    });

    return (
        <>
            <div id="announcementsSection" className="announcements-section" aria-label="お知らせ" />
            {mountedTabs.map((item) => (
                <div
                    key={item.tab}
                    className={`tab-content ${firstTab === item.tab ? 'active' : ''}`}
                    id={`${item.tab}Tab`}
                >
                    <div className="tab-section" id={`${item.tab}Section`} data-tab={item.tab} />
                </div>
            ))}
        </>
    );
}

export function FrontendTabFooter(): JSX.Element | null {
    const isDesignerEmbedPreview = window.__IS_DESIGNER_EMBED_PREVIEW__ === true;
    if (isDesignerEmbedPreview) {
        return null;
    }

    const isNativeEmbed = window.__MOBILE_APP_MODE__ === true || document.body.classList.contains('native-app-embed');
    const menu = getFrontendMenu();

    return (
        <footer
            className="main-tabs"
            style={isNativeEmbed ? { display: 'none', visibility: 'hidden', height: '0', overflow: 'hidden' } : undefined}
            aria-hidden={isNativeEmbed ? true : undefined}
        >
            {menu.map((item, index) => (
                <button
                    key={item.tab}
                    type="button"
                    className={`main-tab-btn ${index === 0 ? 'active' : ''}`}
                    data-tab={item.tab}
                >
                    <i className={`fas ${item.icon ?? 'fa-circle'}`} />
                    <span>{item.label}</span>
                </button>
            ))}
        </footer>
    );
}

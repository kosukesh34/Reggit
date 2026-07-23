import React from 'react';
import { IconCart } from '../demo/icons';
import { isFeatureEnabled } from '../demo/navigation';
import { isIosFixedUiDemo } from '../demo/demoMode';

type FrontendHeaderConfig = {
    title: string;
    logoUrl: string | null;
    friendAddUrl: string | null;
};

type MenuItem = {
    tab: string;
    label: string;
};

function getHeaderConfig(): FrontendHeaderConfig {
    const fallbackTitle = document.title || 'Reggit';
    const header = window.__FRONTEND_HEADER__;
    if (!header || typeof header !== 'object') {
        return {
            title: fallbackTitle,
            logoUrl: null,
            friendAddUrl: null,
        };
    }

    return {
        title: typeof header.title === 'string' && header.title.trim() !== '' ? header.title : fallbackTitle,
        logoUrl: typeof header.logoUrl === 'string' && header.logoUrl.trim() !== '' ? header.logoUrl : null,
        friendAddUrl: typeof header.friendAddUrl === 'string' && header.friendAddUrl.trim() !== '' ? header.friendAddUrl : null,
    };
}

function hasOrderMenus(): boolean {
    return isFeatureEnabled('products');
}

/** ネイティブアプリではモバイル注文を主CTA、LIFF/Web では店内注文を主CTAにする */
function prefersMobileOrderPrimary(): boolean {
    return window.__MOBILE_APP_MODE__ === true;
}

function buildMenuItems(): MenuItem[] {
    const items: MenuItem[] = [];
    if (isFeatureEnabled('home')) {
        items.push({ tab: 'home', label: 'ホーム' });
    }
    if (isFeatureEnabled('products')) {
        items.push({ tab: 'products', label: 'オーダー' });
    }
    if (isFeatureEnabled('member')) {
        items.push({ tab: 'member', label: '会員証' });
    }
    if (isFeatureEnabled('coupons')) {
        items.push({ tab: 'coupons', label: 'クーポン' });
    }
    if (isFeatureEnabled('stamps')) {
        items.push({ tab: 'stamps', label: 'スタンプ' });
    }
    if (isFeatureEnabled('queue')) {
        items.push({ tab: 'queue', label: '順番待ち' });
    }
    if (isFeatureEnabled('reservations')) {
        items.push({ tab: 'reservations', label: '予約' });
    }
    if (isFeatureEnabled('orders')) {
        items.push({ tab: 'orders', label: '注文履歴' });
    }
    if (isFeatureEnabled('announcements')) {
        items.push({ tab: 'announcements', label: 'お知らせ' });
    }
    if (isFeatureEnabled('gallery')) {
        items.push({ tab: 'gallery', label: 'ギャラリー' });
    }
    return items;
}

export function FrontendChrome(): JSX.Element | null {
    const isDesignerEmbedPreview = window.__IS_DESIGNER_EMBED_PREVIEW__ === true;
    if (isDesignerEmbedPreview) {
        return null;
    }

    const header = getHeaderConfig();
    const preferMobilePrimary = prefersMobileOrderPrimary();
    const showCart = hasOrderMenus();
    const menuItems = buildMenuItems();

    const mobileOrderButton = (
        <button
            className={`order-btn ${preferMobilePrimary ? 'order-btn--primary' : 'order-btn--secondary'}`}
            id="orderBtn"
            disabled
        >
            モバイル注文（今すぐ決済）
        </button>
    );
    const inStoreOrderButton = (
        <button
            className={`order-btn ${preferMobilePrimary ? 'order-btn--secondary' : 'order-btn--primary'}`}
            id="inStoreOrderBtn"
            disabled
        >
            店内注文（後で決済）
        </button>
    );

    return (
        <>
            <header className="header ios-nav">
                <div className="header-content">
                    <button
                        type="button"
                        className="menu-btn"
                        id="menuBtn"
                        title="メニュー"
                        aria-label="メニュー"
                        aria-controls="sideMenu"
                        aria-expanded="false"
                    >
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" />
                        </svg>
                    </button>
                    {header.logoUrl ? <img src={header.logoUrl} alt="" className="header-logo" /> : null}
                    <h1 className="logo">{header.title}</h1>
                    {header.friendAddUrl ? (
                        <a
                            href={header.friendAddUrl}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="header-friend-add"
                            id="headerFriendAdd"
                            title="友だち追加でメッセージやクーポンを受け取れます"
                        >
                            友だち追加
                        </a>
                    ) : null}
                    <div className="header-actions">
                        <button
                            type="button"
                            className="account-btn"
                            id="accountBtn"
                            title="会員・設定"
                            aria-label="会員・設定"
                        >
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <circle cx="12" cy="8" r="3.5" stroke="currentColor" strokeWidth="1.7" />
                                <path d="M5 19.5c1.8-3.2 4-4.8 7-4.8s5.2 1.6 7 4.8" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" />
                            </svg>
                        </button>
                        {showCart ? (
                            <button type="button" className="cart-btn" id="cartBtn" title="注文内容" aria-label="注文内容">
                                <IconCart />
                                <span className="cart-count" id="cartCount">
                                    0
                                </span>
                            </button>
                        ) : (
                            <button type="button" className="cart-btn is-hidden" id="cartBtn" hidden aria-hidden="true">
                                <IconCart />
                                <span className="cart-count" id="cartCount">
                                    0
                                </span>
                            </button>
                        )}
                    </div>
                </div>
            </header>

            <aside className="ios-side-menu" id="sideMenu" aria-hidden="true" aria-label="メニュー">
                <div className="ios-side-menu__head">
                    <p className="ios-side-menu__title">{header.title}</p>
                    <button type="button" className="ios-side-menu__close" id="closeMenuBtn" aria-label="閉じる">
                        ✕
                    </button>
                </div>
                <nav className="ios-side-menu__nav">
                    {menuItems.map((item) => (
                        <button
                            key={item.tab}
                            type="button"
                            className="ios-side-menu__item"
                            data-menu-tab={item.tab}
                        >
                            {item.label}
                        </button>
                    ))}
                </nav>
                {isIosFixedUiDemo() ? (
                    <div className="ios-side-menu__footer">
                        <button
                            type="button"
                            className="ios-side-menu__item ios-side-menu__item--lab"
                            data-menu-tab="designlab"
                        >
                            ガチャ・スクラッチ
                        </button>
                    </div>
                ) : null}
            </aside>

            <div className="cart-sidebar ios-cart-sheet" id="cartSidebar">
                <div className="cart-header">
                    <h2>注文内容</h2>
                    <button className="close-btn" id="closeCartBtn" type="button" aria-label="閉じる">
                        ✕
                    </button>
                </div>
                <div className="cart-items" id="cartItems">
                    <div className="empty-cart">カートは空です</div>
                </div>
                <div className="cart-footer">
                    <div className="cart-total">
                        <span>合計 </span>
                        <span className="total-price" id="totalPrice">
                            ¥0
                        </span>
                    </div>
                    <div className="order-actions">
                        {preferMobilePrimary ? (
                            <>
                                {mobileOrderButton}
                                {inStoreOrderButton}
                            </>
                        ) : (
                            <>
                                {inStoreOrderButton}
                                {mobileOrderButton}
                            </>
                        )}
                    </div>
                </div>
            </div>

            <div className="overlay" id="overlay" />
        </>
    );
}

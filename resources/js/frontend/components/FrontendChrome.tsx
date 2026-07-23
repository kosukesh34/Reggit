import React from 'react';
import { isFixedUiShell } from '../demo/demoMode';
import { IconCart } from '../demo/icons';

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
    const menu = window.__FRONTEND_MENU__;
    if (!Array.isArray(menu)) {
        return true;
    }
    return menu.some((item) => item?.tab === 'products' || item?.tab === 'orders');
}

/** ネイティブアプリではモバイル注文を主CTA、LIFF/Web では店内注文を主CTAにする */
function prefersMobileOrderPrimary(): boolean {
    return window.__MOBILE_APP_MODE__ === true;
}

/** デモ固定UI用のサイドメニュー（店舗メニュー設定に依存しない） */
function buildDemoMenuItems(): MenuItem[] {
    return [
        { tab: 'home', label: 'ホーム' },
        { tab: 'products', label: 'オーダー' },
        { tab: 'member', label: '会員証' },
        { tab: 'coupons', label: 'クーポン' },
        { tab: 'stamps', label: 'スタンプ' },
        { tab: 'queue', label: '順番待ち' },
        { tab: 'reservations', label: '予約' },
        { tab: 'orders', label: '注文履歴' },
        { tab: 'announcements', label: 'お知らせ' },
        { tab: 'gallery', label: 'ギャラリー' },
    ];
}

function OrderActionButtons({
    preferMobilePrimary,
    withIcons,
}: {
    preferMobilePrimary: boolean;
    withIcons: boolean;
}): JSX.Element {
    const mobileOrderButton = (
        <button
            className={`order-btn ${preferMobilePrimary ? 'order-btn--primary' : 'order-btn--secondary'}`}
            id="orderBtn"
            disabled
        >
            {withIcons ? <i className="fas fa-credit-card" /> : null}
            {withIcons ? ' モバイル注文（今すぐ決済）' : 'モバイル注文（今すぐ決済）'}
        </button>
    );
    const inStoreOrderButton = (
        <button
            className={`order-btn ${preferMobilePrimary ? 'order-btn--secondary' : 'order-btn--primary'}`}
            id="inStoreOrderBtn"
            disabled
        >
            {withIcons ? <i className="fas fa-store" /> : null}
            {withIcons ? ' 店内注文（後で決済）' : '店内注文（後で決済）'}
        </button>
    );

    if (preferMobilePrimary) {
        return (
            <>
                {mobileOrderButton}
                {inStoreOrderButton}
            </>
        );
    }
    return (
        <>
            {inStoreOrderButton}
            {mobileOrderButton}
        </>
    );
}

/** /liff デモ専用クローム */
function DemoFixedChrome({
    header,
    preferMobilePrimary,
    showCart,
}: {
    header: FrontendHeaderConfig;
    preferMobilePrimary: boolean;
    showCart: boolean;
}): JSX.Element {
    const menuItems = buildDemoMenuItems();

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
                                <span className="cart-count" id="cartCount">0</span>
                            </button>
                        ) : (
                            <button type="button" className="cart-btn is-hidden" id="cartBtn" hidden aria-hidden="true">
                                <IconCart />
                                <span className="cart-count" id="cartCount">0</span>
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
                <div className="ios-side-menu__footer">
                    <button
                        type="button"
                        className="ios-side-menu__item ios-side-menu__item--lab"
                        data-menu-tab="designlab"
                    >
                        ガチャ・スクラッチ
                    </button>
                </div>
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
                        <span className="total-price" id="totalPrice">¥0</span>
                    </div>
                    <div className="order-actions">
                        <OrderActionButtons preferMobilePrimary={preferMobilePrimary} withIcons={false} />
                    </div>
                </div>
            </div>

            <div className="overlay" id="overlay" />
        </>
    );
}

/** 店舗LIFF: Designer 以前のクラシックヘッダー／カート */
function StoreClassicChrome({
    header,
    preferMobilePrimary,
    showCart,
}: {
    header: FrontendHeaderConfig;
    preferMobilePrimary: boolean;
    showCart: boolean;
}): JSX.Element {
    return (
        <>
            <header className="header">
                <div className="header-content">
                    {header.logoUrl ? <img src={header.logoUrl} alt={header.title} className="header-logo" /> : null}
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
                            <i className="fab fa-line" /> 友だち追加
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
                            <i className="fas fa-user" />
                        </button>
                        {showCart ? (
                            <button type="button" className="cart-btn" id="cartBtn" title="注文内容" aria-label="注文内容">
                                <i className="fas fa-shopping-bag" />
                                <span className="cart-count" id="cartCount">0</span>
                            </button>
                        ) : (
                            <button type="button" className="cart-btn is-hidden" id="cartBtn" hidden aria-hidden="true">
                                <i className="fas fa-shopping-bag" />
                                <span className="cart-count" id="cartCount">0</span>
                            </button>
                        )}
                    </div>
                </div>
            </header>

            <div className="cart-sidebar" id="cartSidebar">
                <div className="cart-header">
                    <h2>
                        <i className="fas fa-shopping-bag" /> 注文内容
                    </h2>
                    <button className="close-btn" id="closeCartBtn" type="button" aria-label="閉じる">
                        <i className="fas fa-times" />
                    </button>
                </div>
                <div className="cart-items" id="cartItems">
                    <div className="empty-cart">カートは空です</div>
                </div>
                <div className="cart-footer">
                    <div className="cart-total">
                        <span>合計: </span>
                        <span className="total-price" id="totalPrice">¥0</span>
                    </div>
                    <div className="order-actions">
                        <OrderActionButtons preferMobilePrimary={preferMobilePrimary} withIcons={true} />
                    </div>
                </div>
            </div>

            <div className="overlay" id="overlay" />
        </>
    );
}

export function FrontendChrome(): JSX.Element | null {
    const isDesignerEmbedPreview = window.__IS_DESIGNER_EMBED_PREVIEW__ === true;
    if (isDesignerEmbedPreview) {
        return null;
    }

    const header = getHeaderConfig();
    const preferMobilePrimary = prefersMobileOrderPrimary();
    const showCart = hasOrderMenus();

    if (isFixedUiShell()) {
        return (
            <DemoFixedChrome
                header={header}
                preferMobilePrimary={preferMobilePrimary}
                showCart={showCart}
            />
        );
    }

    return (
        <StoreClassicChrome
            header={header}
            preferMobilePrimary={preferMobilePrimary}
            showCart={showCart}
        />
    );
}

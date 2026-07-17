import React from 'react';

type FrontendHeaderConfig = {
    title: string;
    logoUrl: string | null;
    friendAddUrl: string | null;
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

export function FrontendChrome(): JSX.Element | null {
    const isDesignerEmbedPreview = window.__IS_DESIGNER_EMBED_PREVIEW__ === true;
    if (isDesignerEmbedPreview) {
        return null;
    }

    const header = getHeaderConfig();
    const preferMobilePrimary = prefersMobileOrderPrimary();
    const showCart = hasOrderMenus();

    const mobileOrderButton = (
        <button
            className={`order-btn ${preferMobilePrimary ? 'order-btn--primary' : 'order-btn--secondary'}`}
            id="orderBtn"
            disabled
        >
            <i className="fas fa-credit-card" /> モバイル注文（今すぐ決済）
        </button>
    );
    const inStoreOrderButton = (
        <button
            className={`order-btn ${preferMobilePrimary ? 'order-btn--secondary' : 'order-btn--primary'}`}
            id="inStoreOrderBtn"
            disabled
        >
            <i className="fas fa-store" /> 店内注文（後で決済）
        </button>
    );

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
                                <span className="cart-count" id="cartCount">
                                    0
                                </span>
                            </button>
                        ) : (
                            <button type="button" className="cart-btn is-hidden" id="cartBtn" hidden aria-hidden="true">
                                <i className="fas fa-shopping-bag" />
                                <span className="cart-count" id="cartCount">
                                    0
                                </span>
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
                    <button className="close-btn" id="closeCartBtn">
                        <i className="fas fa-times" />
                    </button>
                </div>
                <div className="cart-items" id="cartItems">
                    <div className="empty-cart">カートは空です</div>
                </div>
                <div className="cart-footer">
                    <div className="cart-total">
                        <span>合計: </span>
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

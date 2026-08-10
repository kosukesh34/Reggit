import React from 'react';
import { FrontendChrome } from './components/FrontendChrome';
import { FrontendTabFooter, FrontendTabLayout } from './components/FrontendTabLayout';
import { LiffAppProvider, useLiffApp } from './context/LiffAppContext';
import { CartProvider } from './features/cart/CartContext';
import { CheckoutModal } from './features/cart/CheckoutModal';
import { useLegacyBootstrap } from './hooks/useLegacyBootstrap';
import { useMembershipGate } from './hooks/useMembershipGate';

function MembershipGateHost(): null {
    const { switchTab } = useLiffApp();
    useMembershipGate(switchTab);
    return null;
}

function BootstrapHost(): null {
    useLegacyBootstrap();
    return null;
}

/**
 * LIFF 単一 React ツリー。chrome / tabs / footer をまとめて描画する。
 */
export function FrontendApp(): JSX.Element {
    const isDesignerEmbedPreview = window.__IS_DESIGNER_EMBED_PREVIEW__ === true;

    return (
        <LiffAppProvider>
            <CartProvider>
                <BootstrapHost />
                <MembershipGateHost />
                <FrontendChrome />
                <main className="main-content">
                    <FrontendTabLayout />
                </main>
                {isDesignerEmbedPreview ? null : <FrontendTabFooter />}
                <CheckoutModal />
            </CartProvider>
        </LiffAppProvider>
    );
}

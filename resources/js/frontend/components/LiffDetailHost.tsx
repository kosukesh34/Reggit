import React, { useEffect, useRef } from 'react';
import { createRoot, type Root } from 'react-dom/client';
import { parseLiffHash } from '../navigation/LiffPageRouter';
import { CartProvider } from '../features/cart/CartContext';
import { OrderFlowPage } from '../features/cart/OrderFlowPage';
import { ProductDetailPage } from '../features/products/ProductDetailPage';

const REACT_DETAIL_MOUNT_ID = 'liffReactDetailMount';

type ReactDetailRoute =
    | { kind: 'product'; id: string }
    | { kind: 'order'; step: 'cart' | 'confirm' | 'complete' };

function reactDetailFromHash(rawHash: string): ReactDetailRoute | null {
    const parsed = parseLiffHash(rawHash);
    if (parsed.kind === 'product') {
        return { kind: 'product', id: parsed.id };
    }
    if (parsed.kind === 'order') {
        return { kind: 'order', step: parsed.step };
    }
    return null;
}

function sameRoute(a: ReactDetailRoute | null, b: ReactDetailRoute | null): boolean {
    if (a === null || b === null) {
        return a === b;
    }
    if (a.kind !== b.kind) {
        return false;
    }
    if (a.kind === 'product' && b.kind === 'product') {
        return a.id === b.id;
    }
    if (a.kind === 'order' && b.kind === 'order') {
        return a.step === b.step;
    }
    return false;
}

/**
 * #liffDetailSection 上で product / order だけ React 描画する。
 * createRoot のマウント点を子要素に限定し、他詳細（Manager innerHTML）を消さない。
 */
export function LiffDetailHost(): null {
    const rootRef = useRef<Root | null>(null);
    const mountElRef = useRef<HTMLElement | null>(null);
    const routeRef = useRef<ReactDetailRoute | null>(null);

    useEffect(() => {
        const ensureMount = (section: HTMLElement): HTMLElement => {
            let mount = section.querySelector(`#${REACT_DETAIL_MOUNT_ID}`) as HTMLElement | null;
            if (mount !== null) {
                return mount;
            }
            section.replaceChildren();
            mount = document.createElement('div');
            mount.id = REACT_DETAIL_MOUNT_ID;
            mount.className = 'liff-detail-slot';
            section.appendChild(mount);
            return mount;
        };

        const tearDownReact = (): void => {
            if (rootRef.current !== null) {
                try {
                    rootRef.current.unmount();
                } catch {
                    /* orphaned after manager innerHTML */
                }
                rootRef.current = null;
            }
            const mount = mountElRef.current;
            mountElRef.current = null;
            if (mount !== null && mount.isConnected) {
                mount.remove();
            }
            const section = document.getElementById('liffDetailSection');
            if (section !== null) {
                delete section.dataset.reactOwned;
            }
            routeRef.current = null;
        };

        const sync = (): void => {
            const section = document.getElementById('liffDetailSection');
            if (section === null) {
                return;
            }
            const next = reactDetailFromHash(window.location.hash || '');
            if (next === null) {
                tearDownReact();
                return;
            }
            if (sameRoute(routeRef.current, next) && rootRef.current !== null && mountElRef.current?.isConnected) {
                return;
            }

            const mount = ensureMount(section);
            section.dataset.reactOwned = '1';
            if (rootRef.current === null || mountElRef.current !== mount) {
                if (rootRef.current !== null) {
                    try {
                        rootRef.current.unmount();
                    } catch {
                        /* ignore */
                    }
                }
                rootRef.current = createRoot(mount);
                mountElRef.current = mount;
            }
            routeRef.current = next;

            if (next.kind === 'product') {
                mount.className = 'liff-detail-slot product-detail-slot';
                rootRef.current.render(<ProductDetailPage productId={next.id} />);
                return;
            }
            mount.className = 'liff-detail-slot';
            rootRef.current.render(
                <CartProvider>
                    <OrderFlowPage step={next.step} />
                </CartProvider>,
            );
        };

        sync();
        window.addEventListener('hashchange', sync);
        document.addEventListener('liff-detail-changed', sync);
        return () => {
            window.removeEventListener('hashchange', sync);
            document.removeEventListener('liff-detail-changed', sync);
            tearDownReact();
        };
    }, []);

    return null;
}

import React, { useEffect, useRef } from 'react';
import { isFixedUiShell } from '../../demo/demoMode';
import { BuilderLayout } from '../../layout/BuilderLayout';
import { getTabBlocks } from '../../layout/getTabBlocks';
import { OrdersView } from './OrdersView';

function OrdersShell({ fixed }: { fixed: boolean }): JSX.Element {
    const sectionRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        const section = sectionRef.current;
        if (section === null) {
            return;
        }
        section.dataset.reactOwned = '1';
        section.dataset.layoutRendered = '1';
    }, []);

    return (
        <div
            ref={sectionRef}
            className="tab-section"
            id="ordersSection"
            data-tab="orders"
            data-react-owned="1"
        >
            {fixed ? <h1 className="ios-large-title">注文履歴</h1> : null}
            <div className="orders-data-mount" data-react-owned="1">
                <OrdersView />
            </div>
        </div>
    );
}

export const OrdersPage = React.memo(function OrdersPage(): JSX.Element {
    if (isFixedUiShell()) {
        return <OrdersShell fixed />;
    }
    const blocks = getTabBlocks('orders');
    if (blocks !== null) {
        return <BuilderLayout tab="orders" blocks={blocks} />;
    }
    return <OrdersShell fixed={false} />;
});

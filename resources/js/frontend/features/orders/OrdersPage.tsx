import React from 'react';
import { TabPageWithBuilderOrPaint } from '../TabPageWithBuilderOrPaint';
import { paintOrders } from '../tabPainters';

export const OrdersPage = React.memo(function OrdersPage(): JSX.Element {
    return <TabPageWithBuilderOrPaint tab="orders" paint={paintOrders} />;
});

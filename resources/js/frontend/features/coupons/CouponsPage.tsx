import React from 'react';
import { TabPageWithBuilderOrPaint } from '../TabPageWithBuilderOrPaint';
import { paintCoupons } from '../tabPainters';

export const CouponsPage = React.memo(function CouponsPage(): JSX.Element {
    return <TabPageWithBuilderOrPaint tab="coupons" paint={paintCoupons} />;
});

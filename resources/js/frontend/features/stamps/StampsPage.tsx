import React from 'react';
import { TabPageWithBuilderOrPaint } from '../TabPageWithBuilderOrPaint';
import { paintStamps } from '../tabPainters';

export const StampsPage = React.memo(function StampsPage(): JSX.Element {
    return <TabPageWithBuilderOrPaint tab="stamps" paint={paintStamps} />;
});

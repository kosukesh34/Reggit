import React from 'react';
import { TabPageWithBuilderOrPaint } from '../TabPageWithBuilderOrPaint';
import { paintQueue } from '../tabPainters';

export const QueuePage = React.memo(function QueuePage(): JSX.Element {
    return <TabPageWithBuilderOrPaint tab="queue" paint={paintQueue} />;
});

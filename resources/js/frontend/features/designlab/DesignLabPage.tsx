import React from 'react';
import { TabPageWithBuilderOrPaint } from '../TabPageWithBuilderOrPaint';
import { paintDesignLab } from '../tabPainters';

export const DesignLabPage = React.memo(function DesignLabPage(): JSX.Element {
    return <TabPageWithBuilderOrPaint tab="designlab" paint={paintDesignLab} />;
});

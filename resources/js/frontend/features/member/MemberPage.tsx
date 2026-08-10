import React from 'react';
import { TabPageWithBuilderOrPaint } from '../TabPageWithBuilderOrPaint';
import { paintMember } from '../tabPainters';

export const MemberPage = React.memo(function MemberPage(): JSX.Element {
    return <TabPageWithBuilderOrPaint tab="member" paint={paintMember} />;
});

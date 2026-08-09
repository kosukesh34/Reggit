import React from 'react';
import { TabPageWithBuilderOrPaint } from '../TabPageWithBuilderOrPaint';
import { paintAnnouncements } from '../tabPainters';

export const AnnouncementPage = React.memo(function AnnouncementPage(): JSX.Element {
    return <TabPageWithBuilderOrPaint tab="announcements" paint={paintAnnouncements} />;
});

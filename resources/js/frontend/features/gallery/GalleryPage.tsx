import React from 'react';
import { TabPageWithBuilderOrPaint } from '../TabPageWithBuilderOrPaint';
import { paintGallery } from '../tabPainters';

export const GalleryPage = React.memo(function GalleryPage(): JSX.Element {
    return <TabPageWithBuilderOrPaint tab="gallery" paint={paintGallery} />;
});

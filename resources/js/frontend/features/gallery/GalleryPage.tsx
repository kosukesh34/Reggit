import React, { useEffect, useRef } from 'react';
import { isFixedUiShell } from '../../demo/demoMode';
import { BuilderLayout } from '../../layout/BuilderLayout';
import { getTabBlocks } from '../../layout/getTabBlocks';
import { GalleryView } from './GalleryView';

function GalleryShell({ fixed }: { fixed: boolean }): JSX.Element {
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
            id="gallerySection"
            data-tab="gallery"
            data-react-owned="1"
        >
            {fixed ? <h1 className="ios-large-title">ギャラリー</h1> : null}
            <div className="gallery-data-mount" data-react-owned="1">
                <GalleryView />
            </div>
        </div>
    );
}

export const GalleryPage = React.memo(function GalleryPage(): JSX.Element {
    if (isFixedUiShell()) {
        return <GalleryShell fixed />;
    }
    const blocks = getTabBlocks('gallery');
    if (blocks !== null) {
        return <BuilderLayout tab="gallery" blocks={blocks} />;
    }
    return <GalleryShell fixed={false} />;
});

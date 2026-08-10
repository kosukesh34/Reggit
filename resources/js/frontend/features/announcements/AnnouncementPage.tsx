import React, { useEffect, useRef } from 'react';
import { isFixedUiShell } from '../../demo/demoMode';
import { BuilderLayout } from '../../layout/BuilderLayout';
import { getTabBlocks } from '../../layout/getTabBlocks';
import { AnnouncementView } from './AnnouncementView';

function AnnouncementsShell({ fixed }: { fixed: boolean }): JSX.Element {
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
            id="announcementsSection"
            data-tab="announcements"
            data-react-owned="1"
        >
            {fixed ? <h1 className="ios-large-title">お知らせ</h1> : null}
            <div className="announcements-data-mount" data-react-owned="1">
                <AnnouncementView />
            </div>
        </div>
    );
}

export const AnnouncementPage = React.memo(function AnnouncementPage(): JSX.Element {
    if (isFixedUiShell()) {
        return <AnnouncementsShell fixed />;
    }
    const blocks = getTabBlocks('announcements');
    if (blocks !== null) {
        return <BuilderLayout tab="announcements" blocks={blocks} />;
    }
    return <AnnouncementsShell fixed={false} />;
});

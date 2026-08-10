import React, { useEffect, useRef } from 'react';
import { AnnouncementView } from '../announcements/AnnouncementView';

/** Designer 未設定時の店舗LIFFホーム（お知らせのみ）。 */
export function HomeFallback(): JSX.Element {
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
            id="homeSection"
            data-tab="home"
            data-react-owned="1"
            data-layout-rendered="1"
        >
            <div className="home-content">
                <div className="announcements-data-mount" data-react-owned="1">
                    <AnnouncementView />
                </div>
            </div>
        </div>
    );
}

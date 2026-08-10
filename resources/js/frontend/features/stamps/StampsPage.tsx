import React, { useEffect, useRef } from 'react';
import { isFixedUiShell } from '../../demo/demoMode';
import { BuilderLayout } from '../../layout/BuilderLayout';
import { getTabBlocks } from '../../layout/getTabBlocks';
import { StampsView } from './StampsView';

function StampsShell(): JSX.Element {
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
            id="stampsSection"
            data-tab="stamps"
            data-react-owned="1"
        >
            <div className="stamps-data-mount" data-react-owned="1">
                <StampsView />
            </div>
        </div>
    );
}

export const StampsPage = React.memo(function StampsPage(): JSX.Element {
    if (isFixedUiShell()) {
        return <StampsShell />;
    }
    const blocks = getTabBlocks('stamps');
    if (blocks !== null) {
        return <BuilderLayout tab="stamps" blocks={blocks} />;
    }
    return <StampsShell />;
});
